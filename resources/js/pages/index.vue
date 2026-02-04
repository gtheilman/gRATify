<template>
  <VContainer class="home-wrapper py-10"
              fluid
  >
    <VRow justify="center">
      <VCol cols="12"
            md="9"
            lg="8"
            class="home-shell"
      >
        <VAlert
          v-if="showDemoWarning"
          type="info"
          variant="tonal"
          class="mb-4"
          closable
        >
          Demo data is seeded for first-time use (admin@example.com / admin and sample assessments). Change passwords, remove demo users/assessments, and configure mail before going live.
        </VAlert>
        <div class="home-title mb-6 d-flex align-center gap-4">
          <img
            :src="gratifyLogo"
            alt="gRATify"
            height="60"
          >
          <h1 class="text-h4 text-md-h3 font-weight-bold mb-2">
            Group Readiness Assurance Testing
          </h1>
        </div>

        <VCard class="home-card mb-6"
               elevation="2"
        >
          <VCardText class="home-body">
            <h5 class="home-subtitle text-h5 mb-3">
              This software manages a very small part of the Team-based Learning Process
            </h5>
            <div class="d-flex flex-column gap-2">
              <div class="d-flex align-center gap-2">
                <VIcon color="primary"
                       icon="tabler-check"
                       size="18"
                />
                <span>The Group Readiness Assurance Test</span>
              </div>
              <div class="d-flex align-center gap-2">
                <VIcon color="primary"
                       icon="tabler-check"
                       size="18"
                />
                <span>The Instructor Feedback</span>
              </div>
            </div>
          </VCardText>
        </VCard>

        <VCard class="home-card"
               elevation="2"
        >
          <VCardText class="text-center">
            <img
              :src="tblProcess"
              alt="TBL Process"
              class="mx-auto tbl-image"
              loading="eager"
              fetchpriority="high"
              decoding="async"
              width="800"
              height="253"
            >
          </VCardText>
        </VCard>

        <VCard v-if="isAdmin"
               class="home-card mt-6"
               elevation="2"
        >
          <VCardTitle class="text-h6">
            Operational Signals (Last {{ signalWindowMinutes }} min)
          </VCardTitle>
          <VCardText>
            <div v-if="signalsLoading"
                 class="text-medium-emphasis"
            >
              Loading signal report…
            </div>
            <div v-else-if="signalsError"
                 class="text-error"
            >
              {{ signalsError }}
            </div>
            <div v-else>
              <div class="d-flex flex-wrap gap-3">
                <VChip color="warning"
                       variant="tonal"
                >
                  401 /api/auth/me: {{ signalTotals.auth_me_401 || 0 }}
                </VChip>
                <VChip color="error"
                       variant="tonal"
                >
                  429 /api/attempts*: {{ signalTotals.attempts_429 || 0 }}
                </VChip>
              </div>
              <div class="signal-footnote text-medium-emphasis mt-3">
                Generated {{ signalGeneratedAt }}
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
// Home page: fetches demo warning lazily to avoid delaying initial paint.
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { resolveDemoWarningState, readDemoWarningCache, writeDemoWarningCache, applyDemoWarningFallback } from '@/utils/demoWarning'
import { useAuthStore } from '@/stores/auth'
import tblProcess from '../../assets/images/TBL_Process.webp'
import gratifyLogo from '../../assets/images/gratify-logo-300x90.webp'

// Start hidden to avoid flash; visibility is decided after we fetch the flag.
const showDemoWarning = ref(false)
const signalTotals = ref({ auth_me_401: 0, attempts_429: 0 })
const signalWindowMinutes = ref(15)
const signalGeneratedAt = ref('just now')
const signalsLoading = ref(false)
const signalsError = ref('')
const authStore = useAuthStore()
const isAdmin = computed(() => {
  const role = authStore.user?.role
  const normalized = role === 'poobah' ? 'admin' : role

  return normalized === 'admin'
})

const fetchDemoWarning = async () => {
  try {
    const { data } = await axios.get('/api/demo-warning')
    const shouldShow = resolveDemoWarningState(data)

    showDemoWarning.value = shouldShow
    writeDemoWarningCache(shouldShow)
  } catch (e) {
    // Default to showing the warning if the status is unknown.
    showDemoWarning.value = applyDemoWarningFallback()
  }
}

const fetchOperationalSignals = async () => {
  if (!isAdmin.value) {
    return
  }
  signalsLoading.value = true
  signalsError.value = ''
  try {
    const { data } = await axios.get('/api/admin/operational-signals')

    signalTotals.value = data?.totals || { auth_me_401: 0, attempts_429: 0 }
    signalWindowMinutes.value = data?.window_minutes || 15
    const generated = data?.generated_at ? new Date(data.generated_at) : null

    signalGeneratedAt.value = generated && !Number.isNaN(generated.getTime())
      ? generated.toLocaleTimeString()
      : 'just now'
  } catch (error) {
    signalsError.value = 'Unable to load operational signal report.'
  } finally {
    signalsLoading.value = false
  }
}

onMounted(() => {
  const cached = readDemoWarningCache()
  if (cached !== null) {
    showDemoWarning.value = cached
    
    return
  }
  // Defer the network call until idle to avoid blocking first contentful paint.
  const schedule = window.requestIdleCallback || function (cb) { return setTimeout(cb, 150) }

  schedule(() => {
    fetchDemoWarning()
    fetchOperationalSignals()
  })
})
</script>

<style scoped>
.home-shell {
  max-width: 1040px;
  margin-top: 16px;
  padding: clamp(12px, 4vw, 32px);
}

.home-wrapper {
  margin-top: clamp(24px, 6vw, 64px);
}

.home-card {
  border-radius: 10px;
  box-shadow: 0px 4px 25px rgba(0, 0, 0, 0.1);
}

.home-body {
  font-size: clamp(15px, 2vw, 17px);
  line-height: 1.6;
}

.home-subtitle {
  font-size: 150%;
}

.announcement {
  color: #e53935;
}

.spacer-line {
  margin: 6px 0;
}

.home-title {
  text-align: left;
}

.tbl-image {
  width: 100%;
  max-width: 100%;
  max-height: clamp(220px, 50vw, 420px);
  object-fit: contain;
}

.signal-footnote {
  font-size: 0.85rem;
}

@media (max-width: 960px) {
  .home-card {
    margin-left: auto;
    margin-right: auto;
  }
}
</style>
