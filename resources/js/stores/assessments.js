// Store for assessment CRUD + editor-specific helpers.
import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'
import { getErrorMessage } from '@/utils/apiError'

const api = useApi

export const useAssessmentsStore = defineStore('assessments', {
  state: () => ({
    assessments: [],
    currentAssessment: null,
    loading: false,
    error: null,
    shortLinkProvider: localStorage.getItem('shortLinkProvider') || 'bitly',
  }),
  getters: {
    questions: state => state.currentAssessment?.questions || [],
  },
  actions: {
    async fetchAssessments() {
      this.loading = true
      this.error = null
      try {
        const { data, error } = await api('/assessments', { method: 'GET' })
        if (error.value)
        {throw error.value}
        const payload = data.value || []

        this.assessments = Array.isArray(payload) ? payload : Object.values(payload)
        
        return this.assessments
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to load assessments')
        throw err
      }
      finally {
        this.loading = false
      }
    },
    setShortLinkProvider(provider) {
      if (!provider)
      {return}
      this.shortLinkProvider = provider
      localStorage.setItem('shortLinkProvider', provider)
    },
    async createAssessment(payload) {
      this.loading = true
      this.error = null
      try {
        const shortlink_provider = this.shortLinkProvider

        const { data, error } = await api('/assessments', {
          method: 'POST',
          body: {
            ...payload,
            shortlink_provider,
          },
        })

        if (error.value)
        {throw error.value}
        const created = data.value

        await this.fetchAssessments()
        
        return created
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to create assessment')
        throw err
      }
      finally {
        this.loading = false
      }
    },
    async loadAssessment(id) {
      if (!id)
      {return null}
      this.loading = true
      this.error = null
      try {
        const provider = this.shortLinkProvider
        const providerParam = provider ? `?shortlink_provider=${encodeURIComponent(provider)}` : ''
        const { data, error } = await api(`/assessments/${id}/edit${providerParam}`, { method: 'GET' })
        if (error.value)
        {throw error.value}

        // Normalize answers.correct to boolean for checkbox bindings
        const normalizeAssessment = assessment => {
          if (!assessment?.questions)
          {return assessment}
          assessment.questions = assessment.questions.map(q => {
            if (q.answers)
            {q.answers = q.answers.map(a => ({ ...a, correct: Boolean(Number(a.correct)) }))}
            
            return q
          })
          
          return assessment
        }

        this.currentAssessment = normalizeAssessment(data.value)
        
        return this.currentAssessment
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to load assessment')
        throw err
      }
      finally {
        this.loading = false
      }
    },
    async updateAssessment(payload) {
      if (!payload?.id)
      {return null}

      this.loading = true
      this.error = null
      try {
        const { data, error } = await api(`/assessments/${payload.id}`, {
          method: 'PUT',
          body: payload,
        })

        if (error.value)
        {throw error.value}
        const updated = data.value || {}

        // Preserve questions already in state (API response for update does not include them)
        this.currentAssessment = {
          ...(this.currentAssessment || {}),
          ...updated,
          questions: this.currentAssessment?.questions || [],
        }
        await this.fetchAssessments()
        
        return data.value
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to update assessment')
        throw err
      }
      finally {
        this.loading = false
      }
    },
    async bulkUpdateAssessment(payload) {
      if (!payload?.assessment?.id)
      {return null}

      this.loading = true
      this.error = null
      try {
        const { data, error } = await api(`/assessments/${payload.assessment.id}/bulk`, {
          method: 'PUT',
          body: payload,
        })

        if (error.value)
        {throw error.value}
        
        return data.value
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to save assessment')
        throw err
      }
      finally {
        this.loading = false
      }
    },
    async deleteAssessment(id) {
      if (!id)
      {return}
      this.loading = true
      this.error = null
      try {
        const { error } = await api(`/assessments/${id}`, { method: 'DELETE' })
        if (error.value)
        {throw error.value}
        if (this.currentAssessment?.id === id)
        {this.currentAssessment = null}
        await this.fetchAssessments()
      }
      catch (err) {
        this.error = getErrorMessage(err, 'Unable to delete assessment')
        throw err
      }
      finally {
        this.loading = false
      }
    },
    async createQuestion(assessmentId) {
      const { data, error } = await api('/questions', {
        method: 'POST',
        body: {
          title: 'New Question Title',
          stem: 'New Question Stem',
          assessment_id: assessmentId,
        },
      })

      if (error.value)
      {throw error.value}
      const createdQuestion = data.value

      // Automatically add two starter answers so the editor isn't empty.
      const defaults = [
        { answer_text: 'Answer A', correct: false, sequence: 1 },
        { answer_text: 'Answer B', correct: false, sequence: 2 },
      ]

      for (const ans of defaults) {
        await api('/answers', {
          method: 'POST',
          body: {
            ...ans,
            question_id: createdQuestion.id,
          },
        })
      }

      await this.loadAssessment(assessmentId)
      
      return createdQuestion
    },
    async updateQuestion(question, options = { reload: true }) {
      const { data, error } = await api(`/questions/${question.id}`, {
        method: 'PUT',
        body: {
          id: question.id,
          title: question.title,
          stem: question.stem,
          sequence: question.sequence,
        },
      })

      if (error.value)
      {throw error.value}
      if (options.reload !== false)
      {await this.loadAssessment(this.currentAssessment?.id)}
      
      return data.value
    },
    async deleteQuestion(questionId) {
      if (!this.currentAssessment)
      {return}
      const { error } = await api(`/questions/${questionId}`, { method: 'DELETE' })
      if (error.value)
      {throw error.value}
      await this.loadAssessment(this.currentAssessment.id)
    },
    async promoteQuestion(questionId) {
      if (!this.currentAssessment)
      {return}

      const { error } = await api('/questions/promote', {
        method: 'POST',
        body: {
          question_id: questionId,
          assessment_id: this.currentAssessment.id,
        },
      })

      if (error.value)
      {throw error.value}
      await this.loadAssessment(this.currentAssessment.id)
    },
    async demoteQuestion(questionId) {
      if (!this.currentAssessment)
      {return}

      const { error } = await api('/questions/demote', {
        method: 'POST',
        body: {
          question_id: questionId,
          assessment_id: this.currentAssessment.id,
        },
      })

      if (error.value)
      {throw error.value}
      await this.loadAssessment(this.currentAssessment.id)
    },
    async addAnswer(questionId) {
      const { data, error } = await api('/answers', {
        method: 'POST',
        body: {
          question_id: questionId,
          answer_text: 'New Answer',
        },
      })

      if (error.value)
      {throw error.value}
      await this.loadAssessment(this.currentAssessment?.id)
      
      return data.value
    },
    async updateAnswer(answer, options = { reload: true }) {
      const { data, error } = await api(`/answers/${answer.id}`, {
        method: 'PUT',
        body: {
          id: answer.id,
          answer_text: answer.answer_text,
          correct: answer.correct,
          sequence: answer.sequence,
        },
      })

      if (error.value)
      {throw error.value}
      if (options.reload !== false)
      {await this.loadAssessment(this.currentAssessment?.id)}
      
      return data.value
    },
    async deleteAnswer(answerId) {
      const { error } = await api(`/answers/${answerId}`, { method: 'DELETE' })
      if (error.value)
      {throw error.value}
      await this.loadAssessment(this.currentAssessment?.id)
    },
  },
})
