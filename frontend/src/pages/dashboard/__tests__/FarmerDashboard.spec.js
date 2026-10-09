import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { setLocale } from '@/i18n'
import { useApi } from '@/composables/useApi'
import FarmerDashboard from '../FarmerDashboard.vue'

vi.mock('@/composables/useApi', () => ({ useApi: vi.fn() }))
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
  RouterLink: { template: '<a><slot /></a>' },
}))

describe('FarmerDashboard', () => {
  let get
  beforeEach(() => {
    setActivePinia(createPinia())
    get = vi.fn()
    useApi.mockReturnValue({ get })
  })
  afterEach(() => {
    setLocale('en')
  })
  it('shows real farm summaries instead of a placeholder activity feed', async () => {
    get.mockResolvedValue({ data: { data: [{ id: 1, name: 'North Field', plots_count: 2 }] } })
    const wrapper = mount(FarmerDashboard)
    await flushPromises()
    expect(wrapper.get('h1').text()).toBe('Farm overview')
    expect(wrapper.text()).toContain('North Field')
    expect(wrapper.text()).not.toContain('Activity feed coming soon')
  })
  it('distinguishes a failed fetch from empty farms and can retry', async () => {
    get.mockRejectedValueOnce(new Error('Offline')).mockResolvedValue({ data: { data: [] } })
    const wrapper = mount(FarmerDashboard)
    await flushPromises()
    expect(wrapper.text()).toContain('Unable to load your farms')
    expect(wrapper.text()).not.toContain('No farms yet')
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Retry')
      .trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('No farms yet')
  })
  it('renders the dashboard heading in Tagalog', async () => {
    setLocale('tl')
    get.mockResolvedValue({ data: { data: [] } })
    const wrapper = mount(FarmerDashboard)
    await flushPromises()
    expect(wrapper.get('h1').text()).toBe('Pangkalahatan ng Bukid')
    expect(wrapper.text()).toContain('Pamahalaan ang mga bukid')
  })
})
