import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ForgotPasswordForm from '../ForgotPasswordForm.vue'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../../../stores/auth'

vi.mock('../../../stores/auth', () => ({
  useAuthStore: vi.fn(),
}))

describe('ForgotPasswordForm.vue', () => {
  let mockStore

  beforeEach(() => {
    setActivePinia(createPinia())
    mockStore = {
      sendPasswordResetLink: vi.fn(),
    }
    useAuthStore.mockReturnValue(mockStore)
  })

  it('renders correctly', () => {
    const wrapper = mount(ForgotPasswordForm)
    expect(wrapper.find('input[type="email"]').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').text()).toBe('Send Reset Link')
  })

  it('shows success message on successful submit', async () => {
    mockStore.sendPasswordResetLink.mockResolvedValue({ message: 'Link sent!' })
    const wrapper = mount(ForgotPasswordForm)

    await wrapper.find('input[type="email"]').setValue('test@example.com')
    await wrapper.find('form').trigger('submit.prevent')

    expect(mockStore.sendPasswordResetLink).toHaveBeenCalledWith('test@example.com')
    expect(wrapper.html()).toContain('Link sent!')
  })

  it('shows error message on failure', async () => {
    mockStore.sendPasswordResetLink.mockRejectedValue({
      response: { data: { message: 'User not found' } },
    })
    const wrapper = mount(ForgotPasswordForm)

    await wrapper.find('input[type="email"]').setValue('test@example.com')
    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.html()).toContain('User not found')
  })
})
