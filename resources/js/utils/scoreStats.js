export const toNumericScores = presentations => {
  return (presentations || [])
    .map(p => {
      const num = Number(p.score)
      
      return Number.isFinite(num) ? num : null
    })
    .filter(num => Number.isFinite(num))
}

export const calcAverageScore = scores => {
  if (!scores.length) return 0
  
  return Math.round(scores.reduce((a, b) => a + b, 0) / scores.length)
}

export const calcMedianScore = scores => {
  if (!scores.length) return 0
  const arr = [...scores].sort((a, b) => a - b)
  const mid = Math.floor(arr.length / 2)
  
  return arr.length % 2 ? arr[mid] : Math.round((arr[mid - 1] + arr[mid]) / 2)
}

export const calcMinScore = scores => {
  return scores.reduce((m, s) => Math.min(m, s), scores[0] ?? 0)
}

export const calcMaxScore = scores => {
  return scores.reduce((m, s) => Math.max(m, s), 0)
}
