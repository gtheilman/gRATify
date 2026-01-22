export const formatScheduledDate = value => {
  if (value === null || value === undefined || value === '')
    return ''
  const dateFromIso = new Date(value)
  if (!Number.isNaN(dateFromIso))
    return dateFromIso.toISOString().slice(0, 10)
  const maybeDate = String(value).split('T')[0]
  return maybeDate || value
}
