export const needsSessionRefresh = message => {
  return String(message || '').includes('Session expired')
}
