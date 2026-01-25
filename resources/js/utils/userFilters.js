export const filterUsers = (users, term, role) => {
  const query = String(term || '').toLowerCase()
  return (users || []).filter(user => {
    const matchesSearch = [user.username, user.email, user.name, user.company]
      .filter(Boolean)
      .some(val => String(val).toLowerCase().includes(query))

    const roleValue = user.role === 'poobah' ? 'admin' : user.role
    const matchesRole = role === 'all' || roleValue === role

    return matchesSearch && matchesRole
  })
}

export const shouldResetUserPage = (prev, next) => {
  return prev.term !== next.term || prev.role !== next.role
}
