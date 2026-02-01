export const buildUsersRangeLabel = (currentPage, pageSize, total) => {
  const safeTotal = Number(total) || 0
  if (safeTotal <= 0)
    return 'Rows 0 - 0 of 0'
  const start = (currentPage - 1) * pageSize + 1
  const end = Math.min(currentPage * pageSize, safeTotal)
  
  return `Rows ${start} - ${end} of ${safeTotal}`
}
