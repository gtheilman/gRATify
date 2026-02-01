import { describe, it, expect, vi } from 'vitest'
import { handleLogoutAction } from '../utils/topNavLogout'

describe('handleLogoutAction', () => {
  it('logs out and redirects to login', async () => {
    const logout = vi.fn().mockResolvedValue()
    const push = vi.fn()

    await handleLogoutAction({ logout, push })
    expect(logout).toHaveBeenCalled()
    expect(push).toHaveBeenCalledWith({ name: 'login' })
  })
})
