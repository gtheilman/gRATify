import { describe, it, expect } from 'vitest'
import { getAssessmentPageCount, paginateAssessments } from '../utils/assessmentsPagination'

describe('assessment pagination helpers', () => {
  it('returns a single page when page size is all', () => {
    expect(getAssessmentPageCount(25, 'all')).toBe(1)
  })

  it('preserves sort order when page size is all', () => {
    const list = [{ id: 2 }, { id: 1 }]

    expect(paginateAssessments(list, 'all', 1)).toEqual(list)
  })

  it('paginates by page size and current page', () => {
    const list = [1, 2, 3, 4, 5]

    expect(paginateAssessments(list, 2, 2)).toEqual([3, 4])
  })
})
