export const getActiveFilterLabel = value => {
  if (value === 'active')
    return 'Active only'
  if (value === 'inactive')
    return 'Inactive only'
  return 'All statuses'
}
