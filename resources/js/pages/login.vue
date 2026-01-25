<script setup>
// Pulls demo warning/mail configuration flags on load for login banner messaging.
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import { getErrorMessage } from '@/utils/apiError'
import { formatLoginError } from '@/utils/loginError'
import { ensureCsrfCookie, fetchJson } from '@/utils/http'
import { validateResetEmail } from '@/utils/loginReset'
import loginIllustration from '../../assets/images/pages/login.webp'

const form = ref({
  email: '',
  password: '',
  remember: false,
})

const api = useApi
const authStore = useAuthStore()
const router = useRouter()
const submitting = ref(false)
const errorMessage = ref('')
const resetSubmitting = ref(false)
const resetMessage = ref('')
const resetError = ref('')
const mailConfigured = ref(true)
const mailEnabled = ref(true)
const mailCheckLoaded = ref(false)
const migrationWarning = ref(false)
const migrationCheckLoaded = ref(false)
const documentationLink = 'https://github.com/gtheilman/gratify'

const handleLogin = async () => {
  submitting.value = true
  errorMessage.value = ''
  try {
    await authStore.login({
      email: form.value.email,
      password: form.value.password,
      remember: form.value.remember,
    })
    await router.push({ name: 'root' })
  }
  catch (err) {
    errorMessage.value = formatLoginError(err)
  }
  finally {
    submitting.value = false
  }
}

const handleReset = async () => {
  resetMessage.value = ''
  resetError.value = ''

  const validationMessage = validateResetEmail(form.value.email)
  if (validationMessage) {
    resetError.value = validationMessage
    return
  }

  resetSubmitting.value = true
  try {
    await ensureCsrfCookie()
    const { data, error } = await api('/auth/password/email', {
      method: 'POST',
      body: { email: form.value.email },
    })
    if (error.value)
      throw error.value

    resetMessage.value = data.value?.status || 'If that address exists, a reset link has been sent.'
  }
  catch (err) {
    // API returns 200 even for unknown emails; surface message if provided.
    resetError.value = getErrorMessage(err, 'Unable to send reset link right now.')
  }
  finally {
    resetSubmitting.value = false
  }
}

onMounted(async () => {
  try {
    const { data, response } = await fetchJson('/api/demo-warning')
    if (response.ok) {
      if (typeof data?.mailConfigured === 'boolean') {
        mailConfigured.value = data.mailConfigured
      }
      if (typeof data?.mailEnabled === 'boolean') {
        mailEnabled.value = data.mailEnabled
        if (!data.mailEnabled)
          mailConfigured.value = false
      }
    }
  } catch {
    // Keep default (configured) to avoid false negatives on errors.
  } finally {
    mailCheckLoaded.value = true
  }

  try {
    const { data, response } = await fetchJson('/api/migration-status')
    if (response.ok && data?.ok === false && Array.isArray(data?.missing) && data.missing.length > 0) {
      migrationWarning.value = true
    }
  } catch {
    // Avoid blocking login if status check fails.
  } finally {
    migrationCheckLoaded.value = true
  }
})
</script>

<template>
  <div class="login-wrapper">
    <VRow
      no-gutters
      class="min-h-screen align-start justify-center bg-surface auth-layout"
    >
      <VCol
        cols="12"
        md="8"
        lg="7"
      >
        <VCard>
          <VRow no-gutters>
            <VCol
              cols="12"
              lg="6"
              class="d-none d-lg-flex align-center justify-center pa-6 illustration-shell"
            >
              <VImg
                :src="loginIllustration"
                alt="login"
                max-width="420"
                class="mx-auto illustration"
                contain
              />
            </VCol>
            <VCol
              cols="12"
              lg="6"
              class="pa-8 form-shell"
            >
              <div class="mb-6">
                <h4 class="text-h5 text-md-h4 font-weight-bold mb-1">
                  Login
                </h4>
                <div class="text-body-2 text-medium-emphasis">
                  Sign in to manage assessments and review results.
                </div>
              </div>

              <VForm @submit.prevent="handleLogin">
                <VAlert
                  v-if="migrationWarning && migrationCheckLoaded"
                  type="warning"
                  density="comfortable"
                  class="mb-4"
                >
                  Database migrations are required. Run <strong>php artisan migrate</strong>.
                  <span v-if="documentationLink">
                    See the README on
                    <a :href="documentationLink" target="_blank" rel="noopener">GitHub</a>.
                  </span>
                </VAlert>
                <VAlert
                  v-if="errorMessage"
                  type="error"
                  density="comfortable"
                  class="mb-4"
                  closable
                  @click:close="errorMessage = ''"
                >
                  {{ errorMessage }}
                </VAlert>

                <VTextField
                  v-model="form.email"
                  label="Email"
                  type="email"
                  placeholder=""
                  class="mb-4"
                  required
                />

                <VTextField
                  v-model="form.password"
                  label="Password"
                  type="password"
                  placeholder="············"
                  class="mb-2"
                  autocomplete="current-password"
                  required
                />
                <VCheckbox
                  v-model="form.remember"
                  label="Remember me"
                  density="comfortable"
                  class="mb-2"
                />

                <VAlert
                  v-if="resetMessage"
                  type="success"
                  variant="tonal"
                  density="comfortable"
                  class="mb-3"
                  closable
                  @click:close="resetMessage = ''"
                >
                  {{ resetMessage }}
                </VAlert>

                <VAlert
                  v-if="resetError"
                  type="error"
                  variant="tonal"
                  density="comfortable"
                  class="mb-3"
                  closable
                  @click:close="resetError = ''"
                >
                  {{ resetError }}
                </VAlert>

                <div class="mb-4">
                  <VBtn
                    variant="text"
                    color="primary"
                    class="px-0"
                    :loading="resetSubmitting"
                    :disabled="submitting || resetSubmitting || (mailCheckLoaded && !mailEnabled)"
                    @click="handleReset"
                  >
                    Forgot Password?
                  </VBtn>
                  <div v-if="mailCheckLoaded && !mailEnabled" class="text-caption text-medium-emphasis mt-2">
                    Password reset requires a configured mail server.
                  </div>
                  <div v-else-if="mailCheckLoaded && !mailConfigured" class="text-caption text-medium-emphasis mt-2">
                    Password reset requires a configured mail server.
                  </div>
                </div>

                <VBtn
                  block
                  color="primary"
                  type="submit"
                  :loading="submitting"
                  :disabled="submitting"
                >
                  Login
                </VBtn>
                <div class="text-caption text-medium-emphasis mt-3">
                  <a href="/privacy" target="_blank" rel="noopener">Privacy</a>
                  <span class="mx-2">·</span>
                  <a href="/terms" target="_blank" rel="noopener">Terms</a>
                </div>
              </VForm>
            </VCol>
          </VRow>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";

.auth-layout {
  padding: clamp(16px, 4vw, 48px) clamp(12px, 4vw, 32px);
  padding-top: clamp(24px, 30vh, 280px);
}

.illustration-shell {
  min-height: 100%;
}

.illustration {
  width: 100%;
  max-width: 420px;
  max-height: clamp(220px, 40vw, 360px);
  object-fit: contain;
}

.form-shell {
  padding: clamp(20px, 4vw, 40px) !important;
}

@media (max-width: 959px) {
  .auth-layout {
    padding: clamp(16px, 6vw, 32px);
  }
}
</style>
