import { describe, it, expect } from 'vitest'
import { clearNotice } from '../utils/staleNotice'

describe('clearNotice', () => {
  it('clears a ref-like value', () => {
    const refValue = { value: 'Stale' }
    clearNotice(refValue)
    expect(refValue.value).toBe('')
  })
})
