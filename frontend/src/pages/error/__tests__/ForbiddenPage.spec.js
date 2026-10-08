import { setActivePinia, createPinia } from 'pinia'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import ForbiddenPage from '../ForbiddenPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('ForbiddenPage.vue home link', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    useApi.mockReturnValue({ get: vi.fn(), post: vi.fn() })
  })

  function buildRouter() {
    return createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/', name: 'home', component: { template: '<div />' } },
        { path: '/dashboard/buyer', name: 'buyer-dashboard', component: { template: '<div />' } },
        {
          path: '/dashboard/farmer',
          name: 'farmer-dashboard',
          component: { template: '<div />' },
        },
        { path: '/contact', name: 'contact', component: { template: '<div />' } },
        { path: '/admin/users', name: 'admin-users', component: { template: '<div />' } },
        { path: '/403', name: 'forbidden', component: { template: '<div />' } },
      ],
    })
  }

  async function mountPage(role) {
    const router = buildRouter()
    await router.push('/contact')

    if (role) {
      const authStore = useAuthStore()
      authStore.token = 'test-token'
      authStore.user = { id: 1, role, email_verified_at: '2026-01-01T00:00:00Z' }
    }

    const wrapper = mount(ForbiddenPage, { global: { plugins: [router] } })
    return { router, wrapper }
  }

  it('sends a buyer home to the buyer dashboard', async () => {
    const { router, wrapper } = await mountPage('buyer')

    await wrapper.findAll('button')[0].trigger('click')

    await vi.waitFor(() => {
      expect(router.currentRoute.value.name).toBe('buyer-dashboard')
    })
  })

  it('sends a farmer home to the farmer dashboard', async () => {
    const { router, wrapper } = await mountPage('farmer')

    await wrapper.findAll('button')[0].trigger('click')

    await vi.waitFor(() => {
      expect(router.currentRoute.value.name).toBe('farmer-dashboard')
    })
  })

  it('sends a guest home to the public landing page', async () => {
    const { router, wrapper } = await mountPage(null)

    await wrapper.findAll('button')[0].trigger('click')

    await vi.waitFor(() => {
      expect(router.currentRoute.value.path).toBe('/')
    })
  })

  it('sends an admin home to the admin users page', async () => {
    const { router, wrapper } = await mountPage('admin')

    await wrapper.findAll('button')[0].trigger('click')

    await vi.waitFor(() => {
      expect(router.currentRoute.value.name).toBe('admin-users')
    })
  })

  it('fails closed to forbidden for an unknown role', async () => {
    const { router, wrapper } = await mountPage('superadmin')

    await wrapper.findAll('button')[0].trigger('click')

    await vi.waitFor(() => {
      expect(router.currentRoute.value.name).toBe('forbidden')
    })
  })

  it('sends contact support to the contact page', async () => {
    const { router, wrapper } = await mountPage('buyer')
    await router.push('/')

    await wrapper.findAll('button')[1].trigger('click')

    await vi.waitFor(() => {
      expect(router.currentRoute.value.name).toBe('contact')
    })
  })
})
