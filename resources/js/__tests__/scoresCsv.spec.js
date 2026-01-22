import { describe, it, expect } from 'vitest'
import { buildScoresCsv } from '../utils/scoresCsv'

describe('buildScoresCsv', () => {
  it('builds a csv payload with header and rows', () => {
    const payload = buildScoresCsv([{ user_id: 'abc', score: 88 }], value => String(value))
    expect(payload).toBe('UserID,Score\r\nabc,88')
  })

  it('strips commas from user ids', () => {
    const payload = buildScoresCsv([{ user_id: 'a,b', score: 90 }])
    expect(payload).toBe('UserID,Score\r\nab,90')
  })

  it('returns header only when no rows are present', () => {
    expect(buildScoresCsv([])).toBe('UserID,Score')
  })

  it('handles missing user ids', () => {
    const payload = buildScoresCsv([{ score: 70 }])
    expect(payload).toBe('UserID,Score\r\n,70')
  })

  it('does not quote score strings with commas', () => {
    const payload = buildScoresCsv([{ user_id: 'u1', score: '1,000' }])
    expect(payload).toBe('UserID,Score\r\nu1,1,000')
  })

  it('allows formatters to blank invalid scores', () => {
    const payload = buildScoresCsv([{ user_id: 'u1', score: null }], value => (value == null ? '' : String(value)))
    expect(payload).toBe('UserID,Score\r\nu1,')
  })

  it('returns header when rows are missing', () => {
    expect(buildScoresCsv()).toBe('UserID,Score')
  })
})
