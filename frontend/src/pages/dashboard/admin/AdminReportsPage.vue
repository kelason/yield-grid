<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useAdminReports } from '@/composables/useAdminReports'
import { queryFilterValue } from '@/utils/adminQueryFilters'
import { useNotificationStore } from '@/stores/notificationStore'
import {
  CONTENT_TYPE_FILTER_OPTIONS,
  DECISION_NOTE_MAX_LENGTH,
  REPORT_OUTCOME,
  REPORT_REASON,
  REPORT_REASON_FILTER_OPTIONS,
  REPORT_STATUS,
  REPORT_STATUS_FILTER_OPTIONS,
  REPORT_STATUS_LABEL_KEYS,
  REPORT_TARGET_LABEL_KEYS,
  REPORT_TARGET_TYPE,
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

const notificationStore = useNotificationStore()
const { t } = useI18n()

const DECISION_COPY = {
  reviewing: {
    titleKey: 'admin.reports.modal_review_title',
    confirmKey: 'admin.reports.modal_review_cta',
    msgKey: 'admin.reports.modal_review_msg',
  },
  resolved_hidden: {
    titleKey: 'admin.reports.modal_hide_title',
    confirmKey: 'admin.reports.modal_hide_cta',
    msgKey: 'admin.reports.modal_hide_msg',
  },
  resolved_no_action: {
    titleKey: 'admin.reports.modal_noaction_title',
    confirmKey: 'admin.reports.modal_noaction_cta',
    msgKey: 'admin.reports.modal_noaction_msg',
  },
  dismissed: {
    titleKey: 'admin.reports.modal_dismiss_title',
    confirmKey: 'admin.reports.modal_dismiss_cta',
    msgKey: 'admin.reports.modal_dismiss_msg',
  },
}
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

const route = useRoute()

function seedFiltersFromQuery() {
  const query = route?.query ?? {}
  filters.value.status = queryFilterValue(query, 'status', Object.values(REPORT_STATUS))
  filters.value.type = queryFilterValue(query, 'type', Object.values(REPORT_TARGET_TYPE))
  filters.value.reason = queryFilterValue(query, 'reason', Object.values(REPORT_REASON))
}

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
  let copy
  if (status === REPORT_STATUS.REVIEWING) copy = DECISION_COPY.reviewing
  else if (status === REPORT_STATUS.DISMISSED) copy = DECISION_COPY.dismissed
  else
    copy =
      outcome === REPORT_OUTCOME.HIDDEN
        ? DECISION_COPY.resolved_hidden
        : DECISION_COPY.resolved_no_action
  return {
    title: t(copy.titleKey),
    confirmText: t(copy.confirmKey),
    message: (id) => t(copy.msgKey, { id }),
  }
})

const statusOptions = computed(() =>
  REPORT_STATUS_FILTER_OPTIONS.map((option) => ({
    value: option.value,
    label: t(option.labelKey),
  })),
)

const typeOptions = computed(() =>
  CONTENT_TYPE_FILTER_OPTIONS.map((option) => ({ value: option.value, label: t(option.labelKey) })),
)

const reasonOptions = computed(() =>
  REPORT_REASON_FILTER_OPTIONS.map((option) => ({
    value: option.value,
    label: option.labelKey ? t(option.labelKey) : option.label,
  })),
)

function typeLabel(report) {
  return t(REPORT_TARGET_LABEL_KEYS[report?.reportable_type] ?? 'admin.reports.content_fallback')
}

function statusLabel(report) {
  const labelKey = REPORT_STATUS_LABEL_KEYS[report?.status]
  return labelKey ? t(labelKey) : (report?.status ?? t('admin.reports.unknown'))
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
    noteError.value = t('admin.reports.err_note')
    return null
  }
  if (note.length > DECISION_NOTE_MAX_LENGTH) {
    noteError.value = t('admin.reports.err_note_max', { max: DECISION_NOTE_MAX_LENGTH })
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
    notificationStore.addNotification({ type: 'success', messageKey: 'admin.reports.decided' })
  } catch (err) {
    if (err?.isVersionConflict) {
      conflictVersion.value = err.currentVersion
      dialogError.value = t('admin.reports.conflict')
    } else {
      dialogError.value = err?.message || t('admin.reports.err_decide')
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
  seedFiltersFromQuery()
  fetchReports(1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="t('admin.reports.title')" :description="t('admin.reports.description')" />

    <AppCard padding="p-5">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <AppSelect
          id="admin-report-status"
          v-model="filters.status"
          :label="t('admin.reports.status_label')"
          @update:model-value="onFilterChange"
        >
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </AppSelect>
        <AppSelect
          id="admin-report-type"
          v-model="filters.type"
          :label="t('admin.reports.type_label')"
          @update:model-value="onFilterChange"
        >
          <option v-for="option in typeOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </AppSelect>
        <AppSelect
          id="admin-report-reason"
          v-model="filters.reason"
          :label="t('admin.reports.reason_label')"
          @update:model-value="onFilterChange"
        >
          <option v-for="option in reasonOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </AppSelect>
        <div class="flex items-end">
          <AppButton type="button" variant="ghost" @click="resetFilters">{{
            t('admin.reports.reset')
          }}</AppButton>
        </div>
      </div>
    </AppCard>

    <LoadingState v-if="loading" :label="t('admin.reports.loading')" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" @click="retryFetch">{{
        t('shell.retry')
      }}</AppButton>
    </AppCard>

    <EmptyState
      v-else-if="reports.length === 0"
      :title="t('admin.reports.empty_title')"
      :description="t('admin.reports.empty_desc')"
    />

    <ul v-else class="space-y-4" :aria-label="t('admin.reports.list_aria')">
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
                :aria-label="t('admin.reports.review_aria', { id: report.id })"
                @click="selectReport(report.id)"
              >
                {{ t('admin.reports.review') }}
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

    <section
      v-if="selectedId !== null"
      :aria-label="t('admin.reports.detail_aria')"
      class="space-y-4"
    >
      <div class="flex items-center justify-between gap-3">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('admin.reports.detail_title', { id: selectedId }) }}
        </h2>
        <AppButton variant="ghost" @click="closeDetail">{{
          t('admin.reports.close_detail')
        }}</AppButton>
      </div>

      <LoadingState v-if="detailLoading" :label="t('admin.reports.detail_loading')" />

      <AppCard v-else-if="detailError" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ detailError }}</p>
        <AppButton variant="outline" class="mt-4" @click="selectReport(selectedId)">{{
          t('shell.retry')
        }}</AppButton>
      </AppCard>

      <AppCard v-else-if="selected" padding="p-5">
        <AdminReportDetail :report="selected" :busy="mutating" @decision="askDecision" />
      </AppCard>
    </section>

    <AppModal
      :title="dialogCopy?.title ?? t('admin.reports.modal_fallback')"
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
          {{ t('admin.reports.reload', { version: conflictVersion }) }}
        </AppButton>
        <FormField
          id="report-decision-note"
          v-model="noteDraft"
          :label="t('admin.reports.note_label')"
          :multiline="true"
          :maxlength="DECISION_NOTE_MAX_LENGTH"
          :required="true"
          :disabled="mutating"
          :error="noteError"
          :placeholder="t('admin.reports.note_ph')"
        />
      </div>
      <template #footer>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <AppButton variant="secondary" :disabled="mutating" @click="cancelDialog">
            {{ t('shell.cancel') }}
          </AppButton>
          <AppButton variant="primary" :loading="mutating" @click="submitDecision">
            {{ dialogCopy?.confirmText ?? t('admin.reports.confirm_fallback') }}
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>
