import { buildScoresCacheKey } from '@/utils/scoresCache'

export const resolveScoresCacheKey = (assessmentId, scheme) => {
  return buildScoresCacheKey(assessmentId, scheme)
}
