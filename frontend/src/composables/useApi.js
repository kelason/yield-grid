import axios from 'axios'
import i18n from '@/i18n'
import { HTTP_STATUS } from '../constants/http'
import { SUSPENDED_CODE } from '../constants/admin'

const t = (...args) => i18n.global.t(...args)

function clearAuthSession() {
  localStorage.removeItem('auth_token')
  sessionStorage.removeItem('auth_token')
  // Dynamic import to avoid a static cycle with the auth store, matching the
  // notification-store pattern below. Storage is already cleared, so a missing
  // Pinia instance outside the app shell is safe to ignore.
  import('../stores/auth')
    .then(({ useAuthStore }) => {
      try {
        useAuthStore().clearSession()
      } catch {
        // No active Pinia; storage clearing above is sufficient.
      }
    })
    .catch(() => {})
}

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Interceptor to add auth token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Interceptor to handle global errors (401, 5xx, etc.)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Dynamic import to avoid circular dependency issues if useApi is used in stores
    import('../stores/notificationStore').then(({ useNotificationStore }) => {
      const notificationStore = useNotificationStore()

      if (!error.response) {
        notificationStore.addNotification({ type: 'error', messageKey: 'auth.network_error' })
        return
      }

      const status = error.response.status
      const data = error.response.data

      if (status === HTTP_STATUS.UNAUTHORIZED) {
        clearAuthSession()
        notificationStore.warning(t('auth.session_expired'))
        // Redirect to login handled via router guards or specific logic elsewhere
      } else if (status === HTTP_STATUS.FORBIDDEN && data?.code === SUSPENDED_CODE) {
        clearAuthSession()
        if (data.message) notificationStore.error(data.message)
        else notificationStore.addNotification({ type: 'error', messageKey: 'auth.suspended' })
      } else if (status === HTTP_STATUS.FORBIDDEN) {
        if (data.message) notificationStore.error(data.message)
        else notificationStore.addNotification({ type: 'error', messageKey: 'auth.forbidden' })
      } else if (status === HTTP_STATUS.UNPROCESSABLE_ENTITY) {
        // Validation errors are typically handled locally by forms,
        // but we can provide a generic toast if needed.
        // notificationStore.error('Please check your input for validation errors.')
      } else if (status >= 500) {
        if (data.message) notificationStore.error(data.message)
        else notificationStore.addNotification({ type: 'error', messageKey: 'auth.server_error' })
      }
    })

    return Promise.reject(error)
  },
)

export function useApi() {
  return api
}
