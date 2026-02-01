export const calcPercent = (correctCount, totalCorrect) => {
  const denom = totalCorrect || 1
  
  return Math.round((correctCount / denom) * 100)
}
