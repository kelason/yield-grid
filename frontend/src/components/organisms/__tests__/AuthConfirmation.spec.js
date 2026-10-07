import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import LoginForm from '../LoginForm.vue'
import RegisterForm from '../RegisterForm.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { useAuthStore } from '@/stores/auth'
vi.mock('@/stores/auth', () => ({ useAuthStore: vi.fn() }))
vi.mock('@/composables/useApi', () => ({ useApi: () => ({ get: vi.fn() }) }))
describe('authentication confirmations and native controls', () => {
  let store
  beforeEach(() => {
    store = { login: vi.fn().mockResolvedValue({}), register: vi.fn().mockResolvedValue({}) }
    useAuthStore.mockReturnValue(store)
  })
  it('confirms sign in and preserves the credential payload', async () => {
    const wrapper = mount(LoginForm, { global: { stubs: { RouterLink: true } } })
    await wrapper.get('#login-email').setValue('farmer@example.com')
    await wrapper.get('#login-password').setValue('known-password')
    expect(wrapper.get('#login-password').attributes('autocomplete')).toBe('current-password')
    await wrapper.get('form').trigger('submit')
    expect(store.login).not.toHaveBeenCalled()
    wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()
    expect(store.login).toHaveBeenCalledWith({
      email: 'farmer@example.com',
      password: 'known-password',
      remember: false,
    })
    wrapper.unmount()
  })
  it('confirms registration before creating an account and keeps the selected role', async () => {
    const wrapper = mount(RegisterForm)
    await wrapper.get('#reg-name').setValue('Juan Santos')
    await wrapper.get('#reg-email').setValue('farmer@example.com')
    await wrapper.get('#reg-password').setValue('known-password')
    await wrapper.get('#reg-password-confirm').setValue('known-password')
    await wrapper.get('#role_farmer').setValue()
    await wrapper.get('form').trigger('submit')
    expect(store.register).not.toHaveBeenCalled()
    wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()
    expect(store.register).toHaveBeenCalledWith({
      name: 'Juan Santos',
      email: 'farmer@example.com',
      password: 'known-password',
      password_confirmation: 'known-password',
      role: 'farmer',
    })
    wrapper.unmount()
  })
})
