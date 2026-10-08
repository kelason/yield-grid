<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAdminContent } from '@/composables/useAdminContent'
import { queryFilterValue } from '@/utils/adminQueryFilters'
import { useNotificationStore } from '@/stores/notificationStore'
import { ADMIN_SEARCH_MAX_LENGTH, SUSPENSION_REASON_MAX_LENGTH } from '@/constants/admin'
import {
  CONTENT_TYPE_OPTIONS,
  CONTENT_VISIBILITY,
  CONTENT_VISIBILITY_FILTER_OPTIONS,
  REPORT_TARGET_LABELS,
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

const CONFIRM_COPY = {
  hide: { title: 'Hide this content?', confirmText: 'Hide content', variant: 'danger' },
  restore: { title: 'Restore this content?', confirmText: 'Restore content', variant: 'primary' },
}

const notificationStore = useNotificationStore()
const {
  activeType,
  list,
  loading,
  error,
  mutating,
  filters,
  pagination,
  selected,
  detailLoading,
  detailError,
  fetchContent,
  fetchDetail,
  clearSelection,
  hide,
  restore,
} = useAdminContent()

const route = useRoute()

function seedFiltersFromQuery() {
  const query = route?.query ?? {}
  const type = queryFilterValue(query, 'type', Object.values(REPORT_TARGET_TYPE))
  if (type) activeType.value = type
  filters.value.visibility = queryFilterValue(
    query,
    'visibility',
    Object.values(CONTENT_VISIBILITY),
  )
}

const selectedKey = ref(null)
const filterError = ref('')
const pendingAction = ref(null)
const reasonDraft = ref('')
const reasonError = ref('')
const dialogError = ref('')

const items = computed(() =>
  (Array.isArray(list.value) ? list.value : []).filter(
    (entry) => entry && typeof entry === 'object',
  ),
)

const confirmCopy = computed(() => (pendingAction.value ? CONFIRM_COPY[pendingAction.value] : null))

const selectedIsHidden = computed(() => selected.value?.is_hidden === true)

const isClone = computed(() => {
  const rootId = selected.value?.moderation_root_id
  return rootId != null && String(rootId) !== String(selected.value?.id)
})

function displayTitle(item) {
  return item?.title || 'Untitled content'
}

function displayExcerpt(item) {
  return item?.body ?? item?.description ?? ''
}

function typeLabel(type) {
  return REPORT_TARGET_LABELS[type] ?? 'Content'
}

function rootMessage() {
  const rootId = selected.value?.moderation_root_id
  const verb = pendingAction.value === 'hide' ? 'Hiding' : 'Restoring'
  return (
    `${verb} this record moderates the whole split family rooted at ${typeLabel(selected.value?.type)} ` +
    `#${rootId}, including future split records created from that root.`
  )
}

function applySearch() {
  const search = String(filters.value.search ?? '')
  if (search.length > ADMIN_SEARCH_MAX_LENGTH) {
    filterError.value = `Search must be ${ADMIN_SEARCH_MAX_LENGTH} characters or fewer.`
    return
  }
  filterError.value = ''
  selectedKey.value = null
  clearSelection()
  fetchContent(activeType.value, 1)
}

function selectType(type) {
  if (mutating.value) return
  filters.value = { search: '', visibility: '' }
  filterError.value = ''
  selectedKey.value = null
  clearSelection()
  fetchContent(type, 1)
}

function onVisibilityChange(value) {
  filters.value.visibility = value
  selectedKey.value = null
  clearSelection()
  fetchContent(activeType.value, 1)
}

function resetFilters() {
  filters.value = { search: '', visibility: '' }
  filterError.value = ''
  selectedKey.value = null
  clearSelection()
  fetchContent(activeType.value, 1)
}

function retryFetch() {
  fetchContent(activeType.value, pagination.value.currentPage)
}

function handlePageChange(page) {
  fetchContent(activeType.value, page)
}

async function inspectItem(item) {
  selectedKey.value = `${activeType.value}:${item.id}`
  pendingAction.value = null
  await fetchDetail(activeType.value, item.id)
}

function closeDetail() {
  selectedKey.value = null
  pendingAction.value = null
  clearSelection()
}

function askModeration(action) {
  if (!selected.value || mutating.value) return
  pendingAction.value = action
  reasonDraft.value = ''
  reasonError.value = ''
  dialogError.value = ''
}

function cancelDialog() {
  if (!mutating.value) pendingAction.value = null
}

function validatedReason() {
  const reason = reasonDraft.value.trim()
  if (!reason) {
    reasonError.value = 'Enter a reason for this decision.'
    return null
  }
  if (reason.length > SUSPENSION_REASON_MAX_LENGTH) {
    reasonError.value = `Reason must be ${SUSPENSION_REASON_MAX_LENGTH} characters or fewer.`
    return null
  }
  reasonError.value = ''
  return reason
}

async function submitDialog() {
  if (!pendingAction.value || mutating.value || !selected.value) return
  const reason = validatedReason()
  if (reason === null) return
  dialogError.value = ''
  try {
    const { type, id } = selected.value
    if (pendingAction.value === 'hide') await hide(type, id, reason)
    else await restore(type, id, reason)
    pendingAction.value = null
    reasonDraft.value = ''
    notificationStore.success(selected.value?.is_hidden ? 'Content hidden.' : 'Content restored.')
  } catch (err) {
    dialogError.value = err?.message || 'Unable to moderate this content.'
  }
}

onMounted(() => {
  seedFiltersFromQuery()
  fetchContent(activeType.value, 1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Content"
      description="Browse reported content by type. Hiding is reversible and never deletes records."
    />

    <div class="flex flex-wrap gap-2" role="group" aria-label="Content types">
      <AppButton
        v-for="option in CONTENT_TYPE_OPTIONS"
        :key="option.value"
        :variant="activeType === option.value ? 'primary' : 'outline'"
        :data-testid="`content-type-${option.value}`"
        :aria-pressed="activeType === option.value"
        @click="selectType(option.value)"
      >
        {{ option.label }}
      </AppButton>
    </div>

    <AppCard padding="p-5">
      <form
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        @submit.prevent="applySearch"
      >
        <FormField
          id="admin-content-search"
          v-model="filters.search"
          label="Search content"
          placeholder="Title or text"
          :maxlength="ADMIN_SEARCH_MAX_LENGTH"
          :error="filterError"
        />
        <AppSelect
          id="admin-content-visibility"
          :model-value="filters.visibility"
          label="Visibility"
          @update:model-value="onVisibilityChange"
        >
          <option
            v-for="option in CONTENT_VISIBILITY_FILTER_OPTIONS"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </AppSelect>
        <div class="flex items-end gap-3">
          <AppButton type="submit" variant="primary">Search</AppButton>
          <AppButton type="button" variant="ghost" @click="resetFilters">Reset filters</AppButton>
        </div>
      </form>
    </AppCard>

    <LoadingState v-if="loading" label="Loading content" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" @click="retryFetch">Retry</AppButton>
    </AppCard>

    <EmptyState
      v-else-if="items.length === 0"
      title="Nothing here"
      description="No records match the current filters."
    />

    <ul v-else class="space-y-4" :aria-label="`${typeLabel(activeType)} records`">
      <li v-for="item in items" :key="item.id">
        <AppCard padding="p-5">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <h2 class="break-words font-serif text-xl font-bold text-stone-900">
                {{ displayTitle(item) }}
              </h2>
              <p
                v-if="displayExcerpt(item)"
                class="mt-1 line-clamp-2 break-words text-sm text-stone-600"
              >
                {{ displayExcerpt(item) }}
              </p>
              <div class="mt-2">
                <span
                  v-if="item.is_hidden"
                  class="inline-flex items-center rounded-full bg-harvest-100 px-2.5 py-0.5 text-xs font-medium text-harvest-800"
                >
                  Hidden
                </span>
                <span
                  v-else
                  class="inline-flex items-center rounded-full bg-moss-100 px-2.5 py-0.5 text-xs font-medium text-moss-800"
                >
                  Visible
                </span>
              </div>
            </div>
            <div class="shrink-0 sm:w-36">
              <AppButton
                variant="outline"
                class="w-full"
                :data-testid="`content-inspect-${item.id}`"
                :aria-label="`Inspect ${displayTitle(item)}`"
                @click="inspectItem(item)"
              >
                Inspect
              </AppButton>
            </div>
          </div>
        </AppCard>
      </li>
    </ul>

    <PaginationControls
      v-if="!loading && !error && items.length > 0"
      :current-page="pagination.currentPage"
      :last-page="pagination.lastPage"
      :total="pagination.total"
      @page-change="handlePageChange"
    />

    <section v-if="selectedKey !== null" aria-label="Content detail" class="space-y-4">
      <div class="flex items-center justify-between gap-3">
        <h2 class="font-serif text-2xl font-bold text-stone-900">Content detail</h2>
        <AppButton variant="ghost" @click="closeDetail">Close detail</AppButton>
      </div>

      <LoadingState v-if="detailLoading" label="Loading content detail" />

      <AppCard v-else-if="detailError" padding="p-6" role="alert">
        <p class="text-base text-stone-900">{{ detailError }}</p>
      </AppCard>

      <AppCard v-else-if="selected" padding="p-5">
        <div class="space-y-2">
          <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">
            {{ typeLabel(selected.type) }}
          </p>
          <h3 class="break-words font-serif text-xl font-bold text-stone-900">
            {{ displayTitle(selected) }}
          </h3>
          <p
            v-if="displayExcerpt(selected)"
            class="break-words text-sm leading-relaxed text-stone-700"
          >
            {{ displayExcerpt(selected) }}
          </p>
          <p v-if="selected.author?.name" class="text-sm text-stone-600">
            Author: {{ selected.author.name }}
          </p>
          <p class="text-sm text-stone-600">
            Visibility:
            <span class="font-medium text-stone-900">{{
              selectedIsHidden ? 'Hidden' : 'Visible'
            }}</span>
          </p>
          <p
            v-if="selectedIsHidden && selected.hidden_reason"
            class="break-words text-sm text-stone-600"
          >
            Hidden reason: {{ selected.hidden_reason }}
          </p>
          <p v-if="isClone" class="text-sm text-stone-600">
            Split record of {{ typeLabel(selected.type) }} #{{ selected.moderation_root_id }}.
          </p>
        </div>
        <div class="mt-5 flex flex-wrap gap-3">
          <AppButton
            v-if="selectedIsHidden"
            variant="primary"
            data-testid="content-restore"
            @click="askModeration('restore')"
          >
            Restore content
          </AppButton>
          <AppButton
            v-else
            variant="danger"
            data-testid="content-hide"
            @click="askModeration('hide')"
          >
            Hide content
          </AppButton>
        </div>
      </AppCard>
    </section>

    <AppModal
      :title="confirmCopy?.title ?? 'Moderate content'"
      :is-open="pendingAction !== null"
      :busy="mutating"
      @close="cancelDialog"
    >
      <div v-if="pendingAction" class="space-y-5">
        <p class="break-words text-base leading-relaxed text-stone-600">
          {{ displayTitle(selected) }}
        </p>
        <p v-if="isClone" class="break-words text-base leading-relaxed text-stone-600">
          {{ rootMessage() }}
        </p>
        <AppAlert v-if="dialogError" type="error">{{ dialogError }}</AppAlert>
        <FormField
          id="content-moderation-reason"
          v-model="reasonDraft"
          label="Reason"
          :multiline="true"
          :maxlength="SUSPENSION_REASON_MAX_LENGTH"
          :required="true"
          :disabled="mutating"
          :error="reasonError"
          placeholder="Why is this moderation needed?"
        />
      </div>
      <template #footer>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <AppButton variant="secondary" :disabled="mutating" @click="cancelDialog">
            Cancel
          </AppButton>
          <AppButton
            :variant="confirmCopy?.variant ?? 'primary'"
            :loading="mutating"
            @click="submitDialog"
          >
            {{ confirmCopy?.confirmText ?? 'Confirm' }}
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>
