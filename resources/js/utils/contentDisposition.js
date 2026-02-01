export const parseFilenameFromDisposition = (header, fallback = 'download') => {
  const match = header?.match(/filename\s*=\s*"?([^"]+)"?/i)
  
  return match?.[1] || fallback
}
