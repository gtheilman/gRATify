import { describe, it, expect } from 'vitest'
import { calcPercent } from '../utils/progressStats'

describe('calcPercent', () => {
  it('computes percent with rounding', () => {
    expect(calcPercent(1, 4)).toBe(25)
  })

  it('avoids division by zero', () => {
    expect(calcPercent(1, 0)).toBe(100)
  })

  it('returns zero when both values are zero', () => {
    expect(calcPercent(0, 0)).toBe(0)
  })
})
