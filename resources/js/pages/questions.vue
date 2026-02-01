<script setup>
// Lightweight question editor view with per-item busy states.
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAssessmentsStore } from '@/stores/assessments'
import { useAuthStore } from '@/stores/auth'
import { getErrorMessage } from '@/utils/apiError'

const authStore = useAuthStore()
const assessmentsStore = useAssessmentsStore()

const route = useRoute()
const router = useRouter()

const selectedAssessmentId = ref(null)
const busyQuestion = ref(null)
const busyAnswer = ref(null)
const errorMessage = ref('')

const questions = computed(() => {
  return [...(assessmentsStore.questions || [])].sort((a, b) => a.sequence - b.sequence)
})

onMounted(async () => {
  await authStore.ensureSession()

  await assessmentsStore.fetchAssessments()

  const preferred = route.query.assessment ? Number(route.query.assessment) : null
  const existsPreferred = assessmentsStore.assessments.find(a => a.id === preferred)
  if (existsPreferred)
    selectedAssessmentId.value = existsPreferred.id
  else if (assessmentsStore.assessments.length)
    selectedAssessmentId.value = assessmentsStore.assessments[0].id

  if (selectedAssessmentId.value)
    await assessmentsStore.loadAssessment(selectedAssessmentId.value)
})

watch(selectedAssessmentId, async newId => {
  if (newId) {
    // Keep the selected assessment in the URL for deep links.
    router.replace({ query: { ...route.query, assessment: newId } })
    await assessmentsStore.loadAssessment(newId)
  }
})

const createQuestion = async () => {
  if (!selectedAssessmentId.value)
    return
  try {
    busyQuestion.value = 'new'
    await assessmentsStore.createQuestion(selectedAssessmentId.value)
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
    await assessmentsStore.updateQuestion(question)
  }
  catch (err) {
    errorMessage.value = getErrorMessage(err, 'Unable to save question')
  }
  finally {
    busyQuestion.value = null
  }
}

const removeQuestion = async question => {
  try {
    busyQuestion.value = question.id
    await assessmentsStore.deleteQuestion(question.id)
  }
  catch (err) {
    errorMessage.value = getErrorMessage(err, 'Unable to delete question')
  }
  finally {
    busyQuestion.value = null
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
</script>

<template>
  <div class="d-flex flex-column gap-6">
    <VRow>
      <VCol cols="12"
            md="4"
      >
        <VSelect
          v-model="selectedAssessmentId"
          :items="assessmentsStore.assessments"
          item-title="title"
          item-value="id"
          label="gRAT"
          :loading="assessmentsStore.loading"
        />
      </VCol>
      <VCol cols="12"
            md="8"
            class="d-flex align-end justify-end gap-4"
      >
        <VBtn
          color="primary"
          :loading="busyQuestion === 'new'"
          :disabled="!selectedAssessmentId"
          @click="createQuestion"
        >
          Add Question
        </VBtn>
      </VCol>
    </VRow>

    <VAlert
      v-if="errorMessage"
      type="error"
      closable
      @click:close="errorMessage = ''"
    >
      {{ errorMessage }}
    </VAlert>

    <VRow v-if="selectedAssessmentId"
          class="gy-6"
    >
      <VCol
        v-for="question in questions"
        :key="question.id"
        cols="12"
      >
        <VCard>
          <VCardTitle class="d-flex justify-space-between align-center">
            <div class="d-flex align-center gap-3">
              <span class="text-caption text-medium-emphasis">#{{ question.sequence }}</span>
              <VBtn
                variant="text"
                icon="tabler-arrow-up"
                size="x-small"
                :loading="busyQuestion === question.id"
                @click="promote(question)"
              />
              <VBtn
                variant="text"
                icon="tabler-arrow-down"
                size="x-small"
                :loading="busyQuestion === question.id"
                @click="demote(question)"
              />
            </div>
            <div class="d-flex gap-2">
              <VBtn
                variant="tonal"
                color="primary"
                size="small"
                :loading="busyQuestion === question.id"
                @click="saveQuestion(question)"
              >
                Save
              </VBtn>
              <VBtn
                variant="text"
                color="error"
                size="small"
                :loading="busyQuestion === question.id"
                @click="removeQuestion(question)"
              >
                Delete
              </VBtn>
            </div>
          </VCardTitle>
          <VCardText class="d-flex flex-column gap-4">
            <VTextField
              v-model="question.title"
              label="Question Title"
              hide-details="auto"
            />
            <VTextarea
              v-model="question.stem"
              label="Question Stem"
              auto-grow
              rows="3"
              hide-details="auto"
            />

            <div class="d-flex align-center justify-space-between">
              <h6 class="text-subtitle-1 mb-0">
                Answers
              </h6>
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

            <div class="d-flex flex-column gap-3">
              <VCard
                v-for="answer in (question.answers || []).sort((a, b) => a.sequence - b.sequence)"
                :key="answer.id"
                variant="tonal"
                class="pa-3"
              >
                <div class="d-flex flex-column flex-md-row gap-3 align-start align-md-center">
                  <div class="flex-grow-1 w-100">
                    <VTextField
                      v-model="answer.answer_text"
                      label="Answer"
                      hide-details="auto"
                    />
                  </div>
                  <VCheckbox
                    v-model="answer.correct"
                    label="Correct"
                    hide-details
                  />
                  <VTextField
                    v-model.number="answer.sequence"
                    type="number"
                    label="Seq"
                    style="max-width: 100px;"
                    hide-details="auto"
                  />
                  <div class="d-flex gap-2">
                    <VBtn
                      variant="tonal"
                      color="primary"
                      size="small"
                      :loading="busyAnswer === answer.id"
                      @click="saveAnswer(answer)"
                    >
                      Save
                    </VBtn>
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
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VAlert
      v-else
      type="info"
    >
      Create an assessment first to add questions.
    </VAlert>
  </div>
</template>
