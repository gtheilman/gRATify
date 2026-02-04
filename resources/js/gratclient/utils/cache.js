// Caches the anonymous student ID per assessment password to preserve progress.
const ID_KEY_PREFIX = 'client-identifier'
const ID_TTL_MS = 20 * 60 * 1000 // 20 minutes

const hasStorage = () => typeof localStorage !== 'undefined'
const buildIdKey = password => `${ID_KEY_PREFIX}:${password || ''}`

export function saveIdentifier (password, userId) {
  if (!hasStorage() || !password || !userId) {return}
  try {
    localStorage.setItem(buildIdKey(password), JSON.stringify({
      userId,
      cachedAt: Date.now(),
    }))
  } catch {
    // ignore storage errors
  }
}

export function loadIdentifier (password) {
  if (!hasStorage() || !password) {return null}
  const raw = localStorage.getItem(buildIdKey(password))
  if (!raw) {return null}
  try {
    const parsed = JSON.parse(raw)
    const age = Date.now() - (parsed.cachedAt || 0)
    if (age > ID_TTL_MS) {
      localStorage.removeItem(buildIdKey(password))
      
      return null
    }
    
    return parsed.userId || null
  } catch {
    return null
  }
}
