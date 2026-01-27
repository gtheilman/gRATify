<template>
  <div>
    <div
      v-if="complete"
      class="status-toast toast-complete"
      role="status"
      aria-live="polite"
      aria-atomic="true"
    >
      <div class="toast-title">Complete!</div>
      <div class="toast-note">You may close this tab.</div>
    </div>
    <div
      v-if="showCloseWarning"
      class="status-toast toast-warning"
      role="status"
      aria-live="assertive"
      aria-atomic="true"
    >
      <div class="toast-title">Completed!</div>
      <div class="toast-note">Do Not Close!</div>
      <div class="toast-note">Saving Answers: {{ queuePendingCount }}</div>
    </div>
    <div
      v-if="showSoftSaving"
      class="status-toast toast-saving"
      role="status"
      aria-live="polite"
      aria-atomic="true"
    >
      <div class="toast-title">Completed!</div>
      <div class="toast-note">Saving answers…</div>
      <div class="toast-spinner" aria-hidden="true"></div>
    </div>
    <div
      v-if="showTabWarning"
      class="tab-warning"
      role="status"
      aria-live="polite"
      aria-atomic="true"
    >
      <div>Another tab is open.</div>
      <div class="complete-note">To avoid sync issues, please use a single tab.</div>
    </div>

    <Swiper
      ref="mySwiper"
      :modules="modules"
      :pagination="pagination"
      :navigation="navigation"
      :keyboard="keyboard"
      aria-roledescription="carousel"
      aria-label="gRAT questions"
      class="assessment-swiper"
      :aria-live="complete ? 'off' : liveMode"
      @slideChange="onSlideChange"
    >
      <SwiperSlide
        v-for="(question, idx) in presentation.assessment.questions"
        :key="question.id"
        :data-question-id="question.id"
        :data-question-index="idx"
        aria-roledescription="slide"
        :aria-label="`Question ${idx + 1} of ${presentation.assessment.questions.length}`"
      >
        <question
          @questionComplete="checkCompletion"
          @attempt-start="onAttemptStart"
          @attempt-end="onAttemptEnd"
          :presentation_id=presentation.id
          :question=question
          :attempts=presentation.attempts
          :appeals="presentation.appeals"
          :password="password"
          :presentationKey="presentationKey">
        </question>
      </SwiperSlide>
    </Swiper>
    <div v-if="complete" class="appeal-toolbar">
      <button
        v-if="appealsOpen"
        type="button"
        class="appeal-open"
        :disabled="appealSubmitting"
        @click.stop.prevent="openAppealModal"
      >
        Appeal
      </button>
      <span v-if="appealSubmitted" class="appeal-badge saved">Saved</span>
      <span v-else-if="!appealsOpen" class="appeal-badge closed">APPEALS CLOSED</span>
    </div>
    <div v-if="showAppealModal" class="appeal-modal" role="dialog" aria-modal="true">
      <div class="appeal-modal-backdrop" @click.self="closeAppealModal"></div>
      <div class="appeal-modal-card">
        <div class="appeal-modal-header">
          <div class="appeal-modal-title">
            Appeal · Question {{ activeQuestionNumber }}
          </div>
          <button type="button" class="appeal-modal-close" @click="closeAppealModal">×</button>
        </div>
        <p v-if="!appealsOpen" class="appeal-note">
          Appeals are closed for this assessment.
        </p>
        <textarea
          v-model="appealDraft"
          class="appeal-textarea"
          rows="4"
          placeholder="Explain briefy why your group's choice is BETTER than the key. It is not enough to just find fault with the key's choice. The faculty will review and respond after class."
          :disabled="appealLocked"
        ></textarea>
        <div class="appeal-actions">
          <button
            type="button"
            class="appeal-submit"
            :disabled="appealLocked || !appealDraftTrimmed"
            @click.stop.prevent="submitAppeal"
          >
            {{ appealSubmitting ? 'Submitting…' : 'Submit appeal' }}
          </button>
          <span v-if="appealStatus" class="appeal-status-text">{{ appealStatus }}</span>
          <span v-if="appealError" class="appeal-error">{{ appealError }}</span>
        </div>
      </div>
    </div>

    <div v-if="debugMode" class="debug-panel" aria-live="polite">
      <div><strong>Debug</strong></div>
      <div>complete: {{ complete }}</div>
      <div>showCloseWarning: {{ showCloseWarning }}</div>
      <div>completed: {{ completedIds.length }}/{{ totalQuestions }}</div>
      <div>pendingAttempts: {{ pendingAttempts }}</div>
      <div>queueKey: {{ queueKey || 'none' }}</div>
      <div>pending: {{ queuePendingCount }}</div>
      <div>failures: {{ queueFailureStreak }}</div>
      <div>concurrency: {{ queueConcurrency }}</div>
      <div>timeoutMs: {{ queueTimeoutMs }}</div>
      <div>emaMs: {{ queueEmaMs }}</div>
      <div>lastBatch: {{ debugLastBatch }}</div>
      <div>lastServerMs: {{ debugServerMs }}</div>
      <div>lastDbMs: {{ debugDbMs }}</div>
      <div>lastQueries: {{ debugQueryCount }}</div>
      <div>lastSuccess: {{ debugLastSuccess }}</div>
      <div>lastError: {{ debugLastError }}</div>
      <div>ticks: {{ debugTicks }}</div>
      <div>lastTick: {{ debugLastTick }}</div>
    </div>

  </div>
</template>


<script>
// Renders the assessment carousel and tracks completion state from attempts.
import Question from './Question.vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation, Pagination, Keyboard } from 'swiper/modules'
import { startQueueSync, stopQueueSync, onQueueEvent, getQueueState, syncQueue, countPending } from '../utils/attemptQueue'
import { ensureCsrfCookie } from '../../utils/http'
import { writePresentationCache } from '../utils/presentationCache'
import axios from 'axios'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

export default {
  name: 'AssessmentComponent',
  components: {
    Swiper,
    SwiperSlide,
    Question
  },
  computed: {
    swiper () {
      return this.$refs.mySwiper?.swiper
    },
    totalQuestions () {
      return this.presentation?.assessment?.questions?.length || 0
    },
    presentationKey () {
      if (!this.password || !this.user_id)
        return ''
      return `${this.password}|${this.user_id}`
    },
    showCloseWarning () {
      if (!this.isSaving)
        return false
      if (this.queuePendingCount > this.saveWarningPendingThreshold)
        return true
      if (!this.saveWarningSince)
        return false
      const now = this.saveWarningNow || Date.now()
      return (now - this.saveWarningSince) >= this.saveWarningHoldMs
    },
    showSoftSaving () {
      if (!this.isSaving)
        return false
      if (this.queuePendingCount > this.saveWarningPendingThreshold)
        return false
      if (!this.saveWarningSince)
        return true
      const now = this.saveWarningNow || Date.now()
      return (now - this.saveWarningSince) < this.saveWarningHoldMs
    },
    showTabWarning () {
      return this.duplicateTab
    },
    isSaving () {
      if (!this.totalQuestions)
        return false
      const allAnswered = this.completedIds.length === this.totalQuestions
      return this.completionReady && allAnswered && !this.complete && (this.pendingAttempts > 0 || this.queuePendingCount > 0)
    },
    debugLastTick () {
      if (!this.debugLastTickAt) return 'never'
      return new Date(this.debugLastTickAt).toLocaleTimeString()
    },
    debugLastSuccess () {
      if (!this.queueLastSuccessAt) return 'never'
      return new Date(this.queueLastSuccessAt).toLocaleTimeString()
    },
    debugLastError () {
      if (!this.queueLastSuccessAt && !this.queueFirstErrorAt) return 'never'
      const ts = this.queueFirstErrorAt || this.queueLastSuccessAt
      return ts ? new Date(ts).toLocaleTimeString() : 'never'
    },
    debugLastBatch () {
      if (!this.queueLastBatch) return 'none'
      const {
        size,
        ms,
        serverErrors,
        clientErrors,
        timeouts,
        networkErrors,
        bulkUsed,
        bulkResultsCount,
        bulkFailed,
        bulkErrorStatus,
        bulkErrorCode,
      } = this.queueLastBatch
      const bulkInfo = bulkFailed ? ` bulkFail=${bulkErrorStatus || bulkErrorCode || 'yes'}` : ''
      const bulkOk = bulkUsed ? ` bulk=${bulkResultsCount ?? 'yes'}` : ''
      return `size=${size} ms=${ms} 5xx/429=${serverErrors} 4xx=${clientErrors} timeouts=${timeouts} network=${networkErrors}${bulkOk}${bulkInfo}`
    },
    debugServerMs () {
      return this.queueLastResponseDebug?.server_ms ?? 'n/a'
    },
    debugDbMs () {
      return this.queueLastResponseDebug?.db_ms ?? 'n/a'
    },
    debugQueryCount () {
      return this.queueLastResponseDebug?.queries ?? 'n/a'
    },
    appealsOpen () {
      return !!this.presentation?.assessment?.appeals_open
    },
    activeQuestion () {
      return this.presentation?.assessment?.questions?.[this.activeQuestionIndex] || null
    },
    activeQuestionId () {
      return this.activeQuestion?.id || null
    },
    activeQuestionNumber () {
      return this.activeQuestionIndex + 1
    },
    appealSubmitted () {
      if (!this.activeQuestionId) return false
      return (this.presentation?.appeals || []).some(appeal => appeal.question_id === this.activeQuestionId)
    },
    appealLocked () {
      return this.appealSubmitting || this.appealSubmitted || !this.appealsOpen
    },
    appealDraftTrimmed () {
      return (this.appealDraft || '').trim()
    }
  },
  methods: {
    setNavLabels () {
      const root = this.$el
      if (!root) return
      const prev = root.querySelector('.swiper-button-prev')
      const next = root.querySelector('.swiper-button-next')
      if (prev) prev.setAttribute('aria-label', 'Previous question')
      if (next) next.setAttribute('aria-label', 'Next question')
    },
    handleArrowKeys (event) {
      const tag = (event.target?.tagName || '').toLowerCase()
      const typingContext = ['input', 'textarea', 'select', 'option'].includes(tag) || event.target?.isContentEditable
      if (typingContext) return

      const key = event.key || event.code || event.keyCode
      const isRight = key === 'ArrowRight' || key === 'Right'
      const isLeft = key === 'ArrowLeft' || key === 'Left'
      const isEsc = key === 'Escape' || key === 'Esc' || key === 27

      const swiper = this.swiper
      if (!swiper) return

      if (isRight) {
        event.preventDefault()
        swiper.slideNext()
      } else if (isLeft) {
        event.preventDefault()
        swiper.slidePrev()
      } else if (isEsc) {
        const active = document.activeElement
        if (active && typeof active.blur === 'function') {
          active.blur()
        }
      }
    },
    onSlideChange () {
      this.syncActiveQuestionFromSwiper()
      this.syncAppealDraft()
      requestAnimationFrame(() => {
        this.syncActiveQuestionFromSwiper()
        this.syncAppealDraft()
      })
      setTimeout(() => {
        this.syncActiveQuestionFromSwiper()
        this.syncAppealDraft()
      }, 150)
    },
    syncActiveQuestionFromSwiper () {
      const activeSlide = this.$el?.querySelector?.('.swiper-slide-active')
      const domIndex = activeSlide?.dataset?.questionIndex
      if (typeof domIndex !== 'undefined') {
        const parsed = Number(domIndex)
        if (!Number.isNaN(parsed)) {
          this.activeQuestionIndex = parsed
          return
        }
      }
      const swiper = this.swiper
      if (swiper) {
        const index = swiper.realIndex ?? swiper.activeIndex ?? 0
        this.activeQuestionIndex = index
        return
      }
      this.activeQuestionIndex = 0
    },
    openAppealModal () {
      this.syncActiveQuestionFromSwiper()
      this.showAppealModal = true
      this.appealError = ''
      this.syncAppealDraft()
      this.refreshAppealsState()
    },
    closeAppealModal () {
      this.showAppealModal = false
    },
    async refreshAppealsState () {
      if (this.appealRefreshInFlight || !this.password || !this.user_id) {
        return
      }
      this.appealRefreshInFlight = true
      try {
        const response = await axios.get(`/api/presentations/store/${this.password}/${this.user_id}`)
        const payload = response?.data || null
        if (payload?.assessment && this.presentation?.assessment) {
          this.presentation.assessment = {
            ...this.presentation.assessment,
            appeals_open: payload.assessment.appeals_open
          }
        }
        if (payload?.appeals && this.presentation) {
          this.presentation.appeals = payload.appeals
        }
        if (this.presentation && this.password && this.user_id) {
          await writePresentationCache(this.password, this.user_id, this.presentation)
        }
      } catch (error) {
        // Silent refresh; UI will retry on next poll.
      } finally {
        this.appealRefreshInFlight = false
      }
    },
    startAppealsPolling () {
      if (this.appealsPollInterval || !this.password || !this.user_id) {
        return
      }
      this.appealsPollInterval = setInterval(() => {
        this.refreshAppealsState()
      }, 10000)
      this.refreshAppealsState()
    },
    stopAppealsPolling () {
      if (this.appealsPollInterval) {
        clearInterval(this.appealsPollInterval)
        this.appealsPollInterval = null
      }
    },
    syncAppealDraft () {
      const existing = (this.presentation?.appeals || []).find(appeal => appeal.question_id === this.activeQuestionId)
      if (this.showAppealModal && this.appealDraftTrimmed && !existing) {
        return
      }
      if (existing) {
        this.appealDraft = existing.body || ''
        this.appealStatus = 'Saved on server.'
      } else {
        this.appealDraft = ''
        this.appealStatus = ''
      }
      this.appealError = ''
    },
    async submitAppeal () {
      if (this.appealSubmitting || this.appealLocked) {
        return
      }
      this.syncActiveQuestionFromSwiper()
      const trimmed = this.appealDraftTrimmed
      if (!trimmed) {
        this.appealError = 'Enter your appeal before submitting.'
        return
      }
      if (!this.activeQuestionId) {
        this.appealError = 'No question selected.'
        return
      }
      this.appealSubmitting = true
      this.appealError = ''
      this.appealStatus = ''
      try {
        await ensureCsrfCookie()
        const response = await axios.post('/api/appeals', {
          presentation_id: this.presentation.id,
          question_id: this.activeQuestionId,
          body: trimmed
        })
        const appeal = response?.data || null
        if (appeal?.body) {
          this.appealDraft = appeal.body
        }
        if (appeal && this.presentation?.appeals) {
          this.presentation.appeals = [...this.presentation.appeals.filter(a => a.question_id !== this.activeQuestionId), appeal]
        }
        if (this.presentation && this.password && this.user_id) {
          await writePresentationCache(this.password, this.user_id, this.presentation)
        }
        this.appealStatus = 'Saved on server.'
        this.showAppealModal = false
      } catch (error) {
        const message = error?.response?.data?.error?.message
        this.appealError = message || 'Unable to submit appeal.'
      } finally {
        this.appealSubmitting = false
      }
    },
    sendTabPresence () {
      if (!this.tabChannel || !this.presentationKey)
        return
      const now = Date.now()
      this.tabChannel.postMessage({
        presentationKey: this.presentationKey,
        instanceId: this.tabInstanceId,
        ts: now,
      })
      this.evaluateTabRole()
    },
    evaluateTabRole () {
      if (!this.tabInstanceId)
        return
      const now = Date.now()
      const peers = []
      for (const [id, ts] of this.tabPeers.entries()) {
        if (now - ts > 12000) {
          this.tabPeers.delete(id)
          continue
        }
        peers.push(id)
      }
      if (!peers.length) {
        this.duplicateTab = false
        return
      }
      const all = [this.tabInstanceId, ...peers].sort()
      const primary = all[0]
      const isDuplicate = this.tabInstanceId !== primary
      if (isDuplicate === this.duplicateTab)
        return
      this.duplicateTab = isDuplicate
      if (this.duplicateTab && this.queueKey) {
        stopQueueSync(this.queueKey)
      } else if (!this.duplicateTab && this.password && this.user_id) {
        this.queueKey = startQueueSync(this.password, this.user_id)
      }
    },
    checkCompletion (questionId) {
      if (!this.completedIds.includes(questionId)) {
        this.completedIds.push(questionId)
      }
      if (this.completedIds.length === this.totalQuestions) {
        this.startCompletionHold()
      }
      this.evaluateCompletion()
    },
    onAttemptStart () {
      this.pendingAttempts += 1
      if (this.completionTimer) {
        clearTimeout(this.completionTimer)
        this.completionTimer = null
      }
      this.complete = false
      this.completionReady = false
      this.completionHoldUntil = null
    },
    onAttemptEnd () {
      this.pendingAttempts = Math.max(0, this.pendingAttempts - 1)
      this.evaluateCompletion()
    },
    startCompletionHold () {
      if (this.completionTimer) {
        clearTimeout(this.completionTimer)
        this.completionTimer = null
      }
      this.completionReady = false
      this.completionHoldUntil = Date.now() + this.completionDelayMs
      this.completionTimer = setTimeout(() => {
        this.completionReady = true
        this.completionTimer = null
        this.evaluateCompletion()
      }, this.completionDelayMs)
    },
    evaluateCompletion () {
      const totalQuestions = this.totalQuestions
      if (!totalQuestions)
        return
      const allAnswered = this.completedIds.length === totalQuestions
      if (!allAnswered) {
        if (this.completionTimer) {
          clearTimeout(this.completionTimer)
          this.completionTimer = null
        }
        this.complete = false
        this.completionHoldUntil = null
        return
      }
      if (!this.completionReady) {
        return
      }
      if (this.pendingAttempts > 0 || this.queuePendingCount > 0) {
        this.complete = false
        return
      }
      if (this.complete)
        return
      // Once everything is saved, show completion immediately.
      this.complete = true
    },
    initializeCompletedFromAttempts () {
      if (!this.presentation?.assessment?.questions?.length) {
        return
      }
      const attempts = Array.isArray(this.presentation?.attempts) ? this.presentation.attempts : []
      const answeredQuestionIds = []
      const answerIdToQuestionId = new Map()
      // Build an answer->question lookup so attempts can mark completion.
      this.presentation.assessment.questions.forEach(q => {
        q.answers.forEach(ans => {
          answerIdToQuestionId.set(ans.id, q.id)
        })
      })
      attempts.forEach(attempt => {
        if (attempt.answer && attempt.answer.correct === true && answerIdToQuestionId.has(attempt.answer_id)) {
          const qid = answerIdToQuestionId.get(attempt.answer_id)
          if (!answeredQuestionIds.includes(qid)) {
            answeredQuestionIds.push(qid)
          }
        }
      })
      this.completedIds = answeredQuestionIds
      if (this.completedIds.length === this.totalQuestions) {
        this.startCompletionHold()
      }
      this.evaluateCompletion()
    }
  },

  props: [
    'presentation',
    'password',
    'user_id'
  ],
  data () {
    return {
      complete: false,
      completionReady: false,
      completionDelayMs: 3000,
      completionHoldUntil: null,
      completedIds: [],
      pendingAttempts: 0,
      completionTimer: null,
      queuePendingCount: 0,
      saveWarningSince: null,
      saveWarningNow: null,
      saveWarningHoldMs: 5000,
      saveWarningPendingThreshold: 2,
      queueFailureStreak: 0,
      queueFirstErrorAt: null,
      queueLastSuccessAt: null,
      queueConcurrency: null,
      queueTimeoutMs: null,
      queueEmaMs: null,
      queueLastBatch: null,
      queueLastResponseDebug: null,
      debugMode: false,
      debugTicks: 0,
      debugLastTickAt: null,
      activeQuestionIndex: 0,
      showAppealModal: false,
      appealDraft: '',
      appealSubmitting: false,
      appealStatus: '',
      appealError: '',
      appealsPollInterval: null,
      appealRefreshInFlight: false,
      queueKey: '',
      queueUnsubscribe: null,
      duplicateTab: false,
      tabInstanceId: '',
      tabPeers: new Map(),
      tabChannel: null,
      tabPresenceInterval: null,
      fastSyncInterval: null,
      modules: [Navigation, Pagination, Keyboard],
      keyboard: {
        enabled: true,
        onlyInViewport: false
      },
      liveMode: 'polite',
      pagination: {
        clickable: true,
        renderBullet: function (index, className) {
          return `<span class="${className}" aria-label="Go to question ${index + 1}" role="button" tabindex="0">${index + 1}</span>`
        }
      },
      navigation: true
    }
  },
  watch: {
    activeQuestionIndex () {
      this.syncAppealDraft()
    },
    'presentation.appeals': {
      deep: true,
      handler () {
        this.syncAppealDraft()
      }
    },
    complete (next) {
      if (next) {
        this.startAppealsPolling()
      } else {
        this.stopAppealsPolling()
      }
    },
    isSaving (next) {
      if (!this.queueKey)
        return
      if (next) {
        if (!this.saveWarningSince && this.queuePendingCount <= this.saveWarningPendingThreshold) {
          this.saveWarningSince = Date.now()
        } else if (this.queuePendingCount > this.saveWarningPendingThreshold) {
          this.saveWarningSince = null
        }
        if (this.fastSyncInterval)
          return
        this.fastSyncInterval = setInterval(() => {
          syncQueue(this.queueKey, { timeoutMs: 8000, maxConcurrent: 25 })
          countPending(this.queueKey).then(count => {
            this.queuePendingCount = count
            this.saveWarningNow = Date.now()
            this.evaluateCompletion()
            if (this.debugMode) {
              this.debugTicks += 1
              this.debugLastTickAt = Date.now()
            }
          }).catch(() => {})
        }, 1000)
        const handleOnline = () => {
          syncQueue(this.queueKey, { timeoutMs: 8000, maxConcurrent: 25 })
        }
        window.addEventListener('online', handleOnline, { once: true })
      } else if (this.fastSyncInterval) {
        clearInterval(this.fastSyncInterval)
        this.fastSyncInterval = null
        this.saveWarningSince = null
        this.saveWarningNow = null
      }
    },
    queuePendingCount (next) {
      if (!this.isSaving)
        return
      if (next > this.saveWarningPendingThreshold) {
        this.saveWarningSince = null
        this.saveWarningNow = Date.now()
        return
      }
      if (!this.saveWarningSince) {
        this.saveWarningSince = Date.now()
        this.saveWarningNow = this.saveWarningSince
      }
    }
  },
  mounted () {
    this.initializeCompletedFromAttempts()
    const params = new URLSearchParams(window.location.search || '')
    this.debugMode = params.get('debug') === '1'
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
      this.tabInstanceId = crypto.randomUUID()
    } else {
      this.tabInstanceId = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
    }
    if (typeof BroadcastChannel !== 'undefined') {
      this.tabChannel = new BroadcastChannel('gratclient-tab-presence')
      this.tabChannel.onmessage = (event) => {
        const data = event?.data || {}
        if (data.presentationKey !== this.presentationKey)
          return
        if (!data.instanceId || data.instanceId === this.tabInstanceId)
          return
        this.tabPeers.set(data.instanceId, data.ts || Date.now())
        this.evaluateTabRole()
      }
      this.tabPresenceInterval = setInterval(() => {
        this.sendTabPresence()
      }, 5000)
      this.sendTabPresence()
    }
    if (this.password && this.user_id) {
      this.queueKey = startQueueSync(this.password, this.user_id)
      const state = getQueueState(this.queueKey)
      this.queuePendingCount = state.pendingCount || 0
      this.queueFailureStreak = state.failureStreak || 0
      this.queueFirstErrorAt = state.firstErrorAt || null
      this.queueLastSuccessAt = state.lastSuccessAt || null
      this.queueConcurrency = state.concurrency || null
      this.queueTimeoutMs = state.timeoutMs || null
      this.queueEmaMs = state.emaMs ? Math.round(state.emaMs) : null
      this.queueLastBatch = state.lastBatch || null
      this.queueLastResponseDebug = state.lastResponseDebug || null
      this.queueUnsubscribe = onQueueEvent(event => {
        if (event.presentationKey !== this.queueKey)
          return
        if (event.type === 'state') {
          this.queuePendingCount = event.state.pendingCount || 0
          this.queueFailureStreak = event.state.failureStreak || 0
          this.queueFirstErrorAt = event.state.firstErrorAt || null
          this.queueLastSuccessAt = event.state.lastSuccessAt || null
          this.queueConcurrency = event.state.concurrency || null
          this.queueTimeoutMs = event.state.timeoutMs || null
          this.queueEmaMs = event.state.emaMs ? Math.round(event.state.emaMs) : null
          this.queueLastBatch = event.state.lastBatch || null
          this.queueLastResponseDebug = event.state.lastResponseDebug || null
          this.evaluateCompletion()
        } else if (event.type === 'response') {
          if (event.payload?.debug) {
            this.queueLastResponseDebug = event.payload.debug
          }
        }
      })
    }
    if (this.complete) {
      this.startAppealsPolling()
    }
    window.addEventListener('keydown', this.handleArrowKeys, { capture: true })
      this.$nextTick(() => {
        this.setNavLabels()
        const swiper = this.swiper
        this.activeQuestionIndex = swiper?.realIndex ?? swiper?.activeIndex ?? 0
        this.syncAppealDraft()
        if (swiper && !swiper.__appealListenerBound) {
          const handler = () => this.onSlideChange()
          swiper.on('slideChange', handler)
          swiper.on('realIndexChange', handler)
          swiper.on('activeIndexChange', handler)
          swiper.on('slideChangeTransitionEnd', handler)
          swiper.__appealListenerBound = true
        }
      })
    },
  beforeUnmount () {
    window.removeEventListener('keydown', this.handleArrowKeys, { capture: true })
    if (this.completionTimer) {
      clearTimeout(this.completionTimer)
      this.completionTimer = null
    }
    if (this.queueUnsubscribe) {
      this.queueUnsubscribe()
      this.queueUnsubscribe = null
    }
    if (this.queueKey) {
      stopQueueSync(this.queueKey)
      this.queueKey = ''
    }
    if (this.fastSyncInterval) {
      clearInterval(this.fastSyncInterval)
      this.fastSyncInterval = null
    }
    this.stopAppealsPolling()
    if (this.tabPresenceInterval) {
      clearInterval(this.tabPresenceInterval)
      this.tabPresenceInterval = null
    }
    if (this.tabChannel) {
      this.tabChannel.close()
      this.tabChannel = null
    }
  }

}
</script>

<style  >
  html,
  body {
    position: relative;
    height: 100%;
  }

  body {
    background: white;
    font-family: Helvetica Neue, Helvetica, Arial, sans-serif;
    /*font-size: 14px;*/
    color: #000;
    margin: 0;
    padding: 0;
  }

  .assessment-title {
    /*font-size: 18px;*/
  }

  .assessment-swiper {
    width: min(1100px, 92vw);
    height: min(720px, 86vh);
    padding-bottom: clamp(52px, 9vh, 90px);
  }

  .swiper-slide {
    text-align: center;
   /* font-size: 2vw;*/
    background: #fff;
    /* Center slide text vertically */
    display: -webkit-box;
    display: -ms-flexbox;
    display: -webkit-flex;
    display: flex;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    -webkit-justify-content: center;
    justify-content: center;
    -webkit-box-align: center;
    -ms-flex-align: center;
    -webkit-align-items: center;
    align-items: center;
  }
  .swiper-pagination {
    position: absolute;
    bottom: clamp(6px, 1.5vw, 14px);
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: clamp(6px, 1vw, 10px);
  }

  .swiper-pagination-bullet {
    width: clamp(22px, 4vw, 28px);
    height: clamp(22px, 4vw, 28px);
    text-align: center;
    line-height: clamp(22px, 4vw, 28px);
    font-size: clamp(12px, 2.2vw, 14px);
    color: #000;
    opacity: 1;
    background: rgba(0, 0, 0, 0.2);
    margin: 0;
    outline: none;
    transition: transform 0.15s ease, background-color 0.15s ease;
  }

  .swiper-pagination-bullet:hover {
    transform: scale(1.05);
    background: rgba(0, 0, 0, 0.28);
    box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.08);
  }

  .swiper-pagination-bullet-active {
    color: #fff;
    background: #007aff;
  }

  .swiper-button-prev,
  .swiper-button-next {
    width: clamp(38px, 6vw, 48px);
    height: clamp(38px, 6vw, 48px);
    color: #007aff;
    outline: none;
    background: rgba(255, 255, 255, 0.92);
    border-radius: 50%;
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: transform 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
  }

  .swiper-button-prev:hover,
  .swiper-button-next:hover {
    transform: scale(1.04);
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18);
    outline: 1px solid rgba(0, 122, 255, 0.12);
  }

  .swiper-button-prev:after,
  .swiper-button-next:after {
    font-size: clamp(18px, 3vw, 24px);
  }

  /* Visible focus ring for keyboard users */
  .swiper-button-prev:focus-visible,
  .swiper-button-next:focus-visible,
  .swiper-pagination-bullet:focus-visible {
    outline: 3px solid rgba(0, 122, 255, 0.35);
    outline-offset: 2px;
  }

  .swiper-button-prev:focus-visible,
  .swiper-button-next:focus-visible,
  .swiper-pagination-bullet:focus-visible {
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.35);
    border-radius: 50%;
  }

  /* Hide nav arrows when disabled on first/last slide */
  .swiper-button-disabled {
    display: none !important;
  }

  .status-toast {
    position: fixed;
    top: 16px;
    right: 16px;
    z-index: 10002;
    color: #fff;
    padding: 12px 16px;
    border-radius: 14px;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.22);
    text-align: left;
    font-weight: 600;
    pointer-events: none;
    max-width: min(320px, 84vw);
  }

  .toast-complete {
    background: rgba(19, 87, 198, 0.92);
  }

  .toast-warning {
    background: #ff2b2b;
  }

  .toast-saving {
    background: rgba(18, 63, 132, 0.92);
  }

  .toast-title {
    font-size: clamp(14px, 2vw, 18px);
    letter-spacing: 0.4px;
  }

  .toast-note {
    font-size: clamp(12px, 1.8vw, 16px);
    margin-top: 4px;
    font-weight: 500;
  }

  .toast-spinner {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    margin-top: 8px;
    animation: toast-spin 0.9s linear infinite;
  }

  @keyframes toast-spin {
    to { transform: rotate(360deg); }
  }
  .complete-note {
    font-size: 3.2vw;
    opacity: 0.85;
    margin-top: 8px;
  }

  .saving-hint {
    text-align: center;
    font-size: 0.9rem;
    color: #5b6570;
    margin-top: 6px;
  }

  .tab-warning {
    position: absolute;
    top: 8px;
    right: 12px;
    padding: 6px 10px;
    font-size: 0.8rem;
    border-radius: 10px;
    background: rgba(247, 240, 232, 0.8);
    border: 1px solid rgba(209, 166, 111, 0.5);
    color: #4b3621;
    z-index: 10001;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
  }

  .appeal-toolbar {
    position: fixed;
    bottom: 16px;
    right: 16px;
    z-index: 10002;
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.92);
    padding: 10px 14px;
    border-radius: 999px;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(0, 0, 0, 0.06);
  }

  .appeal-open {
    border: none;
    border-radius: 999px;
    background: #111827;
    color: #facc15;
    padding: 8px 18px;
    font-weight: 700;
    font-size: clamp(12px, 2vw, 14px);
    cursor: pointer;
  }

  .appeal-open:disabled {
    background: rgba(17, 24, 39, 0.4);
    cursor: not-allowed;
  }

  .appeal-badge {
    font-size: clamp(10px, 1.8vw, 13px);
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }

  .appeal-badge.saved {
    background: rgba(34, 197, 94, 0.18);
    color: #137a3a;
  }

  .appeal-badge.closed {
    background: rgba(239, 68, 68, 0.18);
    color: #b91c1c;
  }

  .appeal-modal {
    position: fixed;
    inset: 0;
    z-index: 12000;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .appeal-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
  }

  .appeal-modal-card {
    position: relative;
    z-index: 1;
    width: min(520px, 90vw);
    background: #fff;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .appeal-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .appeal-modal-title {
    font-weight: 800;
    font-size: clamp(14px, 2.4vw, 18px);
    color: #0f172a;
  }

  .appeal-modal-close {
    border: none;
    background: transparent;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    color: #0f172a;
  }

  .appeal-note {
    font-size: clamp(11px, 2vw, 14px);
    color: rgba(15, 23, 42, 0.7);
    margin: 0;
  }

  .appeal-textarea {
    width: 100%;
    resize: vertical;
    min-height: 72px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, 0.15);
    font-size: clamp(12px, 2vw, 15px);
    font-family: inherit;
    color: #0f172a;
    background: rgba(255, 255, 255, 0.96);
  }

  .appeal-textarea:disabled {
    background: rgba(241, 245, 249, 0.8);
    color: rgba(15, 23, 42, 0.6);
  }

  .appeal-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
  }

  .appeal-submit {
    border: none;
    border-radius: 999px;
    background: #2563eb;
    color: #fff;
    padding: 8px 18px;
    font-weight: 700;
    font-size: clamp(12px, 2vw, 14px);
    cursor: pointer;
  }

  .appeal-submit:disabled {
    background: rgba(37, 99, 235, 0.4);
    cursor: not-allowed;
  }

  .appeal-status-text {
    font-size: clamp(11px, 2vw, 14px);
    color: #0f5132;
    font-weight: 600;
  }

  .appeal-error {
    font-size: clamp(11px, 2vw, 14px);
    color: #b91c1c;
    font-weight: 600;
  }
</style>
