import { setActivePinia, createPinia } from 'pinia'
import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import CheckoutSummary from '../CheckoutSummary.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('CheckoutSummary.vue quantity cap', () => {
  const contract = {
    id: 1,
    type: 'listing',
    title: 'Rice Harvest',
    crop_name: 'Rice',
    quantity_kg: 500,
    price_per_kg: 50,
    total_price: 25000,
    currency: 'PHP',
    estimated_harvest_date: '2026-12-01',
    is_harvest_available: true,
    farmer: { name: 'Juan', location: 'Nueva Ecija' },
  }

  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    useApi.mockReturnValue({ get: vi.fn(), post: vi.fn() })
    const authStore = useAuthStore()
    authStore.user = { id: 9, email_verified_at: '2026-01-01T00:00:00Z' }
  })

  function mountSummary() {
    return mount(CheckoutSummary, {
      props: { contract },
      global: { plugins: [pinia] },
    })
  }

  it('caps the purchase quantity display at 6 characters', async () => {
    const wrapper = mountSummary()

    const input = wrapper.find('input#quantity')
    await input.setValue('1234567')

    expect(input.element.value).toBe('123456')
  })

  it('emits the capped quantity on confirm', async () => {
    const wrapper = mountSummary()

    await wrapper.find('input#quantity').setValue('1234567')
    const buttons = wrapper.findAll('button')
    await buttons[1].trigger('click')

    expect(wrapper.emitted('confirm')[0][0].quantityKg).toBe(123456)
  })
})
