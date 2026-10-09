import { ref } from 'vue'
import i18n from '@/i18n'
import { useApi } from './useApi'
import { useAuthStore } from '@/stores/auth'
import { HTTP_STATUS } from '@/constants/http'
import { PaginationConstants } from '@/constants/pagination'
import { SUSPENDED_CODE } from '@/constants/admin'
import { ADMIN_CONTENT_PATH, REPORT_TARGET_TYPE } from '@/constants/reporting'

const t = (...args) => i18n.global.t(...args)

function isSessionFailure(error) {
  const status = error?.response?.status
  if (status === HTTP_STATUS.UNAUTHORIZED) return true
  return status === HTTP_STATUS.FORBIDDEN && error?.response?.data?.code === SUSPENDED_CODE
}

function errorMessage(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

export function useAdminContent() {
  const api = useApi()
  const authStore = useAuthStore()

  const activeType = ref(REPORT_TARGET_TYPE.THREAD)
  const list = ref([])
  const loading = ref(false)
  const error = ref('')
  const mutating = ref(false)
  const filters = ref({ search: '', visibility: '' })
  const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
  const selected = ref(null)
  const detailLoading = ref(false)
  const detailError = ref('')
  const listRequestId = ref(0)
  const detailRequestId = ref(0)

  function buildParams(page) {
    const params = { page, per_page: PaginationConstants.DEFAULT_PER_PAGE }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.visibility) params.visibility = filters.value.visibility
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

  function replaceItem(item) {
    const index = list.value.findIndex((entry) => entry && String(entry.id) === String(item?.id))
    if (index !== -1) list.value[index] = item
    if (selected.value && String(selected.value.id) === String(item?.id)) {
      selected.value = item
    }
  }

  async function fetchContent(type, page = 1) {
    listRequestId.value += 1
    const requestId = listRequestId.value
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(`${ADMIN_CONTENT_PATH}/${type}`, {
        params: buildParams(page),
      })
      if (requestId !== listRequestId.value) return
      activeType.value = type
      applyList(response?.data, page)
    } catch (err) {
      if (requestId !== listRequestId.value) return
      if (isSessionFailure(err)) authStore.clearSession()
      activeType.value = type
      list.value = []
      error.value = errorMessage(err, t('admin.content.err_load'))
    } finally {
      if (requestId === listRequestId.value) loading.value = false
    }
  }

  async function fetchDetail(type, id) {
    detailRequestId.value += 1
    const requestId = detailRequestId.value
    detailLoading.value = true
    detailError.value = ''
    try {
      // Content IDs stay decimal strings end to end; never coerce through Number.
      const response = await api.get(`${ADMIN_CONTENT_PATH}/${type}/${id}`)
      if (requestId !== detailRequestId.value) return null
      selected.value = response?.data?.data ?? null
      return selected.value
    } catch (err) {
      if (requestId !== detailRequestId.value) return null
      if (isSessionFailure(err)) authStore.clearSession()
      detailError.value = errorMessage(err, t('admin.content.err_detail'))
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

  async function moderate(type, id, action, reason) {
    mutating.value = true
    try {
      const response = await api.post(`${ADMIN_CONTENT_PATH}/${type}/${id}/${action}`, { reason })
      const item = response?.data?.data ?? null
      const meta = response?.data?.meta ?? {}
      if (item) replaceItem(item)
      return {
        item,
        selectedId: meta.selected_id ?? null,
        affectedRootId: meta.affected_root_id ?? null,
      }
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      throw new Error(errorMessage(err, t('admin.content.err_moderate')), { cause: err })
    } finally {
      mutating.value = false
    }
  }

  async function hide(type, id, reason) {
    return moderate(type, id, 'hide', reason)
  }

  async function restore(type, id, reason) {
    return moderate(type, id, 'restore', reason)
  }

  return {
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
  }
}
