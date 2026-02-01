import { describe, it, expect, vi } from 'vitest'
import { getFullscreenElement, requestFullscreen } from '../utils/progressFullscreen'

describe('progress fullscreen helpers', () => {
  it('returns document element for fullscreen target', () => {
    const el = getFullscreenElement()
    if (typeof document === 'undefined') {
      expect(el).toBeNull()
    } else {
      expect(el).toBe(document.documentElement)
    }
  })

  it('invokes standard requestFullscreen when available', () => {
    const el = { requestFullscreen: vi.fn() }

    expect(requestFullscreen(el)).toBe(true)
    expect(el.requestFullscreen).toHaveBeenCalled()
  })
})
