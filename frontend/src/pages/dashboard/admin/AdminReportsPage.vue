<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAdminReports } from '@/composables/useAdminReports'
import { useNotificationStore } from '@/stores/notificationStore'
import {
  CONTENT_TYPE_FILTER_OPTIONS,
  DECISION_NOTE_MAX_LENGTH,
  REPORT_OUTCOME,
  REPORT_REASON_FILTER_OPTIONS,
  REPORT_STATUS,
  REPORT_STATUS_FILTER_OPTIONS,
  REPORT_STATUS_LABELS,
  REPORT_TARGET_LABELS,
} from '@/constants/reporting'
import PageHeader from '@/components/molecules/PageHeader.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppModal from '@/components/molecules/AppModal.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import FormField from '@/components/molecules/FormField.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import AdminReportDetail from '@/components/organisms/AdminReportDetail.vue'

const DECISION_COPY = {
  reviewing: {
    title: 'Mark as reviewing?',
    confirmText: 'Mark reviewing',
    message: (id) => `Move report #${id} to reviewing? The queue entry stays open.`,
  },
  resolved_hidden: {
    title: 'Resolve and hide content?',
    confirmText: 'Resolve and hide',
    message: (id) =>
      `Resolve report #${id} and hide its content? Hiding is reversible from Content.`,
  },
  resolved_no_action: {
    title: 'Resolve with no action?',
    confirmText: 'Resolve report',
    message: (id) => `Resolve report #${id} without changing the content?`,
  },
  dismissed: {
    title: 'Dismiss this report?',
    confirmText: 'Dismiss report',
    message: (id) => `Dismiss report #${id}? Dismissed reports stay closed.`,
  },
}

const notificationStore = useNotificationStore()
const {
  list,
  loading,
  error,
  mutating,
  filters,
  pagination,
  selected,
  detailLoading,
  detailError,
  fetchReports,
  fetchReport,
  clearSelection,
  decide,
} = useAdminReports()

const selectedId = ref(null)
const pendingDecision = ref(null)
const noteDraft = ref('')
const noteError = ref('')
const dialogError = ref('')
const conflictVersion = ref(null)

const reports = computed(() =>
  (Array.isArray(list.value) ? list.value : []).filter(
    (entry) => entry && typeof entry === 'object',
  ),
)

const dialogCopy = computed(() => {
  if (!pendingDecision.value) return null
  const { status, outcome } = pendingDecision.value
  if (status === REPORT_STATUS.REVIEWING) return DECISION_COPY.reviewing
  if (status === REPORT_STATUS.DISMISSED) return DECISION_COPY.dismissed
  return outcome === REPORT_OUTCOME.HIDDEN
    ? DECISION_COPY.resolved_hidden
    : DECISION_COPY.resolved_no_action
})

function typeLabel(report) {
  return REPORT_TARGET_LABELS[report?.reportable_type] ?? 'Content'
}

function statusLabel(report) {
  return REPORT_STATUS_LABELS[report?.status] ?? report?.status ?? 'Unknown'
}

function summaryTitle(report) {
  return report?.target_snapshot?.title || `${typeLabel(report)} #${report?.reportable_id ?? '—'}`
}

function onFilterChange() {
  clearSelection()
  selectedId.value = null
  fetchReports(1)
}

function resetFilters() {
  filters.value = { status: '', type: '', reason: '' }
  clearSelection()
  selectedId.value = null
  fetchReports(1)
}

function retryFetch() {
  fetchReports(pagination.value.currentPage)
}

function handlePageChange(page) {
  fetchReports(page)
}

async function selectReport(id) {
  selectedId.value = id
  pendingDecision.value = null
  await fetchReport(id)
}

function closeDetail() {
  selectedId.value = null
  pendingDecision.value = null
  clearSelection()
}

function askDecision({ status, outcome }) {
  if (!selected.value || mutating.value) return
  pendingDecision.value = { status, outcome, reportId: selected.value.id }
  noteDraft.value = ''
  noteError.value = ''
  dialogError.value = ''
  conflictVersion.value = null
}

function cancelDialog() {
  if (!mutating.value) pendingDecision.value = null
}

function validatedNote() {
  const note = noteDraft.value.trim()
  if (!note) {
    noteError.value = 'Enter a note for this decision.'
    return null
  }
  if (note.length > DECISION_NOTE_MAX_LENGTH) {
    noteError.value = `Note must be ${DECISION_NOTE_MAX_LENGTH} characters or fewer.`
    return null
  }
  noteError.value = ''
  return note
}

async function submitDecision() {
  if (!pendingDecision.value || mutating.value || !selected.value) return
  const note = validatedNote()
  if (note === null) return
  dialogError.value = ''
  conflictVersion.value = null
  try {
    const { status, outcome } = pendingDecision.value
    await decide(selected.value.id, {
      status,
      outcome,
      note,
      expected_version: selected.value.version,
    })
    pendingDecision.value = null
    noteDraft.value = ''
    notificationStore.success('Report decision recorded.')
  } catch (err) {
    if (err?.isVersionConflict) {
      conflictVersion.value = err.currentVersion
      dialogError.value =
        'This report changed since it was loaded. Reload for the latest version, then retry. Your note is kept.'
    } else {
      dialogError.value = err?.message || 'Unable to decide this report.'
    }
  }
}

async function reloadReport() {
  if (!selected.value || mutating.value) return
  dialogError.value = ''
  conflictVersion.value = null
  await fetchReport(selected.value.id)
}

onMounted(() => {
  fetchReports(1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Reports"
      description="Review member reports oldest first. Resolved reports stay closed."
    />

    <AppCard padding="p-5">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <AppSelect
          id="admin-report-status"
          v-model="filters.status"
          label="Status"
          @update:model-value="onFilterChange"
        >
          <option
            v-for="option in REPORT_STATUS_FILTER_OPTIONS"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </AppSelect>
        <AppSelect
          id="admin-report-type"
          v-model="filters.type"
          label="Content type"
          @update:model-value="onFilterChange"
        >
          <option
            v-for="option in CONTENT_TYPE_FILTER_OPTIONS"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </AppSelect>
        <AppSelect
          id="admin-report-reason"
          v-model="filters.reason"
          label="Reason"
          @update:model-value="onFilterChange"
        >
          <option
            v-for="option in REPORT_REASON_FILTER_OPTIONS"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </AppSelect>
        <div class="flex items-end">
          <AppButton type="button" variant="ghost" @click="resetFilters">Reset filters</AppButton>
        </div>
      </div>
    </AppCard>

    <LoadingState v-if="loading" label="Loading reports" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" @click="retryFetch">Retry</AppButton>
    </AppCard>

    <EmptyState
      v-else-if="reports.length === 0"
      title="No reports found"
      description="No reports match the current filters."
    />

    <ul v-else class="space-y-4" aria-label="Content reports">
      <li v-for="report in reports" :key="report.id">
        <AppCard padding="p-5">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <h2 class="break-words font-serif text-xl font-bold text-stone-900">
                {{ summaryTitle(report) }}
              </h2>
              <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                <span
                  class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 font-medium text-soil-800"
                >
                  {{ typeLabel(report) }}
                </span>
                <span
                  class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 font-medium text-soil-800"
                >
                  {{ statusLabel(report) }}
                </span>
              </div>
            </div>
            <div class="shrink-0 sm:w-36">
              <AppButton
                variant="outline"
                class="w-full"
                :data-testid="`report-review-${report.id}`"
                :aria-label="`Review report ${report.id}`"
                @click="selectReport(report.id)"
              >
                Review
              </AppButton>
            </div>
          </div>
        </AppCard>
      </li>
    </ul>

    <PaginationControls
      v-if="!loading && !error && reports.length > 0"
      :current-page="pagination.currentPage"
      :last-page="pagination.lastPage"
      :total="pagination.total"
      @page-change="handlePageChange"
    />

    <section v-if="selectedId !== null" aria-label="Report detail" class="space-y-4">
      <div class="flex items-center justify-between gap-3">
        <h2 class="font-serif text-2xl font-bold text-stone-900">Report #{{ selectedId }}</h2>
        <AppButton variant="ghost" @click="closeDetail">Close detail</AppButton>
      </div>

      <LoadingState v-if="detailLoading" label="Loading report" />

      <AppCard v-else-if="detailError" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ detailError }}</p>
        <AppButton variant="outline" class="mt-4" @click="selectReport(selectedId)"
          >Retry</AppButton
        >
      </AppCard>

      <AppCard v-else-if="selected" padding="p-5">
        <AdminReportDetail :report="selected" :busy="mutating" @decision="askDecision" />
      </AppCard>
    </section>

    <AppModal
      :title="dialogCopy?.title ?? 'Decide this report'"
      :is-open="pendingDecision !== null"
      :busy="mutating"
      @close="cancelDialog"
    >
      <div v-if="pendingDecision" class="space-y-5">
        <p class="text-base leading-relaxed text-stone-600">
          {{ dialogCopy.message(pendingDecision.reportId) }}
        </p>
        <AppAlert v-if="dialogError" type="error">{{ dialogError }}</AppAlert>
        <AppButton
          v-if="conflictVersion !== null"
          variant="outline"
          data-testid="report-conflict-reload"
          @click="reloadReport"
        >
          Reload report (version {{ conflictVersion }})
        </AppButton>
        <FormField
          id="report-decision-note"
          v-model="noteDraft"
          label="Decision note"
          :multiline="true"
          :maxlength="DECISION_NOTE_MAX_LENGTH"
          :required="true"
          :disabled="mutating"
          :error="noteError"
          placeholder="Why is this the right decision?"
        />
      </div>
      <template #footer>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <AppButton variant="secondary" :disabled="mutating" @click="cancelDialog">
            Cancel
          </AppButton>
          <AppButton variant="primary" :loading="mutating" @click="submitDecision">
            {{ dialogCopy?.confirmText ?? 'Confirm' }}
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>
