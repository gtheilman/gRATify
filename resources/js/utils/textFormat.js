export const truncateText = (text, maxLength = 50) => {
  if (!text)
    return ''
  return text.length > maxLength ? `${text.slice(0, maxLength)}...` : text
}

export const normalizeUserId = id => {
  const num = Number(id)
  return Number.isFinite(num) ? num : id
}

export const escapeHtml = value => {
  if (value === null || value === undefined)
    return ''
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}
