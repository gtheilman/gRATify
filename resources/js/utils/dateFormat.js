export const formatTimestamp = value => {
  if (!value)
    return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime()))
    return value
  const pad = n => String(n).padStart(2, '0')
  
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}
