import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { readProgressCache, writeProgressCache } from '../utils/progressCache'

const mockStorage = () => {
  let store = {}
  return {
    getItem: key => (key in store ? store[key] : null),
    setItem: (key, value) => { store[key] = String(value) },
    removeItem: key => { delete store[key] },
    clear: () => { store = {} },
  }
}

describe('progress cache helpers', () => {
  beforeEach(() => {
    vi.stubGlobal('localStorage', mockStorage())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('writes and reads cached payloads', () => {
    writeProgressCache(12, { foo: 'bar' })
    const cached = readProgressCache(12)
    expect(cached?.data?.foo).toBe('bar')
  })

  it('returns null when cached data is invalid', () => {
    globalThis.localStorage.setItem('progress-cache-99', '{')
    expect(readProgressCache(99)).toBeNull()
  })

  it('returns null when no cache is present', () => {
    expect(readProgressCache(123)).toBeNull()
  })
})
