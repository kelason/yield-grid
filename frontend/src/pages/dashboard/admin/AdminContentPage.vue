<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useAdminContent } from '@/composables/useAdminContent'
import { queryFilterValue } from '@/utils/adminQueryFilters'
import { useNotificationStore } from '@/stores/notificationStore'
import { ADMIN_SEARCH_MAX_LENGTH, SUSPENSION_REASON_MAX_LENGTH } from '@/constants/admin'
import {
  CONTENT_TYPE_OPTIONS,
  CONTENT_VISIBILITY,
  CONTENT_VISIBILITY_FILTER_OPTIONS,
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

const CONFIRM_COPY = {
  hide: {
    titleKey: 'admin.content.modal_hide_title',
    confirmKey: 'admin.content.modal_hide_cta',
    variant: 'danger',
  },
  restore: {
    titleKey: 'admin.content.modal_restore_title',
    confirmKey: 'admin.content.modal_restore_cta',
    variant: 'primary',
  },
}

const notificationStore = useNotificationStore()
const { t } = useI18n()
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

const confirmCopy = computed(() => {
  if (!pendingAction.value) return null
  const copy = CONFIRM_COPY[pendingAction.value]
  return { title: t(copy.titleKey), confirmText: t(copy.confirmKey), variant: copy.variant }
})

const typeButtons = computed(() =>
  CONTENT_TYPE_OPTIONS.map((option) => ({ value: option.value, label: t(option.labelKey) })),
)

const visibilityOptions = computed(() =>
  CONTENT_VISIBILITY_FILTER_OPTIONS.map((option) => ({
    value: option.value,
    label: t(option.labelKey),
  })),
)

const selectedIsHidden = computed(() => selected.value?.is_hidden === true)

const isClone = computed(() => {
  const rootId = selected.value?.moderation_root_id
  return rootId != null && String(rootId) !== String(selected.value?.id)
})

function displayTitle(item) {
  return item?.title || t('admin.content.untitled')
}

function displayExcerpt(item) {
  return item?.body ?? item?.description ?? ''
}

function typeLabel(type) {
  return t(REPORT_TARGET_LABEL_KEYS[type] ?? 'admin.content.type_fallback')
}

function rootMessage() {
  return t('admin.content.root_msg', {
    verb: t(
      pendingAction.value === 'hide' ? 'admin.content.verb_hide' : 'admin.content.verb_restore',
    ),
    label: typeLabel(selected.value?.type),
    id: selected.value?.moderation_root_id,
  })
}

function applySearch() {
  const search = String(filters.value.search ?? '')
  if (search.length > ADMIN_SEARCH_MAX_LENGTH) {
    filterError.value = t('admin.content.err_search_max', { max: ADMIN_SEARCH_MAX_LENGTH })
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
    reasonError.value = t('admin.content.err_reason')
    return null
  }
  if (reason.length > SUSPENSION_REASON_MAX_LENGTH) {
    reasonError.value = t('admin.content.err_reason_max', { max: SUSPENSION_REASON_MAX_LENGTH })
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
    notificationStore.addNotification({
      type: 'success',
      messageKey: selected.value?.is_hidden
        ? 'admin.content.hidden_toast'
        : 'admin.content.restored_toast',
    })
  } catch (err) {
    dialogError.value = err?.message || t('admin.content.err_moderate')
  }
}

onMounted(() => {
  seedFiltersFromQuery()
  fetchContent(activeType.value, 1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="t('admin.content.title')" :description="t('admin.content.description')" />

    <div class="flex flex-wrap gap-2" role="group" :aria-label="t('admin.content.types_aria')">
      <AppButton
        v-for="option in typeButtons"
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
          :label="t('admin.content.search_label')"
          :placeholder="t('admin.content.search_ph')"
          :maxlength="ADMIN_SEARCH_MAX_LENGTH"
          :error="filterError"
        />
        <AppSelect
          id="admin-content-visibility"
          :model-value="filters.visibility"
          :label="t('admin.content.vis_label')"
          @update:model-value="onVisibilityChange"
        >
          <option v-for="option in visibilityOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </AppSelect>
        <div class="flex items-end gap-3">
          <AppButton type="submit" variant="primary">{{ t('admin.content.search_btn') }}</AppButton>
          <AppButton type="button" variant="ghost" @click="resetFilters">{{
            t('admin.content.reset')
          }}</AppButton>
        </div>
      </form>
    </AppCard>

    <LoadingState v-if="loading" :label="t('admin.content.loading')" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" @click="retryFetch">{{
        t('shell.retry')
      }}</AppButton>
    </AppCard>

    <EmptyState
      v-else-if="items.length === 0"
      :title="t('admin.content.empty_title')"
      :description="t('admin.content.empty_desc')"
    />

    <ul
      v-else
      class="space-y-4"
      :aria-label="t('admin.content.list_aria', { label: typeLabel(activeType) })"
    >
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
                  {{ t('admin.content.hidden') }}
                </span>
                <span
                  v-else
                  class="inline-flex items-center rounded-full bg-moss-100 px-2.5 py-0.5 text-xs font-medium text-moss-800"
                >
                  {{ t('admin.content.visible') }}
                </span>
              </div>
            </div>
            <div class="shrink-0 sm:w-36">
              <AppButton
                variant="outline"
                class="w-full"
                :data-testid="`content-inspect-${item.id}`"
                :aria-label="t('admin.content.inspect_aria', { title: displayTitle(item) })"
                @click="inspectItem(item)"
              >
                {{ t('admin.content.inspect') }}
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

    <section
      v-if="selectedKey !== null"
      :aria-label="t('admin.content.detail_aria')"
      class="space-y-4"
    >
      <div class="flex items-center justify-between gap-3">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('admin.content.detail_title') }}
        </h2>
        <AppButton variant="ghost" @click="closeDetail">{{
          t('admin.content.close_detail')
        }}</AppButton>
      </div>

      <LoadingState v-if="detailLoading" :label="t('admin.content.detail_loading')" />

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
            {{ t('admin.content.author', { name: selected.author.name }) }}
          </p>
          <p class="text-sm text-stone-600">
            {{ t('admin.content.visibility') }}
            <span class="font-medium text-stone-900">{{
              selectedIsHidden ? t('admin.content.hidden') : t('admin.content.visible')
            }}</span>
          </p>
          <p
            v-if="selectedIsHidden && selected.hidden_reason"
            class="break-words text-sm text-stone-600"
          >
            {{ t('admin.content.hidden_reason', { reason: selected.hidden_reason }) }}
          </p>
          <p v-if="isClone" class="text-sm text-stone-600">
            {{
              t('admin.content.split', {
                label: typeLabel(selected.type),
                id: selected.moderation_root_id,
              })
            }}
          </p>
        </div>
        <div class="mt-5 flex flex-wrap gap-3">
          <AppButton
            v-if="selectedIsHidden"
            variant="primary"
            data-testid="content-restore"
            @click="askModeration('restore')"
          >
            {{ t('admin.content.btn_restore') }}
          </AppButton>
          <AppButton
            v-else
            variant="danger"
            data-testid="content-hide"
            @click="askModeration('hide')"
          >
            {{ t('admin.content.btn_hide') }}
          </AppButton>
        </div>
      </AppCard>
    </section>

    <AppModal
      :title="confirmCopy?.title ?? t('admin.content.modal_fallback')"
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
          :label="t('admin.content.reason_label')"
          :multiline="true"
          :maxlength="SUSPENSION_REASON_MAX_LENGTH"
          :required="true"
          :disabled="mutating"
          :error="reasonError"
          :placeholder="t('admin.content.reason_ph')"
        />
      </div>
      <template #footer>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <AppButton variant="secondary" :disabled="mutating" @click="cancelDialog">
            {{ t('shell.cancel') }}
          </AppButton>
          <AppButton
            :variant="confirmCopy?.variant ?? 'primary'"
            :loading="mutating"
            @click="submitDialog"
          >
            {{ confirmCopy?.confirmText ?? t('admin.content.confirm_fallback') }}
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>
