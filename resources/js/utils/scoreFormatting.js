export const formatScore = value => {
  const num = Number(value)
  if (!Number.isFinite(num))
    return ''
  return Number.isInteger(num) ? String(num) : num.toFixed(1)
}
