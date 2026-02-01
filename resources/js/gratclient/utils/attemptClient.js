// Lightweight client helper to retry attempt submissions once.
import axios from 'axios'
import { ensureCsrfCookie } from '../../utils/http'

const sleep = ms => new Promise(resolve => setTimeout(resolve, ms))
const DEFAULT_TIMEOUT_MS = 2500

const isDebug = () => {
  if (typeof window === 'undefined')
    return false
  const value = new URLSearchParams(window.location.search || '').get('debug')
  
  return value === '1' || value === 'true'
}

/**
 * Post an attempt to the API with automatic retries on failure.
 * Returns the axios response if any attempt succeeds, otherwise rethrows the last error.
 */
export async function postAttemptWithRetry (payload, retryDelay = 300, maxAttempts = 4) {
  let lastError = null

  for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
    try {
      await ensureCsrfCookie()
      
      return await axios.post('/api/attempts', payload, {
        timeout: DEFAULT_TIMEOUT_MS,
        headers: isDebug() ? { 'X-Debug': '1' } : undefined,
      })
    } catch (error) {
      lastError = error

      const remaining = maxAttempts - attempt - 1
      if (remaining > 0) {
        const backoff = Math.min(1500, retryDelay * (2 ** attempt))
        const jitter = Math.round(Math.random() * 120)

        await sleep(backoff + jitter)
      }
    }
  }

  throw lastError
}
