import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n, { setInsuranceLocale } from '@/i18n'
import OfficeDirectory from '../OfficeDirectory.vue'

describe('OfficeDirectory', () => {
  beforeEach(() => {
    setInsuranceLocale('en')
  })

  const offices = [
    {
      name: 'PCIC Head Office',
      region_code: null,
      city: 'Quezon City',
      address: 'NIA Complex',
      phone: '(02) 8441-1323',
      source_note: '',
      is_head_office: true,
      is_serving_region: false,
    },
    {
      name: 'PCIC Regional Office I',
      region_code: '010000000',
      city: 'Urdaneta City',
      address: 'LBP Building',
      phone: '(075) 632-3248',
      source_note: '',
      is_head_office: false,
      is_serving_region: true,
    },
    {
      name: 'PCIC Regional Office (Bicol Region)',
      region_code: '050000000',
      city: null,
      address: null,
      phone: null,
      source_note: 'Confirm via pcic.gov.ph',
      is_head_office: false,
      is_serving_region: false,
    },
  ]

  function mountDirectory() {
    return mount(OfficeDirectory, {
      props: { offices },
      global: { plugins: [i18n] },
    })
  }

  it('flags the office serving the farmer region', () => {
    const wrapper = mountDirectory()

    const cards = wrapper.findAll('[data-testid="office-card"]')
    expect(cards).toHaveLength(3)
    expect(cards[1].text()).toContain('Serves your region')
    expect(cards[1].text()).toContain('(075) 632-3248')
  })

  it('shows a verification note when contact details are missing', () => {
    const wrapper = mountDirectory()

    const cards = wrapper.findAll('[data-testid="office-card"]')
    expect(cards[2].text()).toContain('check pcic.gov.ph')
  })

  it('shows an empty state when the directory fails to load', () => {
    const wrapper = mount(OfficeDirectory, {
      props: { offices: [] },
      global: { plugins: [i18n] },
    })

    expect(wrapper.get('[data-testid="offices-empty"]').exists()).toBe(true)
  })

  it('shows a directory message instead of a loader when no offices exist', () => {
    const wrapper = mount(OfficeDirectory, {
      props: { offices: [] },
      global: { plugins: [i18n] },
    })

    const empty = wrapper.get('[data-testid="offices-empty"]')
    expect(empty.text()).toContain('Office directory is unavailable right now.')
    expect(empty.text()).not.toContain('Loading')
  })
})
