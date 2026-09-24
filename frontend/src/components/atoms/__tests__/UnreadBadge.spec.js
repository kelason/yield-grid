import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import UnreadBadge from '../UnreadBadge.vue'

describe('UnreadBadge.vue', () => {
  it('renders nothing if count is 0', () => {
    const wrapper = mount(UnreadBadge, {
      props: { count: 0 },
    })
    expect(wrapper.find('span').exists()).toBe(false)
  })

  it('renders the count if greater than 0', () => {
    const wrapper = mount(UnreadBadge, {
      props: { count: 5 },
    })
    expect(wrapper.text()).toBe('5')
    expect(wrapper.classes()).toContain('bg-harvest-500')
  })

  it('renders 99+ if count exceeds 99', () => {
    const wrapper = mount(UnreadBadge, {
      props: { count: 105 },
    })
    expect(wrapper.text()).toBe('99+')
  })
})
