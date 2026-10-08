<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAdminIssues } from '@/composables/useAdminIssues'
import { useNotificationStore } from '@/stores/notificationStore'
import { ADMIN_SEARCH_MAX_LENGTH } from '@/constants/admin'
import {
  ISSUE_CATEGORY_FILTER_OPTIONS,
  ISSUE_CATEGORY_LABELS,
  ISSUE_CLOSING_STATUSES,
  ISSUE_RESOLUTION_MAX_LENGTH,
  ISSUE_STATUS,
  ISSUE_STATUS_FILTER_OPTIONS,
  ISSUE_STATUS_LABELS,
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
  if (status === ISSUE_STATUS.IN_PROGRESS && reopen) {
    return {
      title: 'Reopen this issue?',
      confirmText: 'Reopen ticket',
      message: `Reopen issue #${ticketId}? The current resolution will be cleared.`,
    }
  }
  if (status === ISSUE_STATUS.IN_PROGRESS) {
    return {
      title: 'Start progress?',
      confirmText: 'Start progress',
      message: `Move issue #${ticketId} to in progress?`,
    }
  }
  if (status === ISSUE_STATUS.RESOLVED) {
    return {
      title: 'Resolve this issue?',
      confirmText: 'Resolve issue',
      message: `Resolve issue #${ticketId} with the resolution below?`,
    }
  }
  return {
    title: 'Close this issue?',
    confirmText: 'Close issue',
    message: `Close issue #${ticketId} with the resolution below?`,
  }
})

const dialogNeedsResolution = computed(() =>
  pendingTransition.value ? ISSUE_CLOSING_STATUSES.includes(pendingTransition.value.status) : false,
)

function statusLabel(ticket) {
  return ISSUE_STATUS_LABELS[ticket?.status] ?? 'Unknown'
}

function categoryLabel(ticket) {
  return ISSUE_CATEGORY_LABELS[ticket?.category] ?? 'Other'
}

function reporterName(ticket) {
  return ticket?.reporter?.name || 'Former member'
}

function transitionLabel(status) {
  if (status !== ISSUE_STATUS.IN_PROGRESS) return ISSUE_STATUS_LABELS[status] ?? status
  const current = selected.value?.status
  if (current === ISSUE_STATUS.RESOLVED || current === ISSUE_STATUS.CLOSED) {
    return 'Reopen ticket'
  }
  return 'Start progress'
}

function applySearch() {
  const search = String(filters.value.search ?? '')
  if (search.length > ADMIN_SEARCH_MAX_LENGTH) {
    filterError.value = `Search must be ${ADMIN_SEARCH_MAX_LENGTH} characters or fewer.`
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
    notificationStore.success('Issue updated.')
  } catch (err) {
    if (err?.isVersionConflict) {
      conflictVersion.value = err.currentVersion
      dialogError.value =
        'This issue changed since it was loaded. Reload for the latest version, then retry. Your draft is kept.'
    } else {
      dialogError.value = err?.message || 'Unable to update this issue.'
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
  fetchIssues(1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Issues"
      description="Review member issues newest first. Resolving or closing needs a public resolution."
    />

    <AppCard padding="p-5">
      <form data-testid="issue-filters" @submit.prevent="applySearch">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <AppSelect
            id="admin-issue-status"
            :model-value="filters.status"
            label="Status"
            @update:model-value="onStatusFilterChange"
          >
            <option
              v-for="option in ISSUE_STATUS_FILTER_OPTIONS"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </AppSelect>
          <AppSelect
            id="admin-issue-category"
            :model-value="filters.category"
            label="Category"
            @update:model-value="onCategoryFilterChange"
          >
            <option
              v-for="option in ISSUE_CATEGORY_FILTER_OPTIONS"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </AppSelect>
          <FormField
            id="admin-issue-search"
            label="Search"
            :model-value="filters.search"
            :maxlength="ADMIN_SEARCH_MAX_LENGTH"
            :error="filterError"
            placeholder="Subject or description"
            @update:model-value="filters.search = $event"
          />
          <div class="flex items-end gap-3">
            <AppButton type="submit" variant="primary">Search</AppButton>
            <AppButton type="button" variant="ghost" @click="resetFilters">Reset</AppButton>
          </div>
        </div>
      </form>
    </AppCard>

    <LoadingState v-if="loading" label="Loading issues" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" data-testid="issues-retry" @click="retryFetch">
        Retry
      </AppButton>
    </AppCard>

    <EmptyState
      v-else-if="tickets.length === 0"
      title="No issues found"
      description="No issues match the current filters."
    />

    <ul v-else class="space-y-4" aria-label="Issue tickets">
      <li v-for="ticket in tickets" :key="ticket.id">
        <AppCard padding="p-5">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <h2 class="break-words font-serif text-xl font-bold text-stone-900">
                {{ ticket.subject || 'Untitled issue' }}
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
                :aria-label="`Review issue ${ticket.id}`"
                @click="selectTicket(ticket.id)"
              >
                Review
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

    <section v-if="selectedId !== null" aria-label="Issue detail" class="space-y-4">
      <div class="flex items-center justify-between gap-3">
        <h2 class="font-serif text-2xl font-bold text-stone-900">Issue #{{ selectedId }}</h2>
        <AppButton variant="ghost" @click="closeDetail">Close detail</AppButton>
      </div>

      <LoadingState v-if="detailLoading" label="Loading issue" />

      <AppCard v-else-if="detailError" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ detailError }}</p>
        <AppButton variant="outline" class="mt-4" @click="selectTicket(selectedId)">
          Retry
        </AppButton>
      </AppCard>

      <AppCard v-else-if="selected" padding="p-5 sm:p-6">
        <h3 class="break-words font-serif text-xl font-bold text-stone-900">
          {{ selected.subject || 'Untitled issue' }}
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
          {{ selected.description || 'No description provided.' }}
        </p>
        <p v-if="selected.page_path" class="mt-3 break-all text-sm text-stone-500">
          Related page: {{ selected.page_path }}
        </p>
        <p class="mt-3 text-sm text-stone-500">
          Reported by: {{ reporterName(selected) }}
          <span v-if="selected.reporter?.email">({{ selected.reporter.email }})</span>
        </p>
        <div v-if="selected.resolution" class="mt-4 rounded-2xl bg-moss-100 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Resolution</p>
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
        <p class="text-base text-stone-900">This issue is no longer available.</p>
      </AppCard>
    </section>

    <AppModal
      :title="dialogCopy?.title ?? 'Update this issue'"
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
          Reload issue (version {{ conflictVersion }})
        </AppButton>
        <FormField
          v-if="dialogNeedsResolution"
          id="issue-resolution"
          v-model="resolutionDraft"
          label="Resolution"
          :multiline="true"
          :maxlength="ISSUE_RESOLUTION_MAX_LENGTH"
          :required="true"
          :disabled="mutating"
          :error="resolutionError"
          placeholder="What was done to resolve this issue?"
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
            Cancel
          </AppButton>
          <AppButton
            variant="primary"
            :loading="mutating"
            data-testid="issue-transition-confirm"
            @click="submitTransition"
          >
            {{ dialogCopy?.confirmText ?? 'Confirm' }}
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>
