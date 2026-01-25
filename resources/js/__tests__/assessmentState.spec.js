import { describe, it, expect } from 'vitest'
import { parseActiveFlag } from '../utils/assessmentState'

describe('parseActiveFlag', () => {
  it('passes through booleans', () => {
    expect(parseActiveFlag(true)).toBe(true)
    expect(parseActiveFlag(false)).toBe(false)
  })

  it('normalizes numeric flags', () => {
    expect(parseActiveFlag(1)).toBe(true)
    expect(parseActiveFlag(0)).toBe(false)
  })

  it('normalizes string flags', () => {
    expect(parseActiveFlag('1')).toBe(true)
    expect(parseActiveFlag('0')).toBe(false)
  })

  it('returns null for empty values', () => {
    expect(parseActiveFlag(null)).toBeNull()
  })

  it('returns null for undefined values', () => {
    expect(parseActiveFlag(undefined)).toBeNull()
  })
})
