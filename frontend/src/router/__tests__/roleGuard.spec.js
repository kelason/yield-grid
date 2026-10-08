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
  beforeEach(async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    useApi.mockReturnValue({ get: vi.fn(), post: vi.fn() })
    await router.push('/contact')
  })

  function loginAs(role, { verified = true } = {}) {
    const authStore = useAuthStore()
    authStore.token = 'test-token'
    authStore.user = {
      id: role === 'buyer' ? 9 : 8,
      role,
      email_verified_at: verified ? '2026-01-01T00:00:00Z' : null,
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

  it('sends bare /dashboard to the farmer dashboard for a farmer', async () => {
    loginAs('farmer')

    await router.push('/dashboard')

    expect(router.currentRoute.value.name).toBe('farmer-dashboard')
  })

  it('sends bare /dashboard to the buyer dashboard for a buyer', async () => {
    loginAs('buyer')

    await router.push('/dashboard')

    expect(router.currentRoute.value.name).toBe('buyer-dashboard')
  })

  it('sends bare /dashboard to the admin users page for a verified admin', async () => {
    loginAs('admin')

    await router.push('/dashboard')

    expect(router.currentRoute.value.name).toBe('admin-users')
  })

  it('fails closed to forbidden for an unknown role on bare /dashboard', async () => {
    loginAs('superadmin')

    await router.push('/dashboard')

    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('sends a guest on bare /dashboard to login', async () => {
    await router.push('/dashboard')

    expect(router.currentRoute.value.name).toBe('login')
  })

  it('sends an authenticated farmer on home to the farmer dashboard', async () => {
    loginAs('farmer')

    await router.push('/')

    expect(router.currentRoute.value.name).toBe('farmer-dashboard')
  })

  it('sends an authenticated buyer on home to the buyer dashboard', async () => {
    loginAs('buyer')

    await router.push('/')

    expect(router.currentRoute.value.name).toBe('buyer-dashboard')
  })

  it('sends an authenticated admin on home to the admin users page', async () => {
    loginAs('admin')

    await router.push('/')

    expect(router.currentRoute.value.name).toBe('admin-users')
  })

  it('fails closed to forbidden for an unknown role on home, never farmer', async () => {
    loginAs('superadmin')

    await router.push('/')

    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('keeps a guest on home', async () => {
    await router.push('/')

    expect(router.currentRoute.value.name).toBe('home')
  })

  it('sends an authenticated admin on the login page to the admin users page', async () => {
    loginAs('admin')

    await router.push('/auth/login')

    expect(router.currentRoute.value.name).toBe('admin-users')
  })

  it('sends an authenticated farmer on the login page to the farmer dashboard', async () => {
    loginAs('farmer')

    await router.push('/auth/login')

    expect(router.currentRoute.value.name).toBe('farmer-dashboard')
  })

  it('blocks an admin from farmer views', async () => {
    loginAs('admin')

    await router.push('/dashboard/credit-score')
    expect(router.currentRoute.value.name).toBe('forbidden')

    await router.push('/dashboard/farmer')
    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('blocks an admin from buyer views', async () => {
    loginAs('admin')

    await router.push('/dashboard/buyer/marketplace')
    expect(router.currentRoute.value.name).toBe('forbidden')

    await router.push('/dashboard/buyer')
    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('blocks farmers and buyers from the admin users page', async () => {
    loginAs('farmer')

    await router.push('/admin/users')
    expect(router.currentRoute.value.name).toBe('forbidden')

    await router.push('/contact')
    loginAs('buyer')

    await router.push('/admin/users')
    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('sends a guest on the admin users page to login', async () => {
    await router.push('/admin/users')

    expect(router.currentRoute.value.name).toBe('login')
  })

  it('allows a verified admin to reach the admin users page', async () => {
    loginAs('admin')

    await router.push('/admin/users')

    expect(router.currentRoute.value.name).toBe('admin-users')
  })

  it('sends a verified admin on the admin root to the admin users page', async () => {
    loginAs('admin')

    await router.push('/admin')

    expect(router.currentRoute.value.name).toBe('admin-users')
  })

  it('sends an unverified admin on the admin users page to verification', async () => {
    loginAs('admin', { verified: false })

    await router.push('/admin/users')

    expect(router.currentRoute.value.name).toBe('verification-required')
  })

  it('lets an unverified admin stay on the verification page without a redirect loop', async () => {
    loginAs('admin', { verified: false })

    await router.push('/auth/verification-required')

    expect(router.currentRoute.value.name).toBe('verification-required')
  })

  it('sends a guest on the verification page to login', async () => {
    await router.push('/auth/verification-required')

    expect(router.currentRoute.value.name).toBe('login')
  })

  it('keeps the existing dashboard redirect for an unverified farmer', async () => {
    loginAs('farmer', { verified: false })

    await router.push('/dashboard/chat')

    expect(router.currentRoute.value.name).toBe('farmer-dashboard')
  })

  it('sends an unverified buyer on a gated page to the buyer dashboard', async () => {
    loginAs('buyer', { verified: false })

    await router.push('/dashboard/chat')

    expect(router.currentRoute.value.name).toBe('buyer-dashboard')
  })

  it('lets verified members reach gated shared pages', async () => {
    loginAs('farmer')

    await router.push('/dashboard/chat')
    expect(router.currentRoute.value.name).toBe('chat')

    await router.push('/contact')
    loginAs('buyer')

    await router.push('/dashboard/chat')
    expect(router.currentRoute.value.name).toBe('chat')
  })

  it('lets verified farmers and buyers reach the shared issue page', async () => {
    loginAs('farmer')

    await router.push('/dashboard/issues')
    expect(router.currentRoute.value.name).toBe('report-issue')

    await router.push('/contact')
    loginAs('buyer')

    await router.push('/dashboard/issues')
    expect(router.currentRoute.value.name).toBe('report-issue')
  })

  it('blocks admins from the shared issue page', async () => {
    loginAs('admin')

    await router.push('/dashboard/issues')

    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('sends unverified members on the issue page to their dashboard', async () => {
    loginAs('farmer', { verified: false })

    await router.push('/dashboard/issues')
    expect(router.currentRoute.value.name).toBe('farmer-dashboard')

    await router.push('/contact')
    loginAs('buyer', { verified: false })

    await router.push('/dashboard/issues')
    expect(router.currentRoute.value.name).toBe('buyer-dashboard')
  })

  it('sends a guest on the issue page to login', async () => {
    await router.push('/dashboard/issues')

    expect(router.currentRoute.value.name).toBe('login')
  })

  it('allows a verified admin to reach the admin issues page', async () => {
    loginAs('admin')

    await router.push('/admin/issues')

    expect(router.currentRoute.value.name).toBe('admin-issues')
  })

  it('blocks farmers and buyers from the admin issues page', async () => {
    loginAs('farmer')

    await router.push('/admin/issues')
    expect(router.currentRoute.value.name).toBe('forbidden')

    await router.push('/contact')
    loginAs('buyer')

    await router.push('/admin/issues')
    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('sends an unverified admin on the admin issues page to verification', async () => {
    loginAs('admin', { verified: false })

    await router.push('/admin/issues')

    expect(router.currentRoute.value.name).toBe('verification-required')
  })
})
