<script setup>
// Handles admin backup downloads with blob responses and user-facing status messaging.
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAssessmentsStore } from '@/stores/assessments'
import { useAuthStore } from '@/stores/auth'
import { useOffline } from '@/composables/useOffline'
import { useApi } from '@/composables/useApi'
import ErrorNotice from '@/components/ErrorNotice.vue'
import { getErrorMessage } from '@/utils/apiError'
import { fetchBlobOrThrow } from '@/utils/http'
import { getAssessmentPageCount, paginateAssessments } from '@/utils/assessmentsPagination'
import { isAssessmentLocked } from '@/utils/assessmentLocking'
import { filterAssessments } from '@/utils/assessmentFilters'
import { sortAssessments } from '@/utils/assessmentSorting'
import { clearDeleteDialog } from '@/utils/deleteDialog'
import { parseFilenameFromDisposition } from '@/utils/contentDisposition'
import { defaultPageSizeOptions } from '@/utils/pageSizeOptions'
import { shouldResetPageOnPageSizeChange, shouldResetPageOnToggle } from '@/utils/paginationReset'
import { getActiveFilterLabel } from '@/utils/assessmentFilterLabels'
import { formatScheduledDate } from '@/utils/dateLabels'

const router = useRouter()
const authStore = useAuthStore()
const assessmentsStore = useAssessmentsStore()
const api = useApi

const assessments = computed(() => assessmentsStore.assessments ?? [])
const totalAssessments = computed(() => assessments.value.length)
const searchTerm = ref('')
const showEditableOnly = ref(false)
const activeFilter = ref('all') // all | active | inactive
const loaded = ref(false)
const shortLinkConfig = ref({ bitly: false, tinyurl: false })

const shortLinkProvider = computed({
  get: () => assessmentsStore.shortLinkProvider,
  set: value => assessmentsStore.setShortLinkProvider(value),
})

const showShortLinkToggle = computed(() => shortLinkConfig.value.bitly && shortLinkConfig.value.tinyurl)
const pageSizeOptions = defaultPageSizeOptions
const pageSize = ref(10)
const currentPage = ref(1)

watch(pageSize, (next, prev) => {
  if (shouldResetPageOnPageSizeChange(prev, next))
    currentPage.value = 1
})

watch(showEditableOnly, (next, prev) => {
  if (shouldResetPageOnToggle(prev, next))
    currentPage.value = 1
})

const filteredAssessments = computed(() => {
  return filterAssessments(assessments.value, {
    term: searchTerm.value,
    activeFilter: activeFilter.value,
    showEditableOnly: showEditableOnly.value,
  })
})

const sortState = ref({
  key: 'title',
  direction: 'asc', // 'asc' | 'desc'
})

const sortedAssessments = computed(() => {
  return sortAssessments(filteredAssessments.value, sortState.value.key, sortState.value.direction)
})

const toggleSort = key => {
  if (sortState.value.key === key) {
    sortState.value.direction = sortState.value.direction === 'asc' ? 'desc' : 'asc'
  } else {
    sortState.value.key = key
    sortState.value.direction = 'asc'
  }
}

const pageCount = computed(() => getAssessmentPageCount(filteredAssessments.value.length, pageSize.value))
const paginatedAssessments = computed(() => paginateAssessments(sortedAssessments.value, pageSize.value, currentPage.value))

const isAdmin = computed(() => {
  const role = authStore.user?.role
  const normalized = role === 'poobah' ? 'admin' : role
  
  return normalized === 'admin'
})

const listNeedsRefresh = computed(() => {
  const message = getErrorMessage(assessmentsStore.error, '')
  
  return String(message).includes('Session expired')
})

const backupLoading = ref(false)
const backupError = ref('')
const backupSuccess = ref('')
const backupAuthNeeded = ref(false)
const backupSlow = ref(false)
let backupSlowTimer = null
const assessmentsActionError = ref('')
const { isOffline } = useOffline()

const downloadBackup = async () => {
  if (isOffline.value) {
    backupError.value = 'You are offline. Connect to the internet and try again.'
    
    return
  }
  backupError.value = ''
  backupSuccess.value = ''
  backupAuthNeeded.value = false
  backupSlow.value = false
  backupLoading.value = true
  if (backupSlowTimer)
    clearTimeout(backupSlowTimer)
  backupSlowTimer = setTimeout(() => {
    if (backupLoading.value)
      backupSlow.value = true
  }, 6000)
  try {
    await authStore.ensureSession()
    if (!authStore.user)
      throw new Error('You must be logged in as an admin to download a backup.')

    const { blob, response } = await fetchBlobOrThrow('/api/admin/backup/download', {
      method: 'GET',
    })

    const contentType = response.headers.get('content-type') || ''
    if (contentType.includes('html'))
      throw new Error('Received HTML instead of backup; check authentication and API route.')
    backupSlow.value = false
    backupSuccess.value = 'Downloading SQL backup\n'
      + 'To avoid accidental overwrites, this application does not have a "restore database" function.\n'
      + 'If you ever have to use the backup, you\'ll need to interact with the database directly '
      + '(e.g., PHPMyAdmin, MySQL CLI, Adminer, DBeaver, pgAdmin).'

    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    const contentDisposition = response.headers.get('content-disposition') || ''
    const filename = parseFilenameFromDisposition(contentDisposition, 'db-backup.sql.gz')

    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    if (err?.status === 401 || err?.status === 403)
      backupAuthNeeded.value = true
    backupError.value = getErrorMessage(err, 'Unable to download backup')
  } finally {
    backupLoading.value = false
    if (backupSlowTimer) {
      clearTimeout(backupSlowTimer)
      backupSlowTimer = null
    }
  }
}

watch(filteredAssessments, () => {
  currentPage.value = 1
})

const activeFilterLabel = computed(() => {
  return getActiveFilterLabel(activeFilter.value)
})

const formatScheduled = value => formatScheduledDate(value)

const loadShortLinkConfig = async () => {
  try {
    const { data, error } = await api('/shortlink-providers', { method: 'GET' })
    if (error.value)
      throw error.value
    const bitly = !!data.value?.bitly
    const tinyurl = !!data.value?.tinyurl

    shortLinkConfig.value = { bitly, tinyurl }
    if (bitly && !tinyurl)
      assessmentsStore.setShortLinkProvider('bitly')
    if (tinyurl && !bitly)
      assessmentsStore.setShortLinkProvider('tinyurl')
  }
  catch (err) {
    // ignore config fetch errors
  }
}

onMounted(async () => {
  try {
    await loadShortLinkConfig()
    await assessmentsStore.fetchAssessments()
  }
  catch (err) {
    // error state already stored on the store
  }
  finally {
    loaded.value = true
  }
})

const addNewAssessment = async () => {
  assessmentsActionError.value = ''
  if (isOffline.value) {
    assessmentsActionError.value = 'You are offline. Connect to the internet and try again.'
    
    return
  }
  try {
    const created = await assessmentsStore.createAssessment({
      title: 'New gRAT',
      active: true,
    })

    if (created?.id)
      router.push({ name: 'assessments-id', params: { id: created.id } })
  }
  catch (err) {
    assessmentsActionError.value = getErrorMessage(err, 'Unable to create assessment')
  }
}

const goToEdit = assessment => {
  router.push({ name: 'assessments-id', params: { id: assessment.id } })
}

const hasResponses = assessment => isAssessmentLocked(assessment)

const goToPassword = assessment => {
  router.push({ name: 'assessment-password', params: { id: assessment.id } })
}

const goToProgress = assessment => {
  router.push({ name: 'assessment-progress', params: { id: assessment.id } })
}

const goToFeedback = assessment => {
  router.push({ name: 'assessment-feedback', params: { id: assessment.id } })
}

const goToScores = assessment => {
  router.push({ name: 'assessment-scores', params: { id: assessment.id } })
}

const isLocked = assessment => isAssessmentLocked(assessment)

const showDeleteConfirm = ref(false)
const pendingDelete = ref(null)

const startDelete = assessment => {
  pendingDelete.value = assessment
  showDeleteConfirm.value = true
}

const cancelDelete = () => {
  clearDeleteDialog(pendingDelete, showDeleteConfirm)
}

const performDelete = async () => {
  if (!pendingDelete.value)
    return
  if (isOffline.value) {
    assessmentsActionError.value = 'You are offline. Connect to the internet before deleting.'
    
    return
  }
  await assessmentsStore.deleteAssessment(pendingDelete.value.id)
  cancelDelete()
}

const togglingActiveId = ref(null)

const toggleActive = async assessment => {
  if (!assessment?.id)
    return
  if (togglingActiveId.value === assessment.id)
    return
  if (isOffline.value) {
    assessmentsActionError.value = 'You are offline. Connect to the internet before updating the assessment.'
    
    return
  }
  togglingActiveId.value = assessment.id
  assessmentsActionError.value = ''
  try {
    await assessmentsStore.updateAssessment({
      id: assessment.id,
      title: assessment.title,
      time_limit: assessment.time_limit,
      course: assessment.course,
      penalty_method: assessment.penalty_method,
      active: !assessment.active,
      scheduled_at: assessment.scheduled_at,
      memo: assessment.memo,
    })
  } catch (err) {
    assessmentsActionError.value = getErrorMessage(err, 'Unable to update assessment status')
  } finally {
    togglingActiveId.value = null
  }
}
</script>

<template>
  <div class="assessments-page">
    <VCard class="data-card"
           elevation="3"
    >
      <div class="data-card__header">
        <div class="d-flex flex-column gap-1">
          <p class="overline mb-1 text-secondary">
            gRATs
          </p>
          <div class="d-flex align-center gap-2 flex-wrap">
            <VChip
              color="secondary"
              variant="tonal"
              size="small"
              class="text-uppercase"
            >
              {{ filteredAssessments.length }} of {{ totalAssessments }} total
            </VChip>
          </div>
        </div>
        <div class="d-flex flex-wrap align-center gap-3 justify-end">
          <VTooltip v-if="isAdmin"
                    location="top"
          >
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                variant="text"
                color="secondary"
                prepend-icon="tabler-download"
                :loading="backupLoading"
                :disabled="isOffline"
                @click="downloadBackup"
              >
                DB backup
              </VBtn>
            </template>
            <span>Large databases may take a few moments to download.</span>
          </VTooltip>
          <VBtn
            variant="outlined"
            color="primary"
            class="btn-outline text-none"
            prepend-icon="tabler-plus"
            :disabled="isOffline"
            @click="addNewAssessment"
          >
            Add New gRAT
          </VBtn>
        </div>
      </div>

      <ErrorNotice
        v-if="assessmentsStore.error"
        :message="getErrorMessage(assessmentsStore.error, '')"
        :show-refresh="listNeedsRefresh"
        @close="assessmentsStore.error = null"
        @retry="assessmentsStore.fetchAssessments"
        @refresh="() => window.location.reload()"
      />
      <VAlert
        v-if="assessmentsActionError"
        type="error"
        closable
        class="mb-4"
        density="comfortable"
        @click:close="assessmentsActionError = ''"
      >
        {{ assessmentsActionError }}
      </VAlert>
      <VAlert
        v-if="backupError"
        type="error"
        closable
        class="mb-4 backup-error-alert"
        density="comfortable"
        @click:close="backupError = ''"
      >
        <div class="d-flex flex-column gap-2">
          <div>{{ backupError }}</div>
          <div class="d-flex flex-wrap gap-2">
            <VBtn variant="text"
                  color="primary"
                  size="small"
                  @click="downloadBackup"
            >
              Retry
            </VBtn>
            <VBtn
              v-if="backupAuthNeeded"
              variant="text"
              color="secondary"
              size="small"
              @click="() => router.push({ name: 'login' })"
            >
              Sign in
            </VBtn>
          </div>
        </div>
      </VAlert>
      <VAlert
        v-if="backupSlow"
        type="info"
        variant="tonal"
        class="mb-4"
        density="comfortable"
        closable
        @click:close="backupSlow = false"
      >
        This backup is taking longer than usual. It will download automatically once ready.
      </VAlert>
      <VAlert
        v-if="backupSuccess"
        type="success"
        closable
        class="mb-4 backup-success-alert"
        density="comfortable"
        @click:close="backupSuccess = ''"
      >
        {{ backupSuccess }}
      </VAlert>

      <div class="data-card__table">
        <div class="filters-row d-flex flex-wrap align-center gap-3 mb-3 sticky-filters">
          <VTextField
            v-model="searchTerm"
            density="compact"
            hide-details
            placeholder="Search assessments"
            prepend-inner-icon="tabler-search"
            style="max-width: 260px;"
          />
          <div class="d-flex gap-2 align-center flex-wrap">
            <span class="text-caption text-medium-emphasis">Filters:</span>
            <VChip
              label
              :color="activeFilter === 'all' && !showEditableOnly ? 'primary' : 'default'"
              variant="tonal"
              size="small"
              @click="() => { activeFilter = 'all'; showEditableOnly = false }"
            >
              All
            </VChip>
            <VChip
              label
              :color="activeFilter === 'active' ? 'primary' : 'default'"
              variant="tonal"
              size="small"
              @click="activeFilter = 'active'"
            >
              Active
            </VChip>
            <VChip
              label
              :color="activeFilter === 'inactive' ? 'primary' : 'default'"
              variant="tonal"
              size="small"
              @click="activeFilter = 'inactive'"
            >
              Inactive
            </VChip>
          </div>
          <VChip
            label
            :color="showEditableOnly ? 'primary' : 'default'"
            variant="tonal"
            size="small"
            @click="showEditableOnly = !showEditableOnly"
          >
            Editable
          </VChip>
          <div
            v-if="showShortLinkToggle"
            class="shortlink-spacer"
            aria-hidden="true"
          />
          <div v-if="showShortLinkToggle"
               class="shortlink-toggle d-flex align-center"
          >
            <div class="shortlink-label">
              <span class="text-caption text-medium-emphasis">Short Links</span>
            </div>
            <VChipGroup
              v-model="shortLinkProvider"
              class="shortlink-group ms-2"
              mandatory
            >
              <VChip
                value="bitly"
                filter
                variant="outlined"
                size="small"
                :color="shortLinkProvider === 'bitly' ? 'primary' : 'default'"
              >
                Bitly
              </VChip>
              <VChip
                value="tinyurl"
                filter
                variant="outlined"
                size="small"
                :color="shortLinkProvider === 'tinyurl' ? 'primary' : 'default'"
              >
                TinyURL
              </VChip>
            </VChipGroup>
          </div>
        </div>
        <VTable class="elevated-table">
          <thead>
            <tr>
              <th>
                <button class="sort-btn"
                        @click="toggleSort('title')"
                >
                  Title
                  <VIcon
                    v-if="sortState.key === 'title'"
                    :icon="sortState.direction === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                    size="16"
                    class="ms-1"
                  />
                </button>
              </th>
              <th class="text-no-wrap">
                <button class="sort-btn"
                        @click="toggleSort('course')"
                >
                  Course
                  <VIcon
                    v-if="sortState.key === 'course'"
                    :icon="sortState.direction === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                    size="16"
                    class="ms-1"
                  />
                </button>
              </th>
              <th v-if="isAdmin"
                  class="text-no-wrap"
              >
                <button class="sort-btn"
                        @click="toggleSort('owner')"
                >
                  Owner
                  <VIcon
                    v-if="sortState.key === 'owner'"
                    :icon="sortState.direction === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                    size="16"
                    class="ms-1"
                  />
                </button>
              </th>
              <th class="text-no-wrap text-center actions-col">
                <button class="sort-btn"
                        @click="toggleSort('actions')"
                >
                  Actions
                  <VIcon
                    v-if="sortState.key === 'actions'"
                    :icon="sortState.direction === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                    size="16"
                    class="ms-1"
                  />
                </button>
              </th>
              <th class="text-no-wrap">
                <button class="sort-btn"
                        @click="toggleSort('scheduled_at')"
                >
                  Scheduled
                  <VIcon
                    v-if="sortState.key === 'scheduled_at'"
                    :icon="sortState.direction === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                    size="16"
                    class="ms-1"
                  />
                </button>
              </th>
              <th class="text-no-wrap">
                <button class="sort-btn"
                        @click="toggleSort('active')"
                >
                  Active
                  <VIcon
                    v-if="sortState.key === 'active'"
                    :icon="sortState.direction === 'asc' ? 'tabler-caret-up' : 'tabler-caret-down'"
                    size="16"
                    class="ms-1"
                  />
                </button>
              </th>
              <th class="text-no-wrap url-col text-center">
                URL
              </th>
              <th class="text-no-wrap header-sentence">
                Progress
              </th>
              <th class="text-no-wrap header-sentence">
                Feedback
              </th>
              <th class="text-no-wrap header-sentence">
                Scores
              </th>
              <th class="text-no-wrap text-right header-sentence">
                Delete
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="assessment in paginatedAssessments"
              :key="assessment.id"
              :class="[{ 'locked-row': isLocked(assessment) }]"
            >
              <td class="font-weight-medium"
                  data-label="Title"
              >
                <VTooltip v-if="assessment.memo || assessment.details"
                          location="top"
                >
                  <template #activator="{ props }">
                    <span v-bind="props">{{ assessment.title }}</span>
                  </template>
                  <span>{{ assessment.memo || assessment.details }}</span>
                </VTooltip>
                <span v-else>{{ assessment.title }}</span>
              </td>
              <td class="text-medium-emphasis"
                  data-label="Course"
              >
                <span>{{ assessment.course }}</span>
              </td>
              <td v-if="isAdmin"
                  data-label="Owner"
              >
                {{ assessment.owner_username || '—' }}
              </td>
              <td class="text-center actions-col"
                  data-label="Actions"
              >
                <div class="d-inline-flex gap-2 justify-center">
                  <VTooltip location="top">
                    <template #activator="{ props }">
                      <VBtn
                        v-bind="props"
                        size="small"
                        :variant="isLocked(assessment) ? 'text' : 'tonal'"
                        :color="isLocked(assessment) ? 'secondary' : 'primary'"
                        :title="`Questions: ${assessment.questions?.length ?? 0} | Presentations: ${assessment.presentations_count ?? 0}`"
                        @click="goToEdit(assessment)"
                      >
                        <VIcon
                          v-if="isLocked(assessment)"
                          icon="tabler-lock"
                          size="16"
                          class="me-1"
                        />
                        {{ isLocked(assessment) ? 'View' : 'Edit' }}
                      </VBtn>
                    </template>
                    <span>
                      {{ isLocked(assessment)
                        ? 'Cannot change gRAT. Students have left answers'
                        : 'Change the gRAT' }}
                    </span>
                  </VTooltip>
                </div>
              </td>
              <td class="text-medium-emphasis"
                  data-label="Scheduled"
              >
                {{ formatScheduled(assessment.scheduled_at) }}
              </td>
              <td data-label="Active">
                <VTooltip location="top">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      size="small"
                      variant="tonal"
                      :color="assessment.active ? 'success' : 'secondary'"
                      :loading="togglingActiveId === assessment.id"
                      :disabled="isOffline"
                      @click="toggleActive(assessment)"
                    >
                      {{ assessment.active ? 'Active' : 'Inactive' }}
                    </VBtn>
                  </template>
                  <span>
                    {{ assessment.active ? 'Students can take gRAT' : 'Students cannot take gRAT' }}
                  </span>
                </VTooltip>
              </td>
              <td class="url-col"
                  data-label="URL"
              >
                <VTooltip location="top">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      size="small"
                      variant="text"
                      color="primary"
                      class="text-capitalize"
                      @click="goToPassword(assessment)"
                    >
                      URL
                    </VBtn>
                  </template>
                  <span>Display link to gRAT</span>
                </VTooltip>
              </td>
              <td data-label="Progress">
                <VTooltip location="top">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      size="small"
                      variant="text"
                      color="success"
                      class="text-capitalize"
                      @click="goToProgress(assessment)"
                    >
                      Progress
                    </VBtn>
                  </template>
                  <span>How close groups are to being done</span>
                </VTooltip>
              </td>
              <td data-label="Feedback">
                <VTooltip location="top">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      size="small"
                      variant="text"
                      color="warning"
                      class="text-capitalize"
                      @click="goToFeedback(assessment)"
                    >
                      Feedback
                    </VBtn>
                  </template>
                  <span>Review answers with students</span>
                </VTooltip>
              </td>
              <td data-label="Scores">
                <VBtn
                  size="small"
                  variant="text"
                  color="error"
                  class="text-capitalize"
                  @click="goToScores(assessment)"
                >
                  Scores
                </VBtn>
              </td>
              <td class="text-right"
                  data-label="Delete"
              >
                <VBtn
                  v-if="!isLocked(assessment)"
                  size="small"
                  variant="text"
                  color="error"
                  prepend-icon="tabler-trash"
                  :disabled="isOffline"
                  @click="startDelete(assessment)"
                >
                  Delete
                </VBtn>
                <VTooltip v-else
                          location="top"
                >
                  <template #activator="{ props }">
                    <span
                      v-bind="props"
                      class="text-disabled text-body-2"
                    >
                      Locked
                    </span>
                  </template>
                  <span>Cannot delete. Students have submitted answers.</span>
                </VTooltip>
              </td>
            </tr>
            <tr v-if="!loaded && !assessments.length">
              <td :colspan="isAdmin ? 11 : 10"
                  class="text-center py-8"
              >
                <VProgressCircular indeterminate
                                   color="primary"
                                   size="36"
                                   class="mb-2"
                />
                <div class="text-medium-emphasis">
                  Loading assessments…
                </div>
              </td>
            </tr>
            <tr v-else-if="!assessments.length">
              <td :colspan="isAdmin ? 11 : 10"
                  class="text-center py-6"
              >
                No assessments yet. Demo assessments are seeded: passwords <strong>demo-assessment</strong> and <strong>demo-formatting</strong>.
              </td>
            </tr>
            <tr v-else-if="!paginatedAssessments.length">
              <td :colspan="isAdmin ? 11 : 10"
                  class="text-center py-6"
              >
                No results match your filters.
                <VBtn variant="text"
                      color="primary"
                      class="ms-2"
                      @click="() => { searchTerm = ''; activeFilter = 'all'; showEditableOnly = false }"
                >
                  Clear filters
                </VBtn>
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>

      <div class="pagination-row">
        <div class="d-flex flex-wrap align-center gap-3 w-100 justify-space-between">
          <div class="text-body-2 text-medium-emphasis">
            Showing
            <strong>{{ (currentPage - 1) * (pageSize === 'all' ? paginatedAssessments.length : pageSize) + 1 }}</strong>
            –
            <strong>{{ (currentPage - 1) * (pageSize === 'all' ? paginatedAssessments.length : pageSize) + paginatedAssessments.length }}</strong>
            of
            <strong>{{ filteredAssessments.length }}</strong>
          </div>
          <div class="d-flex align-center gap-2">
            <span class="text-caption text-medium-emphasis">Rows per page:</span>
            <VSelect
              v-model="pageSize"
              :items="pageSizeOptions"
              item-title="label"
              item-value="value"
              density="compact"
              hide-details
              style="max-width: 120px;"
            />
            <VPagination
              v-if="pageSize !== 'all'"
              v-model="currentPage"
              :length="pageCount"
              total-visible="5"
            />
          </div>
        </div>
      </div>
    </VCard>

    <VDialog
      v-model="showDeleteConfirm"
      max-width="420"
      persistent
    >
      <VCard>
        <VCardTitle class="text-h6">
          Are you really sure?
        </VCardTitle>
        <VCardText>
          This will permanently delete
          <strong>{{ pendingDelete?.title || 'this assessment' }}</strong>.
          This cannot be undone.
        </VCardText>
        <VCardActions class="justify-end gap-2">
          <VBtn
            variant="text"
            color="secondary"
            @click="cancelDelete"
          >
            Cancel
          </VBtn>
          <VBtn
            color="error"
            @click="performDelete"
          >
            Delete
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.assessments-page {
  width: 100%;
  max-width: none;
  padding: 70px 16px 32px; /* extra top padding to clear the menu */
}

.assessments-page .overline {
  font-size: 15px;
  letter-spacing: 0.08em;
}

.data-card {
  border-radius: 12px;
  margin-top: 8px;
}

.data-card__header {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  flex-wrap: wrap;
  padding: 20px 20px 6px;
}

.data-card__table {
  padding: 0 12px 20px;
}

.search-row {
  padding: 0 20px 12px;
}

.controls-row {
  padding: 0 20px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.btn-outline {
  border-style: dashed;
}

.text-none {
  text-transform: none;
}

.backup-error-alert {
  white-space: pre-line;
}

.backup-success-alert {
  white-space: pre-line;
}

.elevated-table table {
  border-collapse: separate;
  border-spacing: 0 12px;
}

.elevated-table tbody tr {
  box-shadow: 0 12px 22px rgba(0, 0, 0, 0.06);
  border-radius: 10px;
  overflow: hidden;
}

.elevated-table tbody td {
  background: #fff;
  padding: 14px 16px;
}

.elevated-table tbody td:first-child {
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
}

.elevated-table tbody td:last-child {
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
}

.elevated-table thead th {
  font-size: 12px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(0, 0, 0, 0.54);
}

.elevated-table thead th.header-sentence {
  text-transform: none;
  letter-spacing: 0;
}

.pagination-row {
  padding: 8px 20px 16px;
  display: flex;
  justify-content: flex-end;
}

.sort-btn {
  background: transparent;
  border: none;
  color: inherit;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font: inherit;
  padding: 0;
  cursor: pointer;
}

.sort-btn:focus-visible {
  outline: 2px solid rgba(0, 0, 0, 0.2);
  border-radius: 4px;
}

.actions-col {
  width: 140px;
}

.url-col {
  width: 120px;
  text-align: center;
}

.url-col .v-btn {
  margin: 0 auto;
}

.filters-row {
  padding: 0 20px;
}

.shortlink-spacer {
  flex: 0 0 220px;
}

.shortlink-toggle {
  gap: 12px;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: rgba(0, 0, 0, 0.02);
}

.shortlink-label {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.shortlink-group {
  display: flex;
  gap: 6px;
}

.locked-row {
  background: rgba(59, 130, 246, 0.04);
}

.elevated-table tbody td[data-label="Title"],
.elevated-table tbody td[data-label="Course"],
.elevated-table tbody td[data-label="Owner"],
.elevated-table tbody td[data-label="Scheduled"] {
  font-size: 0.75rem;
}

.sticky-filters {
  position: sticky;
  top: 0;
  z-index: 4;
  background: white;
  padding-top: 8px;
  padding-bottom: 8px;
}

/* Responsive collapse for the table */
@media (max-width: 1024px) {
  .elevated-table thead {
    border: none;
    clip: rect(0 0 0 0);
    height: 1px;
    margin: -1px;
    overflow: hidden;
    padding: 0;
    position: absolute;
    width: 1px;
  }

  .sort-btn {
    pointer-events: none;
  }

  .elevated-table tbody tr {
    display: block;
    margin-bottom: 10px;
    border-bottom: 3px solid #ddd;
    padding: 8px 0;
  }

  .elevated-table tbody td {
    display: block;
    width: 100%;
    border-bottom: 1px solid #ddd;
    padding-top: 10px;
    padding-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .elevated-table tbody td::before {
    content: attr(data-label);
    float: left;
    font-weight: 600;
    text-transform: uppercase;
    text-align: left;
    margin-right: 10px;
  }

  .elevated-table tbody td[data-label="Title"]::before,
  .elevated-table tbody td[data-label="Course"]::before,
  .elevated-table tbody td[data-label="Scheduled"]::before {
    font-size: 0.75rem;
  }

  .elevated-table tbody td[data-label="Progress"]::before,
  .elevated-table tbody td[data-label="Feedback"]::before,
  .elevated-table tbody td[data-label="Scores"]::before,
  .elevated-table tbody td[data-label="Delete"]::before {
    text-transform: none;
    letter-spacing: 0;
  }

  .elevated-table tbody td:last-child {
    border-bottom: 0;
  }

  /* In collapsed view, keep URL column aligned with other values on the right */
  .url-col {
    text-align: right;
  }

  .url-col .v-btn {
    margin: 0 0 0 auto;
  }
}
</style>
