const cacheKey = 'demo-warning-state'

export const resolveDemoWarningState = payload => {
  return !!payload?.showWarning || !!payload?.showDemoUsers
}

export const readDemoWarningCache = () => {
  if (typeof sessionStorage === 'undefined')
    return null
  const cached = sessionStorage.getItem(cacheKey)
  if (cached === null)
    return null
  return cached === '1'
}

export const writeDemoWarningCache = value => {
  if (typeof sessionStorage === 'undefined')
    return
  sessionStorage.setItem(cacheKey, value ? '1' : '0')
}

export const applyDemoWarningFallback = () => {
  writeDemoWarningCache(true)
  return true
}
