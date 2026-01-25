import { describe, it, expect } from 'vitest'
import { formatScore } from '../utils/scoreFormatting'

describe('formatScore', () => {
  it('returns empty string for invalid values', () => {
    expect(formatScore('not-a-number')).toBe('')
  })

  it('formats integers without decimals', () => {
    expect(formatScore(90)).toBe('90')
  })

  it('formats floats with one decimal', () => {
    expect(formatScore(87.35)).toBe('87.3')
  })

  it('returns empty string for undefined', () => {
    expect(formatScore(undefined)).toBe('')
  })

  it('rounds to a single decimal place', () => {
    expect(formatScore(0.05)).toBe('0.1')
  })

  it('formats negative scores', () => {
    expect(formatScore(-1.25)).toBe('-1.3')
  })
})
