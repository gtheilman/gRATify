import { describe, it, expect } from 'vitest'
import { buildStaleNotice } from '../utils/cacheNotice'

describe('cache notice helper', () => {
  it('returns empty string for missing timestamps', () => {
    expect(buildStaleNotice(null)).toBe('')
    expect(buildStaleNotice(undefined)).toBe('')
  })

  it('returns empty string for invalid timestamps', () => {
    expect(buildStaleNotice('not-a-date')).toBe('')
  })

  it('formats cached timestamps with the provided formatter', () => {
    const notice = buildStaleNotice('2024-01-02T03:04:05Z', date => date.toISOString().slice(0, 10))

    expect(notice).toBe('Showing cached data from 2024-01-02')
  })
})
