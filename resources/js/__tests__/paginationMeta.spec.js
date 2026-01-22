import { describe, it, expect } from 'vitest'
import { paginationMeta } from '../utils/paginationMeta'

describe('paginationMeta', () => {
  it('handles empty totals', () => {
    expect(paginationMeta({ page: 1, itemsPerPage: 10 }, 0)).toBe('Showing 0 to 0 of 0 entries')
  })

  it('computes correct range for middle pages', () => {
    expect(paginationMeta({ page: 2, itemsPerPage: 10 }, 35)).toBe('Showing 11 to 20 of 35 entries')
  })

  it('clamps range to total on the last page', () => {
    expect(paginationMeta({ page: 2, itemsPerPage: 10 }, 12)).toBe('Showing 11 to 12 of 12 entries')
  })

  it('handles zero items per page', () => {
    expect(paginationMeta({ page: 1, itemsPerPage: 0 }, 10)).toBe('Showing 0 to 0 of 10 entries')
  })

  it('handles negative page values', () => {
    expect(paginationMeta({ page: -1, itemsPerPage: 10 }, 10)).toBe('Showing 1 to 10 of 10 entries')
  })
})
