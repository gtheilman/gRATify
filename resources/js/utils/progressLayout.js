export const calcMaxGroupLength = rows => {
  return (rows || []).reduce((max, row) => Math.max(max, String(row.group || '').length), 0)
}

export const calcGroupLabelWidth = (rows, min = 8) => {
  const max = calcMaxGroupLength(rows)
  return `${Math.max(max + 2, min)}ch`
}
