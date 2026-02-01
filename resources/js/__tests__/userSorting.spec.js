import { describe, it, expect } from 'vitest'
import { sortUsers, normalizeRole } from '../utils/userSorting'

describe('userSorting helpers', () => {
  it('normalizes legacy poobah role', () => {
    expect(normalizeRole('poobah')).toBe('admin')
    expect(normalizeRole('editor')).toBe('editor')
  })

  it('sorts by last name when available', () => {
    const users = [
      { name: 'Alice Zebra' },
      { name: 'Bob Alpha' },
    ]

    const sorted = sortUsers(users, 'name', 'asc')

    expect(sorted[0].name).toBe('Bob Alpha')
  })

  it('sorts by email when requested', () => {
    const users = [
      { email: 'z@example.com' },
      { email: 'a@example.com' },
    ]

    const sorted = sortUsers(users, 'email', 'asc')

    expect(sorted[0].email).toBe('a@example.com')
  })
})
