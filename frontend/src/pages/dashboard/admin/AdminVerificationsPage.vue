<script setup>
import { ref, computed, onMounted } from 'vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import FormField from '@/components/molecules/FormField.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import VerificationPlotMap from '@/components/organisms/VerificationPlotMap.vue'
import VerifiedBadge from '@/components/atoms/VerifiedBadge.vue'
import { useAdminVerifications } from '@/composables/useAdminVerifications'
import {
  VERIFICATION_STATUS,
  VERIFICATION_METHOD_OPTIONS,
  VERIFICATION_STATUS_OPTIONS,
  VERIFICATION_NOTE_MAX_LENGTH,
} from '@/constants/verification'

const queue = useAdminVerifications()
const {
  list,
  loading,
  error,
  mutating,
  dialogError,
  filters,
  pagination,
  selected,
  detailLoading,
  detailError,
} = queue

const SCOPE_TABS = [
  { value: 'farms', label: 'Farms', testid: 'scope-farms' },
  { value: 'plots', label: 'Plots', testid: 'scope-plots' },
]

const STATUS_PILL_CLASSES = {
  [VERIFICATION_STATUS.PENDING]: 'bg-harvest-100 text-harvest-800',
  [VERIFICATION_STATUS.VERIFIED]: 'bg-moss-100 text-moss-800',
  [VERIFICATION_STATUS.REJECTED]: 'bg-stone-100 text-stone-600',
}

const selectedId = ref(null)
const methodDraft = ref('')
const noteDraft = ref('')
const methodError = ref('')
const noteError = ref('')
const pendingConfirm = ref(null)

const methodOptions = computed(() => [
  { value: '', label: 'Choose a method' },
  ...VERIFICATION_METHOD_OPTIONS,
])

const availableDecisions = computed(() => {
  switch (queue.selected.value?.verification_status) {
    case VERIFICATION_STATUS.VERIFIED:
      return ['revoke']
    case VERIFICATION_STATUS.REJECTED:
      return ['verify', 'reopen']
    default:
      return ['verify', 'reject']
  }
})

const DECISION_COPY = {
  verify: { confirmText: 'Verify', type: 'primary' },
  reject: { confirmText: 'Reject', type: 'danger' },
  revoke: { confirmText: 'Revoke', type: 'danger' },
  reopen: { confirmText: 'Reopen', type: 'primary' },
}

const confirmConfig = computed(() => {
  if (!pendingConfirm.value || !queue.selected.value) return null
  const { decision } = pendingConfirm.value
  const name = queue.selected.value.name
  const methodLabel = VERIFICATION_METHOD_OPTIONS.find((o) => o.value === methodDraft.value)?.label
  const summaries = {
    verify: `Verify "${name}"${methodLabel ? ` using ${methodLabel}` : ''}?`,
    reject: `Reject "${name}"? The farmer will see this reason.`,
    revoke: `Revoke verification for "${name}"? The farmer will see this reason.`,
    reopen: `Send "${name}" back to pending review?`,
  }
  const base = DECISION_COPY[decision]
  const message = dialogError.value
    ? `${summaries[decision]} Error: ${dialogError.value}`
    : summaries[decision]
  return { title: `${base.confirmText} record`, message, confirmText: base.confirmText, type: base.type }
})

function clearDrafts() {
  methodDraft.value = ''
  noteDraft.value = ''
  methodError.value = ''
  noteError.value = ''
}

function validateDrafts(decision) {
  methodError.value = ''
  noteError.value = ''
  if (decision === 'verify' && !methodDraft.value) {
    methodError.value = 'Choose how this record was verified.'
  }
  if ((decision === 'reject' || decision === 'revoke') && !noteDraft.value.trim()) {
    noteError.value = 'A reason is required — the farmer will see it.'
  }
  if (noteDraft.value.length > VERIFICATION_NOTE_MAX_LENGTH) {
    noteError.value = `Keep the note under ${VERIFICATION_NOTE_MAX_LENGTH} characters.`
  }
  return !methodError.value && !noteError.value
}

function openConfirm(decision) {
  if (!queue.selected.value || !validateDrafts(decision)) return
  dialogError.value = ''
  pendingConfirm.value = {
    scope: queue.selected.value.type === 'plot' ? 'plots' : 'farms',
    id: queue.selected.value.id,
    decision,
  }
}

async function confirmPending() {
  if (!pendingConfirm.value) return
  const { scope, id, decision } = pendingConfirm.value
  const payload =
    decision === 'verify'
      ? { method: methodDraft.value, note: noteDraft.value.trim() || null }
      : decision === 'reopen'
        ? {}
        : { reason: noteDraft.value.trim() }
  try {
    await queue.decide(scope, id, decision, payload)
    pendingConfirm.value = null
    clearDrafts()
  } catch {
    // dialogError is set by the composable; the dialog stays open with drafts retained.
  }
}

function cancelConfirm() {
  pendingConfirm.value = null
  dialogError.value = ''
}

async function selectRow(entry) {
  selectedId.value = entry.id
  clearDrafts()
  await queue.fetchDetail(filters.value.scope, entry.id)
}

function setScope(scope) {
  if (filters.value.scope === scope) return
  filters.value.scope = scope
  selectedId.value = null
  clearDrafts()
  queue.resetDetail()
  queue.fetchQueue(1)
}

function setStatus(status) {
  filters.value.status = status
  selectedId.value = null
  clearDrafts()
  queue.resetDetail()
  queue.fetchQueue(1)
}

function statusLabel(status) {
  return VERIFICATION_STATUS_OPTIONS.find((o) => o.value === status)?.label ?? status
}

onMounted(() => queue.fetchQueue(1))
</script>

<template>
  <div class="space-y-6 px-4 sm:px-6 lg:px-8">
    <PageHeader
      title="Farm verifications"
      description="Review farms and plots against real life, then verify, reject, or revoke them."
    />

    <div class="flex flex-wrap items-center gap-3">
      <div role="tablist" aria-label="Verification scope" class="flex gap-2">
        <AppButton
          v-for="tab in SCOPE_TABS"
          :key="tab.value"
          :data-testid="tab.testid"
          role="tab"
          :aria-selected="filters.scope === tab.value"
          :variant="filters.scope === tab.value ? 'primary' : 'outline'"
          size="sm"
          @click="setScope(tab.value)"
        >
          {{ tab.label }}
        </AppButton>
      </div>
      <AppSelect
        id="verification-status"
        label="Status"
        :options="VERIFICATION_STATUS_OPTIONS"
        :model-value="filters.status"
        @update:model-value="setStatus"
      />
    </div>

    <LoadingState v-if="loading" label="Loading verifications" />

    <AppCard v-else-if="error" data-testid="queue-error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton
        variant="outline"
        class="mt-4"
        data-testid="queue-retry"
        @click="queue.fetchQueue(pagination.currentPage)"
      >
        Retry
      </AppButton>
    </AppCard>

    <EmptyState
      v-else-if="list.length === 0"
      title="No records found"
      description="No farms or plots match the current filters."
    />

    <ul v-else aria-label="Verification queue" class="space-y-4">
      <li v-for="entry in list" :key="`${entry.type}-${entry.id}`">
        <button
          type="button"
          data-testid="verification-row"
          class="w-full rounded-2xl border border-stone-200 bg-white p-5 text-left shadow-soft transition-all duration-300 hover:border-moss-300"
          @click="selectRow(entry)"
        >
          <div class="flex items-start justify-between gap-4">
            <h3 class="min-w-0 break-words font-serif text-xl font-bold text-stone-900">
              {{ entry.name }}
            </h3>
            <span
              class="shrink-0 rounded-full px-3 py-1 text-xs font-medium"
              :class="STATUS_PILL_CLASSES[entry.verification_status] ?? 'bg-stone-100 text-stone-600'"
            >
              {{ statusLabel(entry.verification_status) }}
            </span>
          </div>
          <p class="mt-2 text-sm text-stone-600">
            {{ entry.type === 'plot' ? 'Plot' : 'Farm' }} · {{ entry.farmer?.name ?? 'Unknown farmer' }}
          </p>
        </button>
      </li>
    </ul>

    <PaginationControls
      v-if="!loading && !error && list.length > 0"
      :current-page="pagination.currentPage"
      :last-page="pagination.lastPage"
      :total="pagination.total"
      @page-change="queue.fetchQueue"
    />

    <section v-if="selectedId !== null" aria-label="Verification detail" class="space-y-4">
      <LoadingState v-if="detailLoading" label="Loading record" />

      <AppCard v-else-if="detailError" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ detailError }}</p>
        <AppButton variant="outline" class="mt-4" @click="selectRow({ id: selectedId })">
          Retry
        </AppButton>
      </AppCard>

      <AppCard v-else-if="selected" data-testid="verification-detail" padding="p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <h2 class="font-serif text-2xl font-bold text-stone-900">{{ selected.name }}</h2>
          <VerifiedBadge v-if="selected.verification_status === VERIFICATION_STATUS.VERIFIED" />
        </div>
        <dl class="mt-4 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
          <div>
            <dt class="font-medium text-soil-700">Type</dt>
            <dd class="text-stone-900">{{ selected.type === 'plot' ? 'Plot' : 'Farm' }}</dd>
          </div>
          <div>
            <dt class="font-medium text-soil-700">Status</dt>
            <dd class="text-stone-900">{{ statusLabel(selected.verification_status) }}</dd>
          </div>
          <div>
            <dt class="font-medium text-soil-700">Farmer</dt>
            <dd class="text-stone-900">{{ selected.farmer?.name ?? 'Unknown' }}</dd>
          </div>
          <div v-if="selected.type === 'farm'">
            <dt class="font-medium text-soil-700">Location</dt>
            <dd class="text-stone-900">
              {{ [selected.city, selected.state, selected.country].filter(Boolean).join(', ') || 'Not provided' }}
            </dd>
          </div>
          <div v-if="selected.type === 'plot'">
            <dt class="font-medium text-soil-700">Farm</dt>
            <dd class="text-stone-900">{{ selected.farm?.name ?? 'Unknown' }}</dd>
          </div>
          <div v-if="selected.verification_method">
            <dt class="font-medium text-soil-700">Method</dt>
            <dd class="text-stone-900">{{ selected.verification_method }}</dd>
          </div>
          <div v-if="selected.verification_note" class="sm:col-span-2">
            <dt class="font-medium text-soil-700">Note</dt>
            <dd class="text-stone-900">{{ selected.verification_note }}</dd>
          </div>
        </dl>

        <VerificationPlotMap
          v-if="selected.type === 'plot'"
          :geojson="selected.geojson ?? null"
          class="mt-4"
        />

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
          <AppSelect
            id="verify-method"
            label="Verification method (required when verifying)"
            :options="methodOptions"
            v-model="methodDraft"
            :disabled="mutating"
            :error="methodError"
          />
          <FormField
            id="verify-note"
            v-model="noteDraft"
            label="Decision note"
            :multiline="true"
            :maxlength="VERIFICATION_NOTE_MAX_LENGTH"
            :disabled="mutating"
            :error="noteError"
            hint="Required when rejecting or revoking — the farmer will see it."
            placeholder="How was this verified, or why was it rejected?"
          />
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <AppButton
            v-for="decision in availableDecisions"
            :key="decision"
            :data-testid="`decision-${decision}`"
            :variant="decision === 'reject' || decision === 'revoke' ? 'danger' : 'primary'"
            :disabled="mutating"
            @click="openConfirm(decision)"
          >
            {{ DECISION_COPY[decision].confirmText }}
          </AppButton>
        </div>
      </AppCard>
    </section>

    <ConfirmModal
      :is-open="pendingConfirm !== null"
      :title="confirmConfig?.title ?? 'Confirm action'"
      :message="confirmConfig?.message ?? ''"
      :confirm-text="confirmConfig?.confirmText ?? 'Confirm'"
      :type="confirmConfig?.type ?? 'primary'"
      :loading="mutating"
      @confirm="confirmPending"
      @cancel="cancelConfirm"
    />
  </div>
</template>
