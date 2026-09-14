import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const api = useApi()

  const isAuthenticated = computed(() => !!token.value)
  const userRole = computed(() => user.value?.role)

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
  }

  async function register(data) {
    const response = await api.post('/register', data)
    user.value = response.data.user
    token.value = response.data.token
    localStorage.setItem('auth_token', token.value)
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    userRole,
    fetchUser,
    login,
    register,
    logout
  }
})
