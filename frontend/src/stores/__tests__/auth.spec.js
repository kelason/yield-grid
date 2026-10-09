import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { useAuthStore } from '../auth'
import { useApi } from '@/composables/useApi'
import i18n, { setLocale, LOCALE_KEY } from '@/i18n'

// Mock the API client
vi.mock('@/composables/useApi', () => {
  const getMock = vi.fn()
  const postMock = vi.fn()
  const patchMock = vi.fn()

  return {
    useApi: () => ({
      get: getMock,
      post: postMock,
      patch: patchMock,
      defaults: {
        baseURL: 'http://localhost:3000/api/v1',
      },
    }),
  }
})

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
    setLocale('en')
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

    const url = 'http://localhost:3000/api/email/verify/1/hash?signature=xyz'

    const result = await store.verifyEmail(url)

    expect(api.get).toHaveBeenNthCalledWith(
      1,
      'http://localhost:3000/api/email/verify/1/hash?signature=xyz',
    )
    expect(api.get).toHaveBeenNthCalledWith(2, '/user')
    expect(result).toEqual({ message: 'Verified' })
    expect(store.user.email_verified_at).not.toBeNull()
  })

  it('verifyEmail throws on invalid path', async () => {
    const store = useAuthStore()
    const url = 'http://localhost:8000/api/some-other-route'
    await expect(store.verifyEmail(url)).rejects.toThrow('Invalid verification URL.')
  })

  it('verifyEmail throws on invalid origin', async () => {
    const store = useAuthStore()
    const url = 'http://malicious.com/api/email/verify/1/hash?signature=xyz'
    await expect(store.verifyEmail(url)).rejects.toThrow('Invalid verification URL origin.')
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

  it('sendPasswordResetLink calls api correctly', async () => {
    const store = useAuthStore()
    const api = useApi()

    api.post.mockResolvedValueOnce({ data: { message: 'Link sent' } })

    const result = await store.sendPasswordResetLink('test@example.com')

    expect(api.post).toHaveBeenCalledWith('/forgot-password', { email: 'test@example.com' })
    expect(result).toEqual({ message: 'Link sent' })
  })

  it('resetPassword calls api correctly', async () => {
    const store = useAuthStore()
    const api = useApi()

    api.post.mockResolvedValueOnce({ data: { message: 'Password reset' } })

    const payload = {
      email: 'test@example.com',
      token: 'token',
      password: 'password',
      password_confirmation: 'password',
    }
    const result = await store.resetPassword(payload)

    expect(api.post).toHaveBeenCalledWith('/reset-password', payload)
    expect(result).toEqual({ message: 'Password reset' })
  })

  it('clearSession clears storage and reactive auth state without an API call', async () => {
    const store = useAuthStore()
    const api = useApi()

    api.post.mockResolvedValueOnce({ data: { message: 'Sent' } })
    await store.resendVerificationEmail()

    store.user = { id: 1, role: 'admin' }
    store.token = 'stale-token'
    localStorage.setItem('auth_token', 'stale-token')
    sessionStorage.setItem('auth_token', 'stale-token')

    store.clearSession()

    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(store.resendCooldown).toBe(0)
    expect(localStorage.getItem('auth_token')).toBeNull()
    expect(sessionStorage.getItem('auth_token')).toBeNull()
    expect(localStorage.getItem('resend_cooldown_start')).toBeNull()
    expect(api.post).toHaveBeenCalledTimes(1)
  })

  it('prefers the profile locale over the local one on login', async () => {
    const store = useAuthStore()
    const api = useApi()
    setLocale('tl')

    api.post.mockResolvedValueOnce({
      data: { user: { id: 1, locale: 'ceb', email_verified_at: '2026-09-17' }, token: 't' },
    })
    await store.login({})

    expect(i18n.global.locale.value).toBe('ceb')
    expect(localStorage.getItem(LOCALE_KEY)).toBe('ceb')
    expect(api.patch).not.toHaveBeenCalled()
  })

  it('pushes the local locale to the profile when unset on login', async () => {
    const store = useAuthStore()
    const api = useApi()
    setLocale('ceb')

    api.post.mockResolvedValueOnce({
      data: { user: { id: 1, email_verified_at: '2026-09-17' }, token: 't' },
    })
    await store.login({})

    expect(api.patch).toHaveBeenCalledWith('/user/locale', { locale: 'ceb' })
  })

  it('sends the local locale with registration', async () => {
    const store = useAuthStore()
    const api = useApi()
    setLocale('tl')

    api.post.mockResolvedValueOnce({
      data: { user: { id: 1, locale: 'tl' }, token: 't' },
    })
    await store.register({ name: 'A' })

    expect(api.post).toHaveBeenCalledWith('/register', { name: 'A', locale: 'tl' })
  })

  it('switches language immediately and retries a failed sync on next fetch', async () => {
    const store = useAuthStore()
    const api = useApi()
    store.token = 't'

    api.patch.mockRejectedValueOnce(new Error('offline'))
    await store.switchLocale('ceb')

    expect(i18n.global.locale.value).toBe('ceb')

    api.get.mockResolvedValueOnce({ data: { id: 1 } })
    await store.fetchUser()

    expect(api.patch).toHaveBeenCalledTimes(2)
    expect(api.patch).toHaveBeenNthCalledWith(2, '/user/locale', { locale: 'ceb' })
  })

  it('does not sync when guests switch language', async () => {
    const store = useAuthStore()
    const api = useApi()

    await store.switchLocale('tl')

    expect(i18n.global.locale.value).toBe('tl')
    expect(api.patch).not.toHaveBeenCalled()
  })
})
