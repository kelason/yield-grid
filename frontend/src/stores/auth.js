import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const api = useApi()

  const isAuthenticated = computed(() => !!token.value)
  const userRole = computed(() => user.value?.role)
  const isEmailVerified = computed(() => user.value?.email_verified_at != null)

  const RESEND_COOLDOWN_SECONDS = 20
  const COOLDOWN_INTERVAL_MS = 1000

  const resendCooldown = ref(0)
  let cooldownInterval = null

  function startResendCooldown() {
    resendCooldown.value = RESEND_COOLDOWN_SECONDS
    localStorage.setItem('resend_cooldown_start', Date.now().toString())
    if (cooldownInterval) clearInterval(cooldownInterval)
    cooldownInterval = setInterval(() => {
      const start = parseInt(localStorage.getItem('resend_cooldown_start')) || Date.now()
      const elapsed = Math.floor((Date.now() - start) / 1000)
      const remaining = RESEND_COOLDOWN_SECONDS - elapsed
      if (remaining <= 0) {
        resendCooldown.value = 0
        clearInterval(cooldownInterval)
      } else {
        resendCooldown.value = remaining
      }
    }, COOLDOWN_INTERVAL_MS)
  }

  function checkResendCooldown() {
    const start = parseInt(localStorage.getItem('resend_cooldown_start'))
    if (start) {
      const elapsed = Math.floor((Date.now() - start) / 1000)
      if (elapsed < RESEND_COOLDOWN_SECONDS) {
        startResendCooldown()
      }
    }
  }

  // Initialize cooldown check
  checkResendCooldown()

  async function fetchUser() {
    if (!token.value) return
    try {
      const response = await api.get('/user')
      user.value = response.data
    } catch {
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function login(credentials) {
    const response = await api.post('/login', credentials)
    user.value = response.data.user
    token.value = response.data.token
    localStorage.setItem('auth_token', token.value)
    startResendCooldown()
  }

  async function register(data) {
    const response = await api.post('/register', data)
    user.value = response.data.user
    token.value = response.data.token
    localStorage.setItem('auth_token', token.value)
    startResendCooldown()
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('resend_cooldown_start')
      resendCooldown.value = 0
      if (cooldownInterval) clearInterval(cooldownInterval)
    }
  }

  async function verifyEmail(url) {
    const parsedUrl = new URL(url)

    // Validate that the path belongs to the email verification endpoint
    const isValidPath =
      parsedUrl.pathname.startsWith('/api/v1/email/verify/') ||
      parsedUrl.pathname.startsWith('/api/email/verify/')
    if (!isValidPath) {
      throw new Error('Invalid verification URL.')
    }

    const apiBaseUrl = new URL(import.meta.env.VITE_API_BASE_URL || window.location.origin)
    if (parsedUrl.origin !== apiBaseUrl.origin) {
      throw new Error('Invalid verification URL origin.')
    }

    // Make the request using the absolute URL directly
    // This bypasses the api baseURL but still uses our interceptors (e.g. for auth tokens)
    const response = await api.get(url)

    // Refresh user state to update email_verified_at
    await fetchUser()

    return response.data
  }

  async function resendVerificationEmail() {
    const response = await api.post('/email/verification-notification')
    startResendCooldown()
    return response.data
  }

  return {
    user,
    token,
    isAuthenticated,
    userRole,
    isEmailVerified,
    resendCooldown,
    fetchUser,
    login,
    register,
    logout,
    verifyEmail,
    resendVerificationEmail,
  }
})
