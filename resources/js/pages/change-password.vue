<script setup>
// Handles self-service password updates with extra parsing for legacy error payloads.
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useApi } from '@/composables/useApi'
import { getErrorMessage } from '@/utils/apiError'
import { extractPasswordStatus } from '@/utils/changePassword'
import { validateChangePasswordForm } from '@/utils/changePasswordValidation'

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
  const validationMessage = validateChangePasswordForm(form.value, !!authStore.user)
  if (validationMessage) {
    errorMessage.value = validationMessage
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
    const statusFlag = await extractPasswordStatus(data.value, error.value)

    if (statusFlag === 'invalid_old_password') {
      errorMessage.value = 'Current Password is Not Correct.'
      return
    }

    if (error.value)
      throw error.value

    // Treat any success response as a completed update.
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
    const statusFlag = await extractPasswordStatus(null, err)
    if (statusFlag === 'invalid_old_password') {
      errorMessage.value = 'Current Password is Not Correct.'
      return
    }
    errorMessage.value = getErrorMessage(err, 'Unable to change password')
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
