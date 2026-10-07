import { describe, it, expect, vi, beforeEach } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import ContactForm from '../ContactForm.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { useApi } from '@/composables/useApi'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('ContactForm.vue', () => {
  let mockPost

  beforeEach(() => {
    mockPost = vi.fn()
    useApi.mockReturnValue({ post: mockPost })
    vi.clearAllMocks()
  })

  async function fillValid(wrapper) {
    await wrapper.find('#contact-name').setValue('Jane Doe')
    await wrapper.find('#contact-email').setValue('jane@example.com')
    await wrapper.find('#contact-subject').setValue('Hello')
    await wrapper.find('#contact-message').setValue('A friendly test message.')
  }

  it('opens the confirmation modal on submit and posts only after confirm', async () => {
    mockPost.mockResolvedValue({ data: { message: 'Sent!' } })
    const wrapper = mount(ContactForm)
    await fillValid(wrapper)

    await wrapper.find('form').trigger('submit.prevent')
    expect(wrapper.findComponent(ConfirmModal).props('isOpen')).toBe(true)
    expect(mockPost).not.toHaveBeenCalled()

    await wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()

    expect(mockPost).toHaveBeenCalledWith('/contact', {
      name: 'Jane Doe',
      email: 'jane@example.com',
      subject: 'Hello',
      message: 'A friendly test message.',
    })
    expect(wrapper.html()).toContain('Sent!')
  })

  it('does not post when the modal is cancelled', async () => {
    const wrapper = mount(ContactForm)
    await fillValid(wrapper)

    await wrapper.find('form').trigger('submit.prevent')
    await wrapper.findComponent(ConfirmModal).vm.$emit('cancel')

    expect(mockPost).not.toHaveBeenCalled()
  })

  it('holds confirmation while sending and retains the message on failure', async () => {
    let reject
    mockPost.mockImplementation(
      () =>
        new Promise((resolve, fail) => {
          reject = fail
        }),
    )
    const wrapper = mount(ContactForm)
    await fillValid(wrapper)
    await wrapper.find('form').trigger('submit')
    const confirmation = wrapper.findComponent(ConfirmModal)
    confirmation.vm.$emit('confirm')
    await flushPromises()
    expect(confirmation.props('isOpen')).toBe(true)
    expect(confirmation.props('loading')).toBe(true)
    confirmation.vm.$emit('confirm')
    expect(mockPost).toHaveBeenCalledTimes(1)
    reject({ response: { data: { message: 'Please retry your message.' } } })
    await flushPromises()
    expect(wrapper.get('#contact-message').element.value).toBe('A friendly test message.')
    expect(wrapper.text()).toContain('Please retry your message.')
    wrapper.unmount()
  })

  it('blocks submit when the message exceeds the max length', async () => {
    const wrapper = mount(ContactForm)
    await fillValid(wrapper)
    // Bypass native/shared input clamping to verify the submit-time guard.
    wrapper.vm.form.message = 'a'.repeat(2001)
    await wrapper.vm.$nextTick()

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.html()).toContain('at most 2000 characters')
    expect(wrapper.findComponent(ConfirmModal).props('isOpen')).toBe(false)
    expect(mockPost).not.toHaveBeenCalled()
  })

  it('shows a live character counter for the message', async () => {
    const wrapper = mount(ContactForm)
    await wrapper.find('#contact-message').setValue('Hello')

    expect(wrapper.html()).toContain('5 / 2000')
  })
})
