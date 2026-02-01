import { describe, it, expect } from 'vitest'
import { sortProgressRows } from '../utils/progressSorting'

describe('sortProgressRows', () => {
  it('sorts by percent when requested', () => {
    const rows = [{ percent: 40 }, { percent: 10 }]
    const sorted = sortProgressRows(rows, 'percent', 'asc')

    expect(sorted[0].percent).toBe(10)
  })

  it('sorts by group label otherwise', () => {
    const rows = [{ group: 'B' }, { group: 'A' }]
    const sorted = sortProgressRows(rows, 'group', 'asc')

    expect(sorted[0].group).toBe('A')
  })

  it('sorts by percent descending', () => {
    const rows = [{ percent: 10 }, { percent: 90 }]
    const sorted = sortProgressRows(rows, 'percent', 'desc')

    expect(sorted[0].percent).toBe(90)
  })

  it('keeps stable ordering for equal percents', () => {
    const rows = [{ percent: 10, group: 'A' }, { percent: 10, group: 'B' }]
    const sorted = sortProgressRows(rows, 'percent', 'asc')

    expect(sorted[0].group).toBe('A')
    expect(sorted[1].group).toBe('B')
  })
})
