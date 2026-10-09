import { ref } from 'vue'
import { useApi } from './useApi'
import { useAuthStore } from '@/stores/auth'
import { HTTP_STATUS } from '@/constants/http'
import { PaginationConstants } from '@/constants/pagination'
import { SUSPENDED_CODE } from '@/constants/admin'
import { ADMIN_VERIFICATIONS_PATH } from '@/constants/verification'

function isSessionFailure(error) {
  const status = error?.response?.status
  if (status === HTTP_STATUS.UNAUTHORIZED) return true
  return status === HTTP_STATUS.FORBIDDEN && error?.response?.data?.code === SUSPENDED_CODE
}

function errorMessage(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

export function useAdminVerifications() {
  const api = useApi()
  const authStore = useAuthStore()

  const list = ref([])
  const loading = ref(false)
  const error = ref('')
  const mutating = ref(false)
  const dialogError = ref('')
  const filters = ref({ scope: 'farms', status: 'pending' })
  const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
  const selected = ref(null)
  const detailLoading = ref(false)
  const detailError = ref('')
  const detailRequestId = ref(0)

  function buildParams(page) {
    const params = { page, per_page: PaginationConstants.DEFAULT_PER_PAGE }
    if (filters.value.scope) params.scope = filters.value.scope
    if (filters.value.status) params.status = filters.value.status
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

  function replaceRow(updated) {
    const index = list.value.findIndex((entry) => entry && String(entry.id) === String(updated?.id))
    if (index !== -1) list.value[index] = updated
    if (selected.value && String(selected.value.id) === String(updated?.id)) {
      selected.value = updated
    }
  }

  async function fetchQueue(page = 1) {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(ADMIN_VERIFICATIONS_PATH, { params: buildParams(page) })
      applyList(response?.data, page)
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      list.value = []
      error.value = errorMessage(err, 'Unable to load verifications.')
    } finally {
      loading.value = false
    }
  }

  async function fetchDetail(scope, id) {
    detailRequestId.value += 1
    const requestId = detailRequestId.value
    detailLoading.value = true
    detailError.value = ''
    try {
      const response = await api.get(`${ADMIN_VERIFICATIONS_PATH}/${scope}/${id}`)
      if (requestId !== detailRequestId.value) return null
      selected.value = response?.data?.data ?? null
      return selected.value
    } catch (err) {
      if (requestId !== detailRequestId.value) return null
      if (isSessionFailure(err)) authStore.clearSession()
      detailError.value =
        err?.response?.status === HTTP_STATUS.NOT_FOUND
          ? 'This record is no longer available.'
          : errorMessage(err, 'Unable to load this record.')
      return null
    } finally {
      if (requestId === detailRequestId.value) detailLoading.value = false
    }
  }

  function resetDetail() {
    detailRequestId.value += 1
    selected.value = null
    detailError.value = ''
    dialogError.value = ''
  }

  async function decide(scope, id, decision, payload) {
    mutating.value = true
    dialogError.value = ''
    try {
      const response = await api.post(
        `${ADMIN_VERIFICATIONS_PATH}/${scope}/${id}/${decision}`,
        payload,
      )
      const updated = response?.data?.data ?? null
      if (updated) replaceRow(updated)
      return updated
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      dialogError.value = errorMessage(err, 'Unable to record this decision.')
      throw new Error(dialogError.value, { cause: err })
    } finally {
      mutating.value = false
    }
  }

  return {
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
    fetchQueue,
    fetchDetail,
    resetDetail,
    decide,
  }
}
