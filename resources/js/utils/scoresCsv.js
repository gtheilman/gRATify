export const buildScoresCsv = (rows, formatScore) => {
  const lines = ['UserID,Score']

  ;(rows || []).forEach(p => {
    const user = String(p.user_id || '').replace(/,/g, '')
    const score = formatScore ? formatScore(p.score) : p.score

    lines.push(`${user},${score}`)
  })
  
  return lines.join('\r\n')
}
