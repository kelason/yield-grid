import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import DeliveryAddressCard from '../DeliveryAddressCard.vue'

const address = {
  formatted_address: '123 Sampaguita St., Brgy. Poblacion, Makati, Philippines',
  latitude: 14.551,
  longitude: 121.031,
  maps_url: 'https://www.google.com/maps/search/?api=1&query=14.551%2C121.031',
}

describe('DeliveryAddressCard', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    Object.defineProperty(navigator, 'clipboard', {
      value: { writeText: vi.fn().mockResolvedValue(undefined) },
      configurable: true,
    })
  })

  it('renders the formatted address and maps link', () => {
    const wrapper = mount(DeliveryAddressCard, { props: { address } })
    expect(wrapper.text()).toContain('Brgy. Poblacion, Makati')
    const link = wrapper.find('a')
    expect(link.attributes('href')).toBe(address.maps_url)
    expect(link.attributes('target')).toBe('_blank')
  })

  it('copies the address with coordinates to the clipboard', async () => {
    const wrapper = mount(DeliveryAddressCard, { props: { address } })
    const copyButton = wrapper.findAll('button').find((b) => b.text().includes('Copy address'))
    await copyButton.trigger('click')
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith(
      `${address.formatted_address}\n${address.latitude}, ${address.longitude}`,
    )
    expect(wrapper.text()).toContain('Copied!')
  })
})
