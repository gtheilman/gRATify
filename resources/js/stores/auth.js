import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'
import { getXsrfToken } from '@/utils/csrf'

const api = useApi

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    forcePasswordReset: localStorage.getItem('forcePasswordReset') === 'true',
    loading: false,
    error: null,
    initialized: false,
  }),
  actions: {
    async login(credentials) {
      this.loading = true
      this.error = null
      try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' })
        const xsrfToken = getXsrfToken()
        const response = await fetch('/login', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
          },
          body: JSON.stringify(credentials),
        })
        const payload = await response.json().catch(() => ({}))
        if (!response.ok) {
          const message = payload?.message || payload?.error || 'Login failed'
          throw new Error(message)
        }
        const needsReset = payload?.force_password_reset ?? false
        this.forcePasswordReset = needsReset
        if (needsReset)
          localStorage.setItem('forcePasswordReset', 'true')
        else
          localStorage.removeItem('forcePasswordReset')
        await this.fetchUser()
        return true
      }
      catch (err) {
        this.error = err
        throw err
      }
      finally {
        this.loading = false
      }
    },
    async fetchUser() {
      try {
        const { data, error } = await api('/auth/me', { method: 'GET' })
        if (error.value)
          throw error.value
        this.user = data.value
        const needsReset = data.value?.force_password_reset ?? false
        this.forcePasswordReset = needsReset
        if (needsReset)
          localStorage.setItem('forcePasswordReset', 'true')
        else
          localStorage.removeItem('forcePasswordReset')
        return this.user
      }
      catch {
        this.user = null
        return null
      }
      finally {
        this.initialized = true
      }
    },
    async ensureSession() {
      if (this.initialized)
        return this.user
      return this.fetchUser()
    },
    async logout() {
      try {
        const xsrfToken = getXsrfToken()
        await fetch('/logout', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
          },
        })
      }
      catch (e) {
        // ignore logout errors
      }
      this.user = null
      this.forcePasswordReset = false
      this.initialized = true
      localStorage.removeItem('forcePasswordReset')
    },
  },
})
