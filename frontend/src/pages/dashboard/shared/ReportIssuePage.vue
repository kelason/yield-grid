<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useIssueTickets } from '@/composables/useIssueTickets'
import { useNotificationStore } from '@/stores/notificationStore'
import {
  ISSUE_CATEGORY_LABELS,
  ISSUE_STATUS_FILTER_OPTIONS,
  ISSUE_STATUS_LABELS,
  isSafeIssuePath,
  validateIssueDraft,
} from '@/constants/issues'
import PageHeader from '@/components/molecules/PageHeader.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import IssueTicketForm from '@/components/organisms/IssueTicketForm.vue'

const notificationStore = useNotificationStore()
const route = useRoute()
const {
  list,
  loading,
  error,
  submitting,
  filters,
  pagination,
  selected,
  detailLoading,
  detailError,
  fetchTickets,
  fetchTicket,
  clearSelection,
  submitTicket,
  rotateRequestKey,
} = useIssueTickets()

const category = ref('')
const subject = ref('')
const description = ref('')
const pagePath = ref('')
const fieldErrors = ref({ category: '', subject: '', description: '', pagePath: '' })
const submitError = ref('')
const confirming = ref(false)
const receipt = ref(null)
const selectedId = ref(null)

const tickets = computed(() =>
  (Array.isArray(list.value) ? list.value : []).filter(
    (entry) => entry && typeof entry === 'object',
  ),
)

const confirmMessage = computed(() =>
  subject.value.trim()
    ? `Submit "${subject.value.trim()}"? Our team will review it and update its status here.`
    : 'Submit this issue? Our team will review it and update its status here.',
)

function statusLabel(ticket) {
  return ISSUE_STATUS_LABELS[ticket?.status] ?? 'Unknown'
}

function categoryLabel(ticket) {
  return ISSUE_CATEGORY_LABELS[ticket?.category] ?? 'Other'
}

function resetDraft() {
  category.value = ''
  subject.value = ''
  description.value = ''
  pagePath.value = ''
  fieldErrors.value = { category: '', subject: '', description: '', pagePath: '' }
  submitError.value = ''
}

function startNewDraft() {
  if (submitting.value) return
  resetDraft()
  receipt.value = null
  confirming.value = false
  rotateRequestKey()
}

function useCurrentPage() {
  pagePath.value = route.path
}

function prefillContext() {
  const candidate = route.query?.from
  if (typeof candidate === 'string' && isSafeIssuePath(candidate)) {
    pagePath.value = candidate
  }
}

function askSubmit() {
  if (submitting.value) return
  fieldErrors.value = validateIssueDraft({
    category: category.value,
    subject: subject.value,
    description: description.value,
    pagePath: pagePath.value,
  })
  const blocked = Object.values(fieldErrors.value).some((message) => message !== '')
  if (blocked) return
  submitError.value = ''
  confirming.value = true
}

function cancelSubmit() {
  if (!submitting.value) confirming.value = false
}

function draftPayload() {
  const trimmedPath = pagePath.value.trim()
  return {
    category: category.value,
    subject: subject.value.trim(),
    description: description.value.trim(),
    page_path: trimmedPath === '' ? null : trimmedPath,
  }
}

async function confirmSubmit() {
  if (!confirming.value || submitting.value) return
  submitError.value = ''
  try {
    const created = await submitTicket(draftPayload())
    receipt.value = created
    resetDraft()
    confirming.value = false
    selectedId.value = null
    clearSelection()
    await fetchTickets(1)
    notificationStore.success('Issue submitted.')
  } catch (err) {
    submitError.value = err?.message || 'Unable to submit this issue.'
  }
}

function onFilterChange() {
  clearSelection()
  selectedId.value = null
  fetchTickets(1)
}

function onStatusChange(value) {
  filters.value.status = value
  onFilterChange()
}

function retryFetch() {
  fetchTickets(pagination.value.currentPage)
}

function handlePageChange(page) {
  fetchTickets(page)
}

async function selectTicket(id) {
  selectedId.value = id
  await fetchTicket(id)
}

function closeDetail() {
  selectedId.value = null
  clearSelection()
}

onMounted(() => {
  prefillContext()
  fetchTickets(1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Report an issue"
      description="Tell us what went wrong. Track your own tickets and their resolutions below."
    />

    <AppCard padding="p-5 sm:p-6">
      <h2 class="font-serif text-2xl font-bold text-stone-900">New issue</h2>
      <div
        v-if="receipt"
        role="status"
        data-testid="issue-receipt"
        class="mt-4 rounded-2xl bg-moss-100 p-4 text-sm leading-relaxed text-stone-700"
      >
        Issue #{{ receipt.id }} received. Current status: {{ statusLabel(receipt) }}.
      </div>
      <div class="mt-4">
        <IssueTicketForm
          :category="category"
          :subject="subject"
          :description="description"
          :page-path="pagePath"
          :busy="submitting"
          :errors="fieldErrors"
          :submit-error="submitError"
          @update:category="category = $event"
          @update:subject="subject = $event"
          @update:description="description = $event"
          @update:page-path="pagePath = $event"
          @use-current-page="useCurrentPage"
          @submit="askSubmit"
          @new-draft="startNewDraft"
        />
      </div>
    </AppCard>

    <section aria-label="My issues" class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <h2 class="font-serif text-2xl font-bold text-stone-900">My issues</h2>
        <AppSelect
          id="my-issues-status"
          :model-value="filters.status"
          label="Status"
          class="sm:w-56"
          @update:model-value="onStatusChange"
        >
          <option
            v-for="option in ISSUE_STATUS_FILTER_OPTIONS"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </AppSelect>
      </div>

      <LoadingState v-if="loading" label="Loading your issues" />

      <AppCard v-else-if="error" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ error }}</p>
        <AppButton variant="outline" class="mt-4" data-testid="issues-retry" @click="retryFetch">
          Retry
        </AppButton>
      </AppCard>

      <EmptyState
        v-else-if="tickets.length === 0"
        title="No issues yet"
        description="Submitted tickets will appear here with their current status."
      />

      <ul v-else class="space-y-4">
        <li v-for="ticket in tickets" :key="ticket.id">
          <AppCard padding="p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="min-w-0">
                <h3 class="break-words font-serif text-xl font-bold text-stone-900">
                  {{ ticket.subject || 'Untitled issue' }}
                </h3>
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
                  :data-testid="`issue-open-${ticket.id}`"
                  :aria-label="`Open issue ${ticket.id}`"
                  @click="selectTicket(ticket.id)"
                >
                  Open
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
    </section>

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
        <div v-if="selected.resolution" class="mt-4 rounded-2xl bg-moss-100 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Resolution</p>
          <p class="mt-1 whitespace-pre-wrap break-words text-base text-stone-900">
            {{ selected.resolution }}
          </p>
        </div>
      </AppCard>

      <AppCard v-else padding="p-6" role="alert">
        <p class="text-base text-stone-900">This issue is no longer available.</p>
      </AppCard>
    </section>

    <ConfirmModal
      title="Submit this issue?"
      :message="confirmMessage"
      confirm-text="Submit issue"
      :is-open="confirming"
      :loading="submitting"
      @confirm="confirmSubmit"
      @cancel="cancelSubmit"
    />
  </div>
</template>
