<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ErrorNotice from '@/components/ErrorNotice.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const error = ref('')
const assessment = ref(null)
const pollingId = ref(null)
const staleNotice = ref('')
const needsRefresh = computed(() => String(error.value || '').includes('Session expired'))

const progressCacheKey = () => `progress-cache-${route.params.id}`

const loadProgressCache = () => {
  try {
    const raw = localStorage.getItem(progressCacheKey())
    if (!raw)
      return null
    return JSON.parse(raw)
  }
  catch {
    return null
  }
}

const storeProgressCache = data => {
  try {
    localStorage.setItem(progressCacheKey(), JSON.stringify({
      data,
      cachedAt: Date.now(),
    }))
  }
  catch {
    // Ignore cache write failures.
  }
}

const readErrorDetail = async response => {
  const contentType = response.headers.get('content-type') || ''
  try {
    if (contentType.includes('json')) {
      const json = await response.json()
      return json?.message || json?.status || JSON.stringify(json)
    }
    return (await response.text())?.trim()
  }
  catch {
    return ''
  }
}

const formatProgressError = async response => {
  const detail = await readErrorDetail(response)
  if (response.status === 401)
    return detail ? `Unauthorized: ${detail}` : 'Unauthorized: please sign in again.'
  if (response.status === 403)
    return detail ? `Forbidden: ${detail}` : 'Forbidden: you do not have access to this progress view.'
  if (response.status === 404)
    return detail ? `Not found: ${detail}` : 'Not found: assessment does not exist.'
  if (response.status >= 500)
    return detail ? `Server error: ${detail}` : 'Server error: unable to load progress right now.'
  return detail || 'Unable to load progress'
}

const fetchProgress = async (silent = false) => {
  if (!silent)
    loading.value = true
  error.value = ''
  if (!silent)
    staleNotice.value = ''
  try {
    const response = await fetch(`/api/assessment/attempts/${route.params.id}`, {
      credentials: 'same-origin',
    })
    if (!response.ok) {
      const message = await formatProgressError(response)
      throw new Error(message)
    }
    const data = await response.json()
    assessment.value = data
    storeProgressCache(data)
  }
  catch (err) {
    error.value = err?.message || 'Unable to load progress'
    const cached = loadProgressCache()
    if (cached?.data) {
      assessment.value = cached.data
      staleNotice.value = `Showing cached data from ${new Date(cached.cachedAt).toLocaleString()}`
    }
  }
  finally {
    if (!silent)
      loading.value = false
  }
}

onMounted(async () => {
  await fetchProgress()
  pollingId.value = window.setInterval(() => fetchProgress(true), 5000)
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  if (pollingId.value)
    window.clearInterval(pollingId.value)
  window.removeEventListener('keydown', handleKeydown)
})

const correctAnswerIds = computed(() => {
  const ids = []
  assessment.value?.questions?.forEach(question => {
    question.answers?.forEach(ans => {
      if (Number(ans.correct))
        ids.push(ans.id)
    })
  })
  return ids
})

const barColor = pct => {
  if (pct < 20) return 'error'
  if (pct < 40) return 'warning'
  if (pct < 60) return 'secondary'
  if (pct < 80) return 'primary'
  return 'success'
}

const enterFullscreen = () => {
  const el = document.documentElement
  if (el.requestFullscreen) el.requestFullscreen()
  else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen()
  else if (el.msRequestFullscreen) el.msRequestFullscreen()
}

const handleKeydown = e => {
  if (e.key && e.key.toLowerCase() === 'f')
    enterFullscreen()
}

const rows = computed(() => {
  if (!assessment.value?.presentations)
    return []

  const correctSet = new Set(correctAnswerIds.value)
  const totalCorrect = correctSet.size || 1

  return assessment.value.presentations.map(pres => {
    let correctCount = 0
    pres.attempts?.forEach(att => {
      if (correctSet.has(att.answer_id))
        correctCount += 1
    })

    const percent = Math.round((correctCount / totalCorrect) * 100)
    const rawGroup = pres.group_label ?? pres.group_id ?? pres.user_id ?? pres.id
    const groupLabel = rawGroup ?? pres.id
    return {
      group: groupLabel,
      percent,
    }
  })
})

const maxGroupLength = computed(() => {
  return rows.value.reduce((max, row) => Math.max(max, String(row.group || '').length), 0)
})

const groupLabelWidth = computed(() => `${Math.max(maxGroupLength.value + 2, 8)}ch`)
const percentWidth = '6ch'

const sortState = ref({
  key: 'group',
  direction: 'asc',
})

const sortedRows = computed(() => {
  const dir = sortState.value.direction === 'asc' ? 1 : -1
  if (sortState.value.key === 'percent') {
    return [...rows.value].sort((a, b) => (a.percent - b.percent) * dir)
  }
  return [...rows.value].sort((a, b) => String(a.group).localeCompare(String(b.group)) * dir)
})

const toggleSort = key => {
  if (sortState.value.key === key) {
    sortState.value.direction = sortState.value.direction === 'asc' ? 'desc' : 'asc'
  } else {
    sortState.value.key = key
    sortState.value.direction = 'asc'
  }
}

const goToFeedback = () => {
  router.push({ name: 'assessment-feedback', params: { id: route.params.id } })
}
</script>

<template>
  <VContainer fluid class="py-6 progress-page">
    <VRow>
      <VCol cols="12">
        <VCard class="w-100">
          <VCardTitle class="justify-space-between align-center">
            <div>
              <div class="overline text-secondary">
                Progress
              </div>
              <div class="text-h6 mt-2" style="padding-top: 10px;">
                Completion by group
              </div>
            </div>
          </VCardTitle>
          <VCardText>
            <ErrorNotice
              v-if="error"
              :message="error"
              :show-refresh="needsRefresh"
              @close="error = ''"
              @retry="fetchProgress"
              @refresh="() => window.location.reload()"
            />
            <VAlert
              v-if="staleNotice"
              type="info"
              variant="tonal"
              class="mb-4"
              closable
              @click:close="staleNotice = ''"
            >
              <div class="d-flex align-center gap-2">
                <VChip size="x-small" color="warning" variant="tonal">Stale</VChip>
                <span>{{ staleNotice }}</span>
              </div>
            </VAlert>

            <div v-if="loading" class="text-center py-6">
              <VProgressCircular indeterminate color="primary" />
            </div>

            <div v-else>
              <VTable class="progress-table">
                <thead>
                  <tr>
                    <th style="width: 260px;">
                      <div class="group-cell header-group">
                        <button
                          class="sort-btn group-label"
                          :style="{ minWidth: groupLabelWidth }"
                          @click="toggleSort('group')"
                        >
                          Group
                          <VIcon
                            v-if="sortState.key === 'group'"
                            :icon="sortState.direction === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                            size="16"
                            class="ms-1"
                          />
                        </button>
                        <div class="percent-header" :style="{ width: percentWidth }">
                          <button
                            class="sort-btn group-percent"
                            @click="toggleSort('percent')"
                          >
                            Percent
                            <VIcon
                              v-if="sortState.key === 'percent'"
                              :icon="sortState.direction === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                              size="16"
                              class="ms-1"
                            />
                          </button>
                        </div>
                      </div>
                    </th>
                    <th>
                      Progress
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in sortedRows"
                    :key="row.group"
                  >
                    <td>
                      <div class="group-cell">
                        <div class="group-label" :style="{ minWidth: groupLabelWidth }">{{ row.group }}</div>
                        <div class="group-percent" :style="{ width: percentWidth }">{{ row.percent }}%</div>
                      </div>
                    </td>
                    <td>
                      <VProgressLinear
                        :model-value="row.percent"
                        :color="barColor(row.percent)"
                        height="18"
                        rounded
                      />
                    </td>
                  </tr>
                  <tr v-if="!rows.length">
                    <td colspan="2" class="text-center py-4 text-medium-emphasis">
                      No progress data available.
                    </td>
                  </tr>
                </tbody>
              </VTable>
              <div class="d-flex justify-end mt-4">
                <VBtn
                  color="primary"
                  variant="tonal"
                  prepend-icon="tabler-refresh"
                  :loading="loading"
                  @click="fetchProgress"
                >
                  Refresh
                </VBtn>
                <VBtn
                  color="secondary"
                  variant="text"
                  prepend-icon="tabler-arrows-maximize"
                  class="ms-2"
                  @click="enterFullscreen"
                >
                  Press F for Fullscreen
                </VBtn>
              </div>
              <div class="feedback-link-wrap">
                <VBtn
                  variant="text"
                  color="secondary"
                  prepend-icon="tabler-messages"
                  @click="goToFeedback"
                >
                  View feedback
                </VBtn>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<style scoped>
.progress-page {
  padding-top: 140px; /* keep content below the top nav */
}

.progress-table tbody tr {
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.05);
  border-radius: 10px;
  overflow: hidden;
}

.progress-table tbody td {
  background: #fff;
  padding: 14px 16px;
}

.progress-table tbody td:first-child {
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
}

.progress-table tbody td:last-child {
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
}

.group-cell {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12px;
}

.header-group {
  align-items: flex-end;
}

.group-label {
  font-weight: 700;
  font-size: 1.05rem;
}

.group-percent {
  font-weight: 700;
  font-size: 1rem;
  min-width: 64px;
  text-align: left;
}

.percent-header {
  display: flex;
  justify-content: flex-start;
}

.progress-table thead th {
  font-size: 12px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(0, 0, 0, 0.54);
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

.sort-btn:focus-visible {
  outline: 2px solid rgba(0, 0, 0, 0.2);
  border-radius: 4px;
}

.divider-dot {
  color: rgba(0, 0, 0, 0.45);
  font-size: 12px;
}

.feedback-link-wrap {
  display: flex;
  justify-content: flex-start;
  margin-top: 12px;
}
</style>
