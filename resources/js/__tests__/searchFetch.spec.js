import { describe, it, expect } from 'vitest'
import { shouldFetchSearchResults } from '../utils/searchFetch'

describe('shouldFetchSearchResults', () => {
  it('returns false for empty queries', () => {
    expect(shouldFetchSearchResults('')).toBe(false)
    expect(shouldFetchSearchResults('   ')).toBe(false)
  })

  it('returns true for non-empty queries', () => {
    expect(shouldFetchSearchResults('assessment')).toBe(true)
  })

  it('ignores tabs and newlines', () => {
    expect(shouldFetchSearchResults('\n\t')).toBe(false)
  })
})
