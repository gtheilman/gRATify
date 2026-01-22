import { describe, it, expect } from 'vitest'
import { filterUsers, shouldResetUserPage } from '../utils/userFilters'

describe('user filter helpers', () => {
  const users = [
    { id: 1, name: 'Alice', email: 'alice@example.com', role: 'editor' },
    { id: 2, name: 'Bob', email: 'bob@example.com', role: 'poobah' },
  ]

  it('filters by search term and role', () => {
    expect(filterUsers(users, 'ali', 'all').map(u => u.id)).toEqual([1])
    expect(filterUsers(users, '', 'admin').map(u => u.id)).toEqual([2])
  })

  it('signals page reset when filters change', () => {
    const prev = { term: 'alice', role: 'all' }
    const next = { term: '', role: 'all' }
    expect(shouldResetUserPage(prev, next)).toBe(true)
  })

  it('does not reset when filters are unchanged', () => {
    const prev = { term: 'alice', role: 'all' }
    const next = { term: 'alice', role: 'all' }
    expect(shouldResetUserPage(prev, next)).toBe(false)
  })
})
