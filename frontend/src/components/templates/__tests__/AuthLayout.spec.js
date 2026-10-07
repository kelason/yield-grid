import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import AuthLayout from '../AuthLayout.vue'
vi.mock('vue-router', () => ({
  useRoute: () => ({ name: 'forgot-password' }),
  RouterView: { template: '<div />' },
  RouterLink: { template: '<a><slot /></a>' },
}))
describe('AuthLayout heading', () => {
  it('names the current auth route with one page heading', () => {
    const wrapper = mount(AuthLayout)
    expect(wrapper.findAll('h1')).toHaveLength(1)
    expect(wrapper.get('h1').text()).toBe('Forgot your password?')
  })
})
