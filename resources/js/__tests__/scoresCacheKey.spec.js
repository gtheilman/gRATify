import { describe, it, expect } from 'vitest'
import { resolveScoresCacheKey } from '../utils/scoresCacheKey'

describe('resolveScoresCacheKey', () => {
  it('builds a cache key from assessment and scheme', () => {
    expect(resolveScoresCacheKey(1, 'linear-decay')).toBe('scores-cache-1-linear-decay')
  })

  it('handles empty scheme values', () => {
    expect(resolveScoresCacheKey(2, '')).toBe('scores-cache-2-')
  })
})
