import axios from 'axios'
import { idbGet, idbSet, idbDelete, idbGetAllByIndex, STORES } from './idb'
import { ensureCsrfCookie } from '../../utils/http'

const syncLocks = new Map()
const syncIntervals = new Map()
const onlineHandlers = new Map()
const listeners = new Set()
const stateByKey = new Map()
const concurrencyByKey = new Map()
const timeoutByKey = new Map()
const emaByKey = new Map()
const MAX_CONCURRENCY = 25
const MIN_CONCURRENCY = 2
const CONCURRENCY_STEP = 5
const MIN_TIMEOUT_MS = 3000
const MAX_TIMEOUT_MS = 15000

const emit = event => {
  listeners.forEach(fn => fn(event))
}

const isDebug = () => {
  if (typeof window === 'undefined')
    return false
  const value = new URLSearchParams(window.location.search || '').get('debug')
  
  return value === '1' || value === 'true'
}

const getState = presentationKey => {
  if (!stateByKey.has(presentationKey)) {
    stateByKey.set(presentationKey, {
      pendingCount: 0,
      lastSuccessAt: null,
      lastErrorAt: null,
      firstErrorAt: null,
      failureStreak: 0,
      concurrency: null,
      timeoutMs: null,
      emaMs: null,
      lastBatch: null,
    })
  }
  
  return stateByKey.get(presentationKey)
}

const updateState = (presentationKey, patch) => {
  const current = getState(presentationKey)

  Object.assign(current, patch)
  emit({ type: 'state', presentationKey, state: { ...current } })
}

const clamp = (value, min, max) => Math.max(min, Math.min(max, value))

const getConcurrency = (presentationKey, requested) => {
  if (!concurrencyByKey.has(presentationKey)) {
    concurrencyByKey.set(presentationKey, Math.min(MAX_CONCURRENCY, Math.max(MIN_CONCURRENCY, Number(requested || MAX_CONCURRENCY))))
  }
  const current = concurrencyByKey.get(presentationKey)
  if (typeof requested === 'number' && requested > 0) {
    const next = Math.min(MAX_CONCURRENCY, Math.max(MIN_CONCURRENCY, requested))

    concurrencyByKey.set(presentationKey, next)
    
    return next
  }
  
  return current
}

const getTimeoutMs = (presentationKey, requested) => {
  if (!timeoutByKey.has(presentationKey)) {
    const initial = typeof requested === 'number' ? requested : 8000

    timeoutByKey.set(presentationKey, clamp(initial, MIN_TIMEOUT_MS, MAX_TIMEOUT_MS))
  }
  const current = timeoutByKey.get(presentationKey)
  if (typeof requested === 'number' && requested > 0) {
    const next = clamp(requested, MIN_TIMEOUT_MS, MAX_TIMEOUT_MS)

    timeoutByKey.set(presentationKey, next)
    
    return next
  }
  
  return current
}

const updateEma = (presentationKey, sampleMs) => {
  if (typeof sampleMs !== 'number' || !Number.isFinite(sampleMs)) {
    return emaByKey.get(presentationKey) || null
  }
  const prev = emaByKey.get(presentationKey)
  const alpha = 0.25
  const next = prev == null ? sampleMs : (alpha * sampleMs + (1 - alpha) * prev)

  emaByKey.set(presentationKey, next)
  
  return next
}

const adjustConcurrency = (presentationKey, { hadServerError, hadSuccess }) => {
  const current = getConcurrency(presentationKey)
  if (hadServerError) {
    const next = Math.max(MIN_CONCURRENCY, current - CONCURRENCY_STEP)

    concurrencyByKey.set(presentationKey, next)
    updateState(presentationKey, { concurrency: next })
    
    return next
  }
  if (hadSuccess && current < MAX_CONCURRENCY) {
    const next = Math.min(MAX_CONCURRENCY, current + 1)

    concurrencyByKey.set(presentationKey, next)
    updateState(presentationKey, { concurrency: next })
    
    return next
  }
  
  return current
}

const adjustTimeout = (presentationKey, { batchMs, hadTimeout, hadServerError }) => {
  const ema = updateEma(presentationKey, batchMs)
  let next = getTimeoutMs(presentationKey)
  if (hadTimeout || hadServerError) {
    next = clamp(next * 1.4, MIN_TIMEOUT_MS, MAX_TIMEOUT_MS)
  } else if (ema) {
    // Keep timeout at roughly 2x EMA to absorb spikes.
    next = clamp(ema * 2, MIN_TIMEOUT_MS, MAX_TIMEOUT_MS)
  }
  timeoutByKey.set(presentationKey, next)
  updateState(presentationKey, { timeoutMs: next, emaMs: ema })
  
  return next
}

export const onQueueEvent = fn => {
  listeners.add(fn)
  
  return () => listeners.delete(fn)
}

const presentationKeyFor = (password, userId) => `${password || ''}|${userId || ''}`

const startSyncForKey = presentationKey => {
  if (!presentationKey)
    return ''
  if (syncIntervals.has(presentationKey)) {
    return presentationKey
  }

  refreshPendingCount(presentationKey)

  const interval = setInterval(() => {
    syncQueue(presentationKey)
  }, 5000)

  syncIntervals.set(presentationKey, interval)

  const handleOnline = () => syncQueue(presentationKey)

  onlineHandlers.set(presentationKey, handleOnline)
  window.addEventListener('online', handleOnline)

  syncQueue(presentationKey)

  return presentationKey
}

const loadPending = async presentationKey => {
  try {
    const all = await idbGetAllByIndex(STORES.attempts, 'presentationKey', presentationKey)
    
    return all.filter(item => item.status === 'pending')
  } catch {
    return []
  }
}

const refreshPendingCount = async presentationKey => {
  const pending = await loadPending(presentationKey)

  updateState(presentationKey, { pendingCount: pending.length })
  
  return pending
}

export const countPending = async presentationKey => {
  return (await refreshPendingCount(presentationKey)).length
}

export const queueAttempt = async ({ presentationId, answerId, questionId, password, userId, presentationKey: providedKey }) => {
  const presentationKey = providedKey || presentationKeyFor(password, userId)
  const id = `${presentationKey}:${answerId}`
  const existing = await idbGet(STORES.attempts, id).catch(() => null)
  if (existing && existing.status === 'pending') {
    return { id, presentationKey }
  }

  const payload = {
    id,
    presentationKey,
    presentationId,
    answerId,
    questionId,
    status: 'pending',
    createdAt: Date.now(),
    updatedAt: Date.now(),
  }

  await idbSet(STORES.attempts, payload)
  await refreshPendingCount(presentationKey)
  startSyncForKey(presentationKey)
  
  return { id, presentationKey }
}

export const markAttemptSynced = async attemptId => {
  const attempt = await idbGet(STORES.attempts, attemptId).catch(() => null)
  if (!attempt)
    return
  await idbDelete(STORES.attempts, attemptId)
  await refreshPendingCount(attempt.presentationKey)
  emit({ type: 'synced', presentationKey: attempt.presentationKey, answerId: attempt.answerId })
}

export const syncQueue = async (presentationKey, options = {}) => {
  if (syncLocks.get(presentationKey))
    return
  syncLocks.set(presentationKey, true)
  try {
    const pending = await refreshPendingCount(presentationKey)
    if (import.meta?.env?.DEV) {
      console.debug('Queue sync tick', {
        presentationKey,
        pendingCount: pending.length,
      })
    }
    if (!pending.length)
      return

    const invalid = pending.filter(item =>
      !item || !item.presentationId || !item.answerId,
    )

    if (invalid.length) {
      await Promise.all(
        invalid.map(item => idbDelete(STORES.attempts, item.id)),
      ).catch(() => {})
      await refreshPendingCount(presentationKey)
    }

    const validPending = pending.filter(item =>
      item && item.presentationId && item.answerId,
    )

    if (!validPending.length)
      return

    const timeoutMs = getTimeoutMs(presentationKey, options.timeoutMs)
    const requestedConcurrent = Math.max(1, Number(options.maxConcurrent || 1))
    const maxConcurrent = getConcurrency(presentationKey, requestedConcurrent)

    updateState(presentationKey, { concurrency: maxConcurrent })
    let failureCount = 0

    for (let i = 0; i < validPending.length; i += maxConcurrent) {
      const batch = validPending.slice(i, i + maxConcurrent)
      const batchStart = Date.now()
      const headers = isDebug() ? { 'X-Debug': '1' } : undefined
      try {
        await ensureCsrfCookie()
      } catch {
        // Ignore CSRF refresh errors; request will retry on failure.
      }
      let results = []
      let bulkUsed = false
      let bulkResultsCount = null
      let bulkFailed = false
      let bulkErrorStatus = null
      let bulkErrorCode = null
      if (batch.length > 1) {
        try {
          const response = await axios.post('/api/attempts/bulk', {
            attempts: batch.map(attempt => ({
              presentation_id: attempt.presentationId,
              answer_id: attempt.answerId,
            })),
          }, { timeout: timeoutMs, headers })

          bulkUsed = true
          bulkResultsCount = Array.isArray(response?.data?.results) ? response.data.results.length : null
          results = [{ ok: true, response, batch }]
        } catch (error) {
          bulkFailed = true
          bulkErrorStatus = error?.response?.status || null
          bulkErrorCode = error?.code || null
          results = batch.map(attempt => ({ ok: false, error, attempt }))
        }
      }

      if (bulkFailed || batch.length === 1) {
        results = await Promise.all(
          batch.map(async attempt => {
            try {
              const response = await axios.post('/api/attempts', {
                presentation_id: attempt.presentationId,
                answer_id: attempt.answerId,
              }, {
                timeout: timeoutMs,
                headers,
              })

              
              return { ok: true, response, attempt }
            } catch (error) {
              return { ok: false, error, attempt }
            }
          }),
        )
      }

      let hadSuccess = false
      let hadServerError = false
      let hadTimeout = false
      let serverErrorCount = 0
      let clientErrorCount = 0
      let timeoutCount = 0
      let networkErrorCount = 0
      for (const result of results) {
        if (result.ok) {
          const { response } = result
          if (response?.data?.debug) {
            updateState(presentationKey, { lastResponseDebug: response.data.debug })
          }
          if (Array.isArray(response?.data?.results) && result.batch) {
            const byKey = new Map(
              result.batch.map(attempt => [`${attempt.presentationId}|${attempt.answerId}`, attempt]),
            )

            for (const item of response.data.results) {
              const key = `${item.presentation_id}|${item.answer_id}`
              const attempt = byKey.get(key)
              if (!attempt)
                continue
              const status = item.status

              const drop = status === 'created'
                || status === 'already_attempted'
                || status === 'not_found'
                || status === 'invalid'

              if (drop) {
                await markAttemptSynced(attempt.id)
                emit({ type: 'response', presentationKey, answerId: attempt.answerId, payload: item })
                hadSuccess = true
              }
            }
          } else if (result.batch) {
            // Bulk response missing expected results payload; treat as server error.
            hadServerError = true
            serverErrorCount += 1
          } else if (result.attempt) {
            const attempt = result.attempt

            await markAttemptSynced(attempt.id)
            emit({ type: 'response', presentationKey, answerId: attempt.answerId, payload: response.data })
            hadSuccess = true
          }
        } else {
          const status = result?.error?.response?.status
          if (!status && result?.error?.code === 'ECONNABORTED') {
            timeoutCount += 1
            hadTimeout = true
          } else if (!status) {
            networkErrorCount += 1
          }
          if (status >= 500 || status === 429) {
            hadServerError = true
            serverErrorCount += 1
          }
          if ([400, 401, 403, 404, 422].includes(status)) {
            clientErrorCount += 1
            // Diagnostics only: drop non-retryable attempts so queue can clear.
            if (import.meta?.env?.DEV) {
              console.warn('Dropping queued attempt (non-retryable)', {
                status,
                presentationId: result.attempt.presentationId,
                answerId: result.attempt.answerId,
              })
            }
            await idbDelete(STORES.attempts, result.attempt.id).catch(() => {})
            await refreshPendingCount(presentationKey)
            emit({ type: 'synced', presentationKey, answerId: result.attempt.answerId })
            hadSuccess = true
            continue
          }
          failureCount += 1
        }
      }
      const batchMs = Date.now() - batchStart

      updateState(presentationKey, {
        lastBatch: {
          size: batch.length,
          ms: batchMs,
          serverErrors: serverErrorCount,
          clientErrors: clientErrorCount,
          timeouts: timeoutCount,
          networkErrors: networkErrorCount,
          bulkUsed,
          bulkResultsCount,
          bulkFailed,
          bulkErrorStatus,
          bulkErrorCode,
        },
      })
      adjustTimeout(presentationKey, { batchMs, hadTimeout, hadServerError })

      if (hadSuccess) {
        updateState(presentationKey, {
          lastSuccessAt: Date.now(),
          lastErrorAt: null,
          firstErrorAt: null,
          failureStreak: 0,
        })
        await refreshPendingCount(presentationKey)
      }

      adjustConcurrency(presentationKey, { hadServerError, hadSuccess })

      if (failureCount >= batch.length) {
        const state = getState(presentationKey)
        const firstErrorAt = state.firstErrorAt || Date.now()

        updateState(presentationKey, {
          lastErrorAt: Date.now(),
          firstErrorAt,
          failureStreak: state.failureStreak + 1,
        })
        break
      }
    }
  } finally {
    syncLocks.set(presentationKey, false)
  }
}

export const startQueueSync = (password, userId) => {
  const presentationKey = presentationKeyFor(password, userId)
  
  return startSyncForKey(presentationKey)
}

export const stopQueueSync = presentationKey => {
  const interval = syncIntervals.get(presentationKey)
  if (interval) {
    clearInterval(interval)
    syncIntervals.delete(presentationKey)
  }
  const handler = onlineHandlers.get(presentationKey)
  if (handler) {
    window.removeEventListener('online', handler)
    onlineHandlers.delete(presentationKey)
  }
}

export const getQueueState = presentationKey => {
  return { ...getState(presentationKey) }
}
