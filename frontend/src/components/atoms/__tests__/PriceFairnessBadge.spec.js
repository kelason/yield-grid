import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import PriceFairnessBadge from '../PriceFairnessBadge.vue'

const fetchGuide = vi.fn()

vi.mock('@/composables/usePriceGuide', () => ({
  usePriceGuide: () => ({ fetchGuide }),
}))

describe('PriceFairnessBadge', () => {
  const guide = {
    available: true,
    tiers: { farmgate: { price_per_kg: 100 } },
  }

  it('shows a fair badge when within the band', () => {
    const wrapper = mount(PriceFairnessBadge, {
      props: { listingPrice: 110, guide },
    })

    expect(wrapper.text()).toContain('Fair price')
  })

  it('shows an above-guide badge when over the band', () => {
    const wrapper = mount(PriceFairnessBadge, {
      props: { listingPrice: 130, guide },
    })

    expect(wrapper.text()).toContain('Above guide')
  })

  it('shows a below-guide badge when under the band', () => {
    const wrapper = mount(PriceFairnessBadge, {
      props: { listingPrice: 70, guide },
    })

    expect(wrapper.text()).toContain('Below guide')
  })

  it('renders nothing without an available guide', () => {
    const wrapper = mount(PriceFairnessBadge, {
      props: { listingPrice: 100, guide: { available: false } },
    })

    expect(wrapper.html()).toBe('<!--v-if-->')
  })

  it('fetches the guide itself when only a crop name is given', async () => {
    fetchGuide.mockResolvedValue({
      available: true,
      tiers: { farmgate: { price_per_kg: 100 } },
    })

    const wrapper = mount(PriceFairnessBadge, {
      props: { listingPrice: 105, cropName: 'rice' },
    })
    await flushPromises()

    expect(fetchGuide).toHaveBeenCalledWith('rice')
    expect(wrapper.text()).toContain('Fair price')
  })

  it('falls back to the retail tier when farmgate is missing', () => {
    const retailGuide = {
      available: true,
      tiers: { retail: { price_per_kg: 100 } },
    }
    const wrapper = mount(PriceFairnessBadge, {
      props: { listingPrice: 105, guide: retailGuide },
    })

    expect(wrapper.text()).toContain('Fair price')
  })
})
