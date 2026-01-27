<script setup>
// Caches feedback payload locally to allow read-only viewing during transient failures.
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { renderMarkdown as renderClientMarkdown } from '@/gratclient/utils/markdown'
import { applyCachedFallback } from '@/utils/cacheFallback'
import { clearNotice } from '@/utils/staleNotice'
import { getErrorMessage } from '@/utils/apiError'
import { formatAssessmentError } from '@/utils/assessmentErrors'
import { fetchJson } from '@/utils/http'
import ErrorNotice from '@/components/ErrorNotice.vue'
import { needsSessionRefresh } from '@/utils/errorFlags'
import { readFeedbackCache, writeFeedbackCache } from '@/utils/feedbackCache'
import { buildAnswerMap } from '@/utils/answerMap'
import { useApi } from '@/composables/useApi'

const route = useRoute()
const api = useApi

const loading = ref(false)
const error = ref('')
const assessment = ref(null)
const activeSlide = ref(0)
const staleNotice = ref('')
const appealsOpen = ref(null)
const appealsToastDismissed = ref(false)
const appealsToggleBusy = ref(false)
const needsRefresh = computed(() => needsSessionRefresh(error.value))

const loadFeedbackCache = () => readFeedbackCache(route.params.id)

const storeFeedbackCache = data => writeFeedbackCache(route.params.id, data)

const formatFeedbackError = (response, data) => formatAssessmentError(response, data, 'feedback')

const fetchFeedback = async () => {
  loading.value = true
  error.value = ''
  staleNotice.value = ''
  try {
    const { data, response } = await fetchJson(`/api/assessment/attempts/${route.params.id}`)
    if (!response.ok) {
      const message = formatFeedbackError(response, data)
      throw new Error(message)
    }
    assessment.value = data
    if (typeof data?.appeals_open !== 'undefined')
      appealsOpen.value = !!data.appeals_open
    storeFeedbackCache(data)
  }
  catch (err) {
    error.value = getErrorMessage(err, 'Unable to load feedback')
    const cached = loadFeedbackCache()
    applyCachedFallback({
      cached,
      applyData: data => { assessment.value = data },
      applyNotice: notice => { staleNotice.value = notice },
    })
  }
  finally {
    loading.value = false
  }
}

onMounted(fetchFeedback)

const clearStaleNotice = () => {
  clearNotice(staleNotice)
}

const dismissAppealsToast = () => {
  appealsToastDismissed.value = true
}

const updateAppealsOpen = async (nextValue) => {
  if (!assessment.value || appealsToggleBusy.value)
    return
  appealsToggleBusy.value = true
  const previous = appealsOpen.value
  appealsOpen.value = nextValue
  try {
    const { error: apiError } = await api(`/assessments/${assessment.value.id}/appeals`, {
      method: 'PATCH',
      body: { appeals_open: nextValue },
    })
    if (apiError.value)
      throw apiError.value
    assessment.value = {
      ...assessment.value,
      appeals_open: !!nextValue,
    }
    storeFeedbackCache(assessment.value)
    appealsToastDismissed.value = true
  } catch (err) {
    appealsOpen.value = previous
    error.value = getErrorMessage(err, 'Unable to update appeals')
  } finally {
    appealsToggleBusy.value = false
  }
}

watch(assessment, (value) => {
  if (typeof value?.appeals_open !== 'undefined')
    appealsOpen.value = !!value.appeals_open
})

const answerMap = computed(() => buildAnswerMap(assessment.value?.questions))

const firstAttemptsByQuestion = computed(() => {
  const attemptMap = new Map()
  if (!assessment.value?.presentations)
    return attemptMap

  assessment.value.presentations.forEach(pres => {
    pres.attempts?.forEach(attempt => {
      const meta = answerMap.value.get(attempt.answer_id)
      if (!meta)
        return
      const key = `${pres.id}-${meta.question_id}`
      const existing = attemptMap.get(key)
      const attemptTime = new Date(attempt.created_at || 0).getTime()
      if (!existing || attemptTime < existing.time) {
        attemptMap.set(key, {
          question_id: meta.question_id,
          answer_id: attempt.answer_id,
          correct: meta.correct,
          time: attemptTime,
        })
      }
    })
  })

  return attemptMap
})

const answerSizeClass = count => {
  if (count >= 12)
    return 'answers-xxs'
  if (count >= 9)
    return 'answers-xs'
  if (count >= 7)
    return 'answers-sm'
  return 'answers-md'
}

const questionFeedback = computed(() => {
  if (!assessment.value?.questions)
    return []

  return assessment.value.questions.map(question => {
    const counts = {}
    question.answers?.forEach(ans => {
      counts[ans.id] = 0
    })

    let total = 0
    firstAttemptsByQuestion.value.forEach(attempt => {
      if (attempt.question_id === question.id) {
        if (counts[attempt.answer_id] !== undefined) {
          counts[attempt.answer_id] += 1
          total += 1
        }
      }
    })

    const answers = question.answers?.map(ans => {
      const count = counts[ans.id] || 0
      const pct = total ? Math.round((count / total) * 100) : 0
      return {
        ...ans,
        percent: pct,
      }
    }) || []

    return {
      ...question,
      totalResponses: total,
      answers,
    }
  })
})

const handlePrev = () => {
  if (activeSlide.value > 0)
    activeSlide.value -= 1
}

const handleNext = () => {
  const lastIndex = Math.max(0, questionFeedback.value.length - 1)
  if (activeSlide.value < lastIndex)
    activeSlide.value += 1
}

const renderMarkdown = text => renderClientMarkdown(text || '')

const toggleFullscreen = () => {
  if (document.fullscreenElement)
    document.exitFullscreen?.()
  else
    document.documentElement.requestFullscreen?.()
}

onMounted(() => {
  // Lazy-load Font Awesome only for this view
  const ensureFontAwesome = () => {
    const existing = document.querySelector('link[data-fa-cdn]')
    if (existing)
      return
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'
    link.crossOrigin = 'anonymous'
    link.referrerPolicy = 'no-referrer'
    link.setAttribute('data-fa-cdn', 'true')
    document.head.appendChild(link)
  }
  ensureFontAwesome()

  const handleKey = event => {
    if (event.key === 'ArrowLeft') {
      event.preventDefault()
      handlePrev()
    }
    else if (event.key === 'ArrowRight' || event.key === ' ') {
      event.preventDefault()
      handleNext()
    }
    else if (event.key?.toLowerCase() === 'f') {
      event.preventDefault()
      toggleFullscreen()
    }
  }
  window.addEventListener('keydown', handleKey)
  onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKey)
  })
})
</script>

<template>
  <VContainer fluid class="py-8 feedback-page">
    <VRow>
      <div class="menu-spacer" />
      <VCol cols="12">
        <VAlert
          v-if="assessment && !appealsToastDismissed"
          class="appeals-toast mb-4"
          variant="flat"
          color="white"
          closable
          @click:close="dismissAppealsToast"
        >
          <div class="d-flex flex-wrap align-center justify-space-between gap-4">
            <div>
              <div class="appeals-toast-title">
                Appeals Submissions Are {{ appealsOpen ? 'Open' : 'Closed' }}
              </div>
              <div class="appeals-toast-subtitle">
                Use the checkbox to open or close student appeals for this assessment.
              </div>
            </div>
            <VCheckbox
              :model-value="!!appealsOpen"
              :disabled="appealsToggleBusy"
              hide-details
              label="Appeals open"
              color="primary"
              @update:model-value="updateAppealsOpen"
            />
          </div>
        </VAlert>
        <VCard
          class="mb-4 surface-glass surface-glass-blur card-radius-lg elevate-soft fade-slide-up"
          elevation="0"
        >
          <VCardTitle class="justify-space-between align-center">
            <VChip
              v-if="assessment?.title"
              color="primary"
              variant="tonal"
              size="small"
            >
              {{ assessment.title }}
            </VChip>
          </VCardTitle>
          <VCardText>
            <ErrorNotice
              v-if="error"
              :message="error"
              :show-refresh="needsRefresh"
              @close="error = ''"
              @retry="fetchFeedback"
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
              <template v-if="questionFeedback.length">
                <div class="carousel-shell">
                  <VCarousel
                    v-model="activeSlide"
                    hide-delimiter-background
                    height="100%"
                    class="feedback-carousel"
                    :show-arrows="questionFeedback.length > 1"
                    :hide-delimiters="false"
                  >
                    <template #prev="{ props }">
                      <VBtn
                        v-bind="props"
                        color="primary"
                        icon="tabler-arrow-left"
                        :style="[props.style, { visibility: activeSlide > 0 ? 'visible' : 'hidden' }]"
                      />
                    </template>
                    <template #next="{ props }">
                      <VBtn
                        v-bind="props"
                        color="primary"
                        icon="tabler-arrow-right"
                        :style="[props.style, { visibility: activeSlide >= questionFeedback.length - 1 ? 'hidden' : 'visible' }]"
                      />
                    </template>
                    <VCarouselItem
                      v-for="(question, idx) in questionFeedback"
                      :key="question.id"
                      :value="idx"
                    >
                      <div class="slide-shell">
                        <VCard class="slide-card card-radius-md elevate-soft slide-card-light" elevation="0">
                          <VCardText class="py-6 px-6">
                            <div
                              v-if="question.title && question.title !== question.stem"
                              class="d-flex justify-space-between align-center mb-3"
                            >
                              <p class="question-title mb-0">
                                {{ question.title }}
                              </p>
                            </div>
                            <div class="question-stem mb-4" v-html="renderMarkdown(question.stem)" />
                            <hr class="question-divider">
                            <div :class="['answers-stack', answerSizeClass(question.answers?.length || 0)]">
                              <div
                                v-for="answer in question.answers"
                                :key="answer.id"
                                :class="[
                                  'd-flex justify-space-between align-center answer-row',
                                  answerSizeClass(question.answers?.length || 0),
                                  { 'correct-answer': !!Number(answer.correct) },
                                ]"
                              >
                                <span class="answer-text" v-html="renderMarkdown(answer.answer_text)" />
                                <span class="percent-chip">
                                  {{ answer.percent }}%
                                </span>
                              </div>
                            </div>
                            <div class="text-caption text-medium-emphasis total-responses">
                              Total first responses: {{ question.totalResponses }}
                            </div>
                          </VCardText>
                        </VCard>
                        <div class="slide-progress text-caption text-medium-emphasis mt-5 text-center">
                          Question {{ idx + 1 }} of {{ questionFeedback.length }}
                        </div>
                      </div>
                    </VCarouselItem>
                  </VCarousel>
                </div>
              </template>
              <template v-else>
                <VSheet class="empty-state" color="white" rounded="lg" elevation="2">
                  <VIcon class="empty-icon" icon="mdi-clipboard-text-outline" />
                  <div class="empty-title">
                    No feedback data available
                  </div>
                  <div class="empty-subtitle">
                    Waiting for responses to show on this slide.
                  </div>
                </VSheet>
              </template>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<style scoped>
.answer-row {
  padding: 12px 14px;
  border-radius: 12px;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  font-size: clamp(0.9rem, 3vh, 2.5rem);
  line-height: 1.5;
  color: #0b0b0b;
  font-weight: 600;
  transition: background-color 120ms ease, border-color 120ms ease;
}

.answer-row.answers-sm {
  padding: 10px 12px;
  font-size: clamp(0.9rem, 2.8vh, 2.2rem);
}

.answer-row.answers-xs {
  padding: 8px 10px;
  font-size: clamp(0.9rem, 2.6vh, 2rem);
  line-height: 1.35;
}

.answer-row.answers-xxs {
  padding: 7px 9px;
  font-size: clamp(0.9rem, 2.4vh, 1.8rem);
  line-height: 1.3;
}

.correct-answer {
  border-color: rgba(74, 222, 128, 0.5);
  background: rgba(74, 222, 128, 0.24);
  color: #000;
  font-weight: 600;
}

.answer-row:hover {
  background: rgba(0, 0, 0, 0.04);
  border-color: rgba(0, 0, 0, 0.12);
}

.correct-answer:hover {
  background: rgba(74, 222, 128, 0.24);
  border-color: rgba(74, 222, 128, 0.5);
}

.answer-text :deep(code) {
  background: rgba(15, 23, 42, 0.08);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.95em;
}

.percent-chip {
  min-width: 76px;
  text-align: right;
  font-weight: 700;
  color: #0f172a;
  background: rgba(15, 23, 42, 0.06);
  padding: 6px 12px 6px 14px;
  border-radius: 999px;
  white-space: nowrap;
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.1);
  letter-spacing: 0.01em;
  text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
  font-size: clamp(0.9rem, 3vh, 2.5rem);
}

.question-stem {
  font-weight: 600;
  font-size: clamp(0.9rem, 3.4vh, 3rem);
  color: #000;
  line-height: 1.6;
}

.question-stem :deep(p) {
  margin: 0 0 8px;
}

.question-stem :deep(ul),
.question-stem :deep(ol) {
  margin: 0 0 8px 18px;
  padding: 0;
}

.question-stem :deep(code) {
  background: rgba(15, 23, 42, 0.08);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.95em;
}

.answers-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  border: 1px dashed rgba(15, 23, 42, 0.06);
  border-radius: 12px;
}

.answers-stack.answers-sm {
  gap: 10px;
  padding: 14px;
}

.answers-stack.answers-xs {
  gap: 8px;
  padding: 12px 12px 10px;
}

.answers-stack.answers-xxs {
  gap: 6px;
  padding: 10px 11px 9px;
}

.question-divider {
  border: none;
  height: 1px;
  background: rgba(15, 23, 42, 0.06);
  margin: 12px 0 16px;
}

.question-title {
  font-weight: 700;
  font-size: clamp(0.9rem, 2.1vh, 2.2rem);
  color: #0a0a0a;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 10px;
}

.total-responses {
  margin-top: 18px;
  color: rgba(15, 23, 42, 0.6);
  font-size: 1.05rem;
  padding-left: 4px;
}

.feedback-carousel :deep(.v-window__container) {
  padding-bottom: 8px;
  height: 100%;
}

.feedback-carousel {
  position: relative;
  height: min(720px, calc(100vh - 140px));
  max-height: calc(100vh - 120px);
}

.feedback-carousel :deep(.v-window-item),
.feedback-carousel :deep(.v-carousel-item) {
  height: 100%;
  display: flex;
}

.carousel-shell {
  position: relative;
  max-width: 100%;
  margin: 0 auto;
  padding: 0 12px;
}

.slide-shell {
  padding: 12px 16px 16px; /* give arrows breathing room from text */
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 24px;
  position: relative;
  min-height: calc(100vh - 100px - 60px - var(--feedback-bottom-gap));
  padding-bottom: var(--feedback-bottom-gap);
}

.feedback-page {
  --feedback-bottom-gap: 50px;
  padding: 100px 0 var(--feedback-bottom-gap);
  background: radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.18), transparent 32%),
    radial-gradient(circle at 80% 10%, rgba(6, 182, 212, 0.16), transparent 30%),
    #0b1220;
  min-height: 100vh;
}

.menu-spacer {
  height: 60px;
  width: 100%;
}

.slide-progress {
  margin-top: auto;
  padding-bottom: 12px;
  font-size: 1.2rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 18px;
  background: rgba(255, 255, 255, 0.86);
  color: #0f172a;
  border-radius: 999px;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
  margin-left: auto;
  margin-right: auto;
}

.appeals-toast {
  border-radius: 16px;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.94);
}

.appeals-toast-title {
  font-size: clamp(1rem, 2.4vh, 1.4rem);
  font-weight: 800;
  color: #0f172a;
}

.appeals-toast-subtitle {
  font-size: clamp(0.85rem, 2vh, 1.1rem);
  color: rgba(15, 23, 42, 0.7);
  margin-top: 4px;
}

.slide-hint {
  margin-top: 18px;
  text-align: center;
  font-size: 1rem;
  color: rgba(15, 23, 42, 0.78);
}

.slide-card {
  flex: 1;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
  width: calc(100% - 100px);
  max-width: none;
  margin: 0 auto;
  max-height: calc(100vh - 210px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.slide-card-light {
  background: #fff;
  color: #0f172a;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.feedback-carousel :deep(.v-window-item) {
  display: flex;
}

.empty-state {
  padding: 24px;
  border: 1px dashed rgba(0, 0, 0, 0.08);
  text-align: center;
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.empty-title {
  font-weight: 700;
  font-size: 1.05rem;
  color: #0f172a;
  margin-bottom: 6px;
}

.empty-subtitle {
  color: rgba(15, 23, 42, 0.7);
}

.empty-icon {
  width: 24px;
  height: 24px;
  color: rgba(15, 23, 42, 0.7);
}

@media (max-width: 960px) {
  .slide-shell {
    min-height: 340px;
    padding: 12px 16px 14px;
  }
}

</style>
