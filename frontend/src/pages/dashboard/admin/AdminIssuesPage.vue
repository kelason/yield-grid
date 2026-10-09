<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useAdminIssues } from '@/composables/useAdminIssues'
import { queryFilterValue } from '@/utils/adminQueryFilters'
import { useNotificationStore } from '@/stores/notificationStore'
import { ADMIN_SEARCH_MAX_LENGTH } from '@/constants/admin'
import {
  ISSUE_CATEGORY,
  ISSUE_CATEGORY_FILTER_OPTIONS,
  ISSUE_CATEGORY_LABEL_KEYS,
  ISSUE_CLOSING_STATUSES,
  ISSUE_RESOLUTION_MAX_LENGTH,
  ISSUE_STATUS,
  ISSUE_STATUS_FILTER_OPTIONS,
  ISSUE_STATUS_LABEL_KEYS,
  ISSUE_TRANSITIONS,
  validateIssueResolution,
} from '@/constants/issues'
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

const notificationStore = useNotificationStore()
const { t } = useI18n()

const TRANSITION_COPY = {
  reopen: {
    titleKey: 'admin.issues.modal_reopen_title',
    confirmKey: 'admin.issues.modal_reopen_cta',
    msgKey: 'admin.issues.modal_reopen_msg',
  },
  start: {
    titleKey: 'admin.issues.modal_start_title',
    confirmKey: 'admin.issues.modal_start_cta',
    msgKey: 'admin.issues.modal_start_msg',
  },
  resolve: {
    titleKey: 'admin.issues.modal_resolve_title',
    confirmKey: 'admin.issues.modal_resolve_cta',
    msgKey: 'admin.issues.modal_resolve_msg',
  },
  close: {
    titleKey: 'admin.issues.modal_close_title',
    confirmKey: 'admin.issues.modal_close_cta',
    msgKey: 'admin.issues.modal_close_msg',
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
  fetchIssues,
  fetchIssue,
  clearSelection,
  transitionIssue,
} = useAdminIssues()

const route = useRoute()

function seedFiltersFromQuery() {
  const query = route?.query ?? {}
  filters.value.status = queryFilterValue(query, 'status', Object.values(ISSUE_STATUS))
  filters.value.category = queryFilterValue(query, 'category', Object.values(ISSUE_CATEGORY))
}

const selectedId = ref(null)
const filterError = ref('')
const pendingTransition = ref(null)
const resolutionDraft = ref('')
const resolutionError = ref('')
const dialogError = ref('')
const conflictVersion = ref(null)

const tickets = computed(() =>
  (Array.isArray(list.value) ? list.value : []).filter(
    (entry) => entry && typeof entry === 'object',
  ),
)

const availableTransitions = computed(() => ISSUE_TRANSITIONS[selected.value?.status] ?? [])

const dialogCopy = computed(() => {
  if (!pendingTransition.value) return null
  const { status, ticketId, reopen } = pendingTransition.value
  let copy = TRANSITION_COPY.close
  if (status === ISSUE_STATUS.IN_PROGRESS && reopen) copy = TRANSITION_COPY.reopen
  else if (status === ISSUE_STATUS.IN_PROGRESS) copy = TRANSITION_COPY.start
  else if (status === ISSUE_STATUS.RESOLVED) copy = TRANSITION_COPY.resolve
  return {
    title: t(copy.titleKey),
    confirmText: t(copy.confirmKey),
    message: t(copy.msgKey, { id: ticketId }),
  }
})

const statusOptions = computed(() =>
  ISSUE_STATUS_FILTER_OPTIONS.map((option) => ({ value: option.value, label: t(option.labelKey) })),
)

const categoryOptions = computed(() =>
  ISSUE_CATEGORY_FILTER_OPTIONS.map((option) => ({
    value: option.value,
    label: option.labelKey ? t(option.labelKey) : option.label,
  })),
)

const dialogNeedsResolution = computed(() =>
  pendingTransition.value ? ISSUE_CLOSING_STATUSES.includes(pendingTransition.value.status) : false,
)

function statusLabel(ticket) {
  const labelKey = ISSUE_STATUS_LABEL_KEYS[ticket?.status]
  return labelKey ? t(labelKey) : t('admin.issues.unknown')
}

function categoryLabel(ticket) {
  const labelKey = ISSUE_CATEGORY_LABEL_KEYS[ticket?.category]
  return labelKey ? t(labelKey) : t('admin.issues.other_fallback')
}

function reporterName(ticket) {
  return ticket?.reporter?.name || t('admin.issues.former_member')
}

function transitionLabel(status) {
  if (status !== ISSUE_STATUS.IN_PROGRESS) {
    const labelKey = ISSUE_STATUS_LABEL_KEYS[status]
    return labelKey ? t(labelKey) : status
  }
  const current = selected.value?.status
  if (current === ISSUE_STATUS.RESOLVED || current === ISSUE_STATUS.CLOSED) {
    return t('admin.issues.btn_reopen')
  }
  return t('admin.issues.btn_start')
}

function applySearch() {
  const search = String(filters.value.search ?? '')
  if (search.length > ADMIN_SEARCH_MAX_LENGTH) {
    filterError.value = t('admin.issues.err_search_max', { max: ADMIN_SEARCH_MAX_LENGTH })
    return
  }
  filterError.value = ''
  clearSelection()
  selectedId.value = null
  fetchIssues(1)
}

function onSelectFilterChange() {
  clearSelection()
  selectedId.value = null
  fetchIssues(1)
}

function onStatusFilterChange(value) {
  filters.value.status = value
  onSelectFilterChange()
}

function onCategoryFilterChange(value) {
  filters.value.category = value
  onSelectFilterChange()
}

function resetFilters() {
  filters.value = { status: '', category: '', search: '' }
  filterError.value = ''
  clearSelection()
  selectedId.value = null
  fetchIssues(1)
}

function retryFetch() {
  fetchIssues(pagination.value.currentPage)
}

function handlePageChange(page) {
  fetchIssues(page)
}

async function selectTicket(id) {
  selectedId.value = id
  pendingTransition.value = null
  await fetchIssue(id)
}

function closeDetail() {
  selectedId.value = null
  pendingTransition.value = null
  clearSelection()
}

function askTransition(status) {
  if (!selected.value || mutating.value) return
  const current = selected.value.status
  pendingTransition.value = {
    status,
    ticketId: selected.value.id,
    reopen:
      status === ISSUE_STATUS.IN_PROGRESS &&
      (current === ISSUE_STATUS.RESOLVED || current === ISSUE_STATUS.CLOSED),
  }
  resolutionDraft.value = ''
  resolutionError.value = ''
  dialogError.value = ''
  conflictVersion.value = null
}

function cancelDialog() {
  if (!mutating.value) pendingTransition.value = null
}

async function submitTransition() {
  if (!pendingTransition.value || mutating.value || !selected.value) return
  const { status } = pendingTransition.value
  resolutionError.value = validateIssueResolution(resolutionDraft.value, status)
  if (resolutionError.value) return
  dialogError.value = ''
  conflictVersion.value = null
  try {
    const trimmed = resolutionDraft.value.trim()
    await transitionIssue(selected.value.id, {
      status,
      resolution: ISSUE_CLOSING_STATUSES.includes(status) ? trimmed : undefined,
      expected_version: selected.value.version,
    })
    pendingTransition.value = null
    resolutionDraft.value = ''
    notificationStore.addNotification({ type: 'success', messageKey: 'admin.issues.updated_toast' })
  } catch (err) {
    if (err?.isVersionConflict) {
      conflictVersion.value = err.currentVersion
      dialogError.value = t('admin.issues.conflict')
    } else {
      dialogError.value = err?.message || t('admin.issues.err_update')
    }
  }
}

async function reloadTicket() {
  if (!selected.value || mutating.value) return
  dialogError.value = ''
  conflictVersion.value = null
  await fetchIssue(selected.value.id)
}

onMounted(() => {
  seedFiltersFromQuery()
  fetchIssues(1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="t('admin.issues.title')" :description="t('admin.issues.description')" />

    <AppCard padding="p-5">
      <form data-testid="issue-filters" @submit.prevent="applySearch">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <AppSelect
            id="admin-issue-status"
            :model-value="filters.status"
            :label="t('admin.issues.status_label')"
            @update:model-value="onStatusFilterChange"
          >
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </AppSelect>
          <AppSelect
            id="admin-issue-category"
            :model-value="filters.category"
            :label="t('admin.issues.cat_label')"
            @update:model-value="onCategoryFilterChange"
          >
            <option v-for="option in categoryOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </AppSelect>
          <FormField
            id="admin-issue-search"
            :label="t('admin.issues.search_label')"
            :model-value="filters.search"
            :maxlength="ADMIN_SEARCH_MAX_LENGTH"
            :error="filterError"
            :placeholder="t('admin.issues.search_ph')"
            @update:model-value="filters.search = $event"
          />
          <div class="flex items-end gap-3">
            <AppButton type="submit" variant="primary">{{
              t('admin.issues.search_btn')
            }}</AppButton>
            <AppButton type="button" variant="ghost" @click="resetFilters">{{
              t('admin.issues.reset')
            }}</AppButton>
          </div>
        </div>
      </form>
    </AppCard>

    <LoadingState v-if="loading" :label="t('admin.issues.loading')" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" data-testid="issues-retry" @click="retryFetch">
        {{ t('shell.retry') }}
      </AppButton>
    </AppCard>

    <EmptyState
      v-else-if="tickets.length === 0"
      :title="t('admin.issues.empty_title')"
      :description="t('admin.issues.empty_desc')"
    />

    <ul v-else class="space-y-4" :aria-label="t('admin.issues.list_aria')">
      <li v-for="ticket in tickets" :key="ticket.id">
        <AppCard padding="p-5">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <h2 class="break-words font-serif text-xl font-bold text-stone-900">
                {{ ticket.subject || t('admin.issues.untitled') }}
              </h2>
              <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                <span
                  class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 font-medium text-soil-800"
                >
                  {{ categoryLabel(ticket) }}
                </span>
                <span
                  class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 font-medium text-soil-800"
                >
                  {{ statusLabel(ticket) }}
                </span>
              </div>
            </div>
            <div class="shrink-0 sm:w-36">
              <AppButton
                variant="outline"
                class="w-full"
                :data-testid="`issue-review-${ticket.id}`"
                :aria-label="t('admin.issues.review_aria', { id: ticket.id })"
                @click="selectTicket(ticket.id)"
              >
                {{ t('admin.issues.review') }}
              </AppButton>
            </div>
          </div>
        </AppCard>
      </li>
    </ul>

    <PaginationControls
      v-if="!loading && !error && tickets.length > 0"
      :current-page="pagination.currentPage"
      :last-page="pagination.lastPage"
      :total="pagination.total"
      @page-change="handlePageChange"
    />

    <section
      v-if="selectedId !== null"
      :aria-label="t('admin.issues.detail_aria')"
      class="space-y-4"
    >
      <div class="flex items-center justify-between gap-3">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('admin.issues.detail_title', { id: selectedId }) }}
        </h2>
        <AppButton variant="ghost" @click="closeDetail">{{
          t('admin.issues.close_detail')
        }}</AppButton>
      </div>

      <LoadingState v-if="detailLoading" :label="t('admin.issues.detail_loading')" />

      <AppCard v-else-if="detailError" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ detailError }}</p>
        <AppButton variant="outline" class="mt-4" @click="selectTicket(selectedId)">
          {{ t('shell.retry') }}
        </AppButton>
      </AppCard>

      <AppCard v-else-if="selected" padding="p-5 sm:p-6">
        <h3 class="break-words font-serif text-xl font-bold text-stone-900">
          {{ selected.subject || t('admin.issues.untitled') }}
        </h3>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
          <span
            class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 font-medium text-soil-800"
          >
            {{ categoryLabel(selected) }}
          </span>
          <span
            class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 font-medium text-soil-800"
          >
            {{ statusLabel(selected) }}
          </span>
        </div>
        <p class="mt-4 whitespace-pre-wrap break-words text-base leading-relaxed text-stone-600">
          {{ selected.description || t('admin.issues.no_desc') }}
        </p>
        <p v-if="selected.page_path" class="mt-3 break-all text-sm text-stone-500">
          {{ t('admin.issues.page', { path: selected.page_path }) }}
        </p>
        <p class="mt-3 text-sm text-stone-500">
          {{ t('admin.issues.reporter', { name: reporterName(selected) }) }}
          <span v-if="selected.reporter?.email">({{ selected.reporter.email }})</span>
        </p>
        <div v-if="selected.resolution" class="mt-4 rounded-2xl bg-moss-100 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">
            {{ t('admin.issues.resolution_title') }}
          </p>
          <p class="mt-1 whitespace-pre-wrap break-words text-base text-stone-900">
            {{ selected.resolution }}
          </p>
        </div>
        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
          <AppButton
            v-for="status in availableTransitions"
            :key="status"
            variant="outline"
            :disabled="mutating"
            :data-testid="`issue-transition-${status}`"
            @click="askTransition(status)"
          >
            {{ transitionLabel(status) }}
          </AppButton>
        </div>
      </AppCard>

      <AppCard v-else padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ t('admin.issues.gone') }}</p>
      </AppCard>
    </section>

    <AppModal
      :title="dialogCopy?.title ?? t('admin.issues.modal_fallback')"
      :is-open="pendingTransition !== null"
      :busy="mutating"
      @close="cancelDialog"
    >
      <div v-if="pendingTransition" class="space-y-5">
        <p class="text-base leading-relaxed text-stone-600">
          {{ dialogCopy.message }}
        </p>
        <AppAlert v-if="dialogError" type="error">{{ dialogError }}</AppAlert>
        <AppButton
          v-if="conflictVersion !== null"
          variant="outline"
          data-testid="issue-conflict-reload"
          @click="reloadTicket"
        >
          {{ t('admin.issues.reload', { version: conflictVersion }) }}
        </AppButton>
        <FormField
          v-if="dialogNeedsResolution"
          id="issue-resolution"
          v-model="resolutionDraft"
          :label="t('admin.issues.resolution_label')"
          :multiline="true"
          :maxlength="ISSUE_RESOLUTION_MAX_LENGTH"
          :required="true"
          :disabled="mutating"
          :error="resolutionError"
          :placeholder="t('admin.issues.resolution_ph')"
        />
      </div>
      <template #footer>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <AppButton
            variant="secondary"
            :disabled="mutating"
            data-testid="issue-transition-cancel"
            @click="cancelDialog"
          >
            {{ t('shell.cancel') }}
          </AppButton>
          <AppButton
            variant="primary"
            :loading="mutating"
            data-testid="issue-transition-confirm"
            @click="submitTransition"
          >
            {{ dialogCopy?.confirmText ?? t('admin.issues.confirm_fallback') }}
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>
