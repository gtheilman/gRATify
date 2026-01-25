import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { saveIdentifier, loadIdentifier } from '../utils/cache'

const mockStorage = () => {
  let store = {}
  return {
    getItem: vi.fn(key => store[key] || null),
    setItem: vi.fn((key, val) => { store[key] = String(val) }),
    removeItem: vi.fn(key => { delete store[key] }),
    key: vi.fn(index => Object.keys(store)[index]),
    get length () {
      return Object.keys(store).length
    },
    clear: () => { store = {} }
  }
}

describe('identifier cache', () => {
  let originalLocalStorage

  beforeEach(() => {
    originalLocalStorage = global.localStorage
    global.localStorage = mockStorage()
  })

  it('saves and loads identifier', () => {
    saveIdentifier('pw', 'abc')
    expect(loadIdentifier('pw')).toBe('abc')
  })

  it('expires after ttl', () => {
    saveIdentifier('pw', 'abc')
    const originalDateNow = Date.now
    Date.now = () => originalDateNow() + (21 * 60 * 1000) // beyond ttl
    expect(loadIdentifier('pw')).toBeNull()
    Date.now = originalDateNow
  })

  afterEach(() => {
    global.localStorage = originalLocalStorage
  })
})
