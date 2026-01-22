export const buildCsvBlob = payload => {
  try {
    return new Blob([payload], { type: 'text/csv;charset=utf-8;' })
  } catch {
    return null
  }
}
