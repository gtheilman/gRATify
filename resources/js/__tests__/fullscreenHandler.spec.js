import { describe, it, expect, vi } from 'vitest'
import { handleFullscreenShortcut } from '../utils/fullscreenHandler'

describe('handleFullscreenShortcut', () => {
  it('invokes callback when shortcut matches', () => {
    const fn = vi.fn()

    handleFullscreenShortcut({ key: 'f' }, fn)
    expect(fn).toHaveBeenCalled()
  })

  it('does nothing when shortcut does not match', () => {
    const fn = vi.fn()

    handleFullscreenShortcut({ key: 'x' }, fn)
    expect(fn).not.toHaveBeenCalled()
  })
})
