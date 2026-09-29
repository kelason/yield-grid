import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import MyPurchasesPage from '../MyPurchasesPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('@/composables/useChatEntry', () => ({
  useChatEntry: () => ({ openChat: vi.fn() }),
}))

describe('MyPurchasesPage status tabs', () => {
  let apiGet

  beforeEach(() => {
    setActivePinia(createPinia())
    apiGet = vi.fn().mockResolvedValue({ data: { data: [], meta: null } })
    useApi.mockReturnValue({ get: apiGet, post: vi.fn() })
  })

  async function mountPage() {
    const wrapper = mount(MyPurchasesPage, {
      global: { stubs: { RouterLink: true } },
    })
    await flushPromises()
    return wrapper
  }

  it('renders status tabs and refetches with the tab status on click', async () => {
    const wrapper = await mountPage()
    const tabs = wrapper.findAll('nav button')
    expect(tabs.map((tab) => tab.text())).toContain('Partially Paid')

    await tabs.find((tab) => tab.text() === 'Partially Paid').trigger('click')
    await flushPromises()

    const lastUrl = apiGet.mock.calls.at(-1)[0]
    expect(lastUrl).toContain('status=partially_paid')
  })

  it('shows a tab-specific empty state', async () => {
    const wrapper = await mountPage()

    const tabs = wrapper.findAll('nav button')
    await tabs.find((tab) => tab.text() === 'Failed').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('No failed purchases')
  })
})
