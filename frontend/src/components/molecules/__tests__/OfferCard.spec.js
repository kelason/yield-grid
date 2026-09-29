import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import OfferCard from '../OfferCard.vue'

const baseOffer = {
  id: 1,
  quantity_kg: 150,
  price_per_kg: 44,
  total_price: 6600,
  status: 'pending',
  farmer: { id: 2, name: 'Mang Juan' },
}

describe('OfferCard', () => {
  it('shows accept and reject actions for buyers on pending offers', () => {
    const wrapper = mount(OfferCard, {
      props: { offer: baseOffer, viewerRole: 'buyer' },
    })
    expect(wrapper.text()).toContain('Accept offer')
    expect(wrapper.text()).toContain('Reject')
    expect(wrapper.text()).not.toContain('Withdraw')
  })

  it('shows pay and message actions for buyers on accepted offers', () => {
    const wrapper = mount(OfferCard, {
      props: { offer: { ...baseOffer, status: 'accepted' }, viewerRole: 'buyer' },
    })
    expect(wrapper.text()).toContain('Pay now')
    expect(wrapper.text()).toContain('Message farmer')
  })

  it('shows confirm receipt for buyers on delivered offers', () => {
    const wrapper = mount(OfferCard, {
      props: { offer: { ...baseOffer, status: 'delivered' }, viewerRole: 'buyer' },
    })
    expect(wrapper.text()).toContain('Confirm receipt')
  })

  it('shows withdraw for farmers on pending offers', () => {
    const wrapper = mount(OfferCard, {
      props: { offer: baseOffer, viewerRole: 'farmer' },
    })
    expect(wrapper.text()).toContain('Withdraw')
    expect(wrapper.text()).not.toContain('Accept offer')
  })

  it('shows message action for buyers on partially paid offers', () => {
    const wrapper = mount(OfferCard, {
      props: { offer: { ...baseOffer, status: 'partially_paid' }, viewerRole: 'buyer' },
    })
    expect(wrapper.text()).toContain('Partially Paid')
    expect(wrapper.text()).toContain('Message farmer')
    expect(wrapper.text()).not.toContain('Confirm receipt')
  })

  it('shows mark delivered for farmers on partially paid offers', () => {
    const wrapper = mount(OfferCard, {
      props: { offer: { ...baseOffer, status: 'partially_paid' }, viewerRole: 'farmer' },
    })
    expect(wrapper.text()).toContain('Mark delivered')
    expect(wrapper.text()).toContain('Message buyer')
  })

  it('shows confirm full payment for farmers on delivered downpayment offers with a balance', () => {
    const wrapper = mount(OfferCard, {
      props: {
        offer: {
          ...baseOffer,
          status: 'delivered',
          purchase: {
            payment_status: 'completed',
            is_downpayment: true,
            amount_paid: 660,
            total_contract_amount: 6600,
          },
        },
        viewerRole: 'farmer',
      },
    })
    expect(wrapper.text()).toContain('Confirm full payment')
    expect(wrapper.text()).not.toContain('Fully settled')
  })

  it('shows the fully settled pill once the balance is settled', () => {
    const wrapper = mount(OfferCard, {
      props: {
        offer: {
          ...baseOffer,
          status: 'delivered',
          purchase: {
            payment_status: 'completed',
            is_downpayment: true,
            amount_paid: 6600,
            total_contract_amount: 6600,
          },
        },
        viewerRole: 'farmer',
      },
    })
    expect(wrapper.text()).toContain('Fully settled')
    expect(wrapper.text()).not.toContain('Confirm full payment')
  })

  it('hides the settle button while the downpayment purchase is still pending', () => {
    const wrapper = mount(OfferCard, {
      props: {
        offer: {
          ...baseOffer,
          status: 'delivered',
          purchase: {
            payment_status: 'pending',
            is_downpayment: true,
            amount_paid: 660,
            total_contract_amount: 6600,
          },
        },
        viewerRole: 'farmer',
      },
    })
    expect(wrapper.text()).not.toContain('Confirm full payment')
    expect(wrapper.text()).not.toContain('Fully settled')
  })

  it('hides settle UI for delivered offers without a downpayment', () => {
    const wrapper = mount(OfferCard, {
      props: {
        offer: {
          ...baseOffer,
          status: 'delivered',
          purchase: { is_downpayment: false, amount_paid: 6600, total_contract_amount: 6600 },
        },
        viewerRole: 'farmer',
      },
    })
    expect(wrapper.text()).not.toContain('Confirm full payment')
    expect(wrapper.text()).not.toContain('Fully settled')
  })

  it('shows mark delivered for farmers on paid offers', () => {
    const wrapper = mount(OfferCard, {
      props: { offer: { ...baseOffer, status: 'paid' }, viewerRole: 'farmer' },
    })
    expect(wrapper.text()).toContain('Mark delivered')
  })

  it('emits actions with the offer payload', async () => {
    const wrapper = mount(OfferCard, {
      props: { offer: baseOffer, viewerRole: 'buyer' },
    })
    const buttons = wrapper.findAll('button')
    const accept = buttons.find((b) => b.text() === 'Accept offer')
    await accept.trigger('click')
    expect(wrapper.emitted('accept')[0]).toEqual([baseOffer])
  })
})
