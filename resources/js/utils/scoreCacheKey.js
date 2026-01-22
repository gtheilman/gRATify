export const buildScoreCacheKey = (id, scheme) => {
  return `scores-cache-${id}-${scheme || ''}`
}
