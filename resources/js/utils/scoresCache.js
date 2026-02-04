import { buildScoreCacheKey } from '@/utils/scoreCacheKey'

export const buildScoresCacheKey = (assessmentId, scheme) => {
  return buildScoreCacheKey(assessmentId, scheme || '')
}

export const readScoresCache = key => {
  try {
    const raw = localStorage.getItem(key)
    if (!raw)
    {return null}
    
    return JSON.parse(raw)
  } catch {
    return null
  }
}

export const writeScoresCache = (key, data) => {
  try {
    localStorage.setItem(key, JSON.stringify({
      data,
      cachedAt: Date.now(),
    }))
  } catch {
    // ignore storage errors
  }
}
