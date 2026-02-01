// Admin user management store; normalizes roles for legacy 'poobah' accounts.
import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'
import { getErrorMessage } from '@/utils/apiError'

const api = useApi

export const useUsersStore = defineStore('users', {
  state: () => ({
    users: [],
    loading: false,
    error: null,
  }),
  getters: {
    roles: state => {
      const roles = new Set()

      state.users.forEach(u => u?.role && roles.add(u.role))
      
      return ['All', ...Array.from(roles)]
    },
  },
  actions: {
    async fetchUsers() {
      this.loading = true
      this.error = null
      try {
        const { data, error } = await api('/user-management/users', { method: 'GET' })
        if (error.value)
          throw error.value
        const payload = data.value
        const normalizeRole = role => role === 'poobah' ? 'admin' : role

        const normalizeUsers = list => (list || []).map(u => ({
          ...u,
          role: normalizeRole(u?.role),
        }))

        if (Array.isArray(payload)) {
          this.users = normalizeUsers(payload)
        } else if (payload && typeof payload === 'object') {
          this.users = normalizeUsers(Object.values(payload))
        } else {
          this.users = []
        }
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to load users')
        throw err
      }
      finally {
        this.loading = false
      }
    },
    async createUser(payload) {
      this.loading = true
      this.error = null
      try {
        const { error } = await api('/user-management/users/register-user', {
          method: 'POST',
          body: payload,
        })

        if (error.value)
          throw error.value
        await this.fetchUsers()
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to load user')
        throw err
      }
      finally {
        this.loading = false
      }
    },
    async updateUser(id, payload) {
      if (!id)
        return
      this.loading = true
      this.error = null
      try {
        const { error } = await api(`/user-management/users/${id}`, {
          method: 'PATCH',
          body: payload,
        })

        if (error.value)
          throw error.value
        await this.fetchUsers()
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to update user')
        throw err
      }
      finally {
        this.loading = false
      }
    },
    async deleteUser(id) {
      if (!id)
        return
      this.loading = true
      this.error = null
      try {
        const { error } = await api(`/user-management/users/${id}`, { method: 'DELETE' })
        if (error.value)
          throw error.value
        await this.fetchUsers()
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to delete user')
        throw err
      }
      finally {
        this.loading = false
      }
    },
  },
})
