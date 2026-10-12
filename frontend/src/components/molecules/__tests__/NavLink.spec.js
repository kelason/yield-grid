import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import NavLink from '../NavLink.vue'
it('an explicit section active state supplies page semantics for nested paths', async () => {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/documentation/:topic', component: { template: '<div />' } }],
  })
  await router.push('/documentation/getting-started')
  const wrapper = mount(NavLink, {
    props: { to: '/documentation', label: 'Documentation', active: true },
    global: { plugins: [router] },
  })
  expect(wrapper.get('a').attributes('aria-current')).toBe('page')
  await wrapper.setProps({ active: false })
  expect(wrapper.get('a').attributes('aria-current')).toBeUndefined()
  wrapper.unmount()
})
