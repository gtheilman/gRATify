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
    migrationWarning: null,
    migrationWarningChecked: false,
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
        {localStorage.setItem('forcePasswordReset', 'true')}
        else
        {localStorage.removeItem('forcePasswordReset')}
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
        {throw error.value}
        this.user = data.value

        const needsReset = data.value?.force_password_reset ?? false

        this.forcePasswordReset = needsReset
        if (needsReset)
        {localStorage.setItem('forcePasswordReset', 'true')}
        else
        {localStorage.removeItem('forcePasswordReset')}
        await this.checkMigrationStatus()
        
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
      {return this.user}
      
      return this.fetchUser()
    },
    async checkMigrationStatus() {
      if (this.migrationWarningChecked)
      {return this.migrationWarning}
      const role = this.user?.role
      const normalized = role === 'poobah' ? 'admin' : role
      if (normalized !== 'admin') {
        this.migrationWarning = null
        this.migrationWarningChecked = true
        
        return null
      }
      try {
        const { data, error } = await api('/admin/migration-status', { method: 'GET' })
        if (error.value)
        {throw error.value}
        const payload = data.value || {}
        if (payload?.ok === false && Array.isArray(payload?.missing) && payload.missing.length > 0)
        {this.migrationWarning = payload}
        else
        {this.migrationWarning = null}
        
        return this.migrationWarning
      }
      catch {
        return null
      }
      finally {
        this.migrationWarningChecked = true
      }
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
      this.migrationWarning = null
      this.migrationWarningChecked = false
      localStorage.removeItem('forcePasswordReset')
    },
  },
})
