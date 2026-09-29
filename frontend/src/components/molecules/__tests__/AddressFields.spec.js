import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import AddressFields from '../AddressFields.vue'

vi.mock('@/composables/useApi', () => {
  const getMock = vi.fn()
  return {
    useApi: () => ({
      get: getMock,
      defaults: { baseURL: 'http://localhost:3000/api/v1' },
    }),
  }
})

import { useApi } from '@/composables/useApi'

function mountFields(modelValue = {}) {
  return mount(AddressFields, {
    props: {
      modelValue: {
        label: '',
        street: '',
        region_code: '',
        province_code: null,
        city_municipality_code: '',
        barangay_code: '',
        latitude: null,
        longitude: null,
        ...modelValue,
      },
      idPrefix: 'test-addr',
    },
    global: {
      stubs: { LeafletPinPicker: true },
    },
  })
}

describe('AddressFields', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    const api = useApi()
    api.get.mockImplementation((url) => {
      if (url === '/geo/regions') {
        return Promise.resolve({
          data: {
            data: [
              { code: '130000000', name: 'NCR' },
              { code: '040000000', name: 'CALABARZON' },
            ],
          },
        })
      }
      if (url.startsWith('/geo/provinces')) {
        return Promise.resolve({ data: { data: [] } })
      }
      if (url.startsWith('/geo/cities-municipalities')) {
        return Promise.resolve({
          data: {
            data: [
              { code: '133900000', name: 'Makati' },
              { code: '133900001', name: 'Taguig' },
            ],
          },
        })
      }
      if (url.startsWith('/geo/barangays')) {
        return Promise.resolve({
          data: { data: [{ code: '1339010018', name: 'Poblacion' }] },
        })
      }
      return Promise.resolve({ data: { data: [] } })
    })
  })

  it('loads regions on mount', async () => {
    const wrapper = mountFields()
    await flushPromises()
    const regionSelect = wrapper.find('#test-addr-region')
    expect(regionSelect.exists()).toBe(true)
    expect(regionSelect.findAll('option').length).toBeGreaterThan(1)
  })

  it('skips province selection when the region has no provinces (NCR)', async () => {
    const wrapper = mountFields()
    await flushPromises()
    await wrapper.find('#test-addr-region').setValue('130000000')
    await flushPromises()

    expect(wrapper.find('#test-addr-province').exists()).toBe(false)
    expect(wrapper.find('#test-addr-city').exists()).toBe(true)
  })

  it('emits patched codes when selections change', async () => {
    const wrapper = mountFields({ region_code: '130000000' })
    await flushPromises()

    const citySelect = wrapper.find('#test-addr-city')
    await citySelect.setValue('133900000')
    await flushPromises()

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    const last = emitted[emitted.length - 1][0]
    expect(last.city_municipality_code).toBe('133900000')
    expect(last.barangay_code).toBeNull()
  })

  it('does not clobber the new code when resetting child selects', async () => {
    const wrapper = mountFields({
      region_code: '130000000',
      city_municipality_code: '133900000',
      barangay_code: '1339010018',
    })
    await flushPromises()

    await wrapper.find('#test-addr-city').setValue('133900001')
    await flushPromises()

    const emitted = wrapper.emitted('update:modelValue')
    const last = emitted[emitted.length - 1][0]
    expect(last.city_municipality_code).toBe('133900001')
    expect(last.barangay_code).toBeNull()
  })
})
