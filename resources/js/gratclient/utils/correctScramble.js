export const decodeCorrectScrambled = (scrambled, password) => {
  if (!password || !scrambled)
    return null
  try {
    const binary = atob(scrambled)
    if (!binary.length)
      return null
    const mask = password.charCodeAt(0) || 0
    const byte = binary.charCodeAt(0) ^ mask
    if (byte === 49)
      return true
    if (byte === 48)
      return false
  } catch {
    return null
  }
  return null
}
