import { describe, it, expect } from 'vitest'
import { canDeleteUser, isLastAdminUser } from '../utils/userDeleteRules'

describe('user delete rules', () => {
  it('blocks deleting the last admin user', () => {
    const user = { id: 1, role: 'admin' }

    expect(isLastAdminUser(user, 1)).toBe(true)
    expect(canDeleteUser(user, 1)).toBe(false)
  })

  it('allows deleting non-admin users without assessments', () => {
    const user = { id: 2, role: 'editor', assessments_count: 0 }

    expect(canDeleteUser(user, 1)).toBe(true)
  })

  it('blocks deleting users with assessments', () => {
    const user = { id: 3, role: 'editor', assessments_count: 2 }

    expect(canDeleteUser(user, 2)).toBe(false)
  })
})
