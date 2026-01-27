<script setup>
// Fetches scored presentations with caching to keep the scores view usable offline.
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useOffline } from '@/composables/useOffline'
import ErrorNotice from '@/components/ErrorNotice.vue'
import { getErrorMessage } from '@/utils/apiError'
import { formatAssessmentError } from '@/utils/assessmentErrors'
import { applyCachedFallback } from '@/utils/cacheFallback'
import { buildCsvBlob } from '@/utils/csvDownload'
import { formatScore } from '@/utils/scoreFormatting'
import { canExportScores } from '@/utils/scoreTable'
import { clearNotice } from '@/utils/staleNotice'
import { needsSessionRefresh } from '@/utils/errorFlags'
import { sortPresentations } from '@/utils/scoresSorting'
import { toNumericScores } from '@/utils/scoreStats'
import { displayStem } from '@/utils/questionFormat'
import { parseActiveFlag } from '@/utils/assessmentState'
import { buildScoreSummary } from '@/utils/scoreSummary'
import { buildScoresCsv } from '@/utils/scoresCsv'
import { scoreSortOptions } from '@/utils/scoreSortOptions'
import { buildTimelineHtml as buildTimelineReport } from '@/utils/presentationExport'
import { defaultScoringScheme, scoringSchemeOptions } from '@/utils/scoringSchemes'
import { formatTimestamp } from '@/utils/dateFormat'
import { fetchJson } from '@/utils/http'
import { readScoresCache, writeScoresCache } from '@/utils/scoresCache'
import { resolveScoresCacheKey } from '@/utils/scoresCacheKey'

const route = useRoute()
const api = useApi

const loading = ref(false)
const error = ref('')
const presentations = ref([])
const showResponses = ref(false)
const sortKey = ref('user') // user | score_desc | score_asc
const assessmentActive = ref(null)
const assessmentTitle = ref('')
const activeToggleBusy = ref(false)
const staleNotice = ref('')
const needsRefresh = computed(() => needsSessionRefresh(error.value))
const csvFallbackOpen = ref(false)
const csvFallbackText = ref('')
const copyCsvStatus = ref('')
const { isOffline } = useOffline()
const openPanelIds = ref([])

const scoringScheme = ref(defaultScoringScheme)

const scoresCacheKey = () => resolveScoresCacheKey(route.params.id, scoringScheme.value)

const loadScoresCache = () => readScoresCache(scoresCacheKey())

const storeScoresCache = data => writeScoresCache(scoresCacheKey(), data)

const formatScoresError = (response, data) => formatAssessmentError(response, data, 'scores')

const fetchScores = async () => {
  loading.value = true
  error.value = ''
  staleNotice.value = ''
  try {
    if (isOffline.value)
      throw new Error('You are offline. Connect to the internet and try again.')
    const { data, response } = await fetchJson(`/api/presentations/score-by-assessment-id/${route.params.id}?scheme=${encodeURIComponent(scoringScheme.value)}`)
    if (!response.ok) {
      const message = formatScoresError(response, data)
      throw new Error(message)
    }
    presentations.value = data
    storeScoresCache(data)
    const assessment = data?.[0]?.assessment
    if (assessment && typeof assessment.active !== 'undefined')
      assessmentActive.value = parseActiveFlag(assessment.active)
    if (assessment?.title)
      assessmentTitle.value = assessment.title
    if (!showResponses.value) {
      openPanelIds.value = presentationsWithAppeals.value.map(p => p.id)
    } else {
      applyShowResponses()
    }
  }
  catch (err) {
    error.value = getErrorMessage(err, 'Unable to load scores')
    const cached = loadScoresCache()
    applyCachedFallback({
      cached,
      applyData: data => { presentations.value = data },
      applyNotice: notice => { staleNotice.value = notice },
      formatter: date => formatTimestamp(date.toISOString()),
    })
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
  return sortPresentations(presentations.value, sortKey.value)
})

const presentationsWithAppeals = computed(() => {
  return sortedPresentations.value.filter(presentation =>
    (presentation?.assessment?.questions || []).some(question => (question.appeals || []).length)
  )
})

const scoresOnly = computed(() => toNumericScores(sortedPresentations.value))
const scoreSummary = computed(() => buildScoreSummary(sortedPresentations.value))
const averageScore = computed(() => scoreSummary.value.average)
const maxScore = computed(() => scoreSummary.value.max)
const minScore = computed(() => scoreSummary.value.min)
const medianScore = computed(() => scoreSummary.value.median)

const exportEnabled = computed(() => canExportScores(presentations.value))

const clearStaleNotice = () => {
  clearNotice(staleNotice)
}

const downloadCsv = () => {
  const payload = buildScoresCsv(sortedPresentations.value, formatScore)
  try {
    const blob = buildCsvBlob(payload)
    if (!blob)
      throw new Error('csv-blob-failed')
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

const buildTimelineHtml = () => {
  const assessmentTitle = sortedPresentations.value[0]?.assessment?.title || 'gRAT'
  return buildTimelineReport({
    presentations: sortedPresentations.value,
    assessmentTitle,
    scoringScheme: scoringScheme.value,
  })
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
      error.value = getErrorMessage(err, 'Unable to load assessment status')
      return
    }
  }
  if (!assessmentTitle.value) {
    try {
      await loadAssessmentActive()
    }
    catch (err) {
      error.value = getErrorMessage(err, 'Unable to load assessment details')
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
    error.value = getErrorMessage(err, 'Unable to update assessment')
  }
  finally {
    activeToggleBusy.value = false
  }
}

const showAppeals = () => {
  openPanelIds.value = presentationsWithAppeals.value.map(p => p.id)
}

const applyShowResponses = () => {
  if (!sortedPresentations.value.length) {
    openPanelIds.value = []
    return
  }
  openPanelIds.value = showResponses.value
    ? sortedPresentations.value.map(p => p.id)
    : []
}

watch(showResponses, () => {
  applyShowResponses()
})

watch(sortedPresentations, () => {
  if (showResponses.value)
    applyShowResponses()
})

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
                :items="scoringSchemeOptions"
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
                    :disabled="!exportEnabled"
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
                    :disabled="!exportEnabled"
                @click="printTimeline"
              >
                Print Timeline
              </VBtn>
              <VBtn
                color="error"
                variant="tonal"
                prepend-icon="tabler-message"
                :disabled="!presentationsWithAppeals.length"
                @click="showAppeals"
              >
                Show Appeals
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
                :items="scoreSortOptions"
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
              @click:close="clearStaleNotice"
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
                v-model="showResponses"
                color="primary"
                inset
                label="Show Responses"
              />
              </div>

              <VExpansionPanels
                v-if="sortedPresentations.length"
                v-model="openPanelIds"
                multiple
                class="mb-4 striped"
              >
                <VExpansionPanel
                  v-for="(presentation, idx) in sortedPresentations"
                  :key="presentation.id"
                  :value="presentation.id"
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
                      <div v-if="question.appeals?.length" class="appeals-block">
                        <div class="appeals-label">Appeals</div>
                        <div
                          v-for="appeal in question.appeals"
                          :key="appeal.id"
                          class="appeal-entry"
                        >
                          {{ appeal.body }}
                        </div>
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
                        <div v-if="question.appeals?.length" class="appeals-block">
                          <div class="appeals-label">Appeals</div>
                          <div
                            v-for="appeal in question.appeals"
                            :key="appeal.id"
                            class="appeal-entry"
                          >
                            {{ appeal.body }}
                          </div>
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

.appeals-block {
  margin-top: 12px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px dashed rgba(220, 38, 38, 0.4);
  background: rgba(220, 38, 38, 0.08);
}

.appeals-label {
  font-weight: 700;
  color: #991b1b;
  margin-bottom: 6px;
}

.appeal-entry {
  color: #7f1d1d;
  font-size: 0.95rem;
  margin-bottom: 4px;
  white-space: pre-wrap;
}
</style>
