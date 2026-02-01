import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { buildScoresCacheKey, readScoresCache, writeScoresCache } from '../utils/scoresCache'
import { buildScoreCacheKey } from '../utils/scoreCacheKey'

const mockStorage = () => {
  let store = {}
  
  return {
    getItem: key => (key in store ? store[key] : null),
    setItem: (key, value) => { store[key] = String(value) },
    removeItem: key => { delete store[key] },
    clear: () => { store = {} },
  }
}

describe('scores cache helpers', () => {
  beforeEach(() => {
    vi.stubGlobal('localStorage', mockStorage())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('builds cache keys by assessment and scheme', () => {
    expect(buildScoresCacheKey(5, 'linear-decay')).toBe('scores-cache-5-linear-decay')
    expect(buildScoreCacheKey(5, 'linear-decay')).toBe('scores-cache-5-linear-decay')
    expect(buildScoreCacheKey(5, '')).toBe('scores-cache-5-')
  })

  it('writes and reads cached payloads', () => {
    const key = buildScoresCacheKey(1, 'geometric-decay')

    writeScoresCache(key, [{ score: 90 }])

    const cached = readScoresCache(key)

    expect(cached?.data?.[0]?.score).toBe(90)
  })
})
