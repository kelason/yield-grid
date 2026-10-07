import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import CheckoutSuccessPage from '../CheckoutSuccessPage.vue'
vi.mock('vue-router', () => ({
  useRoute: () => ({ query: {} }),
  useRouter: () => ({ push: vi.fn() }),
  RouterLink: { template: '<a><slot /></a>' },
}))
vi.mock('@/composables/useApi', () => ({ useApi: () => ({ get: vi.fn() }) }))
describe('checkout result presentation', () => {
  it('does not claim a completed payment without a verified session', async () => {
    const wrapper = mount(CheckoutSuccessPage)
    await flushPromises()
    expect(wrapper.text()).toContain('Payment Pending')
    expect(wrapper.text()).not.toContain('Payment Successful!')
  })
})
