import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import VerifyEmailPage from '../VerifyEmailPage.vue'

const { pushMock } = vi.hoisted(() => ({ pushMock: vi.fn() }))

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
}))

describe('VerifyEmailPage.vue', () => {
  let apiPost

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
    pushMock.mockClear()
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: vi.fn(), post: apiPost })

    const authStore = useAuthStore()
    authStore.token = 'unverified-token'
    authStore.user = { id: '1', role: 'admin', email_verified_at: null }
  })

  afterEach(() => {
    useAuthStore().clearSession()
    localStorage.clear()
    sessionStorage.clear()
  })

  function findButton(wrapper, text) {
    return wrapper.findAll('button').find((button) => button.text().trim() === text)
  }

  it('prompts an unverified admin to verify', () => {
    const wrapper = mount(VerifyEmailPage)

    expect(wrapper.text()).toContain('Verify your email')
    expect(findButton(wrapper, 'Resend Verification Email').exists()).toBe(true)
    expect(findButton(wrapper, 'Log Out').exists()).toBe(true)
  })

  it('resends the verification link once after confirmation', async () => {
    apiPost.mockResolvedValueOnce({ data: { message: 'Verification link sent.' } })
    const wrapper = mount(VerifyEmailPage)

    await findButton(wrapper, 'Resend Verification Email').trigger('click')
    expect(wrapper.findComponent(ConfirmModal).props('isOpen')).toBe(true)

    await wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(1)
    expect(apiPost).toHaveBeenCalledWith('/email/verification-notification')
    expect(wrapper.text()).toContain('Verification link sent.')
  })

  it('shows a resend failure inline without leaving the page', async () => {
    apiPost.mockRejectedValueOnce({
      response: { status: 429, data: { message: 'Too many emails.' } },
    })
    const wrapper = mount(VerifyEmailPage)

    await findButton(wrapper, 'Resend Verification Email').trigger('click')
    await wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()

    expect(wrapper.text()).toContain('Too many emails.')
    expect(pushMock).not.toHaveBeenCalled()
  })

  it('logs out and redirects home after confirmation', async () => {
    apiPost.mockResolvedValueOnce({ data: { message: 'Logged out.' } })
    const wrapper = mount(VerifyEmailPage)

    await findButton(wrapper, 'Log Out').trigger('click')
    await wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()

    const authStore = useAuthStore()
    expect(apiPost).toHaveBeenCalledWith('/logout')
    expect(authStore.token).toBeNull()
    expect(authStore.user).toBeNull()
    expect(pushMock).toHaveBeenCalledWith({ name: 'home' })
  })
})
