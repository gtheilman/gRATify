export const normalizeRole = role => role === 'poobah' ? 'admin' : role

export const sortUsers = (users, sortBy = 'name', sortDir = 'asc') => {
  const dir = sortDir === 'asc' ? 1 : -1

  const normalizedName = user => {
    if (!user)
      return ''
    const parts = String(user.name || user.username || '').trim().split(' ')
    if (parts.length === 1)
      return parts[0]
    const last = parts.pop()
    
    return `${last} ${parts.join(' ')}`
  }

  const list = [...(users || [])]
  
  return list.sort((a, b) => {
    if (sortBy === 'email')
      return dir * String(a.email || '').localeCompare(b.email || '', undefined, { sensitivity: 'base' })
    if (sortBy === 'role')
      return dir * String(a.role || '').localeCompare(b.role || '', undefined, { sensitivity: 'base' })
    if (sortBy === 'company')
      return dir * String(a.company || '').localeCompare(b.company || '', undefined, { sensitivity: 'base' })
    if (sortBy === 'username')
      return dir * String(a.username || '').localeCompare(b.username || '', undefined, { sensitivity: 'base' })
    
    return dir * normalizedName(a).localeCompare(normalizedName(b), undefined, { sensitivity: 'base' })
  })
}
