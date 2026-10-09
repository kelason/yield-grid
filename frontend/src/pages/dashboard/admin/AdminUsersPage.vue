<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useAdminUsers } from '@/composables/useAdminUsers'
import { queryFilterValue } from '@/utils/adminQueryFilters'
import { useNotificationStore } from '@/stores/notificationStore'
import {
  ADMIN_SEARCH_MAX_LENGTH,
  ADMIN_USER_ROLE_OPTIONS,
  ADMIN_USER_STATUS_FILTER,
  ADMIN_USER_STATUS_OPTIONS,
  SUSPENSION_REASON_MAX_LENGTH,
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

const notificationStore = useNotificationStore()
const { t } = useI18n()

const CONFIRM_COPY = {
  suspend: {
    title: () => t('admin.users.suspend_title'),
    confirmText: () => t('admin.users.suspend_ok'),
    variant: 'danger',
    message: (name) => t('admin.users.suspend_msg', { name }),
  },
  unsuspend: {
    title: () => t('admin.users.restore_title'),
    confirmText: () => t('admin.users.restore_ok'),
    variant: 'primary',
    message: (name) => t('admin.users.restore_msg', { name }),
  },
}
const {
  list,
  loading,
  error,
  mutating,
  filters,
  pagination,
  fetchUsers,
  suspendUser,
  unsuspendUser,
} = useAdminUsers()

const route = useRoute()

function seedFiltersFromQuery() {
  const query = route?.query ?? {}
  const roles = ADMIN_USER_ROLE_OPTIONS.map((option) => option.value).filter(
    (value) => value !== '',
  )
  filters.value.role = queryFilterValue(query, 'role', roles)
  filters.value.suspended = queryFilterValue(query, 'suspended', [
    ADMIN_USER_STATUS_FILTER.ACTIVE,
    ADMIN_USER_STATUS_FILTER.SUSPENDED,
  ])
}

const filterError = ref('')
const pendingConfirm = ref(null)
const reasonDraft = ref('')
const reasonError = ref('')
const dialogError = ref('')

const users = computed(() =>
  (Array.isArray(list.value) ? list.value : []).filter(
    (entry) => entry && typeof entry === 'object',
  ),
)

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  const copy = CONFIRM_COPY[pendingConfirm.value.action]
  if (!copy) return null
  return {
    title: copy.title(),
    confirmText: copy.confirmText(),
    variant: copy.variant,
    message: copy.message(displayName(pendingConfirm.value.user)),
  }
})

const roleOptions = computed(() =>
  ADMIN_USER_ROLE_OPTIONS.map((option) => ({ value: option.value, label: t(option.labelKey) })),
)

const statusOptions = computed(() =>
  ADMIN_USER_STATUS_OPTIONS.map((option) => ({ value: option.value, label: t(option.labelKey) })),
)

function displayName(user) {
  return user?.name || t('admin.users.unknown_user')
}

function isSuspended(user) {
  return user?.suspended_at != null
}

function applySearch() {
  const search = String(filters.value.search ?? '')
  if (search.length > ADMIN_SEARCH_MAX_LENGTH) {
    filterError.value = t('admin.users.err_search', { max: ADMIN_SEARCH_MAX_LENGTH })
    return
  }
  filterError.value = ''
  fetchUsers(1)
}

function onRoleChange(value) {
  filters.value.role = value
  fetchUsers(1)
}

function onStatusChange(value) {
  filters.value.suspended = value
  fetchUsers(1)
}

function resetFilters() {
  filters.value = { search: '', role: '', suspended: ADMIN_USER_STATUS_FILTER.ALL }
  filterError.value = ''
  fetchUsers(1)
}

function retryFetch() {
  fetchUsers(pagination.value.currentPage)
}

function askConfirm(action, user) {
  if (!user) return
  pendingConfirm.value = { action, user }
  reasonDraft.value = ''
  reasonError.value = ''
  dialogError.value = ''
}

function cancelDialog() {
  if (!mutating.value) pendingConfirm.value = null
}

function validatedReason() {
  const reason = reasonDraft.value.trim()
  if (!reason) {
    reasonError.value = t('admin.users.err_reason')
    return null
  }
  if (reason.length > SUSPENSION_REASON_MAX_LENGTH) {
    reasonError.value = t('admin.users.err_reason_max', { max: SUSPENSION_REASON_MAX_LENGTH })
    return null
  }
  reasonError.value = ''
  return reason
}

async function submitDialog() {
  if (!pendingConfirm.value || mutating.value) return
  const reason = validatedReason()
  if (reason === null) return
  dialogError.value = ''
  try {
    const { action, user } = pendingConfirm.value
    if (action === 'suspend') await suspendUser(user.id, reason)
    else await unsuspendUser(user.id, reason)
    pendingConfirm.value = null
    reasonDraft.value = ''
    notificationStore.addNotification({
      type: 'success',
      messageKey:
        action === 'suspend' ? 'admin.users.suspended_toast' : 'admin.users.restored_toast',
    })
  } catch (err) {
    dialogError.value = err?.message || t('admin.users.update_failed')
  }
}

onMounted(() => {
  seedFiltersFromQuery()
  fetchUsers(1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="t('admin.users.title')" :description="t('admin.users.description')" />

    <AppCard padding="p-5">
      <form
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        @submit.prevent="applySearch"
      >
        <FormField
          id="admin-user-search"
          v-model="filters.search"
          :label="t('admin.users.search_label')"
          :placeholder="t('admin.users.search_ph')"
          :maxlength="ADMIN_SEARCH_MAX_LENGTH"
          :error="filterError"
        />
        <AppSelect
          id="admin-user-role"
          :model-value="filters.role"
          :label="t('admin.users.role_label')"
          @update:model-value="onRoleChange"
        >
          <option v-for="option in roleOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </AppSelect>
        <AppSelect
          id="admin-user-status"
          :model-value="filters.suspended"
          :label="t('admin.users.status_label')"
          @update:model-value="onStatusChange"
        >
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </AppSelect>
        <div class="flex items-end gap-3">
          <AppButton type="submit" variant="primary">{{ t('admin.users.search_btn') }}</AppButton>
          <AppButton type="button" variant="ghost" @click="resetFilters">{{
            t('admin.users.reset')
          }}</AppButton>
        </div>
      </form>
    </AppCard>

    <LoadingState v-if="loading" :label="t('admin.users.loading')" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" @click="retryFetch">{{
        t('shell.retry')
      }}</AppButton>
    </AppCard>

    <EmptyState
      v-else-if="users.length === 0"
      :title="t('admin.users.empty_title')"
      :description="t('admin.users.empty_desc')"
    />

    <ul v-else class="space-y-4">
      <li v-for="user in users" :key="user.id">
        <AppCard padding="p-5">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <h2 class="font-serif text-xl font-bold break-words text-stone-900">
                {{ displayName(user) }}
              </h2>
              <p class="mt-1 break-words text-sm text-stone-600">{{ user?.email }}</p>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <span
                  class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 text-xs font-medium capitalize text-soil-800"
                >
                  {{ user?.role || t('admin.users.role_unknown') }}
                </span>
                <span
                  v-if="isSuspended(user)"
                  class="inline-flex items-center rounded-full bg-harvest-100 px-2.5 py-0.5 text-xs font-medium text-harvest-800"
                >
                  {{ t('admin.users.suspended_badge') }}
                </span>
                <span
                  v-else
                  class="inline-flex items-center rounded-full bg-moss-100 px-2.5 py-0.5 text-xs font-medium text-moss-800"
                >
                  {{ t('admin.users.active_badge') }}
                </span>
              </div>
              <p
                v-if="isSuspended(user) && user?.suspended_reason"
                class="mt-2 text-sm text-stone-600"
              >
                {{ user.suspended_reason }}
              </p>
            </div>
            <div class="shrink-0 sm:w-36">
              <AppButton
                v-if="isSuspended(user)"
                variant="outline"
                class="w-full"
                :aria-label="t('admin.users.restore_aria', { name: displayName(user) })"
                @click="askConfirm('unsuspend', user)"
              >
                {{ t('admin.users.restore') }}
              </AppButton>
              <AppButton
                v-else
                variant="danger"
                class="w-full"
                :aria-label="t('admin.users.suspend_aria', { name: displayName(user) })"
                @click="askConfirm('suspend', user)"
              >
                {{ t('admin.users.suspend') }}
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
      @page-change="fetchUsers"
    />

    <AppModal
      :is-open="pendingConfirm !== null"
      :title="confirmConfig?.title ?? ''"
      :busy="mutating"
      @close="cancelDialog"
    >
      <div v-if="pendingConfirm && confirmConfig" class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">{{ confirmConfig.message }}</p>
        <FormField
          id="admin-user-reason"
          v-model="reasonDraft"
          :label="t('admin.users.reason_label')"
          multiline
          required
          :maxlength="SUSPENSION_REASON_MAX_LENGTH"
          :error="reasonError"
          :hint="t('admin.users.reason_hint')"
        />
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
            {{ confirmConfig?.confirmText ?? t('admin.users.confirm_fallback') }}
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>
