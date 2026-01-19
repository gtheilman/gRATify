<script setup>
import { computed, onMounted, ref } from 'vue'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import { useApi } from '@/composables/useApi'
import { useOffline } from '@/composables/useOffline'

const usersStore = useUsersStore()
const authStore = useAuthStore()
const api = useApi
const { isOffline } = useOffline()

const search = ref('')
const roleFilter = ref('all')
const sortBy = ref('name') // name | email | role
const sortDir = ref('asc')

const pageSize = ref(10)
const currentPage = ref(1)

const roleOptions = ref([
  { label: 'All', value: 'all' },
  { label: 'Admin', value: 'admin' },
  { label: 'Editor', value: 'editor' },
])
const dialogRoleOptions = ref([
  { label: 'Admin', value: 'admin' },
  { label: 'Editor', value: 'editor' },
])

const dialogOpen = ref(false)
const dialogMode = ref('create') // create | edit
const dialogError = ref('')
const deleteError = ref('')
const dialogForm = ref({
  id: null,
  name: '',
  username: '',
  email: '',
  role: 'editor',
  password: '',
  company: '',
})
const dialogOriginalRole = ref(null)

const changePasswordDialog = ref(false)
const changePasswordError = ref('')
const changePasswordTarget = ref(null)
const changePasswordForm = ref({
  password: '',
  confirm: '',
})
const changePasswordSuccess = ref('')

const loaded = ref(false)
onMounted(async () => {
  await authStore.ensureSession()
  await usersStore.fetchUsers()
  loaded.value = true
})

const normalizedRole = user => (user?.role === 'poobah' ? 'admin' : user?.role || '')
const adminCount = computed(() => (usersStore.users || []).filter(u => normalizedRole(u) === 'admin').length)
const isLastAdmin = user => normalizedRole(user) === 'admin' && adminCount.value <= 1
const hasAssessments = user => Number(user?.assessments_count || 0) > 0
const cannotDeleteUser = user => isLastAdmin(user) || hasAssessments(user)

const filteredUsers = computed(() => {
  const term = search.value.toLowerCase()
  return (usersStore.users || []).filter(user => {
    const matchesSearch = [user.username, user.email, user.name, user.company]
      .filter(Boolean)
      .some(val => String(val).toLowerCase().includes(term))

    const roleValue = user.role === 'poobah' ? 'admin' : user.role
    const matchesRole = roleFilter.value === 'all' || roleValue === roleFilter.value

    return matchesSearch && matchesRole
  })
})

const pagedUsers = computed(() => {
  const list = [...filteredUsers.value].sort((a, b) => {
    const dir = sortDir.value === 'asc' ? 1 : -1
    const normalizedName = user => {
      if (!user)
        return ''
      const parts = String(user.name || user.username || '').trim().split(' ')
      if (parts.length === 1)
        return parts[0]
      const last = parts.pop()
      return `${last} ${parts.join(' ')}`
    }
    if (sortBy.value === 'email')
      return dir * String(a.email || '').localeCompare(b.email || '', undefined, { sensitivity: 'base' })
    if (sortBy.value === 'role')
      return dir * String(a.role || '').localeCompare(b.role || '', undefined, { sensitivity: 'base' })
    if (sortBy.value === 'company')
      return dir * String(a.company || '').localeCompare(b.company || '', undefined, { sensitivity: 'base' })
    if (sortBy.value === 'username')
      return dir * String(a.username || '').localeCompare(b.username || '', undefined, { sensitivity: 'base' })
    return dir * normalizedName(a).localeCompare(normalizedName(b), undefined, { sensitivity: 'base' })
  })
  const start = (currentPage.value - 1) * pageSize.value
  return list.slice(start, start + pageSize.value)
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredUsers.value.length / pageSize.value)))

const registerUser = () => {
  dialogMode.value = 'create'
  dialogForm.value = {
    id: null,
    name: '',
    username: '',
    email: '',
    role: 'editor',
    password: '',
    company: '',
  }
  dialogOriginalRole.value = 'editor'
  dialogError.value = ''
  dialogOpen.value = true
}

const changePassword = user => {
  changePasswordTarget.value = user
  changePasswordForm.value = { password: '', confirm: '' }
  changePasswordError.value = ''
  changePasswordSuccess.value = ''
  changePasswordDialog.value = true
}

const submitChangePassword = async () => {
  changePasswordError.value = ''
  changePasswordSuccess.value = ''
  if (!changePasswordTarget.value?.id) {
    changePasswordError.value = 'No user selected.'
    return
  }
  if (!changePasswordForm.value.password || !changePasswordForm.value.confirm) {
    changePasswordError.value = 'Please enter and confirm the new password.'
    return
  }
  if (changePasswordForm.value.password !== changePasswordForm.value.confirm) {
    changePasswordError.value = 'Passwords do not match.'
    return
  }
  try {
    const { error } = await api('/user-management/users/admin-change-password/', {
      method: 'POST',
      body: {
        user_id: changePasswordTarget.value.id,
        new_password: changePasswordForm.value.password,
        new_password_confirmation: changePasswordForm.value.confirm,
      },
    })
    if (error.value)
      throw error.value
    changePasswordSuccess.value = 'Password Successfully Changed'
    changePasswordTarget.value = null
    changePasswordForm.value = { password: '', confirm: '' }
    // Close modal shortly after success so user sees the message momentarily
    setTimeout(() => {
      changePasswordDialog.value = false
      changePasswordSuccess.value = ''
    }, 600)
  }
  catch (err) {
    changePasswordError.value = err?.message || 'Unable to change password.'
  }
}

const editUser = user => {
  dialogMode.value = 'edit'
  dialogForm.value = {
    id: user.id,
    name: user.name || '',
    username: user.username || '',
    email: user.email || '',
    role: user.role || 'editor',
    password: '',
    company: user.company || '',
  }
  dialogOriginalRole.value = normalizedRole(user)
  dialogError.value = ''
  dialogOpen.value = true
}

const deleteUser = async user => {
  if (!user || !user.id)
    return
  deleteError.value = ''
  if (isOffline.value) {
    deleteError.value = 'You are offline. Connect to the internet before deleting a user.'
    return
  }
  if (isLastAdmin(user)) {
    alert('You cannot delete the last admin. Create another admin before deleting this one.')
    return
  }
  if (!confirm(`Are you sure you want to delete ${user.username || 'this user'}?`))
    return
  try {
    await usersStore.deleteUser(user.id)
  }
  catch (err) {
    if (err?.status === 409 || err?.response?.status === 409) {
      deleteError.value = err?.data?.message || err?.message || 'Cannot delete user with existing assessments.'
      return
    }
    deleteError.value = err?.message || 'Unable to delete user.'
  }
}

const submitDialog = async () => {
  dialogError.value = ''
  if (!dialogForm.value.username || !dialogForm.value.email) {
    dialogError.value = 'Username and email are required.'
    return
  }
  // Block demoting the last admin
  if (dialogMode.value === 'edit'
    && dialogOriginalRole.value === 'admin'
    && dialogForm.value.role !== 'admin'
    && adminCount.value <= 1) {
    dialogError.value = 'You cannot demote the last admin. Create another admin first.'
    return
  }

  try {
    if (dialogMode.value === 'create') {
      await usersStore.createUser(dialogForm.value)
    } else if (dialogForm.value.id) {
      await usersStore.updateUser(dialogForm.value.id, {
        name: dialogForm.value.name,
        username: dialogForm.value.username,
        email: dialogForm.value.email,
        role: dialogForm.value.role,
        company: dialogForm.value.company,
        password: dialogForm.value.password || undefined,
      })
    }
    dialogOpen.value = false
  }
  catch (err) {
    dialogError.value = err?.message || 'Unable to save user'
  }
}

const resetFilters = () => {
  roleFilter.value = 'all'
  search.value = ''
  currentPage.value = 1
  sortBy.value = 'name'
  sortDir.value = 'asc'
}
</script>

<template>
  <div id="page-user-list" class="users-page">
    <div class="hero-card minimal-hero d-flex justify-end mb-3">
      <VBtn color="primary" prepend-icon="tabler-user-plus" @click="registerUser">
        Add User
      </VBtn>
    </div>

    <VAlert
      v-if="deleteError"
      type="error"
      closable
      class="mb-4"
      @click:close="deleteError = ''"
    >
      {{ deleteError }}
    </VAlert>

    <VCard class="mb-4 filter-card">
      <VCardText class="d-flex flex-wrap gap-3 align-center">
        <VTextField
          v-model="search"
          placeholder="Search by name, username, or email"
          prepend-inner-icon="tabler-search"
          hide-details
          density="comfortable"
          class="flex-grow-1"
        />
      </VCardText>
    </VCard>

    <VCard class="legacy-shell glass-card">
      <VTable class="legacy-table users-table">
        <thead>
          <tr>
            <th>
              <button class="sort-btn" @click="() => { sortBy = 'name'; sortDir = sortDir === 'asc' ? 'desc' : 'asc' }">
                User
                <VIcon
                  v-if="sortBy === 'name'"
                  :icon="sortDir === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                  size="16"
                  class="ms-1"
                />
              </button>
            </th>
            <th>
              <button class="sort-btn" @click="() => { sortBy = 'username'; sortDir = sortDir === 'asc' ? 'desc' : 'asc' }">
                Username
                <VIcon
                  v-if="sortBy === 'username'"
                  :icon="sortDir === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                  size="16"
                  class="ms-1"
                />
              </button>
            </th>
            <th>
              <button class="sort-btn" @click="() => { sortBy = 'email'; sortDir = sortDir === 'asc' ? 'desc' : 'asc' }">
                Email
                <VIcon
                  v-if="sortBy === 'email'"
                  :icon="sortDir === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                  size="16"
                  class="ms-1"
                />
              </button>
            </th>
            <th>
              <button class="sort-btn" @click="() => { sortBy = 'company'; sortDir = sortDir === 'asc' ? 'desc' : 'asc' }">
                School/Company
                <VIcon
                  v-if="sortBy === 'company'"
                  :icon="sortDir === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                  size="16"
                  class="ms-1"
                />
              </button>
            </th>
            <th>
              <button class="sort-btn" @click="() => { sortBy = 'role'; sortDir = sortDir === 'asc' ? 'desc' : 'asc' }">
                Role
                <VIcon
                  v-if="sortBy === 'role'"
                  :icon="sortDir === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                  size="16"
                  class="ms-1"
                />
              </button>
            </th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!loaded && !(usersStore.users || []).length">
            <td colspan="6" class="text-center py-6">
              <VProgressCircular indeterminate color="primary" size="32" class="mb-2" />
              <div class="text-medium-emphasis">Loading users…</div>
            </td>
          </tr>
          <tr v-for="user in pagedUsers" :key="user.id">
            <td data-label="User">
              <div class="font-weight-medium">{{ user.name || user.username }}</div>
            </td>
            <td data-label="Username">{{ user.username }}</td>
            <td data-label="Email">{{ user.email }}</td>
            <td data-label="School/Company">{{ user.company }}</td>
            <td data-label="Role">
              <VTooltip location="top">
                <template #activator="{ props }">
                  <VChip
                    v-bind="props"
                    size="small"
                    variant="tonal"
                    :color="(user.role === 'poobah' ? 'admin' : user.role) === 'admin' ? 'primary' : 'secondary'"
                  >
                    {{ user.role === 'poobah' ? 'admin' : user.role }}
                  </VChip>
                </template>
                <span>
                  {{ (user.role === 'poobah' ? 'admin' : user.role) === 'admin'
                    ? 'Can edit users. Sees all gRATs'
                    : 'Cannot see users. Sees only their own gRATs' }}
                </span>
              </VTooltip>
            </td>
            <td data-label="Actions" class="text-right">
              <VMenu location="bottom end">
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    variant="outlined"
                    color="primary"
                    size="small"
                    prepend-icon="tabler-settings"
                    aria-label="User actions"
                  />
                </template>
                <VList>
                  <VListItem @click="editUser(user)">
                    <VListItemTitle>Edit</VListItemTitle>
                  </VListItem>
                  <VListItem @click="changePassword(user)">
                    <VListItemTitle>Change Password</VListItemTitle>
                  </VListItem>
                  <VTooltip v-if="hasAssessments(user)" location="top">
                    <template #activator="{ props }">
                      <span v-bind="props">
                        <VListItem
                          disabled
                        >
                          <VListItemTitle class="text-disabled">Delete</VListItemTitle>
                        </VListItem>
                      </span>
                    </template>
                    <span>Cannot delete. User has gRATs in database. Delete gRATs first.</span>
                  </VTooltip>
                  <VListItem
                    v-else
                    :disabled="isLastAdmin(user) || isOffline"
                    @click="deleteUser(user)"
                  >
                    <VListItemTitle class="text-error">Delete</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </td>
          </tr>
          <tr v-if="loaded && !pagedUsers.length">
            <td colspan="6" class="text-center py-4 text-medium-emphasis">
              No users found. Seeded accounts: admin@example.com / admin, demo1@example.com / demo, demo2@example.com / demo. Remove or change them before production.
            </td>
          </tr>
        </tbody>
      </VTable>

      <div class="d-flex justify-space-between align-center flex-wrap gap-3 px-4 pb-4">
        <div class="text-body-2 text-medium-emphasis">
          Rows {{ (currentPage - 1) * pageSize + 1 }} - {{ Math.min(currentPage * pageSize, filteredUsers.length) }} of {{ filteredUsers.length }}
        </div>
        <div class="d-flex align-center gap-2">
          <span class="text-caption text-medium-emphasis">Rows:</span>
          <VSelect
            v-model="pageSize"
            :items="[10, 20, 25, 30]"
            style="max-width: 120px;"
            hide-details
            density="comfortable"
            @update:model-value="currentPage = 1"
          />
          <VPagination
            v-model="currentPage"
            :length="totalPages"
            total-visible="7"
          />
        </div>
      </div>
    </VCard>

    <VDialog
      v-model="dialogOpen"
      max-width="600"
    >
      <VCard>
        <VCardTitle class="justify-space-between align-center">
          <span>{{ dialogMode === 'create' ? 'Register User' : 'Edit User' }}</span>
          <VBtn icon variant="text" @click="dialogOpen = false">
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-3">
          <VAlert
            v-if="dialogError"
            type="error"
            dense
            closable
            @click:close="dialogError = ''"
          >
            {{ dialogError }}
          </VAlert>

          <VTextField
            v-model="dialogForm.name"
            label="Name"
            hide-details="auto"
            density="comfortable"
          />
          <VTextField
            v-model="dialogForm.username"
            label="Username"
            hide-details="auto"
            density="comfortable"
            required
          />
          <VTextField
            v-model="dialogForm.email"
            label="Email"
            hide-details="auto"
            density="comfortable"
            required
          />
          <VTextField
            v-model="dialogForm.company"
            label="School/Company"
            hide-details="auto"
            density="comfortable"
          />
          <VSelect
            v-model="dialogForm.role"
            :items="dialogRoleOptions"
            item-title="label"
            item-value="value"
            label="Role"
            hide-details="auto"
            density="comfortable"
          />
          <VTextField
            v-model="dialogForm.password"
            type="password"
            label="Password"
            hide-details="auto"
            density="comfortable"
            :hint="dialogMode === 'edit' ? 'Leave blank to keep current password' : ''"
            persistent-hint
          />
        </VCardText>
        <VCardActions class="justify-end gap-2">
          <VBtn variant="text" @click="dialogOpen = false">
            Cancel
          </VBtn>
          <VBtn color="primary" @click="submitDialog">
            Save
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="changePasswordDialog"
      max-width="480"
    >
      <VCard>
        <VCardTitle class="justify-space-between align-center">
          <span>Change Password</span>
          <VBtn icon variant="text" @click="changePasswordDialog = false">
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        <VCardText>
          <VForm
            class="d-flex flex-column gap-3"
            @submit.prevent="submitChangePassword"
          >
            <VAlert
              v-if="changePasswordError"
              type="error"
              dense
              closable
              @click:close="changePasswordError = ''"
            >
              {{ changePasswordError }}
            </VAlert>
            <VAlert
              v-if="changePasswordSuccess"
              type="success"
              dense
              closable
              @click:close="() => { changePasswordSuccess = ''; changePasswordDialog = false }"
            >
              {{ changePasswordSuccess }}
            </VAlert>
            <div class="text-body-2 text-medium-emphasis">
              {{ changePasswordTarget?.username || 'Selected user' }}
            </div>
            <VTextField
              v-model="changePasswordForm.password"
              label="New password"
              type="password"
              hide-details="auto"
              density="comfortable"
              class="mb-2"
            />
            <VTextField
              v-model="changePasswordForm.confirm"
              label="Repeat new password"
              type="password"
              hide-details="auto"
              density="comfortable"
              class="mb-4"
            />
            <VCardActions class="justify-end gap-2 pa-0 pt-1">
              <VBtn variant="text" type="button" @click="changePasswordDialog = false">
                Cancel
              </VBtn>
              <VBtn color="primary" type="submit">
                Save
              </VBtn>
            </VCardActions>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.users-page {
  padding-top: 70px; /* clear space for menu */
}

.legacy-shell {
  border-radius: 10px;
}

.legacy-table thead th {
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.08em;
}

.legacy-table tbody tr {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.legacy-table tbody td {
  padding: 12px 16px;
}

.legacy-table tbody td:last-child {
  white-space: nowrap;
}

.sort-btn {
  background: transparent;
  border: none;
  color: inherit;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font: inherit;
  padding: 0;
  cursor: pointer;
}
.hero-card {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(16, 185, 129, 0.18));
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 14px;
  padding: 18px 20px;
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.minimal-hero {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding: 10px 14px;
  margin-bottom: 12px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 12px;
}

.filter-card {
  border-radius: 14px;
}

.glass-card {
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

/* Responsive collapse for users table */
.users-table {
  width: 100%;
}

@media (max-width: 1024px) {
  .users-table table {
    border: 0;
    width: 100%;
  }

  .users-table thead {
    border: none;
    clip: rect(0 0 0 0);
    height: 1px;
    margin: -1px;
    overflow: hidden;
    padding: 0;
    position: absolute;
    width: 1px;
  }

  .users-table tbody tr {
    border-bottom: 3px solid #e5e7eb;
    display: block;
    margin-bottom: 12px;
    padding: 6px 4px;
  }

  .users-table tbody td {
    display: block;
    text-align: right;
    border-bottom: 1px solid #e5e7eb;
    padding: 12px 10px;
    font-size: 0.94rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .users-table tbody td::before {
    content: attr(data-label);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: #64748b;
  }

  .users-table tbody td:last-child {
    border-bottom: 0;
  }

  .users-table .sort-btn {
    pointer-events: none;
  }
}

</style>
