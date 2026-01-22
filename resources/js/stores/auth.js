// Centralizes auth state and keeps the force-password-reset flag in localStorage.
import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'
import { extractApiErrorMessage, getErrorMessage } from '@/utils/apiError'
import { ensureCsrfCookie, fetchJson, buildHttpError } from '@/utils/http'

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
        await ensureCsrfCookie()
        const { data, response } = await fetchJson('/login', {
          method: 'POST',
          body: credentials,
        })
        if (!response.ok) {
          const message = extractApiErrorMessage(data) || data?.error || 'Login failed'
          throw buildHttpError(response, data, message)
        }
        const payload = data || {}
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
        this.error = getErrorMessage(err, 'Login failed')
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
        await fetchJson('/logout', { method: 'POST' })
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
