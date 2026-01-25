import { describe, it, expect } from 'vitest'
import { buildUsersRangeLabel } from '../utils/usersPagination'

describe('buildUsersRangeLabel', () => {
  it('handles empty totals', () => {
    expect(buildUsersRangeLabel(1, 10, 0)).toBe('Rows 0 - 0 of 0')
  })

  it('formats ranges for populated lists', () => {
    expect(buildUsersRangeLabel(2, 10, 35)).toBe('Rows 11 - 20 of 35')
  })

  it('clamps end to total size', () => {
    expect(buildUsersRangeLabel(3, 10, 25)).toBe('Rows 21 - 25 of 25')
  })
})
