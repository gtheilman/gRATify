<script setup>
// Includes custom Aiken upload handling with parse/validation hints for instructors.
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAssessmentsStore } from '@/stores/assessments'
import { useOffline } from '@/composables/useOffline'
import { extractApiErrorMessage, getErrorMessage } from '@/utils/apiError'
import { buildSequenceSwap } from '@/utils/sequenceSwap'
import { buildAssessmentBulkPayload } from '@/utils/assessmentBulkPayload'
import { buildAikenMessages } from '@/utils/aikenErrors'
import { fetchJson } from '@/utils/http'

const route = useRoute()
const router = useRouter()
const assessmentsStore = useAssessmentsStore()

const formError = ref('')
const saving = ref(false)
const uploadBusy = ref(false)
const uploadMessage = ref('')
const fileInput = ref(null)
const busyQuestion = ref(null)
const busyAnswer = ref(null)
const errorMessage = ref('')
const savingAll = ref(false)
const showImportDialog = ref(false)
const showAdvancedDialog = ref(false)

const form = ref({
  id: null,
  title: '',
  course: '',
  memo: '',
  active: true,
  scheduled_at: '',
})

const autosaveTimeouts = {}
const questionSaveTimeouts = {}
const questionStatus = ref({})
const answerSaveTimeouts = {}
const answerStatus = ref({})
const answerRetry = ref(null)
const showAnswerRetry = ref(false)
const answerRetryMessage = ref('')
const activeToggleBusy = ref(false)
const titleInput = ref(null)
const showQuestionDeleteConfirm = ref(false)
const pendingQuestionDelete = ref(null)
const showInvalidAikenDialog = ref(false)
const invalidAikenMessages = ref([])
const invalidAikenSampleLink = '/files/sample_aiken.txt'
const copyAikenStatus = ref('')
const uploadSlow = ref(false)
let uploadSlowTimer = null
const { isOffline } = useOffline()
const questionsLoading = ref(false)
const questionsLoadedFor = ref(null)

const questions = computed(() => {
  return [...(assessmentsStore.questions || [])].sort((a, b) => a.sequence - b.sequence)
})

const hasAttempts = computed(() => {
  const assessment = assessmentsStore.currentAssessment
  if (!assessment)
    return false

  const presentationCount = Number(assessment.presentations_count || assessment.attempts_count || 0)
  const hasPresentations = Array.isArray(assessment.presentations) && assessment.presentations.length > 0
  const hasAttemptsArray = Array.isArray(assessment.attempts) && assessment.attempts.length > 0

  return presentationCount > 0 || hasPresentations || hasAttemptsArray
})

const allowEditing = computed(() => !hasAttempts.value)

const showEmptyQuestions = computed(() => {
  if (assessmentsStore.loading || saving.value || busyQuestion.value || busyAnswer.value)
    return false

  const hasQuestions = questions.value.length > 0
  const currentHasQuestions = !!assessmentsStore.currentAssessment?.questions?.length
  return !hasQuestions && !currentHasQuestions
})

const formatScheduledValue = value => {
  if (!value)
    return ''
  // Accept both ISO strings and already formatted strings
  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime()))
    return value
  const pad = n => String(n).padStart(2, '0')
  const datePart = `${parsed.getFullYear()}-${pad(parsed.getMonth() + 1)}-${pad(parsed.getDate())}`
  const h = parsed.getHours()
  const m = parsed.getMinutes()
  const s = parsed.getSeconds()
  if (h === 0 && m === 0 && s === 0)
    return datePart
  return `${datePart} ${pad(h)}:${pad(m)}`
}

const loadData = () => {
  if (!assessmentsStore.currentAssessment)
    return

  form.value = {
    id: assessmentsStore.currentAssessment.id,
    title: assessmentsStore.currentAssessment.title || '',
    course: assessmentsStore.currentAssessment.course || '',
    memo: assessmentsStore.currentAssessment.memo || '',
    active: Boolean(assessmentsStore.currentAssessment.active),
    scheduled_at: formatScheduledValue(assessmentsStore.currentAssessment.scheduled_at || ''),
  }
}

const fetchAssessment = async () => {
  const id = Number(route.params.id)
  if (!id)
    return router.push({ name: 'assessments' })

  const isNewAssessment = questionsLoadedFor.value !== id
  if (isNewAssessment)
    questionsLoading.value = true
  try {
    await assessmentsStore.loadAssessment(id)
    loadData()
  }
  finally {
    if (isNewAssessment) {
      questionsLoading.value = false
      questionsLoadedFor.value = id
    }
  }
}

const setQuestionStatus = (id, status) => {
  questionStatus.value = { ...questionStatus.value, [id]: status }
}

const setAnswerStatus = (id, status) => {
  answerStatus.value = { ...answerStatus.value, [id]: status }
}

const scheduleQuestionAutosave = question => {
  if (!allowEditing.value || !question?.id)
    return

  if (questionSaveTimeouts[question.id])
    clearTimeout(questionSaveTimeouts[question.id])

  questionSaveTimeouts[question.id] = setTimeout(async () => {
    await saveQuestionWithAnswers(question)
  }, 700)
}

const saveQuestionWithAnswers = async question => {
  if (!question?.id)
    return
  setQuestionStatus(question.id, 'saving')

  const payload = {
    id: question.id,
    title: (question.title || '').trim() || question.stem,
    stem: question.stem,
    sequence: question.sequence,
  }
  const answers = (question.answers || []).map(ans => ({
    id: ans.id,
    answer_text: ans.answer_text,
    correct: ans.correct,
    sequence: ans.sequence,
  }))

  try {
    await assessmentsStore.updateQuestion(payload, { reload: false })
    await Promise.all(answers.map(a => assessmentsStore.updateAnswer(a, { reload: false })))
    setQuestionStatus(question.id, 'saved')
    setTimeout(() => setQuestionStatus(question.id, 'idle'), 1800)
  }
  catch (err) {
    errorMessage.value = getErrorMessage(err, 'Unable to save question')
    setQuestionStatus(question.id, 'error')
  }
  finally {
    delete questionSaveTimeouts[question.id]
  }
}

const scheduleAnswerAutosave = answer => {
  if (!allowEditing.value || !answer?.id)
    return

  if (answerSaveTimeouts[answer.id])
    clearTimeout(answerSaveTimeouts[answer.id])

  answerSaveTimeouts[answer.id] = setTimeout(async () => {
    await saveAnswerDirect(answer)
  }, 600)
}

const saveAnswerDirect = async answer => {
  if (!answer?.id)
    return
  setAnswerStatus(answer.id, 'saving')
  try {
    await assessmentsStore.updateAnswer(answer, { reload: false })
    setAnswerStatus(answer.id, 'saved')
    setTimeout(() => setAnswerStatus(answer.id, 'idle'), 1600)
  }
  catch (err) {
    errorMessage.value = getErrorMessage(err, 'Unable to save answer')
    answerRetry.value = { ...answer }
    answerRetryMessage.value = 'Answer save failed. Retry?'
    showAnswerRetry.value = true
    setAnswerStatus(answer.id, 'error')
  }
  finally {
    delete answerSaveTimeouts[answer.id]
  }
}

const setCorrectAnswer = (question, answer) => {
  if (!question?.answers)
    return

  const targetChecked = !!answer.correct

  // If marking an answer correct, uncheck all others in this question
  if (targetChecked) {
    question.answers.forEach(ans => {
      if (ans.id !== answer.id && ans.correct) {
        ans.correct = false
        scheduleAnswerAutosave(ans)
      }
    })
  }

  scheduleAnswerAutosave(answer)
}

const retrySaveAnswer = async () => {
  if (!answerRetry.value)
    return
  await saveAnswerDirect(answerRetry.value)
  showAnswerRetry.value = false
}

const scheduleAutosave = (field, value) => {
  if (autosaveTimeouts[field])
    clearTimeout(autosaveTimeouts[field])
  autosaveTimeouts[field] = setTimeout(async () => {
    await saveField(field, value)
  }, 800)
}

const saveField = async (field, value) => {
  if (!form.value.id)
    return

  // Send full payload so required fields (e.g., title) are always present
  const payload = {
    ...form.value,
    [field]: value,
  }
  try {
    saving.value = true
    await assessmentsStore.updateAssessment(payload)
    formError.value = ''
  }
  catch (err) {
    formError.value = getErrorMessage(err, 'Unable to save assessment')
  }
  finally {
    saving.value = false
  }
}

const maybeSelectDefaultTitle = () => {
  if (form.value.title !== 'New gRAT')
    return
  const input = titleInput.value?.$el?.querySelector?.('input')
  if (input && typeof input.select === 'function')
    input.select()
}

const selectIfDefault = (event, expected) => {
  if (!expected)
    return
  const target = event?.target
  if (!target || typeof target.select !== 'function')
    return
  const defaults = Array.isArray(expected) ? expected : [expected]
  if (defaults.includes(target.value))
    target.select()
}

const toggleActiveStatus = async () => {
  if (activeToggleBusy.value)
    return
  const nextValue = !form.value.active
  form.value.active = nextValue
  activeToggleBusy.value = true
  try {
    await saveField('active', nextValue)
  } finally {
    activeToggleBusy.value = false
  }
}

onMounted(async () => {
  if (!assessmentsStore.assessments.length)
    await assessmentsStore.fetchAssessments()
  await fetchAssessment()
})

watch(() => route.params.id, async () => {
  await fetchAssessment()
})

const save = async () => {
  formError.value = ''
  saving.value = true
  try {
    await assessmentsStore.updateAssessment(form.value)
    await assessmentsStore.loadAssessment(form.value.id)
    loadData()
    uploadMessage.value = ''
  }
  catch (err) {
    formError.value = getErrorMessage(err, 'Unable to save assessment')
  }
  finally {
    saving.value = false
  }
}

const saveAll = async () => {
  if (!form.value.id)
    return
  formError.value = ''
  savingAll.value = true
  try {
    const questionList = questions.value || []
    await assessmentsStore.bulkUpdateAssessment(buildAssessmentBulkPayload(form.value, questionList))

    await assessmentsStore.loadAssessment(form.value.id)
    loadData()
    uploadMessage.value = ''
  }
  catch (err) {
    formError.value = getErrorMessage(err, 'Unable to save all changes')
  }
  finally {
    savingAll.value = false
  }
}

const goToQuestions = () => {
  router.push({ name: 'questions', query: { assessment: form.value.id } })
}

const closePage = () => {
  router.push({ name: 'assessments' })
}

const triggerImport = () => {
  if (fileInput.value)
    fileInput.value.click()
}

const exportQuestions = () => {
  if (!questions.value.length) {
    alert('No questions to export.')
    return
  }

  const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
  let exportData = ''

  const sortedQuestions = [...questions.value].sort((a, b) => a.sequence - b.sequence)
  sortedQuestions.forEach(q => {
    exportData += `${(q.stem || '').trim()}\n`
    let correctLetter = ''
    const sortedAnswers = (q.answers || []).sort((a, b) => a.sequence - b.sequence)
    sortedAnswers.forEach((ans, idx) => {
      const letter = letters[idx] || '?'
      exportData += `${letter}) ${(ans.answer_text || '').trim()}\n`
      if (ans.correct) {
        correctLetter = letter
      }
    })
    exportData += `ANSWER: ${correctLetter}\n\n`
  })

  const blob = new Blob([exportData], { type: 'text/plain;charset=utf-8;' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'aiken.txt'
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  window.URL.revokeObjectURL(url)
}

const advancedFormatting = () => {
  showAdvancedDialog.value = true
}

const createQuestion = async () => {
  if (!form.value.id)
    return
  try {
    busyQuestion.value = 'new'
    await assessmentsStore.createQuestion(form.value.id)
    await assessmentsStore.loadAssessment(form.value.id)
  }
  catch (err) {
    errorMessage.value = getErrorMessage(err, 'Unable to create question')
  }
  finally {
    busyQuestion.value = null
  }
}

const saveQuestion = async question => {
  try {
    busyQuestion.value = question.id
    setQuestionStatus(question.id, 'saving')
    const payload = {
      ...question,
      title: (question.title || '').trim() || question.stem,
    }
    await assessmentsStore.updateQuestion(payload)
    setQuestionStatus(question.id, 'saved')
    setTimeout(() => setQuestionStatus(question.id, 'idle'), 1800)
  }
  catch (err) {
    errorMessage.value = getErrorMessage(err, 'Unable to save question')
    setQuestionStatus(question.id, 'error')
  }
  finally {
    busyQuestion.value = null
  }
}

const startDeleteQuestion = question => {
  if (!question?.id)
    return
  pendingQuestionDelete.value = question
  showQuestionDeleteConfirm.value = true
}

const cancelDeleteQuestion = () => {
  pendingQuestionDelete.value = null
  showQuestionDeleteConfirm.value = false
}

const confirmDeleteQuestion = async () => {
  if (!pendingQuestionDelete.value?.id)
    return
  try {
    busyQuestion.value = pendingQuestionDelete.value.id
    await assessmentsStore.deleteQuestion(pendingQuestionDelete.value.id)
  }
  catch (err) {
    errorMessage.value = getErrorMessage(err, 'Unable to delete question')
  }
  finally {
    busyQuestion.value = null
    cancelDeleteQuestion()
  }
}

const promote = async question => {
  busyQuestion.value = question.id
  await assessmentsStore.promoteQuestion(question.id)
  busyQuestion.value = null
}

const demote = async question => {
  busyQuestion.value = question.id
  await assessmentsStore.demoteQuestion(question.id)
  busyQuestion.value = null
}

const addAnswer = async question => {
  busyAnswer.value = `new-${question.id}`
  await assessmentsStore.addAnswer(question.id)
  busyAnswer.value = null
}

const saveAnswer = async answer => {
  busyAnswer.value = answer.id
  await assessmentsStore.updateAnswer(answer)
  busyAnswer.value = null
}

const deleteAnswer = async answer => {
  busyAnswer.value = answer.id
  await assessmentsStore.deleteAnswer(answer.id)
  busyAnswer.value = null
}

const moveAnswer = async (question, answer, direction) => {
  const swap = buildSequenceSwap(question?.answers, answer?.id, direction)
  if (!swap)
    return

  const { current, neighbor, currentSequence, neighborSequence } = swap

  busyAnswer.value = `move-${answer.id}`
  try {
    await assessmentsStore.updateAnswer({ ...current, sequence: neighborSequence })
    await assessmentsStore.updateAnswer({ ...neighbor, sequence: currentSequence })
    await assessmentsStore.loadAssessment(form.value.id)
  }
  finally {
    busyAnswer.value = null
  }
}


const handleFileUpload = async event => {
  const file = event.target.files?.[0]
  if (!file || !form.value.id)
    return
  if (isOffline.value) {
    formError.value = 'You are offline. Connect to the internet before uploading.'
    if (fileInput.value)
      fileInput.value.value = ''
    return
  }

  uploadBusy.value = true
  uploadSlow.value = false
  if (uploadSlowTimer)
    clearTimeout(uploadSlowTimer)
  uploadSlowTimer = setTimeout(() => {
    if (uploadBusy.value)
      uploadSlow.value = true
  }, 6000)
  uploadMessage.value = ''
  formError.value = ''

  const formData = new FormData()
  formData.append('assessment', file)
  formData.append('assessment_id', form.value.id)
  formData.append('format', 'aiken')

  try {
    const { data, response, text } = await fetchJson('/api/questions/upload', {
      method: 'POST',
      body: formData,
      parseText: true,
    })
    if (!response.ok) {
      const rawText = text || ''
      let json = null
      try {
        json = rawText ? JSON.parse(rawText) : null
      } catch {
        json = null
      }
      const message = extractApiErrorMessage(data) || extractApiErrorMessage(json) || rawText || ''
      const hasAikenStatus = String(message).includes('Aiken')
      const hasErrorLines = String(rawText).includes('Error:')
      // Special-case invalid Aiken uploads to show a detailed modal instead of a generic toast.
      const isInvalidAiken = response.status === 422 && (hasAikenStatus || hasErrorLines)
      if (isInvalidAiken) {
        invalidAikenMessages.value = buildAikenMessages(json, rawText)
        showImportDialog.value = false
        showInvalidAikenDialog.value = true
        return
      }
      throw new Error(message || 'Upload failed')
    }
    showImportDialog.value = false
    uploadMessage.value = 'Upload complete. Questions imported.'
    await assessmentsStore.loadAssessment(form.value.id)
  }
  catch (err) {
    formError.value = getErrorMessage(err, 'Upload failed')
  }
  finally {
    uploadBusy.value = false
    if (uploadSlowTimer) {
      clearTimeout(uploadSlowTimer)
      uploadSlowTimer = null
    }
    uploadSlow.value = false
    if (fileInput.value)
      fileInput.value.value = ''
  }
}

const dismissInvalidAiken = () => {
  showInvalidAikenDialog.value = false
  invalidAikenMessages.value = []
  copyAikenStatus.value = ''
  showImportDialog.value = true
}

const openFilePicker = () => {
  if (fileInput.value)
    fileInput.value.click()
}

const copyAikenErrors = async () => {
  if (!invalidAikenMessages.value.length)
    return
  const text = invalidAikenMessages.value.join('\n')
  try {
    await navigator.clipboard.writeText(text)
    copyAikenStatus.value = 'Copied'
  }
  catch {
    copyAikenStatus.value = 'Copy failed'
  }
  setTimeout(() => {
    copyAikenStatus.value = ''
  }, 1500)
}

const downloadAikenErrors = () => {
  if (!invalidAikenMessages.value.length)
    return
  const text = invalidAikenMessages.value.join('\n')
  const blob = new Blob([text], { type: 'text/plain;charset=utf-8' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'aiken-upload-errors.txt'
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  window.URL.revokeObjectURL(url)
}
</script>

<template>
<VContainer class="py-6 edit-assessment-page bg-white" fluid>
    <div class="menu-spacer" />
    <VRow>
      <VCol cols="12">
        <template v-if="allowEditing">
          <VCard
            class="mb-4 panel-card surface-glass surface-glass-blur card-radius-lg elevate-soft"
            elevation="0"
          >
            <VCardTitle class="d-flex justify-space-between align-center">
              <div class="d-flex align-center gap-3">
                <h3 class="text-h5 mb-0">Edit gRAT</h3>
                <div class="d-flex align-center gap-2">
                  <VBtn
                    size="small"
                    variant="flat"
                    :color="form.active ? 'success' : 'grey'"
                    :loading="activeToggleBusy"
                    prepend-icon="tabler-power"
                    :class="['active-toggle-btn', { 'active-toggle-btn--inactive': !form.active }]"
                    @click="toggleActiveStatus"
                  >
                    {{ form.active ? 'Active' : 'Inactive' }}
                  </VBtn>
                  <span class="text-caption text-medium-emphasis">
                    {{ form.active ? 'Students can access gRAT' : 'Students cannot access gRAT' }}
                  </span>
                </div>
                <VChip
                  v-if="form.scheduled_at"
                  color="primary"
                  variant="tonal"
                  size="small"
                >
                  Scheduled: {{ form.scheduled_at }}
                </VChip>
              </div>
            </VCardTitle>
            <VCardText class="pt-0">
              <VAlert
                v-if="formError"
                type="error"
                closable
                class="mb-4"
                @click:close="formError = ''"
              >
                {{ formError }}
              </VAlert>

              <VRow dense class="title-course-row">
                <VCol cols="12" md="6">
                <div class="field-label">gRAT Title (required)</div>
                <VTextField
                  ref="titleInput"
                  class="legacy-input"
                  v-model="form.title"
                  variant="outlined"
                  placeholder="gRAT Title"
                  density="comfortable"
                  @focus="maybeSelectDefaultTitle"
                  @blur="scheduleAutosave('title', form.title)"
                />
              </VCol>
              <VCol cols="12" md="6">
                <div class="field-label">Course (optional)</div>
                <VTextField
                  class="legacy-input"
                  v-model="form.course"
                  variant="outlined"
                  placeholder="Course"
                  density="comfortable"
                  @blur="scheduleAutosave('course', form.course)"
                />
              </VCol>
              </VRow>

              <VRow dense>
                <VCol cols="12" md="6">
                  <div class="field-label">Scheduled On (optional)</div>
                  <VTextField
                    class="legacy-input"
                    v-model="form.scheduled_at"
                    variant="outlined"
                    type="date"
                    density="comfortable"
                    @blur="scheduleAutosave('scheduled_at', form.scheduled_at)"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <div class="field-label">Details (optional)</div>
                  <VTextarea
                    class="legacy-input"
                    v-model="form.memo"
                    variant="outlined"
                    placeholder="Details"
                    rows="3"
                    auto-grow
                    density="comfortable"
                    @blur="scheduleAutosave('memo', form.memo)"
                  />
                </VCol>
              </VRow>

            </VCardText>
          </VCard>

          <VCard
            class="mb-4 panel-card surface-glass surface-glass-blur card-radius-lg elevate-soft"
            elevation="0"
          >
            <VCardTitle class="d-flex justify-space-between align-center flex-wrap gap-3">
              <div class="d-flex align-center gap-2">
                <span class="text-h6">Questions</span>
                <span class="text-body-2 text-medium-emphasis">Autosaves; only one correct answer per question.</span>
              </div>
              <VBtn color="primary" variant="text" size="small" @click="advancedFormatting">
                Advanced Formatting
              </VBtn>
            </VCardTitle>
            <VCardText class="pt-1">
              <VAlert
                v-if="uploadMessage"
                type="success"
                dense
                closable
                class="mb-3"
                @click:close="uploadMessage = ''"
              >
                {{ uploadMessage }}
              </VAlert>
              <VProgressLinear
                v-if="uploadBusy"
                indeterminate
                color="primary"
                class="mb-3"
              />
              <VAlert
                v-if="errorMessage"
                type="error"
                dense
                closable
                class="mb-3"
                @click:close="errorMessage = ''"
              >
                {{ errorMessage }}
              </VAlert>

              <div
                v-if="questionsLoading && !questions.length"
                class="d-flex align-center justify-center gap-2 text-medium-emphasis mb-3"
              >
                <VProgressCircular indeterminate size="24" width="2" color="primary" />
                <span>Loading questions…</span>
              </div>
              <div v-if="questions.length" class="d-flex flex-column gap-4 question-stack question-stack--ruled">
                <VCard
                  v-for="question in questions"
                  :key="question.id"
                  class="legacy-question panel-card"
                  elevation="0"
                >
                  <VCardTitle class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center gap-2">
                  <span class="seq-chip">Question {{ question.sequence }}</span>
                  <VTooltip
                    v-if="question.sequence > 1"
                    text="Move up in question order"
                    location="top"
                  >
                    <template #activator="{ props }">
                      <VBtn
                        v-bind="props"
                        icon="tabler-arrow-up"
                        variant="text"
                        size="x-small"
                        :loading="busyQuestion === question.id"
                        @click="promote(question)"
                      />
                    </template>
                  </VTooltip>
                  <VTooltip
                    v-if="question.sequence < questions.length"
                    text="Move down in question order"
                    location="top"
                  >
                    <template #activator="{ props }">
                      <VBtn
                        v-bind="props"
                        icon="tabler-arrow-down"
                        variant="text"
                        size="x-small"
                        :loading="busyQuestion === question.id"
                        @click="demote(question)"
                      />
                    </template>
                  </VTooltip>
                    </div>
                    <div class="question-action-group d-flex gap-2 align-center">
                      <VChip
                        v-if="questionStatus[question.id] === 'saved'"
                        color="success"
                        variant="tonal"
                        size="small"
                      >
                        Saved
                      </VChip>
                      <VChip
                        v-else-if="questionStatus[question.id] === 'error'"
                        color="error"
                        variant="tonal"
                        size="small"
                      >
                        Save failed
                      </VChip>
                      <VProgressCircular
                        v-else-if="questionStatus[question.id] === 'saving'"
                        indeterminate
                        size="20"
                        width="2"
                        color="primary"
                      />
                      <VBtn
                        class="question-delete-btn"
                        variant="text"
                        color="error"
                        size="x-small"
                        :loading="busyQuestion === question.id"
                        @click="startDeleteQuestion(question)"
                      >
                        Delete Question {{ question.sequence }}
                      </VBtn>
                    </div>
                  </VCardTitle>
                  <VCardText class="d-flex flex-column gap-3">
                    <VTextField
                      v-model="question.title"
                      label="Question Title (optional)"
                      density="comfortable"
                      @focus="selectIfDefault($event, 'New Question Title')"
                      @blur="scheduleQuestionAutosave(question)"
                    />
                    <VTextarea
                      v-model="question.stem"
                      label="Question Text"
                      rows="3"
                      auto-grow
                      density="comfortable"
                      @focus="selectIfDefault($event, 'New Question Stem')"
                      @blur="scheduleQuestionAutosave(question)"
                    />
                    <div class="d-flex flex-column gap-2">
                      <VCard
                        v-for="answer in (question.answers || []).sort((a, b) => a.sequence - b.sequence)"
                        :key="answer.id"
                        class="pa-3"
                        :class="[{ 'answer-correct': answer.correct }]"
                        variant="tonal"
                      >
                        <div class="answer-row d-flex flex-column flex-md-row gap-3 align-start align-md-center">
                          <VTextField
                            v-model="answer.answer_text"
                            label="Answer Text"
                            density="comfortable"
                            class="answer-text flex-grow-1"
                            @focus="selectIfDefault($event, ['Answer A', 'Answer B'])"
                            @blur="scheduleAnswerAutosave(answer)"
                          />
                          <VCheckbox
                            v-model="answer.correct"
                            label="Correct"
                            hide-details
                            @change="setCorrectAnswer(question, answer)"
                          />
                          <div class="d-flex align-center gap-2">
                            <div class="answer-arrows d-flex gap-1 justify-center">
                            <VTooltip
                              v-if="answer.sequence > 1"
                              text="Move up in answer order"
                              location="top"
                            >
                              <template #activator="{ props }">
                                <VBtn
                                  v-bind="props"
                                  icon="tabler-arrow-up"
                                  variant="text"
                                  size="x-small"
                                  :loading="busyAnswer === `move-${answer.id}`"
                                  @click="moveAnswer(question, answer, 'up')"
                                />
                              </template>
                            </VTooltip>
                            <VTooltip
                              v-if="answer.sequence < (question.answers?.length || 0)"
                              text="Move down in answer order"
                              location="top"
                            >
                              <template #activator="{ props }">
                                <VBtn
                                  v-bind="props"
                                  icon="tabler-arrow-down"
                                  variant="text"
                                  size="x-small"
                                  :loading="busyAnswer === `move-${answer.id}`"
                              @click="moveAnswer(question, answer, 'down')"
                                />
                              </template>
                            </VTooltip>
                          </div>
                          </div>
                          <div class="d-flex gap-2">
                            <VBtn
                              variant="text"
                              color="error"
                              size="small"
                              :loading="busyAnswer === answer.id"
                              @click="deleteAnswer(answer)"
                            >
                              Delete
                            </VBtn>
                          </div>
                        </div>
                      </VCard>
                    </div>
                    <div class="d-flex justify-end">
                      <VBtn
                        variant="tonal"
                        color="secondary"
                        size="small"
                        :loading="busyAnswer === `new-${question.id}`"
                        @click="addAnswer(question)"
                      >
                        Add Answer
                      </VBtn>
                    </div>
                  </VCardText>
                </VCard>
              </div>
              <div v-else-if="showEmptyQuestions" class="text-medium-emphasis mb-3">
                No questions yet. Add your first question below.
              </div>
              <div class="d-flex gap-2 flex-wrap justify-end mt-4">
                <VBtn color="primary" variant="flat" size="small" :loading="busyQuestion === 'new'" @click="createQuestion">
                  Add Question
                </VBtn>
                <VTooltip location="top" text="Import questions from text file">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      color="primary"
                      variant="tonal"
                      size="small"
                      @click="showImportDialog = true"
                    >
                      Import
                    </VBtn>
                  </template>
                </VTooltip>
                <VTooltip location="top" text="Export questions to text file">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      color="primary"
                      variant="tonal"
                      size="small"
                      @click="exportQuestions"
                    >
                      Export
                    </VBtn>
                  </template>
                </VTooltip>
              </div>
            </VCardText>
          </VCard>

          <VCard class="panel-card surface-glass surface-glass-blur card-radius-lg elevate-soft" elevation="0">
            <VCardText>
              <div class="actions-row sticky-actions">
                <div class="d-flex align-center gap-2 flex-wrap">
                  <VBtn color="primary" variant="flat" :loading="savingAll" @click="saveAll">
                    Save All
                  </VBtn>
                  <VBtn color="secondary" variant="flat" class="close-btn-contrast" @click="closePage">
                    Close
                  </VBtn>
                </div>
              </div>
            </VCardText>
          </VCard>
        </template>

        <template v-else>
          <VCard class="mb-4 panel-card surface-glass surface-glass-blur card-radius-lg elevate-soft" elevation="0">
            <VCardTitle class="d-flex justify-space-between align-center">
              <div class="d-flex align-center gap-3">
                <h3 class="text-h5 mb-0">gRAT Locked</h3>
                <VChip color="warning" variant="tonal" size="small">
                  Responses exist
                </VChip>
                <div class="d-flex align-center gap-2">
                  <VBtn
                    size="small"
                    variant="flat"
                    :color="form.active ? 'success' : 'grey'"
                    :loading="activeToggleBusy"
                    prepend-icon="tabler-power"
                    :class="['active-toggle-btn', { 'active-toggle-btn--inactive': !form.active }]"
                    @click="toggleActiveStatus"
                  >
                    {{ form.active ? 'Active' : 'Inactive' }}
                  </VBtn>
                  <span class="text-caption text-medium-emphasis">
                    {{ form.active ? 'Students can access gRAT' : 'Students cannot access gRAT' }}
                  </span>
                </div>
              </div>
              <div class="text-medium-emphasis text-body-2 text-right">
                Edits are disabled because this assessment has responses. You can still toggle Active.
              </div>
            </VCardTitle>
            <VCardText class="pt-0">
              <VRow>
                <VCol cols="12" md="6">
                  <div class="display-field">
                    <div class="label">Title</div>
                    <div class="value">{{ form.title || '—' }}</div>
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="display-field">
                    <div class="label">Course</div>
                    <div class="value">{{ form.course || '—' }}</div>
                  </div>
                </VCol>
              </VRow>
              <VRow>
                <VCol cols="12" md="6">
                  <div class="display-field">
                    <div class="label">Scheduled On</div>
                    <div class="value">{{ form.scheduled_at || '—' }}</div>
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="display-field">
                    <div class="label">Details</div>
                    <div class="value">{{ form.memo || '—' }}</div>
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard class="panel-card surface-glass surface-glass-blur card-radius-lg elevate-soft" elevation="0">
            <VCardTitle class="d-flex justify-space-between align-center flex-wrap gap-2">
              <span class="text-h6">Questions (read-only)</span>
              <VBtn
                color="primary"
                variant="tonal"
                size="small"
                @click="exportQuestions"
              >
                Export
              </VBtn>
            </VCardTitle>
            <VCardText class="pt-1">
              <div v-if="questions.length" class="d-flex flex-column gap-4 question-stack">
                <VCard
                  v-for="question in questions"
                  :key="question.id"
                  class="panel-card"
                  elevation="0"
                >
                  <VCardTitle class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center gap-2">
                      <span class="seq-chip">Question {{ question.sequence }}</span>
                    </div>
                  </VCardTitle>
                  <VCardText class="d-flex flex-column gap-3">
                    <div class="display-field">
                      <div class="label">Question</div>
                      <div class="value">
                        <div class="locked-stem" v-text="question.stem || '—'" />
                      </div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                      <div
                        v-for="answer in (question.answers || []).sort((a, b) => a.sequence - b.sequence)"
                        :key="answer.id"
                        class="locked-answer d-flex justify-space-between align-center"
                      >
                        <span>{{ answer.answer_text }}</span>
                        <VChip
                          v-if="Number(answer.correct)"
                          color="success"
                          variant="tonal"
                          size="x-small"
                        >
                          Correct
                        </VChip>
                      </div>
                    </div>
                  </VCardText>
                </VCard>
              </div>
              <div v-else-if="showEmptyQuestions" class="text-medium-emphasis">
                No questions available.
              </div>
            </VCardText>
          </VCard>
        </template>
      </VCol>
    </VRow>

    <VDialog
      v-model="showImportDialog"
      max-width="700"
    >
      <VCard>
        <VCardTitle class="justify-space-between align-center">
          <span>Import Questions in Aiken Format</span>
          <VBtn icon variant="text" @click="showImportDialog = false">
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-3">
          <p>
            The
            <a href="https://docs.moodle.org/39/en/Aiken_format" target="_blank" rel="noopener">
              Aiken format
            </a>
            is a very simple way of creating multiple choice questions using a clear human-readable format in a text file.
          </p>
          <p>
            I've tested uploading question using
            <a href="/files/sample_aiken.txt" target="_blank" rel="noopener" download>
              this file
            </a>. It works. If you get an error, please double-check the formatting of your text file.
          </p>
          <div class="text-center">
            <VBtn
              color="primary"
              variant="tonal"
              :disabled="isOffline"
              @click="openFilePicker"
            >
              Upload File
            </VBtn>
            <div v-if="uploadBusy" class="mt-3 d-flex align-center justify-center gap-2">
              <VProgressCircular indeterminate color="primary" size="28" />
              <span class="text-medium-emphasis">Importing Questions</span>
            </div>
            <div v-if="uploadSlow" class="mt-2 text-caption text-medium-emphasis text-center">
              This is taking longer than usual. Please wait…
            </div>
            <input
              ref="fileInput"
              type="file"
              accept=".txt"
              class="d-none"
              @change="handleFileUpload"
            >
          </div>
        </VCardText>
      </VCard>
    </VDialog>
    <VDialog
      v-model="showInvalidAikenDialog"
      max-width="520"
      persistent
    >
      <VCard>
        <VCardTitle class="text-h6">
          Invalid Aiken File
        </VCardTitle>
        <VCardText>
          <ul v-if="invalidAikenMessages.length" class="aiken-error-list">
            <li v-for="message in invalidAikenMessages" :key="message">
              {{ message }}
            </li>
          </ul>
          <div v-else>
            Invalid Aiken format text file.
          </div>
          <div class="mt-3 d-flex flex-wrap gap-2 align-center">
            <VBtn
              variant="tonal"
              color="secondary"
              size="small"
              @click="copyAikenErrors"
            >
              Copy errors
            </VBtn>
            <VBtn
              variant="tonal"
              color="primary"
              size="small"
              @click="downloadAikenErrors"
            >
              Download errors
            </VBtn>
            <VBtn
              variant="text"
              color="primary"
              size="small"
              :href="invalidAikenSampleLink"
              target="_blank"
              rel="noopener"
              download
            >
              Download sample file
            </VBtn>
            <span v-if="copyAikenStatus" class="text-caption text-medium-emphasis">
              {{ copyAikenStatus }}
            </span>
          </div>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn color="primary" variant="text" @click="dismissInvalidAiken">
            Dismiss
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
    <VDialog
      v-model="showAdvancedDialog"
      max-width="700"
    >
      <VCard>
        <VCardTitle class="justify-space-between align-center">
          <span>Advanced Formatting</span>
          <VBtn icon variant="text" @click="showAdvancedDialog = false">
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-3">
          <p>
            While plain text is the preferred way to display questions and answers, questions can include some
            <a href="/files/advanced_formatting.html" target="_blank" rel="noopener">
              simple formatting, equations and images
            </a>.
          </p>
          <p>
            This
            <a href="/files/advanced_aiken_formatting.txt" download>
              Aiken format file
            </a>
            has working examples.
          </p>
        </VCardText>
      </VCard>
    </VDialog>
    <VSnackbar
      v-model="showAnswerRetry"
      :timeout="4000"
      color="error"
    >
      {{ answerRetryMessage }}
      <template #actions>
        <VBtn color="white" variant="text" @click="retrySaveAnswer">
          Retry
        </VBtn>
        <VBtn color="white" variant="text" @click="showAnswerRetry = false">
          Dismiss
        </VBtn>
      </template>
    </VSnackbar>
  </VContainer>
  <VDialog
    v-model="showQuestionDeleteConfirm"
    max-width="420"
    persistent
  >
    <VCard>
      <VCardTitle class="text-h6">
        Delete this question?
      </VCardTitle>
      <VCardText>
        This will permanently delete
        <strong>Question {{ pendingQuestionDelete?.sequence ?? '' }}</strong>
        and its answers. This cannot be undone.
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn
          variant="text"
          color="secondary"
          @click="cancelDeleteQuestion"
        >
          Cancel
        </VBtn>
        <VBtn
          variant="text"
          color="error"
          :loading="busyQuestion === pendingQuestionDelete?.id"
          @click="confirmDeleteQuestion"
        >
          Delete
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.legacy-shell {
  border-radius: 10px;
}

.panel-card {
  background: #fff;
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.legacy-question .seq-chip {
  background: rgba(0, 0, 0, 0.08);
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
}

.legacy-question {
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.14);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.legacy-question:hover {
  border-color: rgba(59, 130, 246, 0.25);
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.2);
}

.edit-assessment-page {
  padding-top: 24px;
  background: #fff;
  min-height: 100vh;
}

.menu-spacer {
  height: 70px;
}

.active-toggle-btn {
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0.004em;
  padding-inline: 7px;
  font-size: 0.7rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.active-toggle-btn--inactive {
  border: 2px solid rgba(120, 120, 120, 0.75);
}

.title-course-row {
  padding-top: 20px;
}

.legacy-input :deep(.v-field) {
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 6px;
}

.answer-row {
  align-items: center;
}

.answer-text {
  min-width: 260px;
}

.answer-arrows {
  min-width: 80px;
}

.actions-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
}

.sticky-actions {
  position: sticky;
  bottom: 12px;
  z-index: 5;
  background: rgba(11, 18, 32, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  padding: 12px;
  backdrop-filter: blur(8px);
}

.question-stack > .panel-card {
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.question-stack--ruled > .panel-card {
  border-bottom: 2px solid rgba(0, 0, 0, 0.28);
}

.question-stack--ruled > .panel-card:last-child {
  border-bottom: none;
}

.question-action-group {
  background: rgba(244, 67, 54, 0.06);
  border: 1px solid rgba(244, 67, 54, 0.18);
  border-radius: 12px;
  padding: 6px 10px;
}

.question-action-group .v-chip {
  background-color: rgba(0, 0, 0, 0.04);
}

.question-delete-btn {
  font-weight: 600;
}

.aiken-error-list {
  margin: 0;
  padding-left: 20px;
}

.aiken-error-list li {
  margin-bottom: 6px;
}

.field-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.7);
  margin-bottom: 6px;
}

.close-btn-contrast {
  color: #0b0b0b !important;
  background: rgba(255, 255, 255, 0.92) !important;
}

.display-field .label {
  font-size: 0.85rem;
  color: rgba(0, 0, 0, 0.65);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 4px;
}

.display-field .value {
  font-size: 1.05rem;
  color: #0b0b0b;
}

.locked-stem {
  white-space: pre-wrap;
  color: #0b0b0b;
}

.locked-answer {
  padding: 10px 12px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  background: rgba(0, 0, 0, 0.04);
  color: #0b0b0b;
}

.answer-correct {
  border: 1px solid rgba(46, 204, 113, 0.5) !important;
  background: rgba(46, 204, 113, 0.08) !important;
}
</style>
