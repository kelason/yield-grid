import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import EmailVerificationBanner from './EmailVerificationBanner.vue'
import { useAuthStore } from '@/stores/auth'

describe('EmailVerificationBanner.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('renders correctly with warning text', () => {
    const wrapper = mount(EmailVerificationBanner)

    expect(wrapper.text()).toContain('Action Required:')
    expect(wrapper.text()).toContain('Please verify your email address.')
    expect(wrapper.find('button').text()).toContain('Resend Verification Email')
  })

  it('displays sending text while in loading state', async () => {
    const wrapper = mount(EmailVerificationBanner)

    // Set isSending to true manually to check text
    wrapper.vm.isSending = true
    await wrapper.vm.$nextTick()

    expect(wrapper.find('button').text()).toContain('Sending...')
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })

  it('displays countdown text when resendCooldown > 0 and disables button', async () => {
    const authStore = useAuthStore()
    authStore.resendCooldown = 15

    const wrapper = mount(EmailVerificationBanner)

    expect(wrapper.find('button').text()).toContain('Resend in 15s')
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })

  it('calls authStore.resendVerificationEmail when button is clicked', async () => {
    const authStore = useAuthStore()
    authStore.resendVerificationEmail = vi.fn().mockResolvedValueOnce({})

    const wrapper = mount(EmailVerificationBanner)

    // Mock the window.alert
    vi.spyOn(window, 'alert').mockImplementation(() => {})

    await wrapper.find('button').trigger('click')

    expect(authStore.resendVerificationEmail).toHaveBeenCalledTimes(1)
  })
})
