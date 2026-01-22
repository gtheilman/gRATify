import { describe, it, expect } from 'vitest'
import { shouldResetPageOnPageSizeChange, shouldResetPageOnToggle } from '../utils/paginationReset'

describe('shouldResetPageOnPageSizeChange', () => {
  it('returns true when page size changes', () => {
    expect(shouldResetPageOnPageSizeChange(10, 25)).toBe(true)
  })

  it('returns false when page size is unchanged', () => {
    expect(shouldResetPageOnPageSizeChange(10, 10)).toBe(false)
  })
})

describe('shouldResetPageOnToggle', () => {
  it('returns true when toggle changes', () => {
    expect(shouldResetPageOnToggle(false, true)).toBe(true)
  })

  it('returns false when toggle unchanged', () => {
    expect(shouldResetPageOnToggle(true, true)).toBe(false)
  })
})
