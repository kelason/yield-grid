<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAdminInquiries } from '@/composables/useAdminInquiries'
import { queryFilterValue } from '@/utils/adminQueryFilters'
import { useNotificationStore } from '@/stores/notificationStore'
import {
  ADMIN_INQUIRY_DELIVERY_OPTIONS,
  ADMIN_INQUIRY_STATUS_OPTIONS,
  ADMIN_SEARCH_MAX_LENGTH,
  CONTACT_INQUIRY_STATUS,
  CONTACT_REPLY_BODY_MAX_LENGTH,
  DUPLICATE_DELIVERY_CAUTION,
  REPLY_DELIVERY_STATUS,
} from '@/constants/admin'
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
import AdminInquiryDetail from '@/components/organisms/AdminInquiryDetail.vue'

const INQUIRY_STATUS_LABELS = {
  unread: 'Unread',
  read: 'Read',
  replied: 'Replied',
  closed: 'Closed',
}

const CONFIRM_COPY = {
  read: { title: 'Mark as read?', confirmText: 'Mark read', variant: 'primary' },
  close: { title: 'Close inquiry?', confirmText: 'Close inquiry', variant: 'primary' },
  reopen: { title: 'Reopen inquiry?', confirmText: 'Reopen inquiry', variant: 'primary' },
  reply: { title: 'Queue reply?', confirmText: 'Queue reply', variant: 'primary' },
  retry: { title: 'Retry delivery?', confirmText: 'Retry delivery', variant: 'primary' },
}

const SUCCESS_COPY = {
  read: 'Inquiry marked as read.',
  close: 'Inquiry closed.',
  reopen: 'Inquiry reopened.',
  reply: 'Reply queued for delivery.',
  retry: 'Reply delivery retried.',
}

function newRequestKey() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
    const random = Math.floor(Math.random() * 16)
    const value = char === 'x' ? random : (random & 0x3) | 0x8
    return value.toString(16)
  })
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
  fetchInquiries,
  fetchInquiry,
  clearSelection,
  markRead,
  closeInquiry,
  reopenInquiry,
  queueReply,
  retryReply,
} = useAdminInquiries()

const route = useRoute()

function seedFiltersFromQuery() {
  const query = route?.query ?? {}
  filters.value.status = queryFilterValue(query, 'status', Object.values(CONTACT_INQUIRY_STATUS))
  const deliveries = ADMIN_INQUIRY_DELIVERY_OPTIONS.map((option) => option.value).filter(
    (value) => value !== '',
  )
  filters.value.delivery = queryFilterValue(query, 'delivery', deliveries)
}

const filterError = ref('')
const selectedId = ref(null)
const draft = ref('')
const draftError = ref('')
const pendingConfirm = ref(null)
const dialogError = ref('')
const notice = ref('')
const clientRequestId = ref(newRequestKey())

const inquiries = computed(() =>
  (Array.isArray(list.value) ? list.value : []).filter(
    (entry) => entry && typeof entry === 'object',
  ),
)

const detailReplies = computed(() =>
  Array.isArray(selected.value?.replies) ? selected.value.replies : [],
)

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  const copy = CONFIRM_COPY[pendingConfirm.value.action]
  if (!copy) return null
  return { ...copy, message: confirmMessage(pendingConfirm.value) }
})

function displayName(message) {
  return message?.name || 'Unknown inquiry'
}

function statusLabel(message) {
  return INQUIRY_STATUS_LABELS[message?.status] ?? 'Unknown'
}

function confirmMessage(pending) {
  const name = displayName(pending.message)
  if (pending.action === 'reply') {
    return `Queue this reply to ${name}? Delivery happens in the background.`
  }
  if (pending.action === 'retry') {
    const caution =
      pending.reply?.delivery_status === REPLY_DELIVERY_STATUS.SENDING
        ? ` ${DUPLICATE_DELIVERY_CAUTION}`
        : ''
    return `Retry delivery of this reply to ${name}? The same reply is sent again.${caution}`
  }
  if (pending.action === 'read') return `Mark the inquiry from ${name} as read?`
  if (pending.action === 'close') return `Close the inquiry from ${name}?`
  return `Reopen the inquiry from ${name}?`
}

function applySearch() {
  const search = String(filters.value.search ?? '')
  if (search.length > ADMIN_SEARCH_MAX_LENGTH) {
    filterError.value = `Search must be ${ADMIN_SEARCH_MAX_LENGTH} characters or fewer.`
    return
  }
  filterError.value = ''
  fetchInquiries(1)
}

function onStatusChange(value) {
  filters.value.status = value
  fetchInquiries(1)
}

function onDeliveryChange(value) {
  filters.value.delivery = value
  fetchInquiries(1)
}

function resetFilters() {
  filters.value = { search: '', status: '', delivery: '' }
  filterError.value = ''
  fetchInquiries(1)
}

function retryFetch() {
  fetchInquiries(pagination.value.currentPage)
}

function resetComposer() {
  draft.value = ''
  draftError.value = ''
  dialogError.value = ''
  notice.value = ''
  pendingConfirm.value = null
  clientRequestId.value = newRequestKey()
}

function selectInquiry(id) {
  if (id == null) return
  selectedId.value = id
  resetComposer()
  fetchInquiry(id)
}

function backToList() {
  selectedId.value = null
  clearSelection()
  resetComposer()
}

function askTransition(action) {
  if (!selected.value) return
  pendingConfirm.value = { action, message: selected.value }
  dialogError.value = ''
}

function validatedDraft() {
  const body = draft.value.trim()
  if (!body) {
    draftError.value = 'Enter a reply before sending.'
    return null
  }
  if (body.length > CONTACT_REPLY_BODY_MAX_LENGTH) {
    draftError.value = 'Reply must be 5,000 characters or fewer.'
    return null
  }
  draftError.value = ''
  return body
}

function askReply() {
  if (!selected.value || mutating.value) return
  if (validatedDraft() === null) return
  pendingConfirm.value = { action: 'reply', message: selected.value }
  dialogError.value = ''
}

function askRetry(reply) {
  if (!selected.value || !reply) return
  pendingConfirm.value = { action: 'retry', message: selected.value, reply }
  dialogError.value = ''
}

function cancelDialog() {
  if (!mutating.value) pendingConfirm.value = null
}

async function runConfirmedAction(pending) {
  const id = pending.message?.id
  if (pending.action === 'read') return markRead(id)
  if (pending.action === 'close') return closeInquiry(id)
  if (pending.action === 'reopen') return reopenInquiry(id)
  if (pending.action === 'reply') {
    return queueReply(id, draft.value.trim(), clientRequestId.value)
  }
  return retryReply(id, pending.reply?.id)
}

function afterConfirmedAction(pending, result) {
  if (pending.action === 'reply') {
    draft.value = ''
    draftError.value = ''
    clientRequestId.value = newRequestKey()
  }
  if (result?.warning) notice.value = result.warning
  notificationStore.success(SUCCESS_COPY[pending.action] ?? 'Done.')
}

async function submitDialog() {
  if (!pendingConfirm.value || mutating.value) return
  dialogError.value = ''
  try {
    const pending = pendingConfirm.value
    const result = await runConfirmedAction(pending)
    pendingConfirm.value = null
    afterConfirmedAction(pending, result)
  } catch (err) {
    dialogError.value = err?.message || 'Unable to update this inquiry.'
  }
}

onMounted(() => {
  seedFiltersFromQuery()
  fetchInquiries(1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Inquiries"
      description="Read Contact Us messages and queue replies. Delivery runs in the background."
    />

    <AppCard padding="p-5">
      <form
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        @submit.prevent="applySearch"
      >
        <FormField
          id="admin-inquiry-search"
          v-model="filters.search"
          label="Search inquiries"
          placeholder="Name, email or subject"
          :maxlength="ADMIN_SEARCH_MAX_LENGTH"
          :error="filterError"
        />
        <AppSelect
          id="admin-inquiry-status"
          :model-value="filters.status"
          label="Status"
          @update:model-value="onStatusChange"
        >
          <option
            v-for="option in ADMIN_INQUIRY_STATUS_OPTIONS"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </AppSelect>
        <AppSelect
          id="admin-inquiry-delivery"
          :model-value="filters.delivery"
          label="Reply delivery"
          @update:model-value="onDeliveryChange"
        >
          <option
            v-for="option in ADMIN_INQUIRY_DELIVERY_OPTIONS"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </AppSelect>
        <div class="flex items-end gap-3">
          <AppButton type="submit" variant="primary">Search</AppButton>
          <AppButton type="button" variant="ghost" @click="resetFilters">Reset</AppButton>
        </div>
      </form>
    </AppCard>

    <LoadingState v-if="loading" label="Loading inquiries" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" @click="retryFetch">Retry</AppButton>
    </AppCard>

    <EmptyState
      v-else-if="inquiries.length === 0"
      title="No inquiries found"
      description="No messages match the current filters."
    />

    <ul v-else class="space-y-4">
      <li v-for="(inquiry, index) in inquiries" :key="inquiry?.id ?? `inquiry-${index}`">
        <AppCard padding="p-5">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <h2 class="font-serif text-xl font-bold break-words text-stone-900">
                {{ displayName(inquiry) }}
              </h2>
              <p class="mt-1 break-words text-sm text-stone-600">
                {{ inquiry?.subject || 'Untitled inquiry' }}
              </p>
              <div class="mt-2">
                <span
                  class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 text-xs font-medium text-soil-800"
                >
                  {{ statusLabel(inquiry) }}
                </span>
              </div>
            </div>
            <div class="shrink-0 sm:w-36">
              <AppButton
                v-if="inquiry?.id != null"
                variant="outline"
                class="w-full"
                :aria-label="`View inquiry from ${displayName(inquiry)}`"
                @click="selectInquiry(inquiry.id)"
              >
                View
              </AppButton>
            </div>
          </div>
        </AppCard>
      </li>
    </ul>

    <PaginationControls
      v-if="!loading && !error"
      :current-page="pagination.currentPage"
      :last-page="pagination.lastPage"
      :total="pagination.total"
      @page-change="fetchInquiries"
    />

    <section v-if="selectedId !== null" aria-label="Inquiry detail">
      <div class="mb-4">
        <AppButton variant="ghost" @click="backToList">Back to list</AppButton>
      </div>

      <LoadingState v-if="detailLoading" label="Loading inquiry" />

      <AppCard v-else-if="detailError" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ detailError }}</p>
        <AppButton variant="outline" class="mt-4" @click="selectInquiry(selectedId)">
          Retry
        </AppButton>
      </AppCard>

      <div v-else-if="selected" class="space-y-4">
        <AppAlert v-if="notice" type="warning">{{ notice }}</AppAlert>
        <div data-testid="admin-inquiry-detail">
          <AdminInquiryDetail
            :message="selected"
            :replies="detailReplies"
            :draft="draft"
            :draft-error="draftError"
            :busy="mutating"
            @update:draft="draft = $event"
            @transition="askTransition"
            @reply="askReply"
            @retry="askRetry"
          />
        </div>
      </div>
    </section>

    <AppModal
      :is-open="pendingConfirm !== null"
      :title="confirmConfig?.title ?? ''"
      :busy="mutating"
      @close="cancelDialog"
    >
      <div v-if="pendingConfirm && confirmConfig" class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">{{ confirmConfig.message }}</p>
        <div
          v-if="pendingConfirm.action === 'reply'"
          data-testid="admin-inquiry-reply-preview"
          class="rounded-2xl border border-stone-200 p-4"
        >
          <p class="text-base leading-relaxed break-words whitespace-pre-line text-stone-900">
            {{ draft.trim() }}
          </p>
          <p class="mt-2 text-sm tabular-nums text-stone-600">
            {{ draft.trim().length }} / {{ CONTACT_REPLY_BODY_MAX_LENGTH }}
          </p>
        </div>
        <AppAlert v-if="dialogError" type="error">{{ dialogError }}</AppAlert>
      </div>
      <template #footer>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <AppButton variant="secondary" :disabled="mutating" @click="cancelDialog">
            Cancel
          </AppButton>
          <AppButton
            :variant="confirmConfig?.variant === 'danger' ? 'danger' : 'primary'"
            :loading="mutating"
            @click="submitDialog"
          >
            {{ confirmConfig?.confirmText ?? 'Confirm' }}
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>
