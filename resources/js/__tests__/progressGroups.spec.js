import { describe, it, expect } from 'vitest'
import { resolveGroupLabel } from '../utils/progressGroups'

describe('resolveGroupLabel', () => {
  it('prefers group label over ids', () => {
    expect(resolveGroupLabel({ group_label: 'Group A', group_id: 1 })).toBe('Group A')
  })

  it('falls back to group id or user id', () => {
    expect(resolveGroupLabel({ group_id: 5 })).toBe(5)
    expect(resolveGroupLabel({ user_id: 'user-1' })).toBe('user-1')
  })

  it('falls back to presentation id when others missing', () => {
    expect(resolveGroupLabel({ id: 99 })).toBe(99)
  })

  it('prefers group label even with other ids', () => {
    expect(resolveGroupLabel({ group_label: 'Group X', user_id: 'u1' })).toBe('Group X')
  })
})
