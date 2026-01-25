import { describe, it, expect } from 'vitest'
import { filterAssessments } from '../utils/assessmentFilters'

describe('assessment filters', () => {
  const assessments = [
    { id: 1, title: 'Alpha', active: true, presentations_count: 1 },
    { id: 2, title: 'Beta', active: false, presentations_count: 0 },
  ]

  it('filters by active status', () => {
    const activeOnly = filterAssessments(assessments, { activeFilter: 'active' })
    expect(activeOnly.map(a => a.id)).toEqual([1])
  })

  it('filters by search term', () => {
    const results = filterAssessments(assessments, { term: 'beta' })
    expect(results.map(a => a.id)).toEqual([2])
  })

  it('trims whitespace in search terms', () => {
    const results = filterAssessments(assessments, { term: '  alpha  ' })
    expect(results.map(a => a.id)).toEqual([1])
  })

  it('returns all when no filters applied', () => {
    const results = filterAssessments(assessments, {})
    expect(results.map(a => a.id)).toEqual([1, 2])
  })

  it('filters out locked assessments when editable-only is enabled', () => {
    const results = filterAssessments(assessments, { showEditableOnly: true })
    expect(results.map(a => a.id)).toEqual([2])
  })
})
