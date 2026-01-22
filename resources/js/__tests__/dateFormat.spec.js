import { describe, it, expect } from 'vitest'
import { formatTimestamp } from '../utils/dateFormat'

describe('formatTimestamp', () => {
  it('returns empty string for empty values', () => {
    expect(formatTimestamp('')).toBe('')
  })

  it('returns original value for invalid dates', () => {
    expect(formatTimestamp('not-a-date')).toBe('not-a-date')
  })

  it('formats valid dates', () => {
    expect(formatTimestamp('2024-01-02T03:04:05')).toBe('2024-01-02 03:04:05')
  })

  it('returns empty string for null', () => {
    expect(formatTimestamp(null)).toBe('')
  })

  it('returns empty string for undefined', () => {
    expect(formatTimestamp(undefined)).toBe('')
  })
})
