import { describe, it, expect } from 'vitest'
import { scoreSortOptions } from '../utils/scoreSortOptions'

describe('score sort options', () => {
  it('includes expected sort keys', () => {
    expect(scoreSortOptions.map(option => option.value)).toEqual(['user', 'score_desc', 'score_asc'])
  })

  it('includes titles for display', () => {
    expect(typeof scoreSortOptions[0].title).toBe('string')
  })

  it('keeps option count stable', () => {
    expect(scoreSortOptions.length).toBe(3)
  })

  it('defaults to user sort as first option', () => {
    expect(scoreSortOptions[0].value).toBe('user')
  })
})
