import { describe, it, expect } from 'vitest'
import { resolveAuthNavigation } from '../utils/routeGuards'

describe('resolveAuthNavigation', () => {
  it('logs out when a signed-in user hits login', () => {
    const decision = resolveAuthNavigation({ name: 'login' }, { user: { id: 1 } })

    expect(decision.shouldLogout).toBe(true)
    expect(decision.redirect).toBeNull()
  })

  it('redirects to change-password when force reset is required', () => {
    const decision = resolveAuthNavigation({ name: 'root' }, { user: { id: 1 }, forcePasswordReset: true })

    expect(decision.redirect).toEqual({ name: 'change-password' })
  })

  it('redirects to login for protected routes when unauthenticated', () => {
    const decision = resolveAuthNavigation({ name: 'assessments', meta: {} }, { user: null })

    expect(decision.redirect).toEqual({ name: 'login' })
  })

  it('allows admin-only routes for poobah roles', () => {
    const decision = resolveAuthNavigation({ name: 'users', meta: { requiresAdmin: true } }, { user: { role: 'poobah' } })

    expect(decision.redirect).toBeNull()
  })

  it('redirects non-admin roles away from admin routes', () => {
    const decision = resolveAuthNavigation({ name: 'users', meta: { requiresAdmin: true } }, { user: { role: 'teacher' } })

    expect(decision.redirect).toEqual({ name: 'root' })
  })
})
