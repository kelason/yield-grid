import { ref } from 'vue'
import { useApi } from './useApi'
import { useAuthStore } from '@/stores/auth'
import { HTTP_STATUS } from '@/constants/http'
import { PaginationConstants } from '@/constants/pagination'
import { SUSPENDED_CODE } from '@/constants/admin'
import { ISSUES_PATH, newClientRequestId } from '@/constants/issues'

function isSessionFailure(error) {
  const status = error?.response?.status
  if (status === HTTP_STATUS.UNAUTHORIZED) return true
  return status === HTTP_STATUS.FORBIDDEN && error?.response?.data?.code === SUSPENDED_CODE
}

function errorMessage(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

export function useIssueTickets() {
  const api = useApi()
  const authStore = useAuthStore()

  const list = ref([])
  const loading = ref(false)
  const error = ref('')
  const submitting = ref(false)
  const filters = ref({ status: '' })
  const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
  const selected = ref(null)
  const detailLoading = ref(false)
  const detailError = ref('')
  const detailRequestId = ref(0)
  const clientRequestId = ref(newClientRequestId())
  let inflight = null

  function buildParams(page) {
    const params = { page, per_page: PaginationConstants.DEFAULT_PER_PAGE }
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

  async function fetchTickets(page = 1) {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(ISSUES_PATH, { params: buildParams(page) })
      applyList(response?.data, page)
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      list.value = []
      error.value = errorMessage(err, 'Unable to load your issues.')
    } finally {
      loading.value = false
    }
  }

  async function fetchTicket(id) {
    detailRequestId.value += 1
    const requestId = detailRequestId.value
    detailLoading.value = true
    detailError.value = ''
    try {
      // Ticket IDs stay decimal strings end to end; never coerce through Number.
      const response = await api.get(`${ISSUES_PATH}/${id}`)
      if (requestId !== detailRequestId.value) return null
      selected.value = response?.data?.data ?? null
      return selected.value
    } catch (err) {
      if (requestId !== detailRequestId.value) return null
      if (isSessionFailure(err)) authStore.clearSession()
      detailError.value =
        err?.response?.status === HTTP_STATUS.NOT_FOUND
          ? 'This issue is no longer available.'
          : errorMessage(err, 'Unable to load this issue.')
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

  async function runSubmit(payload) {
    submitting.value = true
    try {
      const response = await api.post(ISSUES_PATH, {
        ...payload,
        client_request_id: clientRequestId.value,
      })
      const receipt = response?.data?.data ?? null
      clientRequestId.value = newClientRequestId()
      return receipt
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      throw new Error(errorMessage(err, 'Unable to submit this issue.'), { cause: err })
    } finally {
      submitting.value = false
      inflight = null
    }
  }

  function submitTicket(payload) {
    if (inflight) return inflight
    inflight = runSubmit(payload)
    return inflight
  }

  function rotateRequestKey() {
    clientRequestId.value = newClientRequestId()
  }

  return {
    list,
    loading,
    error,
    submitting,
    filters,
    pagination,
    selected,
    detailLoading,
    detailError,
    clientRequestId,
    fetchTickets,
    fetchTicket,
    clearSelection,
    submitTicket,
    rotateRequestKey,
  }
}
