import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setLocale } from '@/i18n'
import LoginForm from '../LoginForm.vue'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/stores/auth', () => ({ useAuthStore: vi.fn() }))
vi.mock('@/composables/useApi', () => ({ useApi: () => ({ get: vi.fn() }) }))

describe('LoginForm.vue', () => {
  beforeEach(() => {
    setLocale('en')
    useAuthStore.mockReturnValue({ login: vi.fn().mockResolvedValue({}) })
  })

  afterEach(() => {
    setLocale('en')
  })

  it('labels the submit button in English', () => {
    const wrapper = mount(LoginForm, { global: { stubs: { RouterLink: true } } })

    expect(wrapper.find('button[type="submit"]').text()).toBe('Sign in')
    wrapper.unmount()
  })

  it('labels the submit button in Tagalog', () => {
    setLocale('tl')
    const wrapper = mount(LoginForm, { global: { stubs: { RouterLink: true } } })

    expect(wrapper.find('button[type="submit"]').text()).toBe('Mag-sign in')
    wrapper.unmount()
  })
})
