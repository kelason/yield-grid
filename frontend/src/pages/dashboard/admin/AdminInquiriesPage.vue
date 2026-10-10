<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
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

const INQUIRY_STATUS_LABEL_KEYS = {
  unread: 'admin.inquiry.status_unread',
  read: 'admin.inquiry.status_read',
  replied: 'admin.inquiry.status_replied',
  closed: 'admin.inquiry.status_closed',
}

const CONFIRM_COPY = {
  read: {
    titleKey: 'admin.inquiry.read_title',
    confirmKey: 'admin.inquiry.read_ok',
    variant: 'primary',
  },
  close: {
    titleKey: 'admin.inquiry.close_title',
    confirmKey: 'admin.inquiry.close_ok',
    variant: 'primary',
  },
  reopen: {
    titleKey: 'admin.inquiry.reopen_title',
    confirmKey: 'admin.inquiry.reopen_ok',
    variant: 'primary',
  },
  reply: {
    titleKey: 'admin.inquiry.reply_title',
    confirmKey: 'admin.inquiry.reply_ok',
    variant: 'primary',
  },
  retry: {
    titleKey: 'admin.inquiry.retry_title',
    confirmKey: 'admin.inquiry.retry_ok',
    variant: 'primary',
  },
}

const SUCCESS_KEYS = {
  read: 'admin.inquiry.toast_read',
  close: 'admin.inquiry.toast_close',
  reopen: 'admin.inquiry.toast_reopen',
  reply: 'admin.inquiry.toast_reply',
  retry: 'admin.inquiry.toast_retry',
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
const { t } = useI18n()
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
  return {
    title: t(copy.titleKey),
    confirmText: t(copy.confirmKey),
    variant: copy.variant,
    message: confirmMessage(pendingConfirm.value),
  }
})

const statusOptions = computed(() =>
  ADMIN_INQUIRY_STATUS_OPTIONS.map((option) => ({
    value: option.value,
    label: t(option.labelKey),
  })),
)

const deliveryOptions = computed(() =>
  ADMIN_INQUIRY_DELIVERY_OPTIONS.map((option) => ({
    value: option.value,
    label: t(option.labelKey),
  })),
)

function displayName(message) {
  return message?.name || t('admin.inquiry.unknown_inquiry')
}

function statusLabel(message) {
  return t(INQUIRY_STATUS_LABEL_KEYS[message?.status] ?? 'admin.inquiry.unknown_status')
}

function confirmMessage(pending) {
  const name = displayName(pending.message)
  if (pending.action === 'reply') {
    return t('admin.inquiry.reply_msg', { name })
  }
  if (pending.action === 'retry') {
    const caution =
      pending.reply?.delivery_status === REPLY_DELIVERY_STATUS.SENDING
        ? ` ${t('admin.inquiry.dup_caution')}`
        : ''
    return t('admin.inquiry.retry_msg', { name, caution })
  }
  if (pending.action === 'read') return t('admin.inquiry.read_msg', { name })
  if (pending.action === 'close') return t('admin.inquiry.close_msg', { name })
  return t('admin.inquiry.reopen_msg', { name })
}

function applySearch() {
  const search = String(filters.value.search ?? '')
  if (search.length > ADMIN_SEARCH_MAX_LENGTH) {
    filterError.value = t('admin.inquiry.err_search', { max: ADMIN_SEARCH_MAX_LENGTH })
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
    draftError.value = t('admin.inquiry.err_draft')
    return null
  }
  if (body.length > CONTACT_REPLY_BODY_MAX_LENGTH) {
    draftError.value = t('admin.inquiry.err_draft_max')
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
  notificationStore.addNotification({
    type: 'success',
    messageKey: SUCCESS_KEYS[pending.action] ?? 'admin.inquiry.toast_done',
  })
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
    dialogError.value = err?.message || t('admin.inquiry.update_failed')
  }
}

onMounted(() => {
  seedFiltersFromQuery()
  fetchInquiries(1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="t('admin.inquiry.title')" :description="t('admin.inquiry.description')" />

    <AppCard padding="p-5">
      <form
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        @submit.prevent="applySearch"
      >
        <FormField
          id="admin-inquiry-search"
          v-model="filters.search"
          :label="t('admin.inquiry.search_label')"
          :placeholder="t('admin.inquiry.search_ph')"
          :maxlength="ADMIN_SEARCH_MAX_LENGTH"
          :error="filterError"
        />
        <AppSelect
          id="admin-inquiry-status"
          :model-value="filters.status"
          :label="t('admin.inquiry.status_label')"
          @update:model-value="onStatusChange"
        >
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </AppSelect>
        <AppSelect
          id="admin-inquiry-delivery"
          :model-value="filters.delivery"
          :label="t('admin.inquiry.delivery_label')"
          @update:model-value="onDeliveryChange"
        >
          <option v-for="option in deliveryOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </AppSelect>
        <div class="flex items-end gap-3">
          <AppButton type="submit" variant="primary">{{ t('admin.inquiry.search_btn') }}</AppButton>
          <AppButton type="button" variant="ghost" @click="resetFilters">{{
            t('admin.inquiry.reset')
          }}</AppButton>
        </div>
      </form>
    </AppCard>

    <LoadingState v-if="loading" :label="t('admin.inquiry.loading')" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" @click="retryFetch">{{
        t('shell.retry')
      }}</AppButton>
    </AppCard>

    <EmptyState
      v-else-if="inquiries.length === 0"
      :title="t('admin.inquiry.empty_title')"
      :description="t('admin.inquiry.empty_desc')"
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
                {{ inquiry?.subject || t('admin.inquiry.untitled') }}
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
                :aria-label="t('admin.inquiry.view_aria', { name: displayName(inquiry) })"
                @click="selectInquiry(inquiry.id)"
              >
                {{ t('admin.inquiry.view') }}
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

    <section v-if="selectedId !== null" :aria-label="t('admin.inquiry.detail_aria')">
      <div class="mb-4">
        <AppButton variant="ghost" @click="backToList">{{ t('admin.inquiry.back') }}</AppButton>
      </div>

      <LoadingState v-if="detailLoading" :label="t('admin.inquiry.detail_loading')" />

      <AppCard v-else-if="detailError" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ detailError }}</p>
        <AppButton variant="outline" class="mt-4" @click="selectInquiry(selectedId)">
          {{ t('shell.retry') }}
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
            {{ t('shell.cancel') }}
          </AppButton>
          <AppButton
            :variant="confirmConfig?.variant === 'danger' ? 'danger' : 'primary'"
            :loading="mutating"
            @click="submitDialog"
          >
            {{ confirmConfig?.confirmText ?? t('admin.inquiry.confirm_fallback') }}
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>
