import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useApi } from '@/composables/useApi'
import ProfilePage from '../ProfilePage.vue'

const { routeParams } = vi.hoisted(() => ({ routeParams: { userId: '7' } }))

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('vue-router', () => ({
  useRoute: () => ({ params: routeParams }),
  RouterLink: { template: '<a><slot /></a>' },
}))

describe('ProfilePage.vue', () => {
  const farmerProfile = {
    id: 7,
    name: 'Maria Farmer',
    role: 'farmer',
    avatar_url: null,
    stats: { total_listed: 3, total_sold: 2, total_reserved: 0, total_revenue: 6500 },
    posts: [
      {
        id: 1,
        title: 'Harvest tips',
        body: 'Rotate your crops.',
        vote_score: 5,
        reply_count: 2,
        created_at: '2026-01-01T00:00:00Z',
      },
    ],
  }

  const buyerProfile = {
    id: 9,
    name: 'Jose Buyer',
    role: 'buyer',
    avatar_url: null,
    stats: { total_purchases: 4, total_spent: 12000 },
    posts: [],
  }

  beforeEach(() => {
    setActivePinia(createPinia())
    routeParams.userId = '7'
    useApi.mockReturnValue({
      get: vi.fn().mockResolvedValue({ data: { data: farmerProfile } }),
    })
  })

  async function mountPage() {
    const wrapper = mount(ProfilePage)
    await flushPromises()
    return wrapper
  }

  it('shows the anonymous state without fetching', async () => {
    routeParams.userId = 'anonymous'
    const api = { get: vi.fn() }
    useApi.mockReturnValue(api)

    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('This profile is anonymous')
    expect(api.get).not.toHaveBeenCalled()
  })

  it('shows farmer sales stats and posts', async () => {
    const wrapper = await mountPage()

    expect(useApi().get).toHaveBeenCalledWith('/users/7')
    expect(wrapper.text()).toContain('Maria Farmer')
    expect(wrapper.text()).toContain('Items sold')
    expect(wrapper.text()).toContain('Revenue earned')
    expect(wrapper.text()).toContain('Harvest tips')
  })

  it('shows buyer purchase stats and the empty posts state', async () => {
    useApi.mockReturnValue({
      get: vi.fn().mockResolvedValue({ data: { data: buyerProfile } }),
    })

    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('Jose Buyer')
    expect(wrapper.text()).toContain('Purchases')
    expect(wrapper.text()).toContain('Total spent')
    expect(wrapper.text()).not.toContain('Items sold')
    expect(wrapper.text()).toContain('No posts yet')
  })

  it('shows the not-found state on 404', async () => {
    useApi.mockReturnValue({
      get: vi.fn().mockRejectedValue({ response: { status: 404 } }),
    })

    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('Profile not found')
  })
})
