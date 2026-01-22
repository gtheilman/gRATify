import { describe, it, expect } from 'vitest'
import { buildScoreSummary } from '../utils/scoreSummary'

describe('buildScoreSummary', () => {
  it('computes summary statistics', () => {
    const summary = buildScoreSummary([{ score: 100 }, { score: 50 }])
    expect(summary.average).toBe(75)
    expect(summary.max).toBe(100)
    expect(summary.min).toBe(50)
    expect(summary.median).toBe(75)
  })

  it('returns zeros for empty input', () => {
    const summary = buildScoreSummary([])
    expect(summary.average).toBe(0)
    expect(summary.max).toBe(0)
    expect(summary.min).toBe(0)
    expect(summary.median).toBe(0)
  })

  it('ignores non-numeric scores', () => {
    const summary = buildScoreSummary([{ score: 'abc' }])
    expect(summary.average).toBe(0)
  })
})
