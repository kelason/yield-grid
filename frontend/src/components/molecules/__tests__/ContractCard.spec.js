import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import ContractCard from '../ContractCard.vue'

describe('ContractCard.vue verification badge', () => {
  const baseContract = {
    id: 1,
    type: 'listing',
    title: 'Premium Rice',
    crop_name: 'Rice',
    status: 'available',
    quantity_kg: 100,
    price_per_kg: 50,
    total_price: 5000,
    estimated_harvest_date: '2026-12-01',
  }

  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('shows the badge only on listings from verified farms', () => {
    const verified = mount(ContractCard, {
      props: { contract: { ...baseContract, is_from_verified_farm: true } },
    })
    const contract = mount(ContractCard, {
      props: { contract: { ...baseContract, type: 'contract', is_from_verified_farm: false } },
    })

    expect(verified.find('[data-testid="verified-badge"]').exists()).toBe(true)
    expect(contract.find('[data-testid="verified-badge"]').exists()).toBe(false)
  })

  it('hides the badge on unverified listings', () => {
    const wrapper = mount(ContractCard, {
      props: { contract: { ...baseContract, is_from_verified_farm: false } },
    })

    expect(wrapper.find('[data-testid="verified-badge"]').exists()).toBe(false)
  })

  it('hides the badge when the flag is missing', () => {
    const wrapper = mount(ContractCard, { props: { contract: baseContract } })

    expect(wrapper.find('[data-testid="verified-badge"]').exists()).toBe(false)
  })
})
