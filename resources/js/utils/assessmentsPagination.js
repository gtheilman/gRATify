export const getAssessmentPageCount = (total, pageSize) => {
  if (pageSize === 'all')
  {return 1}
  
  return Math.max(1, Math.ceil(total / pageSize))
}

export const paginateAssessments = (list, pageSize, currentPage) => {
  if (pageSize === 'all')
  {return list}
  const start = (currentPage - 1) * pageSize
  
  return list.slice(start, start + pageSize)
}
