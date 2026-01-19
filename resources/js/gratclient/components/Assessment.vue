<template>
  <div>
    <div
      v-if="complete"
      class="complete"
      role="status"
      aria-live="polite"
      aria-atomic="true"
    >
      <div>Complete!</div>
      <div class="complete-note">You may close this tab.</div>
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
    >
      <SwiperSlide
        v-for="(question, idx) in presentation.assessment.questions"
        :key="question.id"
        aria-roledescription="slide"
        :aria-label="`Question ${idx + 1} of ${presentation.assessment.questions.length}`"
      >
        <question
          @questionComplete="checkCompletion"
          :presentation_id=presentation.id
          :question=question
          :attempts=presentation.attempts>
        </question>
      </SwiperSlide>
    </Swiper>

  </div>
</template>


<script>
import Question from './Question.vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation, Pagination, Keyboard } from 'swiper/modules'
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
    checkCompletion (questionId) {
      if (!this.completedIds.includes(questionId)) {
        this.completedIds.push(questionId)
      }
      this.complete = this.completedIds.length === this.presentation.assessment.questions.length
    },
    initializeCompletedFromAttempts () {
      if (!this.presentation?.assessment?.questions?.length) {
        return
      }
      const attempts = Array.isArray(this.presentation?.attempts) ? this.presentation.attempts : []
      const answeredQuestionIds = []
      const answerIdToQuestionId = new Map()
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
      this.complete = this.completedIds.length === this.presentation.assessment.questions.length
    }
  },

  props: [
    'presentation',
    'password'
  ],
  data () {
    return {
      complete: false,
      completedIds: [],
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
  mounted () {
    this.initializeCompletedFromAttempts()
    window.addEventListener('keydown', this.handleArrowKeys, { capture: true })
    this.$nextTick(() => {
      this.setNavLabels()
    })
  },
  beforeUnmount () {
    window.removeEventListener('keydown', this.handleArrowKeys, { capture: true })
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

  .complete {
    transform: translate(-50%, -50%);
    margin-right: 50%;
    position: absolute;
    top: 30%;
    left: 50%;
    z-index: 10000;
    font-size: 9vw;
    font-weight: 700;
    opacity: 0.45 !important;
    color: #1357c6;
    text-align: center;
    pointer-events: none;
    text-shadow: 0 6px 18px rgba(19, 87, 198, 0.35);
    background: rgba(255, 255, 255, 0.35);
    border: 2px solid rgba(19, 87, 198, 0.2);
    padding: clamp(12px, 2vw, 22px) clamp(16px, 3vw, 30px);
    border-radius: 20px;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.2);
  }
  .complete-note {
    font-size: 3.2vw;
    opacity: 0.85;
    margin-top: 8px;
  }
</style>
