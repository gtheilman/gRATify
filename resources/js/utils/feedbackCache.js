const cacheKey = id => `feedback-cache-${id}`

export const readFeedbackCache = id => {
  try {
    const raw = localStorage.getItem(cacheKey(id))
    if (!raw)
      return null
    return JSON.parse(raw)
  } catch {
    return null
  }
}

export const writeFeedbackCache = (id, data) => {
  try {
    localStorage.setItem(cacheKey(id), JSON.stringify({
      data,
      cachedAt: Date.now(),
    }))
  } catch {
    // ignore storage errors
  }
}
