import { ref } from 'vue'
import i18n from '@/i18n'
import { useApi } from './useApi'
import { useAuthStore } from '@/stores/auth'
import { HTTP_STATUS } from '@/constants/http'
import { PaginationConstants } from '@/constants/pagination'
import { SUSPENDED_CODE } from '@/constants/admin'
import { ADMIN_ISSUES_PATH } from '@/constants/issues'

const t = (...args) => i18n.global.t(...args)

function isSessionFailure(error) {
  const status = error?.response?.status
  if (status === HTTP_STATUS.UNAUTHORIZED) return true
  return status === HTTP_STATUS.FORBIDDEN && error?.response?.data?.code === SUSPENDED_CODE
}

function errorMessage(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

function conflictError(error) {
  const failure = new Error(errorMessage(error, t('admin.issues.err_changed')))
  failure.isVersionConflict = true
  failure.currentVersion = error?.response?.data?.current_version ?? null
  failure.cause = error
  return failure
}

export function useAdminIssues() {
  const api = useApi()
  const authStore = useAuthStore()

  const list = ref([])
  const loading = ref(false)
  const error = ref('')
  const mutating = ref(false)
  const filters = ref({ status: '', category: '', search: '' })
  const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
  const selected = ref(null)
  const detailLoading = ref(false)
  const detailError = ref('')
  const detailRequestId = ref(0)

  function buildParams(page) {
    const params = { page, per_page: PaginationConstants.DEFAULT_PER_PAGE }
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.category) params.category = filters.value.category
    if (filters.value.search) params.search = filters.value.search
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

  function replaceTicket(updated) {
    const index = list.value.findIndex((entry) => entry && String(entry.id) === String(updated?.id))
    if (index !== -1) list.value[index] = updated
    if (selected.value && String(selected.value.id) === String(updated?.id)) {
      selected.value = updated
    }
  }

  async function fetchIssues(page = 1) {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(ADMIN_ISSUES_PATH, { params: buildParams(page) })
      applyList(response?.data, page)
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      list.value = []
      error.value = errorMessage(err, t('admin.issues.err_load'))
    } finally {
      loading.value = false
    }
  }

  async function fetchIssue(id) {
    detailRequestId.value += 1
    const requestId = detailRequestId.value
    detailLoading.value = true
    detailError.value = ''
    try {
      // Ticket IDs stay decimal strings end to end; never coerce through Number.
      const response = await api.get(`${ADMIN_ISSUES_PATH}/${id}`)
      if (requestId !== detailRequestId.value) return null
      selected.value = response?.data?.data ?? null
      return selected.value
    } catch (err) {
      if (requestId !== detailRequestId.value) return null
      if (isSessionFailure(err)) authStore.clearSession()
      detailError.value =
        err?.response?.status === HTTP_STATUS.NOT_FOUND
          ? 'This issue is no longer available.'
          : errorMessage(err, t('admin.issues.err_detail'))
      return null
    } finally {
      if (requestId === detailRequestId.value) detailLoading.value = false
    }
  }

  function clearSelection() {
    detailRequestId.value += 1
    selected.value = null
    detailError.value = ''
  }

  async function transitionIssue(id, { status, resolution, expected_version }) {
    mutating.value = true
    try {
      const response = await api.post(`${ADMIN_ISSUES_PATH}/${id}/transition`, {
        status,
        resolution: resolution ?? null,
        expected_version,
      })
      const updated = response?.data?.data ?? null
      if (updated) replaceTicket(updated)
      return updated
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      if (err?.response?.status === HTTP_STATUS.CONFLICT) throw conflictError(err)
      throw new Error(errorMessage(err, t('admin.issues.err_update')), { cause: err })
    } finally {
      mutating.value = false
    }
  }

  return {
    list,
    loading,
    error,
    mutating,
    filters,
    pagination,
    selected,
    detailLoading,
    detailError,
    fetchIssues,
    fetchIssue,
    clearSelection,
    transitionIssue,
  }
}
