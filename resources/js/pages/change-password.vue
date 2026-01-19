<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useApi } from '@/composables/useApi'

const authStore = useAuthStore()
const router = useRouter()
const api = useApi

const form = ref({
  old_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const submitting = ref(false)
const message = ref('')
const errorMessage = ref('')
const handleSuccessClose = () => {
  message.value = ''
  router.push({ name: 'assessments' })
}

const handleChange = async () => {
  if (!authStore.user) {
    errorMessage.value = 'You must be logged in.'
    return
  }
  if (!form.value.old_password || !form.value.new_password || !form.value.new_password_confirmation) {
    errorMessage.value = 'Please fill in all fields.'
    return
  }
  if (form.value.new_password !== form.value.new_password_confirmation) {
    errorMessage.value = 'New passwords do not match.'
    return
  }
  submitting.value = true
  message.value = ''
  errorMessage.value = ''
  try {
    const { data, error } = await api('/change-password/', {
      method: 'POST',
      body: {
        user_id: authStore.user.id,
        old_password: form.value.old_password,
        new_password: form.value.new_password,
        new_password_confirmation: form.value.new_password_confirmation,
      },
    })
    // Gather any status clues from success or error payloads
    let statusFlag = data.value?.status || error.value?.data?.status || error.value?.status

    // If still unknown and we have a response body, try to parse it
    if (!statusFlag && error.value?.response?.clone) {
      try {
        const json = await error.value.response.clone().json()
        statusFlag = json?.status
      }
      catch (parseErr) {
        // ignore parse errors
      }
    }

    if (statusFlag === 'invalid_old_password') {
      errorMessage.value = 'Current Password is Not Correct.'
      return
    }

    if (error.value)
      throw error.value

    message.value = data.value?.status === 'ok' ? 'Password updated.' : 'Password updated.'
    form.value.old_password = ''
    form.value.new_password = ''
    form.value.new_password_confirmation = ''
    authStore.forcePasswordReset = false
    localStorage.removeItem('forcePasswordReset')
    await authStore.fetchUser()
    router.push({ name: 'assessments' })
  }
  catch (err) {
    // Attempt to parse invalid_old_password from error in catch as a final fallback
    let statusFlag = err?.data?.status || err?.status
    if (!statusFlag && err?.response?.clone) {
      try {
        const json = await err.response.clone().json()
        statusFlag = json?.status || json?.message
      }
      catch (parseErr) {
        // ignore parse errors
      }
    }
    if (statusFlag === 'invalid_old_password') {
      errorMessage.value = 'Current Password is Not Correct.'
      return
    }
    try {
      const json = await err?.response?.clone()?.json()
      if (json?.status === 'invalid_old_password') {
        errorMessage.value = 'Current Password is Not Correct.'
        return
      }
    }
    catch (e) {
      // ignore parse errors
    }
    errorMessage.value = err?.message || 'Unable to change password'
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <VContainer class="py-8">
    <VRow justify="center">
      <VCol cols="12" md="6">
        <VCard class="change-password-card">
          <VCardTitle>Change Password</VCardTitle>
          <VCardText>
            <VAlert
              v-if="message"
              type="success"
              density="comfortable"
              closable
              class="mb-4"
              @click:close="handleSuccessClose"
            >
              {{ message }}
            </VAlert>
            <VAlert
              v-if="errorMessage"
              type="error"
              density="comfortable"
              closable
              class="mb-4"
              @click:close="errorMessage = ''"
            >
              {{ errorMessage }}
            </VAlert>

            <VForm @submit.prevent="handleChange">
              <VTextField
                v-model="form.old_password"
                label="Current password"
                type="password"
                class="mb-4"
                autocomplete="current-password"
                required
              />
              <VTextField
                v-model="form.new_password"
                label="New password"
                type="password"
                class="mb-4"
                autocomplete="new-password"
                required
              />
              <VTextField
                v-model="form.new_password_confirmation"
                label="Repeat new password"
                type="password"
                class="mb-6"
                autocomplete="new-password"
                required
              />
              <VBtn
                color="primary"
                type="submit"
                :loading="submitting"
                :disabled="submitting"
              >
                Update Password
              </VBtn>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<style scoped>
.change-password-card {
  margin-top: 100px;
}
</style>
