import { describe, it, expect, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { setLocale } from '@/i18n'
import PurchaseCard from '../PurchaseCard.vue'

function demandOfferPurchase() {
  return {
    id: 7,
    payment_status: 'pending',
    demand_offer: { quantity_kg: 50, crop_name: 'Rice', price_per_kg: 40 },
    farmer: { name: 'Maria' },
  }
}

describe('PurchaseCard.vue', () => {
  afterEach(() => {
    setLocale('en')
  })
  it('marks demand-offer purchases', () => {
    setActivePinia(createPinia())
    const wrapper = mount(PurchaseCard, { props: { purchase: demandOfferPurchase() } })
    expect(wrapper.text()).toContain('Demand offer')
  })
  it('marks demand-offer purchases in Tagalog', () => {
    setActivePinia(createPinia())
    setLocale('tl')
    const wrapper = mount(PurchaseCard, { props: { purchase: demandOfferPurchase() } })
    expect(wrapper.text()).toContain('Alok sa demand')
  })
})
