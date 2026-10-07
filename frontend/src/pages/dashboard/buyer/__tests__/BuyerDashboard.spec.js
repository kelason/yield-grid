import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useApi } from '@/composables/useApi'
import { useMarketStore } from '@/stores/marketStore'
import BuyerDashboard from '../BuyerDashboard.vue'

vi.mock('@/composables/useApi', () => ({ useApi: vi.fn() }))
vi.mock('@/composables/useChatEntry', () => ({ useChatEntry: () => ({ openChat: vi.fn() }) }))
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
  RouterLink: { template: '<a><slot /></a>' },
}))

describe('BuyerDashboard', () => {
  let get
  beforeEach(() => {
    setActivePinia(createPinia())
    get = vi.fn().mockResolvedValue({
      data: { data: [], meta: { current_page: 1, last_page: 2, total: 20, per_page: 10 } },
    })
    useApi.mockReturnValue({ get, post: vi.fn() })
  })
  it('labels paginated summaries and leaves purchase-history filters intact', async () => {
    const store = useMarketStore()
    store.buyerPurchasesFilters.search = 'corn'
    const wrapper = mount(BuyerDashboard)
    await flushPromises()
    expect(wrapper.get('h1').text()).toBe('Buyer overview')
    expect(wrapper.text()).toContain('Purchases on this page')
    expect(wrapper.text()).toContain('Completed payments on this page')
    expect(wrapper.text()).toContain('Amount paid on this page')
    expect(store.buyerPurchasesFilters.search).toBe('corn')
    expect(get.mock.calls[0][0]).not.toContain('search=corn')
  })
})
