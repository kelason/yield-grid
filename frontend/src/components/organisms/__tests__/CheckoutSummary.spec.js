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

  function mountSummary(overrides = {}) {
    return mount(CheckoutSummary, {
      props: { contract: { ...contract, ...overrides } },
      global: { plugins: [pinia] },
    })
  }

  it('caps the purchase quantity display at 8 characters', async () => {
    const wrapper = mountSummary()

    const input = wrapper.find('input#quantity')
    await input.setValue('123456789')

    expect(input.element.value).toBe('12345678')
  })

  it('blocks confirm with an error when quantity exceeds the order max', async () => {
    const wrapper = mountSummary()

    await wrapper.find('input#quantity').setValue('1234567')
    const buttons = wrapper.findAll('button')
    await buttons[1].trigger('click')

    expect(wrapper.emitted('confirm')).toBeUndefined()
    expect(wrapper.text()).toMatch(/cannot exceed .* kg for this order/)
  })

  it('emits the quantity on confirm when within bounds', async () => {
    const wrapper = mountSummary()

    await wrapper.find('input#quantity').setValue('100')
    const buttons = wrapper.findAll('button')
    await buttons[1].trigger('click')

    expect(wrapper.emitted('confirm')[0][0].quantityKg).toBe(100)
  })

  it('opens a 14000 kg listing at full quantity', () => {
    const wrapper = mountSummary({ quantity_kg: 14000 })

    expect(wrapper.find('input#quantity-slider').attributes('max')).toBe('14000')
    expect(wrapper.find('input#quantity').element.value).toBe('14000')
  })

  it('confirms a 14000 kg order without error', async () => {
    const wrapper = mountSummary({ quantity_kg: 14000 })
    const buttons = wrapper.findAll('button')
    await buttons[1].trigger('click')

    expect(wrapper.emitted('confirm')[0][0].quantityKg).toBe(14000)
    expect(wrapper.text()).not.toMatch(/cannot exceed/)
  })

  it('clamps the initial quantity when the listing exceeds the order max', () => {
    const wrapper = mountSummary({ quantity_kg: 140000 })

    expect(wrapper.find('input#quantity-slider').attributes('max')).toBe('99999')
    expect(wrapper.find('input#quantity').element.value).toBe('99999')
  })

  it('steps the slider by 5 kg', () => {
    const wrapper = mountSummary()

    expect(wrapper.find('input#quantity-slider').attributes('step')).toBe('5')
  })

  it('spans the slider from 0 to availability', () => {
    const wrapper = mountSummary({ quantity_kg: 500 })

    expect(wrapper.find('input#quantity-slider').attributes('min')).toBe('0')
    expect(wrapper.find('input#quantity-slider').attributes('max')).toBe('500')
  })

  it('keeps the precise 1 kg minimum on the number input', () => {
    const wrapper = mountSummary()

    expect(wrapper.find('input#quantity').attributes('min')).toBe('1')
  })
})
