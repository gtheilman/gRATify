const cacheKey = id => `progress-cache-${id}`

export const readProgressCache = id => {
  try {
    const raw = localStorage.getItem(cacheKey(id))
    if (!raw)
      return null
    
    return JSON.parse(raw)
  } catch {
    return null
  }
}

export const writeProgressCache = (id, data) => {
  try {
    localStorage.setItem(cacheKey(id), JSON.stringify({
      data,
      cachedAt: Date.now(),
    }))
  } catch {
    // ignore storage errors
  }
}
