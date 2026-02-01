import { describe, it, expect } from 'vitest'
import { buildAnswerMap } from '../utils/answerMap'

describe('buildAnswerMap', () => {
  it('maps answers to question metadata', () => {
    const map = buildAnswerMap([{ id: 1, answers: [{ id: 10, correct: 1 }] }])

    expect(map.get(10)).toEqual({ question_id: 1, correct: true })
  })

  it('returns empty map for missing questions', () => {
    const map = buildAnswerMap(null)

    expect(map.size).toBe(0)
  })
})
