import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

vi.mock('@/composables/useApi', () => {
  return {
    useApi: () => () => ({
      data: { value: { id: 1, force_password_reset: false } },
      error: { value: null },
    }),
  }
})

vi.mock('@/utils/http', () => {
  return {
    ensureCsrfCookie: vi.fn().mockResolvedValue(null),
    fetchJson: vi.fn().mockResolvedValue({
      data: { force_password_reset: true },
      response: { ok: true },
    }),
    buildHttpError: (response, data, message) => {
      const err = new Error(message)
      err.status = response?.status
      err.data = data
      return err
    },
  }
})

const mockStorage = () => {
  let store = {}
  return {
    getItem: key => (key in store ? store[key] : null),
    setItem: (key, value) => { store[key] = String(value) },
    removeItem: key => { delete store[key] },
    clear: () => { store = {} },
  }
}

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('localStorage', mockStorage())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.resetModules()
  })

  it('persists force_password_reset flag after login', async () => {
    const { useAuthStore } = await import('@/stores/auth')
    const store = useAuthStore()

    await store.login({ email: 'a@b.com', password: 'pw' })

    expect(store.forcePasswordReset).toBe(true)
    expect(globalThis.localStorage.getItem('forcePasswordReset')).toBe('true')
  })

  it('clears force_password_reset flag on logout', async () => {
    const { useAuthStore } = await import('@/stores/auth')
    const store = useAuthStore()
    store.forcePasswordReset = true
    globalThis.localStorage.setItem('forcePasswordReset', 'true')

    await store.logout()

    expect(store.forcePasswordReset).toBe(false)
    expect(globalThis.localStorage.getItem('forcePasswordReset')).toBe(null)
  })
})
