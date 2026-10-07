import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi, afterEach } from 'vitest'
import EmailVerificationCallback from '../EmailVerificationCallback.vue'
const { push, verifyEmail } = vi.hoisted(() => ({
  push: vi.fn(),
  verifyEmail: vi.fn().mockResolvedValue(),
}))
vi.mock('vue-router', () => ({
  useRoute: () => ({
    query: {
      verify_url: 'https://yieldgrid.example.test/api/v1/email/verify/1/hash?expires=9999999999',
    },
  }),
  useRouter: () => ({ push }),
}))
vi.mock('@/stores/auth', () => ({ useAuthStore: () => ({ verifyEmail, isAuthenticated: false }) }))
afterEach(() => vi.useRealTimers())
describe('email verification callback', () => {
  it('shows verified state before redirect and cancels the redirect on unmount', async () => {
    vi.useFakeTimers()
    const wrapper = mount(EmailVerificationCallback)
    await flushPromises()
    expect(wrapper.text()).toContain('Email Verified!')
    expect(wrapper.text()).not.toContain('Verifying your email...')
    wrapper.unmount()
    vi.advanceTimersByTime(2000)
    expect(push).not.toHaveBeenCalled()
  })
})
