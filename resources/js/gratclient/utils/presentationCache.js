import { idbGet, idbSet, idbDelete, STORES } from './idb'
import { scramble, descramble } from './scramble'

const CACHE_TTL_MS = 5 * 60 * 1000
const CACHE_VERSION = 2
const memoryCache = new Map()

const cacheKey = (password, userId) => `v${CACHE_VERSION}|${password || ''}|${userId || ''}`
const scrambleKey = (password, userId) => `${password || ''}:${userId || ''}`

const isExpired = (cachedAt) => (Date.now() - (cachedAt || 0)) > CACHE_TTL_MS

const hasScrambledCorrect = (data) => {
  const questions = data?.assessment?.questions
  if (!Array.isArray(questions) || !questions.length)
    return false
  return questions.every(q =>
    Array.isArray(q.answers) &&
    q.answers.length > 0 &&
    q.answers.every(ans => typeof ans.correct_scrambled === 'string' && ans.correct_scrambled.length > 0),
  )
}

export const readPresentationCache = async (password, userId) => {
  const key = cacheKey(password, userId)
  const inMemory = memoryCache.get(key)
  if (inMemory) {
    if (isExpired(inMemory.cachedAt)) {
      memoryCache.delete(key)
    } else {
      return inMemory.data
    }
  }

  try {
    const record = await idbGet(STORES.presentation, key)
    if (!record)
      return null
    if (isExpired(record.cachedAt)) {
      await idbDelete(STORES.presentation, key)
      return null
    }
    const decoded = descramble(record.payload, scrambleKey(password, userId))
    const data = JSON.parse(decoded)
    if (!hasScrambledCorrect(data)) {
      await idbDelete(STORES.presentation, key)
      return null
    }
    memoryCache.set(key, { data, cachedAt: record.cachedAt })
    return data
  } catch {
    return null
  }
}

export const writePresentationCache = async (password, userId, data) => {
  const key = cacheKey(password, userId)
  const cachedAt = Date.now()
  memoryCache.set(key, { data, cachedAt })
  try {
    const payload = scramble(JSON.stringify(data), scrambleKey(password, userId))
    await idbSet(STORES.presentation, { key, payload, cachedAt })
  } catch {
    // Ignore indexedDB errors; in-memory cache still works for the session.
  }
}
