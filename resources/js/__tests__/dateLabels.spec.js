import { describe, it, expect } from 'vitest'
import { formatScheduledDate } from '../utils/dateLabels'

describe('formatScheduledDate', () => {
  it('returns empty string for missing values', () => {
    expect(formatScheduledDate('')).toBe('')
  })

  it('formats iso timestamps', () => {
    expect(formatScheduledDate('2024-01-02T03:04:05Z')).toBe('2024-01-02')
  })

  it('passes through non-iso strings', () => {
    expect(formatScheduledDate('2024-01-02')).toBe('2024-01-02')
  })

  it('returns empty string for null', () => {
    expect(formatScheduledDate(null)).toBe('')
  })

  it('handles numeric timestamps', () => {
    expect(formatScheduledDate(0)).toBe('1970-01-01')
  })
})
