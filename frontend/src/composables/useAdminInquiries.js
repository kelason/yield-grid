import { ref } from 'vue'
import i18n from '@/i18n'
import { useApi } from './useApi'
import { useAuthStore } from '@/stores/auth'
import { HTTP_STATUS } from '@/constants/http'
import { PaginationConstants } from '@/constants/pagination'
import { ADMIN_INQUIRIES_PATH, SUSPENDED_CODE } from '@/constants/admin'

const t = (...args) => i18n.global.t(...args)

function isSessionFailure(error) {
  const status = error?.response?.status
  if (status === HTTP_STATUS.UNAUTHORIZED) return true
  return status === HTTP_STATUS.FORBIDDEN && error?.response?.data?.code === SUSPENDED_CODE
}

function errorMessage(error, fallbackKey) {
  return error?.response?.data?.message || error?.message || t(fallbackKey)
}

export function useAdminInquiries() {
  const api = useApi()
  const authStore = useAuthStore()

  const list = ref([])
  const loading = ref(false)
  const error = ref('')
  const mutating = ref(false)
  const filters = ref({ search: '', status: '', delivery: '' })
  const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
  const selected = ref(null)
  const detailLoading = ref(false)
  const detailError = ref('')
  const detailRequestId = ref(0)

  function buildParams(page) {
    const params = { page, per_page: PaginationConstants.DEFAULT_PER_PAGE }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.delivery) params.delivery = filters.value.delivery
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

  function replaceMessage(updated) {
    const index = list.value.findIndex((entry) => entry && String(entry.id) === String(updated?.id))
    if (index !== -1) list.value[index] = updated
    if (selected.value && String(selected.value.id) === String(updated?.id)) {
      selected.value = updated
    }
  }

  function applyReply(messageId, reply) {
    const targets = [selected.value, ...list.value].filter(
      (entry) => entry && String(entry.id) === String(messageId),
    )
    for (const target of targets) {
      const replies = Array.isArray(target.replies) ? [...target.replies] : []
      const index = replies.findIndex((entry) => entry && String(entry.id) === String(reply?.id))
      if (index === -1) replies.push(reply)
      else replies[index] = reply
      target.replies = replies
    }
  }

  async function fetchInquiries(page = 1) {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(ADMIN_INQUIRIES_PATH, { params: buildParams(page) })
      applyList(response?.data, page)
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      list.value = []
      error.value = errorMessage(err, 'admin.inquiry.load_failed')
    } finally {
      loading.value = false
    }
  }

  async function fetchInquiry(id) {
    detailRequestId.value += 1
    const requestId = detailRequestId.value
    detailLoading.value = true
    detailError.value = ''
    try {
      // Message IDs stay decimal strings end to end; never coerce through Number.
      const response = await api.get(`${ADMIN_INQUIRIES_PATH}/${id}`)
      if (requestId !== detailRequestId.value) return null
      selected.value = response?.data?.data ?? null
      return selected.value
    } catch (err) {
      if (requestId !== detailRequestId.value) return null
      if (isSessionFailure(err)) authStore.clearSession()
      detailError.value = errorMessage(err, 'admin.inquiry.detail_failed')
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

  async function transition(id, action) {
    mutating.value = true
    try {
      const response = await api.post(`${ADMIN_INQUIRIES_PATH}/${id}/${action}`)
      const updated = response?.data?.data ?? null
      if (updated) replaceMessage(updated)
      return updated
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      throw new Error(errorMessage(err, 'admin.inquiry.update_failed'), { cause: err })
    } finally {
      mutating.value = false
    }
  }

  async function markRead(id) {
    return transition(id, 'read')
  }

  async function closeInquiry(id) {
    return transition(id, 'close')
  }

  async function reopenInquiry(id) {
    return transition(id, 'reopen')
  }

  async function queueReply(messageId, body, clientRequestId) {
    mutating.value = true
    try {
      const response = await api.post(`${ADMIN_INQUIRIES_PATH}/${messageId}/replies`, {
        body,
        client_request_id: clientRequestId,
      })
      const reply = response?.data?.data ?? null
      if (reply) applyReply(messageId, reply)
      return { reply, warning: response?.data?.meta?.warning ?? '' }
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      throw new Error(errorMessage(err, 'admin.inquiry.queue_failed'), { cause: err })
    } finally {
      mutating.value = false
    }
  }

  async function retryReply(messageId, replyId) {
    mutating.value = true
    try {
      const response = await api.post(
        `${ADMIN_INQUIRIES_PATH}/${messageId}/replies/${replyId}/retry`,
      )
      const reply = response?.data?.data ?? null
      if (reply) applyReply(messageId, reply)
      return { reply, warning: response?.data?.meta?.warning ?? '' }
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      throw new Error(errorMessage(err, 'admin.inquiry.retry_failed'), { cause: err })
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
    fetchInquiries,
    fetchInquiry,
    clearSelection,
    markRead,
    closeInquiry,
    reopenInquiry,
    queueReply,
    retryReply,
  }
}
