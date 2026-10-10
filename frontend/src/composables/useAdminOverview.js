import { ref } from 'vue'
import i18n from '@/i18n'
import { useApi } from './useApi'
import { useAuthStore } from '@/stores/auth'
import { HTTP_STATUS } from '@/constants/http'
import { ADMIN_OVERVIEW_PATH, SUSPENDED_CODE } from '@/constants/admin'

const t = (...args) => i18n.global.t(...args)

function isSessionFailure(error) {
  const status = error?.response?.status
  if (status === HTTP_STATUS.UNAUTHORIZED) return true
  return status === HTTP_STATUS.FORBIDDEN && error?.response?.data?.code === SUSPENDED_CODE
}

function errorMessage(error, fallbackKey) {
  return error?.response?.data?.message || error?.message || t(fallbackKey)
}

export function useAdminOverview() {
  const api = useApi()
  const authStore = useAuthStore()

  const overview = ref(null)
  const loading = ref(false)
  const error = ref('')
  const generatedAt = ref('')

  async function fetchOverview() {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(ADMIN_OVERVIEW_PATH)
      const payload = response?.data?.data ?? null
      overview.value = payload && typeof payload === 'object' ? payload : null
      if (typeof overview.value?.generated_at === 'string') {
        generatedAt.value = overview.value.generated_at
      }
    } catch (err) {
      if (isSessionFailure(err)) authStore.clearSession()
      error.value = errorMessage(err, 'admin.overview.load_failed')
    } finally {
      loading.value = false
    }
  }

  return {
    overview,
    loading,
    error,
    generatedAt,
    fetchOverview,
  }
}
