import { calcAverageScore, calcMaxScore, calcMedianScore, calcMinScore, toNumericScores } from '@/utils/scoreStats'

export const buildScoreSummary = presentations => {
  const scores = toNumericScores(presentations)
  
  return {
    average: calcAverageScore(scores),
    max: calcMaxScore(scores),
    min: calcMinScore(scores),
    median: calcMedianScore(scores),
  }
}
