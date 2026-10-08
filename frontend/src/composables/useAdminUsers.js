import { ref } from 'vue'
import { useApi } from './useApi'
import { useAuthStore } from '@/stores/auth'
import { HTTP_STATUS } from '@/constants/http'
import { PaginationConstants } from '@/constants/pagination'
import { ADMIN_USERS_PATH, ADMIN_USER_STATUS_FILTER, SUSPENDED_CODE } from '@/constants/admin'

function isSessionFailure(error) {
  const status = error?.response?.status
  if (status === HTTP_STATUS.UNAUTHORIZED) return true
  return status === HTTP_STATUS.FORBIDDEN && error?.response?.data?.code === SUSPENDED_CODE
}

function errorMessage(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

function suspendedParam(filter) {
  if (filter === ADMIN_USER_STATUS_FILTER.ACTIVE) return false
  if (filter === ADMIN_USER_STATUS_FILTER.SUSPENDED) return true
  return null
}

export function useAdminUsers() {
  const api = useApi()
  const authStore = useAuthStore()

  const list = ref([])
  const loading = ref(false)
  const error = ref('')
  const mutating = ref(false)
  const filters = ref({ search: '', role: '', suspended: ADMIN_USER_STATUS_FILTER.ALL })
  const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })

  function buildParams(page) {
    const params = { page, per_page: PaginationConstants.DEFAULT_PER_PAGE }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.role) params.role = filters.value.role
    const suspended = suspendedParam(filters.value.suspended)
    if (suspended !== null) params.suspended = suspended
    return params
  }

  function applyList(payload, page) {
    list.value = Array.isArray(payload?.data) ? payload.data : []
    const meta = payload?.meta ?? {}
    pagination.value = {
      currentPage: meta.current_page ?? page,
      lastPage: meta.last_page ?? page,
      total: meta.total ?? list.value.length,
    }
  }

  function replaceUser(updated) {
    const index = list.value.findIndex((entry) => entry && String(entry.id) === String(updated?.id))
    if (index !== -1) list.value[index] = updated
  }

  async function fetchUsers(page = 1) {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(ADMIN_USERS_PATH, { params: buildParams(page) })
      applyList(response?.data, page)
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      list.value = []
      error.value = errorMessage(err, 'Unable to load users.')
    } finally {
      loading.value = false
    }
  }

  async function mutateUser(userId, action, reason) {
    // User IDs stay decimal strings end to end; never coerce through Number.
    mutating.value = true
    try {
      const response = await api.post(`${ADMIN_USERS_PATH}/${userId}/${action}`, { reason })
      const updated = response?.data?.data
      if (updated) replaceUser(updated)
      return updated
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      throw new Error(errorMessage(err, 'Unable to update this user.'), { cause: err })
    } finally {
      mutating.value = false
    }
  }

  async function suspendUser(userId, reason) {
    return mutateUser(userId, 'suspend', reason)
  }

  async function unsuspendUser(userId, reason) {
    return mutateUser(userId, 'unsuspend', reason)
  }

  return {
    list,
    loading,
    error,
    mutating,
    filters,
    pagination,
    fetchUsers,
    suspendUser,
    unsuspendUser,
  }
}
