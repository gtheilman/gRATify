export const sortProgressRows = (rows, key, direction) => {
  const dir = direction === 'desc' ? -1 : 1
  const list = [...(rows || [])]
  if (key === 'percent')
  {return list.sort((a, b) => (a.percent - b.percent) * dir)}
  
  return list.sort((a, b) => String(a.group).localeCompare(String(b.group)) * dir)
}
