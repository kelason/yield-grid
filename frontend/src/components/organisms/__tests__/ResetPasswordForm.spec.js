import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ResetPasswordForm from '../ResetPasswordForm.vue'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../../../stores/auth'

vi.mock('../../../stores/auth', () => ({
  useAuthStore: vi.fn(),
}))

const pushMock = vi.fn()
vi.mock('vue-router', () => ({
  useRoute: () => ({
    query: {
      email: 'test@example.com',
      token: 'valid-token',
    },
  }),
  useRouter: () => ({
    push: pushMock,
  }),
}))

describe('ResetPasswordForm.vue', () => {
  let mockStore

  beforeEach(() => {
    setActivePinia(createPinia())
    mockStore = {
      resetPassword: vi.fn(),
    }
    useAuthStore.mockReturnValue(mockStore)
    vi.clearAllMocks()
    vi.useFakeTimers()
  })

  it('populates email from route query', async () => {
    const wrapper = mount(ResetPasswordForm)
    await wrapper.vm.$nextTick()
    expect(wrapper.find('input[type="email"]').element.value).toBe('test@example.com')
  })

  it('handles successful password reset', async () => {
    mockStore.resetPassword.mockResolvedValue({ message: 'Reset successful' })
    const wrapper = mount(ResetPasswordForm)

    await wrapper.find('#reset-password').setValue('new-password')
    await wrapper.find('#reset-password-confirmation').setValue('new-password')
    await wrapper.find('form').trigger('submit.prevent')

    expect(mockStore.resetPassword).toHaveBeenCalledWith({
      email: 'test@example.com',
      token: 'valid-token',
      password: 'new-password',
      password_confirmation: 'new-password',
    })
    expect(wrapper.html()).toContain('Reset successful')

    // advance timers to trigger router.push
    vi.advanceTimersByTime(2000)
    expect(pushMock).toHaveBeenCalledWith({ name: 'login' })
  })

  it('handles error on password reset', async () => {
    mockStore.resetPassword.mockRejectedValue({ response: { data: { message: 'Invalid token' } } })
    const wrapper = mount(ResetPasswordForm)

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.html()).toContain('Invalid token')
    expect(pushMock).not.toHaveBeenCalled()
  })
})
