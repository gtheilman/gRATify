<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useOffline } from '@/composables/useOffline'
import ErrorNotice from '@/components/ErrorNotice.vue'

const route = useRoute()
const api = useApi

const loading = ref(false)
const error = ref('')
const presentations = ref([])
const showCollapsible = ref(true)
const sortKey = ref('user') // user | score_desc | score_asc
const assessmentActive = ref(null)
const assessmentTitle = ref('')
const activeToggleBusy = ref(false)
const staleNotice = ref('')
const needsRefresh = computed(() => String(error.value || '').includes('Session expired'))
const csvFallbackOpen = ref(false)
const csvFallbackText = ref('')
const copyCsvStatus = ref('')
const { isOffline } = useOffline()

const scoringScheme = ref('geometric-decay')

const scoresCacheKey = () => `scores-cache-${route.params.id}-${scoringScheme.value}`

const loadScoresCache = () => {
  try {
    const raw = localStorage.getItem(scoresCacheKey())
    if (!raw)
      return null
    return JSON.parse(raw)
  }
  catch {
    return null
  }
}

const storeScoresCache = data => {
  try {
    localStorage.setItem(scoresCacheKey(), JSON.stringify({
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

const formatScoresError = async response => {
  const detail = await readErrorDetail(response)
  if (response.status === 401)
    return detail ? `Unauthorized: ${detail}` : 'Unauthorized: please sign in again.'
  if (response.status === 403)
    return detail ? `Forbidden: ${detail}` : 'Forbidden: you do not have access to these scores.'
  if (response.status === 404)
    return detail ? `Not found: ${detail}` : 'Not found: assessment does not exist.'
  if (response.status >= 500)
    return detail ? `Server error: ${detail}` : 'Server error: unable to load scores right now.'
  return detail || 'Unable to load scores'
}

const fetchScores = async () => {
  loading.value = true
  error.value = ''
  staleNotice.value = ''
  try {
    if (isOffline.value)
      throw new Error('You are offline. Connect to the internet and try again.')
    const response = await fetch(`/api/presentations/score-by-assessment-id/${route.params.id}?scheme=${encodeURIComponent(scoringScheme.value)}`, {
      credentials: 'same-origin',
    })
    if (!response.ok) {
      const message = await formatScoresError(response)
      throw new Error(message)
    }
    const data = await response.json()
    presentations.value = data
    storeScoresCache(data)
    const assessment = data?.[0]?.assessment
    if (assessment && typeof assessment.active !== 'undefined')
      assessmentActive.value = !!Number(assessment.active)
    if (assessment?.title)
      assessmentTitle.value = assessment.title
  }
  catch (err) {
    error.value = err?.message || 'Unable to load scores'
    const cached = loadScoresCache()
    if (cached?.data) {
      presentations.value = cached.data
      staleNotice.value = `Showing cached data from ${formatTimestamp(new Date(cached.cachedAt).toISOString())}`
    }
  }
  finally {
    loading.value = false
  }
}

onMounted(fetchScores)

const loadAssessmentActive = async () => {
  if (assessmentActive.value !== null)
    return
  const { data, error: apiError } = await api(`/assessments/${route.params.id}/edit`, { method: 'GET' })
  if (apiError.value)
    throw apiError.value
  assessmentActive.value = !!Number(data.value?.active)
  if (data.value?.title)
    assessmentTitle.value = data.value.title
}

const sortedPresentations = computed(() => {
  const list = [...presentations.value]
  if (sortKey.value === 'score_desc')
    return list.sort((a, b) => (Number(b.score) || 0) - (Number(a.score) || 0))
  if (sortKey.value === 'score_asc')
    return list.sort((a, b) => (Number(a.score) || 0) - (Number(b.score) || 0))
  const normalizeId = id => {
    const num = Number(id)
    return Number.isFinite(num) ? num : id
  }
  return list.sort((a, b) => {
    const aId = normalizeId(a.user_id)
    const bId = normalizeId(b.user_id)
    if (typeof aId === 'number' && typeof bId === 'number')
      return aId - bId
    return String(aId || '').localeCompare(String(bId || ''))
  })
})

const scoresOnly = computed(() => sortedPresentations.value.map(p => Number(p.score) || 0).filter(Number.isFinite))
const averageScore = computed(() => {
  const arr = scoresOnly.value
  if (!arr.length) return 0
  return Math.round(arr.reduce((a, b) => a + b, 0) / arr.length)
})
const maxScore = computed(() => scoresOnly.value.reduce((m, s) => Math.max(m, s), 0))
const minScore = computed(() => scoresOnly.value.reduce((m, s) => Math.min(m, s), scoresOnly.value[0] ?? 0))
const medianScore = computed(() => {
  const arr = [...scoresOnly.value].sort((a, b) => a - b)
  if (!arr.length) return 0
  const mid = Math.floor(arr.length / 2)
  return arr.length % 2 ? arr[mid] : Math.round((arr[mid - 1] + arr[mid]) / 2)
})

const formatTimestamp = value => {
  if (!value)
    return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime()))
    return value
  const pad = n => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

const truncateText = text => {
  if (!text)
    return ''
  return text.length > 50 ? `${text.slice(0, 50)}...` : text
}

const displayStem = question => question?.stem || ''

const formatScore = value => {
  const num = Number(value)
  if (!Number.isFinite(num))
    return ''
  return Number.isInteger(num) ? String(num) : num.toFixed(1)
}

const downloadCsv = () => {
  const lines = ['UserID,Score']
  sortedPresentations.value.forEach(p => {
    const user = String(p.user_id || '').replace(/,/g, '')
    lines.push(`${user},${formatScore(p.score)}`)
  })

  const payload = lines.join('\r\n')
  try {
    const blob = new Blob([payload], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'scores.csv'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  }
  catch (err) {
    csvFallbackText.value = payload
    csvFallbackOpen.value = true
  }
}

const copyCsvFallback = async () => {
  if (!csvFallbackText.value)
    return
  try {
    await navigator.clipboard.writeText(csvFallbackText.value)
    copyCsvStatus.value = 'Copied'
  }
  catch {
    copyCsvStatus.value = 'Copy failed'
  }
  setTimeout(() => {
    copyCsvStatus.value = ''
  }, 1500)
}

const escapeHtml = value => {
  if (value === null || value === undefined)
    return ''
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

const buildTimelineHtml = () => {
  const assessmentTitle = sortedPresentations.value[0]?.assessment?.title || 'gRAT'
  const schemeLabel = scoringScheme.value === 'linear-decay' ? 'Linear decay' : 'Geometric decay'
  const printedAt = formatTimestamp(new Date().toISOString())
  const rows = sortedPresentations.value.map(presentation => {
    const questions = (presentation.assessment?.questions || []).map(question => {
      const attempts = (question.attempts || []).map(attempt => {
        const timestamp = escapeHtml(formatTimestamp(attempt.created_at))
        const answer = escapeHtml(truncateText(attempt.answer?.answer_text))
        return `<div class="attempt-row"><span class="attempt-time">${timestamp}</span><span>${answer}</span></div>`
      }).join('')

      return `
        <div class="question-block">
          <div class="question-stem">${escapeHtml(question.stem)}</div>
          <div class="question-score">Score ${escapeHtml(question.score)}%</div>
          <div class="attempts">${attempts || '<div class="attempt-row empty">No attempts</div>'}</div>
        </div>
      `
    }).join('')

    return `
      <div class="presentation-block">
        <div class="presentation-header">
          <span>User ID: ${escapeHtml(presentation.user_id)}</span>
          <span class="presentation-score">${escapeHtml(presentation.score)}%</span>
        </div>
        ${questions || '<div class="question-block empty">No questions</div>'}
      </div>
    `
  }).join('')

  return `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>${escapeHtml(assessmentTitle)}</title>
        <style>
          body { font-family: "Segoe UI", Arial, sans-serif; margin: 0.5in; color: #1f2933; }
          .print-header { margin-bottom: 5px; }
          .print-title { font-size: 20px; font-weight: 700; margin: 0 0 2px; }
          .print-meta { font-size: 12px; color: #607380; }
          .presentation-block { border: 1px solid #e0e6ed; border-radius: 10px; padding: 5px 7px; margin-bottom: 6px; }
          .presentation-header { display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 4px; }
          .presentation-score { color: #0f766e; }
          .question-block { padding: 4px 0; border-top: 1px solid #eef2f6; }
          .question-block:first-of-type { border-top: none; }
          .question-stem { font-weight: 600; font-size: 13px; margin-bottom: 2px; }
          .question-score { font-size: 13px; color: #607380; margin-bottom: 2px; }
          .attempt-row { display: flex; gap: 12px; font-size: 13px; margin-bottom: 2px; }
          .attempt-time { color: #6b7280; min-width: 170px; }
          .empty { color: #98a2b3; font-style: italic; }
        </style>
      </head>
      <body>
        <div class="print-header">
          <div class="print-title">${escapeHtml(assessmentTitle)}</div>
          <div class="print-meta">${escapeHtml(printedAt)} · ${escapeHtml(schemeLabel)}</div>
        </div>
        ${rows || '<div class="empty">No scores available.</div>'}
      </body>
    </html>
  `
}

const printTimeline = () => {
  const html = buildTimelineHtml()
  const printWindow = window.open('', '_blank', 'width=900,height=700')
  if (!printWindow)
    return
  printWindow.document.open()
  printWindow.document.write(html)
  printWindow.document.close()
  printWindow.focus()
  printWindow.print()
}

const toggleActive = async () => {
  if (activeToggleBusy.value)
    return
  if (isOffline.value) {
    error.value = 'You are offline. Connect to the internet before updating the assessment.'
    return
  }
  if (assessmentActive.value === null) {
    try {
      await loadAssessmentActive()
    }
    catch (err) {
      error.value = err?.message || 'Unable to load assessment status'
      return
    }
  }
  if (!assessmentTitle.value) {
    try {
      await loadAssessmentActive()
    }
    catch (err) {
      error.value = err?.message || 'Unable to load assessment details'
      return
    }
  }
  const next = !assessmentActive.value
  assessmentActive.value = next
  activeToggleBusy.value = true
  try {
    const { error: apiError } = await api(`/assessments/${route.params.id}`, {
      method: 'PUT',
      body: { active: next, title: assessmentTitle.value },
    })
    if (apiError.value)
      throw apiError.value
  }
  catch (err) {
    assessmentActive.value = !assessmentActive.value
    error.value = err?.message || 'Unable to update assessment'
  }
  finally {
    activeToggleBusy.value = false
  }
}

</script>

<template>
  <VContainer class="py-8 scores-page" fluid>
    <VRow>
      <VCol cols="12">
        <VCard class="first-card">
          <VCardTitle class="justify-space-between align-center">
            <div>
              <div class="overline text-secondary mb-4">
                Scores
              </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <VSelect
                v-model="scoringScheme"
                :items="[
                  { title: 'Geometric decay', value: 'geometric-decay' },
                  { title: 'Linear decay', value: 'linear-decay' },
                ]"
                label="Scoring scheme"
                hide-details
                density="comfortable"
                style="max-width: 220px;"
                @update:model-value="fetchScores"
              />
              <VBtn
                color="primary"
                variant="tonal"
                prepend-icon="tabler-refresh"
                :loading="loading"
                :disabled="isOffline"
                @click="fetchScores"
              >
                Refresh
              </VBtn>
              <VTooltip location="top">
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    color="success"
                    variant="tonal"
                    prepend-icon="tabler-download"
                    :disabled="!presentations.length"
                    @click="downloadCsv"
                  >
                    CSV
                  </VBtn>
                </template>
                <span>Download Scores</span>
              </VTooltip>
              <VTooltip location="top">
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    variant="tonal"
                    :color="assessmentActive ? 'success' : 'secondary'"
                    :loading="activeToggleBusy"
                    :disabled="(assessmentActive === null && !presentations.length) || isOffline"
                    @click="toggleActive"
                  >
                    {{ assessmentActive ? 'Active' : 'Inactive' }}
                  </VBtn>
                </template>
                <span>
                  {{ assessmentActive ? 'Students can take gRAT' : 'Students cannot take gRAT' }}
                </span>
              </VTooltip>
              <VBtn
                color="secondary"
                variant="tonal"
                prepend-icon="tabler-printer"
                :disabled="!presentations.length"
                @click="printTimeline"
              >
                Print Timeline
              </VBtn>
            </div>
          </VCardTitle>
          <VCardText>
            <VDialog v-model="csvFallbackOpen" max-width="720">
              <VCard>
                <VCardTitle class="text-h6">Manual CSV Copy</VCardTitle>
                <VCardText>
                  <p class="text-body-2 text-medium-emphasis mb-3">
                    Automatic download failed. Copy the CSV below and save it as a file on your device.
                  </p>
                  <VTextarea
                    v-model="csvFallbackText"
                    auto-grow
                    rows="6"
                    density="comfortable"
                    hide-details
                  />
                </VCardText>
                <VCardActions class="justify-end gap-2">
                  <VBtn variant="text" @click="csvFallbackOpen = false">Close</VBtn>
                  <VBtn color="primary" variant="tonal" @click="copyCsvFallback">Copy</VBtn>
                  <span v-if="copyCsvStatus" class="text-caption text-medium-emphasis">
                    {{ copyCsvStatus }}
                  </span>
                </VCardActions>
              </VCard>
            </VDialog>
            <VRow class="mb-4">
              <VCol cols="12" sm="6" md="3">
                <VCard class="stat-card" outlined>
                  <VCardText>
                    <div class="text-caption text-medium-emphasis">Average</div>
                    <div class="text-h5 font-weight-bold">{{ averageScore }}%</div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol cols="12" sm="6" md="3">
                <VCard class="stat-card" outlined>
                  <VCardText>
                    <div class="text-caption text-medium-emphasis">Median</div>
                    <div class="text-h5 font-weight-bold">{{ medianScore }}%</div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol cols="12" sm="6" md="3">
                <VCard class="stat-card" outlined>
                  <VCardText>
                    <div class="text-caption text-medium-emphasis">High</div>
                    <div class="text-h5 font-weight-bold">{{ maxScore }}%</div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol cols="12" sm="6" md="3">
                <VCard class="stat-card" outlined>
                  <VCardText>
                    <div class="text-caption text-medium-emphasis">Low</div>
                    <div class="text-h5 font-weight-bold">{{ minScore }}%</div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
            <div class="d-flex flex-wrap gap-3 align-center mb-4">
              <VSelect
                v-model="sortKey"
                :items="[
                  { title: 'User ID (A→Z)', value: 'user' },
                  { title: 'Score (high → low)', value: 'score_desc' },
                  { title: 'Score (low → high)', value: 'score_asc' },
                ]"
                label="Sort by"
                hide-details
                density="comfortable"
                style="max-width: 240px;"
              />
            </div>
          </VCardText>
          <VCardText>
            <ErrorNotice
              v-if="error"
              :message="error"
              :show-refresh="needsRefresh"
              @close="error = ''"
              @retry="fetchScores"
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
              <div class="d-flex justify-end mb-2">
                <VSwitch
                  v-model="showCollapsible"
                  color="primary"
                  inset
                  label="Collapse responses"
                />
              </div>

              <VExpansionPanels
                v-if="sortedPresentations.length && showCollapsible"
                multiple
                class="mb-4 striped"
              >
                <VExpansionPanel
                  v-for="(presentation, idx) in sortedPresentations"
                  :key="presentation.id"
                  :class="idx % 2 === 0 ? 'row-alt' : ''"
                >
                  <VExpansionPanelTitle>
                    <div class="d-flex align-center justify-space-between w-100">
                      <span>User ID: {{ presentation.user_id }}</span>
                      <span class="font-weight-medium">{{ presentation.score }}%</span>
                    </div>
                  </VExpansionPanelTitle>
                  <VExpansionPanelText>
                    <div
                      v-for="(question, qIndex) in presentation.assessment?.questions || []"
                      :key="question.id"
                      class="mb-4"
                    >
                      <div class="font-weight-bold mb-1">
                        {{ question.stem }}
                      </div>
                      <div class="text-body-2 text-medium-emphasis mb-2">
                        Score {{ question.score }}%
                      </div>
                      <div
                        v-for="attempt in question.attempts || []"
                        :key="attempt.id"
                        class="d-flex gap-3 mb-1"
                      >
                        <span class="text-medium-emphasis">
                          {{ formatTimestamp(attempt.created_at) }}
                        </span>
                        <span>
                          {{ truncateText(attempt.answer?.answer_text) }}
                        </span>
                      </div>
                    </div>
                  </VExpansionPanelText>
                </VExpansionPanel>
              </VExpansionPanels>

              <div v-else>
                <div
                  v-for="(presentation, idx) in sortedPresentations"
                  :key="presentation.id"
                  :class="['mb-4', idx % 2 === 0 ? 'row-alt' : '']"
                >
                  <VCard outlined>
                    <VCardTitle class="d-flex justify-space-between align-center">
                      <span>User ID: {{ presentation.user_id }}</span>
                      <span class="font-weight-medium">{{ presentation.score }}%</span>
                    </VCardTitle>
                    <VCardText>
                      <div
                        v-for="(question, qIndex) in presentation.assessment?.questions || []"
                        :key="question.id"
                        class="mb-4"
                      >
                        <div class="font-weight-bold mb-1">
                          {{ question.stem }}
                        </div>
                        <div class="text-body-2 text-medium-emphasis mb-2">
                          Score {{ question.score }}%
                        </div>
                        <div
                          v-for="attempt in question.attempts || []"
                          :key="attempt.id"
                          class="d-flex gap-3 mb-1"
                        >
                          <span class="text-medium-emphasis">
                            {{ formatTimestamp(attempt.created_at) }}
                          </span>
                          <span>
                            {{ truncateText(attempt.answer?.answer_text) }}
                          </span>
                        </div>
                      </div>
                    </VCardText>
                  </VCard>
                </div>
              </div>

              <div v-if="!sortedPresentations.length" class="text-center text-medium-emphasis">
                No scores available.
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<style scoped>
.scores-page {
  padding-top: 96px;
}

.first-card {
  padding-top: 50px;
}

.stat-card {
  border-radius: 10px;
}

.row-alt :deep(.v-expansion-panel-title) {
  background: #f8fafc;
}

.row-alt :deep(.v-expansion-panel-text) {
  background: #fff;
}
</style>
