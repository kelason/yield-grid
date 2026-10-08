<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAdminUsers } from '@/composables/useAdminUsers'
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

const CONFIRM_COPY = {
  suspend: {
    title: 'Suspend user?',
    confirmText: 'Suspend user',
    variant: 'danger',
    message: (name) =>
      `Suspend ${name}? Their sessions end immediately and they cannot sign in until restored.`,
  },
  unsuspend: {
    title: 'Restore user?',
    confirmText: 'Restore user',
    variant: 'primary',
    message: (name) => `Restore ${name}? They can sign in again with a fresh login.`,
  },
}

const notificationStore = useNotificationStore()
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
  return { ...copy, message: copy.message(displayName(pendingConfirm.value.user)) }
})

function displayName(user) {
  return user?.name || 'Unknown user'
}

function isSuspended(user) {
  return user?.suspended_at != null
}

function applySearch() {
  const search = String(filters.value.search ?? '')
  if (search.length > ADMIN_SEARCH_MAX_LENGTH) {
    filterError.value = `Search must be ${ADMIN_SEARCH_MAX_LENGTH} characters or fewer.`
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
    notificationStore.success(action === 'suspend' ? 'User suspended.' : 'User restored.')
  } catch (err) {
    dialogError.value = err?.message || 'Unable to update this user.'
  }
}

onMounted(() => {
  fetchUsers(1)
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Users"
      description="Review member accounts. Suspending ends every session at once."
    />

    <AppCard padding="p-5">
      <form
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        @submit.prevent="applySearch"
      >
        <FormField
          id="admin-user-search"
          v-model="filters.search"
          label="Search users"
          placeholder="Name or email"
          :maxlength="ADMIN_SEARCH_MAX_LENGTH"
          :error="filterError"
        />
        <AppSelect
          id="admin-user-role"
          :model-value="filters.role"
          label="Role"
          @update:model-value="onRoleChange"
        >
          <option
            v-for="option in ADMIN_USER_ROLE_OPTIONS"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </AppSelect>
        <AppSelect
          id="admin-user-status"
          :model-value="filters.suspended"
          label="Status"
          @update:model-value="onStatusChange"
        >
          <option
            v-for="option in ADMIN_USER_STATUS_OPTIONS"
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

    <LoadingState v-if="loading" label="Loading users" />

    <AppCard v-else-if="error" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" @click="retryFetch">Retry</AppButton>
    </AppCard>

    <EmptyState
      v-else-if="users.length === 0"
      title="No users found"
      description="No accounts match the current filters."
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
                  {{ user?.role || 'unknown' }}
                </span>
                <span
                  v-if="isSuspended(user)"
                  class="inline-flex items-center rounded-full bg-harvest-100 px-2.5 py-0.5 text-xs font-medium text-harvest-800"
                >
                  Suspended
                </span>
                <span
                  v-else
                  class="inline-flex items-center rounded-full bg-moss-100 px-2.5 py-0.5 text-xs font-medium text-moss-800"
                >
                  Active
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
                :aria-label="`Restore ${displayName(user)}`"
                @click="askConfirm('unsuspend', user)"
              >
                Restore
              </AppButton>
              <AppButton
                v-else
                variant="danger"
                class="w-full"
                :aria-label="`Suspend ${displayName(user)}`"
                @click="askConfirm('suspend', user)"
              >
                Suspend
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
          label="Reason"
          multiline
          required
          :maxlength="SUSPENSION_REASON_MAX_LENGTH"
          :error="reasonError"
          hint="Recorded with this decision."
        />
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
