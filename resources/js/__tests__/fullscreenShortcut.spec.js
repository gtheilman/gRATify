import { describe, it, expect } from 'vitest'
import { shouldTriggerFullscreen } from '../utils/fullscreenShortcut'

describe('shouldTriggerFullscreen', () => {
  it('returns true for f key', () => {
    expect(shouldTriggerFullscreen({ key: 'f' })).toBe(true)
    expect(shouldTriggerFullscreen({ key: 'F' })).toBe(true)
  })

  it('returns false for other keys', () => {
    expect(shouldTriggerFullscreen({ key: 'x' })).toBe(false)
  })

  it('returns false when key is missing', () => {
    expect(shouldTriggerFullscreen({})).toBe(false)
  })
})
