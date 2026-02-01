export const progressBarColor = pct => {
  if (pct < 20) return 'error'
  if (pct < 40) return 'warning'
  if (pct < 60) return 'secondary'
  if (pct < 80) return 'primary'
  
  return 'success'
}
