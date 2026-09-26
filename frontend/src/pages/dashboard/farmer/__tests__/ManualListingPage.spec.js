import { setActivePinia, createPinia } from 'pinia'
import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notificationStore'
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
    expect(wrapper.text()).toContain('0/500 words')
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

  it('blocks submit when the description exceeds 500 words', async () => {
    const wrapper = mountPage()

    await wrapper.find('textarea').setValue('word '.repeat(501).trim())
    expect(wrapper.text()).toContain('501/500 words')
    await wrapper.find('form').trigger('submit.prevent')

    expect(mockPost).not.toHaveBeenCalled()
    const notificationStore = useNotificationStore()
    expect(notificationStore.notifications.at(-1).message).toMatch(/500 words/)
  })
})
