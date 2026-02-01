import { describe, it, expect } from 'vitest'
import { collectCorrectAnswerIds } from '../utils/correctAnswers'

describe('collectCorrectAnswerIds', () => {
  it('collects only correct answer ids', () => {
    const ids = collectCorrectAnswerIds([{ answers: [{ id: 1, correct: 1 }, { id: 2, correct: 0 }] }])

    expect(ids).toEqual([1])
  })

  it('returns empty array when questions are missing', () => {
    expect(collectCorrectAnswerIds(null)).toEqual([])
  })

  it('treats string correct flags as truthy', () => {
    const ids = collectCorrectAnswerIds([{ answers: [{ id: 3, correct: '1' }] }])

    expect(ids).toEqual([3])
  })
})
