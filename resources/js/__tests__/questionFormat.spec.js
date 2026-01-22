import { describe, it, expect } from 'vitest'
import { displayStem } from '../utils/questionFormat'

describe('displayStem', () => {
  it('returns stem when present', () => {
    expect(displayStem({ stem: 'Question?' })).toBe('Question?')
  })

  it('returns empty string for missing stem', () => {
    expect(displayStem(null)).toBe('')
  })
})
