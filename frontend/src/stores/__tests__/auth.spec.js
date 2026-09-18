import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { useAuthStore } from '../auth'
import { useApi } from '@/composables/useApi'

// Mock the API client
vi.mock('@/composables/useApi', () => {
  const getMock = vi.fn()
  const postMock = vi.fn()

  return {
    useApi: () => ({
      get: getMock,
      post: postMock,
    }),
  }
})

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('computes isEmailVerified correctly', () => {
    const store = useAuthStore()
    expect(store.isEmailVerified).toBe(false) // initially false

    store.user = { email_verified_at: null }
    expect(store.isEmailVerified).toBe(false)

    store.user = { email_verified_at: '2026-09-17T12:00:00Z' }
    expect(store.isEmailVerified).toBe(true)
  })

  it('verifyEmail calls absolute url and updates state', async () => {
    const store = useAuthStore()
    const api = useApi()

    api.get.mockResolvedValueOnce({ data: { message: 'Verified' } }) // For the verify call
    api.get.mockResolvedValueOnce({ data: { id: 1, email_verified_at: '2026-09-17' } }) // For fetchUser

    const token = 'fake-token'
    store.token = token // necessary for fetchUser to proceed

    const url = 'http://localhost:8000/api/email/verify/1/hash?signature=xyz'

    const result = await store.verifyEmail(url)

    expect(api.get).toHaveBeenNthCalledWith(1, 'http://localhost:8000/api/email/verify/1/hash?signature=xyz')
    expect(api.get).toHaveBeenNthCalledWith(2, '/user')
    expect(result).toEqual({ message: 'Verified' })
    expect(store.user.email_verified_at).not.toBeNull()
  })

  it('manages resend cooldown correctly', async () => {
    const store = useAuthStore()
    const api = useApi()

    api.post.mockResolvedValueOnce({ data: { message: 'Sent' } })

    expect(store.resendCooldown).toBe(0)

    await store.resendVerificationEmail()

    expect(store.resendCooldown).toBe(20)
    expect(localStorage.getItem('resend_cooldown_start')).not.toBeNull()

    // Fast forward 5 seconds
    vi.advanceTimersByTime(5000)
    expect(store.resendCooldown).toBe(15)

    // Fast forward 15 more seconds
    vi.advanceTimersByTime(15000)
    expect(store.resendCooldown).toBe(0)
  })
})
