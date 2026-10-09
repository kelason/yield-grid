<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useIssueTickets } from '@/composables/useIssueTickets'
import { useNotificationStore } from '@/stores/notificationStore'
import {
  ISSUE_CATEGORY_LABEL_KEYS,
  ISSUE_STATUS_FILTER_OPTIONS,
  ISSUE_STATUS_LABEL_KEYS,
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
const { t } = useI18n()
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
    ? t('issues.report.confirm_msg_subject', { subject: subject.value.trim() })
    : t('issues.report.confirm_msg_plain'),
)

const statusFilterOptions = computed(() =>
  ISSUE_STATUS_FILTER_OPTIONS.map((option) => ({ value: option.value, label: t(option.labelKey) })),
)

function statusLabel(ticket) {
  return t(ISSUE_STATUS_LABEL_KEYS[ticket?.status] ?? 'issues.report.unknown_status')
}

function categoryLabel(ticket) {
  return t(ISSUE_CATEGORY_LABEL_KEYS[ticket?.category] ?? 'issues.report.other_category')
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
    notificationStore.addNotification({ type: 'success', messageKey: 'issues.report.submitted' })
  } catch (err) {
    submitError.value = err?.message || t('issues.report.submit_failed')
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
    <PageHeader :title="t('issues.report.title')" :description="t('issues.report.description')" />

    <AppCard padding="p-5 sm:p-6">
      <h2 class="font-serif text-2xl font-bold text-stone-900">
        {{ t('issues.report.new_title') }}
      </h2>
      <div
        v-if="receipt"
        role="status"
        data-testid="issue-receipt"
        class="mt-4 rounded-2xl bg-moss-100 p-4 text-sm leading-relaxed text-stone-700"
      >
        {{ t('issues.report.receipt', { id: receipt.id, status: statusLabel(receipt) }) }}
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

    <section :aria-label="t('issues.report.list_aria')" class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('issues.report.list_title') }}
        </h2>
        <AppSelect
          id="my-issues-status"
          :model-value="filters.status"
          :label="t('issues.report.status_label')"
          class="sm:w-56"
          @update:model-value="onStatusChange"
        >
          <option v-for="option in statusFilterOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </AppSelect>
      </div>

      <LoadingState v-if="loading" :label="t('issues.report.loading')" />

      <AppCard v-else-if="error" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ error }}</p>
        <AppButton variant="outline" class="mt-4" data-testid="issues-retry" @click="retryFetch">
          {{ t('shell.retry') }}
        </AppButton>
      </AppCard>

      <EmptyState
        v-else-if="tickets.length === 0"
        :title="t('issues.report.empty_title')"
        :description="t('issues.report.empty_desc')"
      />

      <ul v-else class="space-y-4">
        <li v-for="ticket in tickets" :key="ticket.id">
          <AppCard padding="p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="min-w-0">
                <h3 class="break-words font-serif text-xl font-bold text-stone-900">
                  {{ ticket.subject || t('issues.report.untitled') }}
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
                  :aria-label="t('issues.report.open_aria', { id: ticket.id })"
                  @click="selectTicket(ticket.id)"
                >
                  {{ t('issues.report.open') }}
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

    <section
      v-if="selectedId !== null"
      :aria-label="t('issues.report.detail_aria')"
      class="space-y-4"
    >
      <div class="flex items-center justify-between gap-3">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('issues.report.detail_title', { id: selectedId }) }}
        </h2>
        <AppButton variant="ghost" @click="closeDetail">{{
          t('issues.report.close_detail')
        }}</AppButton>
      </div>

      <LoadingState v-if="detailLoading" :label="t('issues.report.detail_loading')" />

      <AppCard v-else-if="detailError" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ detailError }}</p>
        <AppButton variant="outline" class="mt-4" @click="selectTicket(selectedId)">
          {{ t('shell.retry') }}
        </AppButton>
      </AppCard>

      <AppCard v-else-if="selected" padding="p-5 sm:p-6">
        <h3 class="break-words font-serif text-xl font-bold text-stone-900">
          {{ selected.subject || t('issues.report.untitled') }}
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
          {{ selected.description || t('issues.report.no_desc') }}
        </p>
        <p v-if="selected.page_path" class="mt-3 break-all text-sm text-stone-500">
          {{ t('issues.report.related', { path: selected.page_path }) }}
        </p>
        <div v-if="selected.resolution" class="mt-4 rounded-2xl bg-moss-100 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">
            {{ t('issues.report.resolution') }}
          </p>
          <p class="mt-1 whitespace-pre-wrap break-words text-base text-stone-900">
            {{ selected.resolution }}
          </p>
        </div>
      </AppCard>

      <AppCard v-else padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ t('issues.report.gone') }}</p>
      </AppCard>
    </section>

    <ConfirmModal
      :title="t('issues.report.confirm_title')"
      :message="confirmMessage"
      :confirm-text="t('issues.report.confirm_ok')"
      :is-open="confirming"
      :loading="submitting"
      @confirm="confirmSubmit"
      @cancel="cancelSubmit"
    />
  </div>
</template>
