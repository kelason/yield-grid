import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import MyDemandsPage from '../MyDemandsPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('@/composables/useChatEntry', () => ({
  useChatEntry: () => ({ openChat: vi.fn() }),
}))

vi.mock('@/composables/usePayment', () => ({
  usePayment: () => ({ startOfferCheckout: vi.fn(), loading: { value: false } }),
}))

describe('MyDemandsPage status tabs', () => {
  const demand = {
    id: 9,
    title: 'Tomatoes wanted',
    crop_name: 'Tomato',
    quantity_kg: 600,
    remaining_quantity_kg: 600,
    status: 'open',
    pending_offers_count: 0,
    delivery_address: null,
  }

  let apiGet

  beforeEach(() => {
    setActivePinia(createPinia())
    apiGet = vi.fn().mockResolvedValue({ data: { data: [demand] } })
    useApi.mockReturnValue({ get: apiGet, post: vi.fn() })
  })

  async function mountPage() {
    const wrapper = mount(MyDemandsPage, {
      global: { stubs: { RouterLink: true } },
    })
    await flushPromises()
    return wrapper
  }

  it('renders status tabs and refetches with the tab status on click', async () => {
    const wrapper = await mountPage()
    const tabs = wrapper.findAll('nav button')
    expect(tabs.map((tab) => tab.text())).toContain('Fulfilled')

    await tabs.find((tab) => tab.text() === 'Fulfilled').trigger('click')
    await flushPromises()

    expect(apiGet).toHaveBeenLastCalledWith('/buyer/demands?status=fulfilled')
  })

  it('shows a tab-specific empty state', async () => {
    apiGet.mockResolvedValue({ data: { data: [] } })
    const wrapper = await mountPage()

    const tabs = wrapper.findAll('nav button')
    await tabs.find((tab) => tab.text() === 'Cancelled').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('No cancelled demands')
  })
})
