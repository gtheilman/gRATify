import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { readFeedbackCache, writeFeedbackCache } from '../utils/feedbackCache'

const mockStorage = () => {
  let store = {}
  
  return {
    getItem: key => (key in store ? store[key] : null),
    setItem: (key, value) => { store[key] = String(value) },
    removeItem: key => { delete store[key] },
    clear: () => { store = {} },
  }
}

describe('feedback cache helpers', () => {
  beforeEach(() => {
    vi.stubGlobal('localStorage', mockStorage())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('writes and reads cached payloads', () => {
    writeFeedbackCache(9, { foo: 'bar' })

    const cached = readFeedbackCache(9)

    expect(cached?.data?.foo).toBe('bar')
  })

  it('returns null for invalid cache data', () => {
    globalThis.localStorage.setItem('feedback-cache-2', '{')
    expect(readFeedbackCache(2)).toBeNull()
  })

  it('returns null when no cache is present', () => {
    expect(readFeedbackCache(42)).toBeNull()
  })
})
