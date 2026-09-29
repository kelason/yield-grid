import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import MyOffersPage from '../MyOffersPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('@/composables/useChatEntry', () => ({
  useChatEntry: () => ({ openChat: vi.fn() }),
}))

describe('MyOffersPage tabs and address collapse', () => {
  const address = { id: 5, formatted_address: '123 Farm Rd, Cabanatuan' }

  function makeOffer(id, status) {
    return {
      id,
      quantity_kg: 150,
      price_per_kg: 44,
      total_price: 6600,
      status,
      farmer: { id: 2, name: 'Mang Juan' },
      demand: {
        id: 9,
        title: 'Tomatoes wanted',
        crop_name: 'Tomato',
        status: 'open',
        buyer: { id: 3, name: 'Buyer' },
        delivery_address: address,
      },
    }
  }

  let apiGet

  beforeEach(() => {
    setActivePinia(createPinia())
    apiGet = vi.fn().mockResolvedValue({ data: { data: [makeOffer(1, 'accepted')] } })
    useApi.mockReturnValue({ get: apiGet, post: vi.fn() })
  })

  async function mountPage() {
    const wrapper = mount(MyOffersPage)
    await flushPromises()
    return wrapper
  }

  it('renders status tabs and refetches with the tab status on click', async () => {
    const wrapper = await mountPage()
    const tabs = wrapper.findAll('nav button')
    expect(tabs.map((tab) => tab.text())).toContain('Paid')

    await tabs.find((tab) => tab.text() === 'Paid').trigger('click')
    await flushPromises()

    expect(apiGet).toHaveBeenLastCalledWith('/farmer/offers?status=partially_paid%2Cpaid')
  })

  it('keeps each delivery address collapsed until its own toggle is clicked', async () => {
    apiGet.mockResolvedValue({
      data: { data: [makeOffer(1, 'accepted'), makeOffer(2, 'paid')] },
    })
    const wrapper = await mountPage()

    expect(wrapper.text()).not.toContain('123 Farm Rd, Cabanatuan')

    const toggles = wrapper.findAll('button[aria-label="Show delivery address"]')
    expect(toggles).toHaveLength(2)

    await toggles[0].trigger('click')
    expect(wrapper.text()).toContain('123 Farm Rd, Cabanatuan')
    expect(wrapper.findAll('button[aria-label="Hide delivery address"]')).toHaveLength(1)

    await toggles[0].trigger('click')
    expect(wrapper.text()).not.toContain('123 Farm Rd, Cabanatuan')
  })
})
