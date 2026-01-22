import { describe, it, expect } from 'vitest'
import { calcGroupLabelWidth, calcMaxGroupLength } from '../utils/progressLayout'

describe('progress layout helpers', () => {
  it('calculates max group length', () => {
    expect(calcMaxGroupLength([{ group: 'A' }, { group: 'Longer' }])).toBe(6)
  })

  it('computes group label width with minimum', () => {
    expect(calcGroupLabelWidth([{ group: 'AB' }], 8)).toBe('8ch')
  })

  it('expands width when labels are long', () => {
    expect(calcGroupLabelWidth([{ group: 'LongLabel' }], 8)).toBe('11ch')
  })
})
