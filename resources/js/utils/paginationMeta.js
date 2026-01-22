// Generates "Showing X to Y of Z entries" summaries for table pagination.
export const paginationMeta = (options, total) => {
  const itemsPerPage = Math.max(0, Number(options.itemsPerPage) || 0)
  const page = Math.max(1, Number(options.page) || 1)
  if (total === 0 || itemsPerPage === 0)
    return `Showing 0 to 0 of ${total} entries`

  const start = (page - 1) * itemsPerPage + 1
  const end = Math.min(page * itemsPerPage, total)
  
  return `Showing ${total === 0 ? 0 : start} to ${end} of ${total} entries`
}
