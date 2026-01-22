import { describe, it, expect } from 'vitest'
import { needsSessionRefresh } from '../utils/errorFlags'

describe('needsSessionRefresh', () => {
  it('detects session expiration messages', () => {
    expect(needsSessionRefresh('Session expired: please refresh and try again.')).toBe(true)
  })

  it('returns false for unrelated messages', () => {
    expect(needsSessionRefresh('Unauthorized')).toBe(false)
  })
})
