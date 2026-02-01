import { describe, it, expect } from 'vitest'
import { calcAverageScore, calcMedianScore, toNumericScores } from '../utils/scoreStats'

describe('score stats helpers', () => {
  it('converts presentations to numeric scores', () => {
    const scores = toNumericScores([{ score: '90' }, { score: 'abc' }])

    expect(scores).toEqual([90])
  })

  it('computes average and median scores', () => {
    expect(calcAverageScore([100, 50])).toBe(75)
    expect(calcMedianScore([50, 100, 0])).toBe(50)
  })

  it('handles empty arrays', () => {
    expect(calcAverageScore([])).toBe(0)
    expect(calcMedianScore([])).toBe(0)
  })

  it('computes median for even-sized arrays', () => {
    expect(calcMedianScore([0, 100])).toBe(50)
  })
})
