<template>
  <VContainer class="home-wrapper py-10" fluid>
    <VRow justify="center">
      <VCol cols="12" md="9" lg="8" class="home-shell">
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
            src="/gratify-logo-300x90.png"
            alt="gRATify"
            height="60"
          />
          <h1 class="text-h4 text-md-h3 font-weight-bold mb-2">
            Group Readiness Assurance Testing
          </h1>
        </div>

        <VCard class="home-card mb-6" elevation="2">
          <VCardText class="home-body">
            <h5 class="home-subtitle text-h5 mb-3">
              This software manages a very small part of the Team-based Learning Process
            </h5>
            <div class="d-flex flex-column gap-2">
              <div class="d-flex align-center gap-2">
                <VIcon color="primary" icon="tabler-check" size="18" />
                <span>The Group Readiness Assurance Test</span>
              </div>
              <div class="d-flex align-center gap-2">
                <VIcon color="primary" icon="tabler-check" size="18" />
                <span>The Instructor Feedback</span>
              </div>
            </div>
          </VCardText>
        </VCard>

        <VCard class="home-card" elevation="2">
          <VCardText class="text-center">
            <VImg
              :src="tblProcess"
              alt="TBL Process"
              class="mx-auto tbl-image"
              contain
              max-height="360"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'
import tblProcess from '../../assets/images/TBL_Process.png'

// Start hidden to avoid flash; visibility is decided after we fetch the flag.
const showDemoWarning = ref(false)
const demoWarningCacheKey = 'demo-warning-state'

const fetchDemoWarning = async () => {
  try {
    const { data } = await axios.get('/api/demo-warning')
    const shouldShow = !!data?.showWarning || !!data?.showDemoUsers
    showDemoWarning.value = shouldShow
    sessionStorage.setItem(demoWarningCacheKey, shouldShow ? '1' : '0')
  } catch (e) {
    showDemoWarning.value = true
  }
}

onMounted(() => {
  const cached = sessionStorage.getItem(demoWarningCacheKey)
  if (cached !== null) {
    showDemoWarning.value = cached === '1'
    return
  }
  const schedule = window.requestIdleCallback || function (cb) { return setTimeout(cb, 150) }
  schedule(() => { fetchDemoWarning() })
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

@media (max-width: 960px) {
  .home-card {
    margin-left: auto;
    margin-right: auto;
  }
}
</style>
