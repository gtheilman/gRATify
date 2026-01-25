export const normalizeUserRole = role => (role === 'poobah' ? 'admin' : role)

export const hasUserAssessments = user => Number(user?.assessments_count || 0) > 0

export const isLastAdminUser = (user, adminCount) => {
  return normalizeUserRole(user?.role) === 'admin' && adminCount <= 1
}

export const canDeleteUser = (user, adminCount) => {
  if (!user)
    return false
  if (isLastAdminUser(user, adminCount))
    return false
  if (hasUserAssessments(user))
    return false
  return true
}
