import { ref } from 'vue'
import { useApi } from './useApi'
import { useAuthStore } from '@/stores/auth'
import { HTTP_STATUS } from '@/constants/http'
import { PaginationConstants } from '@/constants/pagination'
import { SUSPENDED_CODE } from '@/constants/admin'
import { ADMIN_REPORTS_PATH } from '@/constants/reporting'

function isSessionFailure(error) {
  const status = error?.response?.status
  if (status === HTTP_STATUS.UNAUTHORIZED) return true
  return status === HTTP_STATUS.FORBIDDEN && error?.response?.data?.code === SUSPENDED_CODE
}

function errorMessage(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

function conflictError(error) {
  const failure = new Error(errorMessage(error, 'This report changed. Reload and retry.'))
  failure.isVersionConflict = true
  failure.currentVersion = error?.response?.data?.current_version ?? null
  failure.cause = error
  return failure
}

export function useAdminReports() {
  const api = useApi()
  const authStore = useAuthStore()

  const list = ref([])
  const loading = ref(false)
  const error = ref('')
  const mutating = ref(false)
  const filters = ref({ status: '', type: '', reason: '' })
  const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
  const selected = ref(null)
  const detailLoading = ref(false)
  const detailError = ref('')
  const detailRequestId = ref(0)

  function buildParams(page) {
    const params = { page, per_page: PaginationConstants.DEFAULT_PER_PAGE }
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.type) params.type = filters.value.type
    if (filters.value.reason) params.reason = filters.value.reason
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

  function replaceReport(updated) {
    const index = list.value.findIndex((entry) => entry && String(entry.id) === String(updated?.id))
    if (index !== -1) list.value[index] = updated
    if (selected.value && String(selected.value.id) === String(updated?.id)) {
      selected.value = updated
    }
  }

  async function fetchReports(page = 1) {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(ADMIN_REPORTS_PATH, { params: buildParams(page) })
      applyList(response?.data, page)
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      list.value = []
      error.value = errorMessage(err, 'Unable to load reports.')
    } finally {
      loading.value = false
    }
  }

  async function fetchReport(id) {
    detailRequestId.value += 1
    const requestId = detailRequestId.value
    detailLoading.value = true
    detailError.value = ''
    try {
      // Report IDs stay decimal strings end to end; never coerce through Number.
      const response = await api.get(`${ADMIN_REPORTS_PATH}/${id}`)
      if (requestId !== detailRequestId.value) return null
      selected.value = response?.data?.data ?? null
      return selected.value
    } catch (err) {
      if (requestId !== detailRequestId.value) return null
      if (isSessionFailure(err)) authStore.clearSession()
      detailError.value = errorMessage(err, 'Unable to load this report.')
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

  async function decide(id, { status, outcome, note, expected_version }) {
    mutating.value = true
    try {
      const response = await api.post(`${ADMIN_REPORTS_PATH}/${id}/decision`, {
        status,
        outcome: outcome ?? null,
        note,
        expected_version,
      })
      const updated = response?.data?.data ?? null
      if (updated) replaceReport(updated)
      return updated
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      if (err?.response?.status === HTTP_STATUS.CONFLICT) throw conflictError(err)
      throw new Error(errorMessage(err, 'Unable to decide this report.'), { cause: err })
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
    fetchReports,
    fetchReport,
    clearSelection,
    decide,
  }
}
