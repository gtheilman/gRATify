// Lightweight client helper to retry attempt submissions once.
import axios from 'axios'

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms))

/**
 * Post an attempt to the API with a single automatic retry on failure.
 * Returns the axios response if either attempt succeeds, otherwise rethrows the last error.
 */
export async function postAttemptWithRetry (payload, retryDelay = 300) {
  let lastError = null

  for (let attempt = 0; attempt < 2; attempt += 1) {
    try {
      return await axios.post('/api/attempts', payload)
    } catch (error) {
      lastError = error
      if (attempt === 0) {
        await sleep(retryDelay)
      }
    }
  }

  throw lastError
}
