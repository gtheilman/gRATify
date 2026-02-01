import { describe, it, expect } from 'vitest'
import { clearDeleteDialog } from '../utils/deleteDialog'

describe('clearDeleteDialog', () => {
  it('clears pending/delete refs', () => {
    const pending = { value: { id: 1 } }
    const show = { value: true }

    clearDeleteDialog(pending, show)
    expect(pending.value).toBeNull()
    expect(show.value).toBe(false)
  })
})
