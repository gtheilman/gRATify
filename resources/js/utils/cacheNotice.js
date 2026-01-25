export const buildStaleNotice = (cachedAt, formatter = value => new Date(value).toLocaleString()) => {
  if (!cachedAt)
    return ''

  const date = new Date(cachedAt)
  if (Number.isNaN(date.getTime()))
    return ''

  const formatted = formatter(date)
  if (!formatted)
    return ''

  return `Showing cached data from ${formatted}`
}
