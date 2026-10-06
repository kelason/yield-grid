import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import router from '../index'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('@/pages/dashboard/farmer/CreditScorePage.vue', () => ({
  default: { template: '<div />' },
}))

describe('router role guard', () => {
  beforeEach(() => {
    const pinia = createPinia()
    setActivePinia(pinia)
    useApi.mockReturnValue({ get: vi.fn(), post: vi.fn() })
  })

  function loginAs(role) {
    const authStore = useAuthStore()
    authStore.token = 'test-token'
    authStore.user = {
      id: role === 'buyer' ? 9 : 8,
      role,
      email_verified_at: '2026-01-01T00:00:00Z',
    }
  }

  it('redirects a buyer visiting a farmer-only page to the 403 page', async () => {
    loginAs('buyer')

    await router.push('/dashboard/credit-score')

    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('redirects a farmer visiting a buyer-only page to the 403 page', async () => {
    loginAs('farmer')

    await router.push('/dashboard/buyer/marketplace')

    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('redirects a buyer visiting farm management to the 403 page', async () => {
    loginAs('buyer')

    await router.push('/dashboard/farms')

    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('redirects a buyer visiting the compatibility checker to the 403 page', async () => {
    loginAs('buyer')

    await router.push('/dashboard/compatibility')

    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('allows a farmer to visit a farmer-only page', async () => {
    loginAs('farmer')

    await router.push('/dashboard/credit-score')

    expect(router.currentRoute.value.name).toBe('credit-score')
  })
})
