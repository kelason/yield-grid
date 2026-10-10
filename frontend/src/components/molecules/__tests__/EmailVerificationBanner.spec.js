import { mount, RouterLinkStub } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import EmailVerificationBanner from '../EmailVerificationBanner.vue'
import { useAuthStore } from '@/stores/auth'

describe('EmailVerificationBanner.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  function mountBanner(props = {}) {
    return mount(EmailVerificationBanner, {
      props,
      global: { stubs: { RouterLink: RouterLinkStub } },
    })
  }

  it('renders correctly with warning text', () => {
    const wrapper = mountBanner()

    expect(wrapper.text()).toContain('Action Required:')
    expect(wrapper.text()).toContain('Please verify your email address.')
    expect(wrapper.find('button').text()).toContain('Resend Verification Email')
  })

  it('displays sending text while in loading state', async () => {
    const wrapper = mountBanner({ loading: true })

    expect(wrapper.find('button').text()).toContain('Sending...')
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })

  it('displays countdown text when resendCooldown > 0 and disables button', async () => {
    const authStore = useAuthStore()
    authStore.resendCooldown = 15

    const wrapper = mountBanner({ cooldown: authStore.resendCooldown })

    expect(wrapper.find('button').text()).toContain('Resend in 15s')
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })

  it('shows the admin-approval note with a contact link', () => {
    const wrapper = mountBanner()

    expect(wrapper.text()).toContain('Admin approval')
    expect(wrapper.text()).toContain('Contact the admin to approve your verification request.')
    const contactLink = wrapper.findComponent(RouterLinkStub)
    expect(contactLink.exists()).toBe(true)
    expect(contactLink.props('to')).toBe('/contact')
  })

  it('asks its owner to confirm resend instead of changing account state directly', async () => {
    const authStore = useAuthStore()
    authStore.resendVerificationEmail = vi.fn().mockResolvedValueOnce({})

    const wrapper = mountBanner()

    // Mock the window.alert
    vi.spyOn(window, 'alert').mockImplementation(() => {})

    await wrapper.find('button').trigger('click')

    expect(authStore.resendVerificationEmail).not.toHaveBeenCalled()
    expect(wrapper.emitted('resend')).toHaveLength(1)
  })
})
