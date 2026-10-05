import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import i18n, { setInsuranceLocale } from '@/i18n'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import InsurancePage from '../InsurancePage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('InsurancePage', () => {
  let apiGet
  let apiPut

  beforeEach(() => {
    setActivePinia(createPinia())
    setInsuranceLocale('en')

    apiGet = vi.fn((url) => {
      if (url === '/farmer/insurance/profile') {
        return Promise.resolve({
          data: { data: { rsbsa_number: null, rsbsa_status: 'not_registered' } },
        })
      }
      if (url === '/farmer/insurance/enrollments') {
        return Promise.resolve({ data: { data: [] } })
      }
      if (url === '/farmer/insurance/reminders') {
        return Promise.resolve({ data: { data: [] } })
      }
      if (url === '/farmer/insurance/offices') {
        return Promise.resolve({ data: { data: [] } })
      }
      if (url === '/plots') {
        return Promise.resolve({
          data: { data: [{ id: 3, name: 'North Plot', calculated_area: 1.5 }] },
        })
      }
      return Promise.reject(new Error(`unexpected ${url}`))
    })
    apiPut = vi.fn().mockResolvedValue({
      data: { data: { rsbsa_number: 'RSBSA-1', rsbsa_status: 'registered' } },
    })
    useApi.mockReturnValue({ get: apiGet, post: vi.fn(), put: apiPut, patch: vi.fn() })
  })

  async function mountPage() {
    const wrapper = mount(InsurancePage, { global: { plugins: [i18n] } })
    await flushPromises()
    return wrapper
  }

  it('renders the guide, trackers, and directory after loading', async () => {
    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('Crop Insurance')
    expect(wrapper.text()).toContain('Enrollment Guide')
    expect(wrapper.text()).toContain('My Enrollments')
    expect(wrapper.text()).toContain('Reminders')
    expect(wrapper.text()).toContain('PCIC Offices')
  })

  it('switches the whole page to Tagalog', async () => {
    const wrapper = await mountPage()

    await wrapper.get('[data-testid="locale-tl"]').trigger('click')

    expect(wrapper.text()).toContain('Seguro sa Pananim')
    expect(wrapper.text()).toContain('Gabay sa Enrollment')
  })

  it('saves RSBSA details through a confirmation', async () => {
    const wrapper = await mountPage()

    await wrapper.get('[data-testid="rsbsa-number"] input').setValue('RSBSA-1')
    await wrapper.get('[data-testid="rsbsa-status"] select').setValue('registered')
    await wrapper.get('[data-testid="rsbsa-save"]').trigger('click')

    const modal = wrapper.findComponent(ConfirmModal)
    expect(modal.props('isOpen')).toBe(true)

    await modal.vm.$emit('confirm')
    await flushPromises()

    expect(apiPut).toHaveBeenCalledWith('/farmer/insurance/profile', {
      rsbsa_number: 'RSBSA-1',
      rsbsa_status: 'registered',
    })
  })
})
