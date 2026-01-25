import { describe, it, expect } from 'vitest'
import { sortAssessments } from '../utils/assessmentSorting'

describe('assessment sorting helpers', () => {
  it('sorts by title', () => {
    const list = [
      { id: 1, title: 'Beta' },
      { id: 2, title: 'Alpha' },
    ]
    const sorted = sortAssessments(list, 'title', 'asc')
    expect(sorted.map(item => item.id)).toEqual([2, 1])
  })

  it('sorts locked assessments after unlocked when using actions', () => {
    const list = [
      { id: 1, title: 'Locked', presentations_count: 1 },
      { id: 2, title: 'Open', presentations_count: 0 },
    ]
    const sorted = sortAssessments(list, 'actions', 'asc')
    expect(sorted.map(item => item.id)).toEqual([2, 1])
  })

  it('sorts by course when requested', () => {
    const list = [
      { id: 1, course: 'B' },
      { id: 2, course: 'A' },
    ]
    const sorted = sortAssessments(list, 'course', 'asc')
    expect(sorted[0].id).toBe(2)
  })
})
