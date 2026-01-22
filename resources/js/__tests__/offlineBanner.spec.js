import { describe, it, expect, vi } from 'vitest'
import { buildOfflineRetry, offlineBannerMessage } from '../utils/offlineBanner'

describe('offline banner helpers', () => {
  it('exposes the offline banner message', () => {
    expect(offlineBannerMessage).toContain('You are offline')
  })

  it('invokes router reload for retry', () => {
    const router = { go: vi.fn() }
    const retry = buildOfflineRetry(router)
    retry()
    expect(router.go).toHaveBeenCalledWith(0)
  })

  it('returns undefined when router is missing', () => {
    const retry = buildOfflineRetry(null)
    expect(retry()).toBeUndefined()
  })

  it('returns undefined when router lacks go()', () => {
    const retry = buildOfflineRetry({})
    expect(retry()).toBeUndefined()
  })
})
