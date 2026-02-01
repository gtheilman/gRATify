import { describe, it, expect } from 'vitest'
import { filterEditableAssessments, isAssessmentLocked } from '../utils/assessmentLocking'

describe('assessment locking helpers', () => {
  it('detects locked assessments from counts or arrays', () => {
    expect(isAssessmentLocked({ presentations_count: 1 })).toBe(true)
    expect(isAssessmentLocked({ attempts: [{ id: 1 }] })).toBe(true)
    expect(isAssessmentLocked({})).toBe(false)
  })

  it('treats zero counts as unlocked', () => {
    expect(isAssessmentLocked({ presentations_count: 0 })).toBe(false)
  })

  it('filters locked assessments when editable-only is enabled', () => {
    const items = [{ id: 1, presentations_count: 1 }, { id: 2, presentations_count: 0 }]
    const filtered = filterEditableAssessments(items, true)

    expect(filtered.map(item => item.id)).toEqual([2])
  })
})
