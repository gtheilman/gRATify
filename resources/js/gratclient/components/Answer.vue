<template>
  <div class="answer-wrapper" :class="wrapperClasses">
    <span
      role="button"
      tabindex="0"
      :aria-label="`${statusLabel}: ${answer?.answer_text || ''}`"
      :aria-disabled="answered || checking || questionLocked || isCorrect ? 'true' : 'false'"
      ref="answerButton"
      @click="checkAnswer"
      @keyup.enter="checkAnswer"
      @keyup.space.prevent="checkAnswer">
      <div v-if="checking" class="pending-overlay" aria-hidden="true">
        <span class="pending-spinner"></span>
        <span class="pending-text">Checking…</span>
      </div>
      <div
        class="md-body answer-text"
        :class="{'correct-answer': isCorrect, 'incorrect-answer': isInCorrect, 'checking': checking, 'grayedOut': grayedOut}"
        :style="textStyle">
        <span v-html="answerHtml" />
        <span v-if="checking" class="status-icon status-pending" aria-hidden="true">
          <span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>
        </span>
        <span v-if="isCorrect" class="status-icon status-correct" aria-hidden="true">✔</span>
        <span v-else-if="isInCorrect" class="status-icon status-incorrect" aria-hidden="true">✖</span>
      </div>
    </span>
    <div
      v-if="resultAnnouncement"
      class="sr-live"
      aria-live="polite"
      aria-atomic="true"
      ref="resultLive"
      tabindex="-1">
      {{ resultAnnouncement }}
    </div>
    <div
      v-if="errorMessage"
      class="error-inline"
      role="alert"
      aria-live="assertive"
      aria-atomic="true">
      {{ errorMessage }}
      <button
        v-if="retryAvailable"
        class="retry-btn"
        type="button"
        @click="manualRetry"
        :disabled="checking">
        Retry now
      </button>
    </div>
  </div>
</template>

<script>
import { renderMarkdown } from '../utils/markdown'
import { postAttemptWithRetry } from '../utils/attemptClient'

export default {
  name: 'AnswerComponent',
  attemptClient: postAttemptWithRetry,
  data () {
    return {
      isCorrect: false,
      isInCorrect: false,
      checking: false,
      lastClickAt: null,
      errorMessage: '',
      retryAvailable: false,
      resultAnnouncement: ''
    }
  },
  props: {
    answer: {
      type: Object
    },
    presentation_id: {
      type: Number
    },
    answered: {
      type: Boolean,
      default: false
    },
    attempts: {
      type: Array
    },
    questionLocked: {
      type: Boolean,
      default: false
    },
    textStyle: null
  },
  emits: ['markAnswered', 'attempt-start', 'attempt-end', 'answer-correct'],
  computed: {
    answerHtml () {
      return renderMarkdown(this.answer?.answer_text || '')
    },
    statusLabel () {
      if (this.isCorrect) return 'Correct answer'
      if (this.isInCorrect) return 'Incorrect answer'
      if (this.grayedOut) return 'Answer disabled'
      return 'Answer'
    },
    grayedOut () {
      return !!(this.answered && !this.isCorrect && !this.isInCorrect)
    },
    wrapperClasses () {
      return {
        locked: this.checking || this.questionLocked,
        checking: this.checking,
        'disabled-wrapper': this.grayedOut,
        'correct-wrapper': this.isCorrect,
        'incorrect-wrapper': this.isInCorrect && !this.isCorrect
      }
    }
  },
  methods: {
    applyAttempts () {
      const attemptsForThisAnswer = Array.isArray(this.attempts)
        ? this.attempts.filter(attempt => attempt.answer_id === this.answer.id)
        : []

      const hasCorrect = attemptsForThisAnswer.some(attempt => {
        const flag = attempt.answer_correct
        const ans = attempt.answer
        return flag === true || flag === 1 || (ans && (ans.correct === true || ans.correct === 1))
      })
      const hasIncorrect = attemptsForThisAnswer.some(attempt => {
        const flag = attempt.answer_correct
        const ans = attempt.answer
        // Treat null/0/false as incorrect for marking
        const incorrectFlag = flag === false || flag === 0 || flag === null
        const incorrectAns = ans && (ans.correct === false || ans.correct === 0 || ans.correct === null)
        return incorrectFlag || incorrectAns
      })

      if (hasCorrect) {
        this.isCorrect = true
        this.isInCorrect = false
        this.$emit('markAnswered', true)
      } else if (hasIncorrect) {
        this.isInCorrect = true
      }
    },
    async checkAnswer () {
      const now = Date.now()
      if (this.lastClickAt && (now - this.lastClickAt) < 400) {
        return
      }
      this.lastClickAt = now

      if (this.answered || this.checking || this.isCorrect || this.questionLocked) {
        return
      }
      this.errorMessage = ''
      this.resultAnnouncement = ''
      this.retryAvailable = false
      this.$emit('attempt-start')
      this.checking = true
      try {
        const response = await this.postAttempt({
          presentation_id: this.presentation_id,
          answer_id: this.answer.id
        })
        const payload = response.data
        const responseIsCorrect = payload && payload.correct === true
        const responseIsIncorrect = payload && payload.correct === false

        if (responseIsCorrect) {
          this.isCorrect = true
          this.$emit('markAnswered', true)
          this.$emit('answer-correct')
          this.resultAnnouncement = 'Answer marked correct.'
        } else if (responseIsIncorrect && !this.isCorrect) {
          this.isInCorrect = true
          this.resultAnnouncement = 'Answer marked incorrect.'
        } else if (import.meta.env.DEV) {
          console.warn('Unexpected attempt response', payload)
        }
        this.$nextTick(() => {
          this.$refs.resultLive?.focus?.()
        })
      } catch (error) {
        const code = `ERR-${Date.now().toString(36)}`
        console.error('Attempt failed', code, error)
        this.errorMessage = `Network issue, retrying failed. Show this code to your instructor: ${code}`
        this.retryAvailable = true
      } finally {
        this.checking = false
        this.$emit('attempt-end')
      }
    },
    postAttempt (payload) {
      return this.$options.attemptClient(payload)
    },
    manualRetry () {
      this.lastClickAt = 0
      this.checkAnswer()
    }
  },
  mounted () {
    this.applyAttempts()
  },
  updated () {
    this.applyAttempts()
  },
  watch: {
    attempts: {
      handler () {
        this.applyAttempts()
      },
      deep: true
    }
  }
}
</script>

<style scoped>
  .answer-text {
  padding-left: 0;
  padding-bottom: clamp(6px, 1.5vw, 14px);
  font-size: clamp(10px, calc(var(--font-size) * 0.9), 18px);
  cursor: pointer;
  line-height: 1.3;
  color: #1f2a33;
  font-weight: 500;
  overflow-wrap: break-word;
  word-break: break-word;
  hyphens: auto;
  display: block;
  width: 100%;
  text-align: left;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.answer-text :deep(p),
.answer-text :deep(ul),
.answer-text :deep(ol),
.answer-text :deep(li),
.answer-text :deep(.katex),
.answer-text :deep(.katex-display),
.answer-text :deep(.latex) {
  font-size: inherit;
  line-height: 1.2;
  margin: 0.15em 0;
}
  .answer-text:hover {
     font-style: italic;
  }

  .grayedOut {
    opacity: 0.3;
    cursor: not-allowed;
    font-style: normal;
  }
  .grayedOut:hover {
    font-style: normal;
  }

  .checking {
    position: relative;
    color: #555;
  }

  .answer-wrapper.checking {
    background-color: #eef2f6;
    border-color: #8aa4b2;
  }

  .answer-wrapper.disabled-wrapper {
    background-color: #e7eaee;
    border-color: #b8c2cb;
  }

  .pending-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 10px;
    pointer-events: none;
    font-weight: 600;
    color: #374151;
  }

  .pending-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(55, 65, 81, 0.25);
    border-top-color: rgba(55, 65, 81, 0.75);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  .pending-text {
    font-size: 0.95rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .correct-answer {
    color: inherit;
    cursor: not-allowed;
  }
  .correct-answer:hover {
    text-decoration: none;
  }
  .incorrect-answer {
    color: inherit;
    cursor: not-allowed;
  }
  .incorrect-answer:hover {
    text-decoration: none;
  }

  .error-inline {
    color: #b00;
    font-size: 12px;
    margin-top: 6px;
  }
  .retry-btn {
    margin-left: 8px;
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 12px;
    border: 1px solid #b00;
    background: #fff;
    cursor: pointer;
  }
  .retry-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .sr-live {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border: 0;
  }

  .locked {
    pointer-events: none;
  }

  .answer-wrapper {
    position: relative;
    border: 2px solid #a1b7c1;
    border-radius: 12px;
    box-sizing: border-box;
    padding: clamp(8px, 1.8vw, 12px);
    box-shadow: 0px 0px 10px -10px rgba(0,0,0,0.3), 0px 8px 25px -15px rgba(0,0,0,0.3);
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: clamp(6px, 1.2vw, 10px);
    margin-bottom: clamp(6px, 1.2vw, 10px);
    background-color: #E5EDF2;
    display: block;
    width: var(--qa-width, 100%);
    max-width: var(--qa-width, 100%);
    min-width: clamp(260px, 60%, 720px);
  }

  .answer-wrapper:focus-within {
    outline: 3px solid rgba(0, 122, 255, 0.4);
    outline-offset: 2px;
  }

  .answer-wrapper.correct-wrapper {
    background-color: #c4eac0;
    border-color: #5e9b59;
  }

  .answer-wrapper.incorrect-wrapper {
    background-color: #f7cfcf;
    border-color: #c47a7a;
  }

  .answer-wrapper:not(.correct-wrapper):not(.incorrect-wrapper):not(.checking):hover {
    background-color: #f3f6f9;
  }

  .status-icon {
    font-size: 1rem;
    flex-shrink: 0;
  }

  .status-correct {
    color: #2f7d2f;
  }

  .status-incorrect {
    color: #b02727;
  }

  .status-pending {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    color: #4b5563;
    font-weight: 700;
    min-width: 24px;
    justify-content: flex-end;
  }

  .status-pending .dot {
    display: inline-block;
    animation: dotPulse 1s infinite ease-in-out;
  }

  .status-pending .dot:nth-child(2) { animation-delay: 0.15s; }
  .status-pending .dot:nth-child(3) { animation-delay: 0.3s; }

  @keyframes dotPulse {
    0%, 80%, 100% { opacity: 0.2; transform: translateY(0); }
    40% { opacity: 1; transform: translateY(-2px); }
  }

</style>
