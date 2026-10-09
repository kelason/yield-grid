import { setActivePinia, createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notificationStore'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import ManualListingPage from '../ManualListingPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('vue-router', () => ({
  useRouter: vi.fn(),
}))

describe('ManualListingPage.vue limits', () => {
  let mockPost
  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    mockPost = vi.fn()
    useApi.mockReturnValue({ get: vi.fn(), post: mockPost })
    useRouter.mockReturnValue({ push: vi.fn() })
  })

  function mountPage() {
    return mount(ManualListingPage, {
      global: { plugins: [pinia] },
    })
  }

  it('shows empty title and description counters', () => {
    const wrapper = mountPage()

    expect(wrapper.text()).toContain('0/50')
    expect(wrapper.text()).toContain('0/5000')
  })

  it('caps the title at 50 characters on submit', async () => {
    mockPost.mockResolvedValueOnce({ data: {} })
    const wrapper = mountPage()

    const numberInputs = wrapper.findAll('input[type="number"]')
    await numberInputs[1].setValue('100')
    await numberInputs[2].setValue('50')
    await wrapper.find('input[type="date"]').setValue('2026-12-01')
    const titleInput = wrapper.find('input[placeholder="e.g. Premium Grade Rice Harvest"]')
    await titleInput.setValue('x'.repeat(60))

    expect(titleInput.element.value).toHaveLength(50)
    await wrapper.find('form').trigger('submit.prevent')

    // Submit opens the confirmation modal; the post fires only after confirm.
    expect(mockPost).not.toHaveBeenCalled()
    expect(wrapper.findComponent(ConfirmModal).exists()).toBe(true)
    await wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()

    expect(mockPost).toHaveBeenCalledOnce()
    expect(mockPost.mock.calls[0][1].title).toHaveLength(50)
  })

  it('truncates numeric fields past their char caps with no counter', async () => {
    const wrapper = mountPage()

    const numberInputs = wrapper.findAll('input[type="number"]')
    await numberInputs[0].setValue('12345')
    await numberInputs[1].setValue('1234567')
    await numberInputs[2].setValue('123456789')

    expect(numberInputs[0].element.value).toBe('1234')
    expect(numberInputs[1].element.value).toBe('123456')
    expect(numberInputs[2].element.value).toBe('12345678')

    // Typing one more char once already at the cap must not change the display
    await numberInputs[0].setValue('12349')
    await numberInputs[1].setValue('1234569')
    await numberInputs[2].setValue('123456789')

    expect(numberInputs[0].element.value).toBe('1234')
    expect(numberInputs[1].element.value).toBe('123456')
    expect(numberInputs[2].element.value).toBe('12345678')
    // Only the title and description fields show counters
    expect(wrapper.text()).not.toMatch(/\d+\/4(?!\d)/)
    expect(wrapper.text()).not.toContain('/6')
    expect(wrapper.text()).not.toContain('/8')
  })

  it('blocks submit when the description exceeds 5000 characters', async () => {
    const wrapper = mountPage()

    // Bypass native/shared input clamping to verify the submit-time guard.
    wrapper.vm.form.description = 'a'.repeat(5001)
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toContain('5001/5000')
    await wrapper.find('form').trigger('submit.prevent')

    expect(mockPost).not.toHaveBeenCalled()
    const notificationStore = useNotificationStore()
    expect(notificationStore.notifications.at(-1).message).toMatch(/5000 characters/)
  })

  it('populates farm options from the own farms endpoint', async () => {
    mockSourceApi()
    const wrapper = mountPage()
    await flushPromises()

    const options = wrapper.find('#listing-farm').findAll('option')
    expect(options.map((o) => o.text())).toContain('Green Acres')
    expect(options.map((o) => o.text())).toContain('River Lot')
  })

  it('filters plot options by the chosen farm and clears the plot on farm change', async () => {
    mockSourceApi()
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('#listing-farm').setValue('1')
    let plotOptions = wrapper.find('#listing-plot').findAll('option')
    expect(plotOptions.map((o) => o.text())).toContain('North field')
    expect(plotOptions.map((o) => o.text())).not.toContain('South field')

    await wrapper.find('#listing-plot').setValue('11')
    expect(wrapper.vm.form.plot_id).toBe('11')

    await wrapper.find('#listing-farm').setValue('2')
    expect(wrapper.vm.form.plot_id).toBe('')
    plotOptions = wrapper.find('#listing-plot').findAll('option')
    expect(plotOptions.map((o) => o.text())).toContain('South field')
  })

  it('blocks submit when a plot is set without a farm', async () => {
    mockSourceApi()
    const wrapper = mountPage()
    await flushPromises()

    await fillValidListingForm(wrapper)
    // Bypass the dependent select to verify the submit-time guard.
    wrapper.vm.form.plot_id = '11'
    await wrapper.vm.$nextTick()
    await wrapper.find('form').trigger('submit.prevent')

    expect(mockPost).not.toHaveBeenCalled()
    const notificationStore = useNotificationStore()
    expect(notificationStore.notifications.at(-1).message).toMatch(/farm/i)
  })

  it('sends farm_id and plot_id when a source is chosen', async () => {
    mockSourceApi()
    mockPost.mockResolvedValueOnce({ data: {} })
    const wrapper = mountPage()
    await flushPromises()

    await fillValidListingForm(wrapper)
    await wrapper.find('#listing-farm').setValue('1')
    await wrapper.find('#listing-plot').setValue('11')
    await wrapper.find('form').trigger('submit.prevent')
    await wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()

    expect(mockPost).toHaveBeenCalledOnce()
    expect(mockPost.mock.calls[0][1].farm_id).toBe(1)
    expect(mockPost.mock.calls[0][1].plot_id).toBe(11)
  })

  it('omits farm_id and plot_id when no source is chosen', async () => {
    mockSourceApi()
    mockPost.mockResolvedValueOnce({ data: {} })
    const wrapper = mountPage()
    await flushPromises()

    await fillValidListingForm(wrapper)
    await wrapper.find('form').trigger('submit.prevent')
    await wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()

    expect(mockPost).toHaveBeenCalledOnce()
    expect(mockPost.mock.calls[0][1]).not.toHaveProperty('farm_id')
    expect(mockPost.mock.calls[0][1]).not.toHaveProperty('plot_id')
  })

  function mockSourceApi() {
    const mockGet = vi.fn().mockImplementation((url) => {
      if (url === '/farms') {
        return Promise.resolve({
          data: {
            data: [
              { id: 1, name: 'Green Acres' },
              { id: 2, name: 'River Lot' },
            ],
          },
        })
      }
      if (url === '/plots') {
        return Promise.resolve({
          data: {
            data: [
              { id: 11, farm_id: 1, name: 'North field' },
              { id: 12, farm_id: 2, name: 'South field' },
            ],
          },
        })
      }
      return Promise.resolve({ data: {} })
    })
    useApi.mockReturnValue({ get: mockGet, post: mockPost })
  }

  async function fillValidListingForm(wrapper) {
    const numberInputs = wrapper.findAll('input[type="number"]')
    await numberInputs[1].setValue('100')
    await numberInputs[2].setValue('50')
    await wrapper.find('input[type="date"]').setValue('2026-12-01')
  }
})
