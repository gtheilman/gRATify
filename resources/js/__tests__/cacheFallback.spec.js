import { describe, it, expect } from 'vitest'
import { applyCachedFallback } from '../utils/cacheFallback'

describe('applyCachedFallback', () => {
  it('returns false when no cached data is available', () => {
    const applied = applyCachedFallback({ cached: null })

    expect(applied).toBe(false)
  })

  it('applies cached data and notice when available', () => {
    let dataValue = null
    let noticeValue = ''

    const applied = applyCachedFallback({
      cached: { data: { id: 1 }, cachedAt: '2024-01-02T03:04:05Z' },
      applyData: data => { dataValue = data },
      applyNotice: notice => { noticeValue = notice },
      formatter: date => date.toISOString().slice(0, 10),
    })

    expect(applied).toBe(true)
    expect(dataValue).toEqual({ id: 1 })
    expect(noticeValue).toBe('Showing cached data from 2024-01-02')
  })

  it('returns false when cached data is empty', () => {
    const applied = applyCachedFallback({ cached: { data: null } })

    expect(applied).toBe(false)
  })

  it('uses custom formatter output', () => {
    let noticeValue = ''
    applyCachedFallback({
      cached: { data: { id: 1 }, cachedAt: '2024-01-01T00:00:00Z' },
      applyData: () => {},
      applyNotice: notice => { noticeValue = notice },
      formatter: () => 'Custom',
    })
    expect(noticeValue).toBe('Showing cached data from Custom')
  })
})
