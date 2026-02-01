<template>
  <div class="question-shell">
    <div class="content-wrap">
      <div class="md-body question-stem"
           :style="textStyle"
           v-html="stemHtml"
      />
      <div class="answers-list">
        <Answer
          v-for="answer in question.answers"
          :key="answer.id"
          :presentation_id="presentation_id"
          :answer="answer"
          :answered="answered"
          :question-locked="inFlight || answered || hasCorrect"
          :attempts="attempts"
          :password="password"
          :presentation-key="presentationKey"
          :text-style="textStyle"
          @mark-answered="markAnswered"
          @attempt-start="onAttemptStart"
          @attempt-end="onAttemptEnd"
          @answer-correct="onAnswerCorrect"
        />
      </div>
    </div>
  </div>
</template>

<script>
// Determines completion state based on attempts and adapts font size to content volume.
import Answer from './Answer.vue'
import { renderMarkdown } from '../utils/markdown'

export default {
  name: 'QuestionComponent',
  components: {
    Answer,
  },
  props: {
    question: {
      type: Object,
    },
    presentation_id: {
      type: Number,
    },
    attempts: {
      type: Array,
    },
    password: {
      type: String,
      default: '',
    },
    presentationKey: {
      type: String,
      default: '',
    },
    appeals: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['questionComplete', 'attempt-start', 'attempt-end'],
  data () {
    return {
      answered: false,
      numberAnswered: 0,
      inFlight: false,
      hasCorrect: false,
      window: {
        width: 0,
        height: 0,
      },
    }
  },
  methods: {
    markAnswered () {
      if (this.answered) {
        return
      }
      this.answered = true
      this.$emit('questionComplete', this.question.id)
    },
    onAnswerCorrect () {
      this.hasCorrect = true
      this.answered = true
    },
    onAttemptStart () {
      this.inFlight = true
      this.$emit('attempt-start')
    },
    onAttemptEnd () {
      this.inFlight = false
      this.$emit('attempt-end')
    },
    initializeAnsweredFromAttempts () {
      if (!Array.isArray(this.attempts)) {
        return
      }
      const answerIds = new Set(this.question.answers.map(answer => answer.id))
      // Consider a question complete if any correct attempt exists for one of its answers.
      const hasCorrectAttempt = this.attempts.some(attempt => answerIds.has(attempt.answer_id) && attempt.answer && attempt.answer.correct === true)
      if (hasCorrectAttempt) {
        this.hasCorrect = true
        this.answered = true
      } else {
        this.hasCorrect = false
        this.answered = false
      }
    },
    handleResize () {
      this.window.width = window.innerWidth
      this.window.height = window.innerHeight
    },
  },
  mounted () {
    window.addEventListener('resize', this.handleResize)
    this.handleResize()
    this.initializeAnsweredFromAttempts()
  },
  beforeUnmount () {
    window.removeEventListener('resize', this.handleResize)
  },
  watch: {
    attempts: {
      immediate: true,
      handler () {
        this.initializeAnsweredFromAttempts()
      },
    },
    appeals: {
      immediate: true,
      handler () {
        // Placeholder to react to appeal updates from parent.
      },
    },
  },
  computed: {
    stemHtml () {
      return renderMarkdown(this.question?.stem || '')
    },
    fontSize () {
      let textVolume = 0
      let proposedFontSize
      textVolume = textVolume + this.question.stem.length
      for (let i = 0; i < this.question.answers.length; i++) {
        textVolume = textVolume + this.question.answers[i].answer_text.length
      }
      const width = this.window.width

      // Dynamic scaling to keep long questions readable across viewport sizes.
      if (width < 576) {
        proposedFontSize = Math.round(180 / (this.question.answers.length + (textVolume * 70 / width)))
        if (proposedFontSize > 16) {
          proposedFontSize = 16
        } else if (proposedFontSize < 9) {
          proposedFontSize = 9
        }
      } else if (width < 768) {
        proposedFontSize = Math.round(180 / (this.question.answers.length + (textVolume * 40 / width)))
        if (proposedFontSize > 22) {
          proposedFontSize = 22
        } else if (proposedFontSize < 10) {
          proposedFontSize = 10
        }
      } else if (width < 992) {
        proposedFontSize = Math.round(180 / (this.question.answers.length + (textVolume * 30 / width)))
        if (proposedFontSize > 32) {
          proposedFontSize = 32
        } else if (proposedFontSize < 10) {
          proposedFontSize = 10
        }
      } else {
        proposedFontSize = Math.round(180 / (this.question.answers.length + (textVolume * 20 / width)))
        if (proposedFontSize > 32) {
          proposedFontSize = 32
        } else if (proposedFontSize < 12) {
          proposedFontSize = 12
        }
      }
      
      return proposedFontSize
    },
    textStyle () {
      return {
        '--font-size': this.fontSize + 'px',
        'text-align': 'left',
      }
    },
  },
}
</script>

<style scoped>
.question-shell {
  display: flex;
  flex-direction: column;
  height: 100%;
  gap: clamp(4px, 1vw, 10px);
}

.content-wrap {
  --qa-width: 100%;
  width: clamp(260px, 75vw, 900px);
  margin: 0 auto;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: clamp(8px, 1.5vw, 14px);
  height: 100%;
  padding-left: clamp(8px, 3vw, 16px);
  padding-right: clamp(8px, 3vw, 16px);
}

.question-stem {
  text-align: left;
  padding: clamp(8px, 3vw, 16px);
  font-size: var(--font-size);
  font-weight: 700;
  color: #111;
  border: none;
  border-radius: 12px;
  line-height: 1.4;
  margin-bottom: 0;
  overflow-wrap: break-word;
  word-break: normal;
  hyphens: none;
}

/* Child blocks inside the stem shrink with the parent and drop large margins */
.question-stem :deep(p),
.question-stem :deep(ul),
.question-stem :deep(ol),
.question-stem :deep(li),
.question-stem :deep(.katex),
.question-stem :deep(.katex-display),
.question-stem :deep(.latex) {
  font-size: inherit;
  line-height: 1.25;
  margin: 0.2em 0;
}

.answers-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: clamp(8px, 1.5vw, 14px);
  padding: clamp(0px, 0.5vw, 6px) 0 clamp(14px, 2.5vw, 22px);
  width: 100%;
  align-items: stretch;
  text-align: left;
}

</style>
