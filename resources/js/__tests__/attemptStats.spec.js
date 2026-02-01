import { describe, it, expect } from 'vitest'
import { countCorrectAttempts } from '../utils/attemptStats'

describe('countCorrectAttempts', () => {
  it('counts attempts whose answer ids are in the correct set', () => {
    const attempts = [{ answer_id: 1 }, { answer_id: 2 }]
    const correctSet = new Set([2])

    expect(countCorrectAttempts(attempts, correctSet)).toBe(1)
  })

  it('returns zero when inputs are missing', () => {
    expect(countCorrectAttempts(null, null)).toBe(0)
  })

  it('ignores attempts without answer id', () => {
    const attempts = [{}, { answer_id: 1 }]
    const correctSet = new Set([1])

    expect(countCorrectAttempts(attempts, correctSet)).toBe(1)
  })
})
