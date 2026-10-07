import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatCard from '../StatCard.vue'

describe('StatCard', () => {
  it('distinguishes an unknown KPI from an actual zero', async () => {
    const wrapper = mount(StatCard, { props: { label: 'Farms', value: null } })
    expect(wrapper.get('dd').text()).toBe('—')
    await wrapper.setProps({ value: 0 })
    expect(wrapper.get('dd').text()).toBe('0')
    await wrapper.setProps({ loading: true })
    expect(wrapper.get('dd').text()).not.toBe('0')
    expect(wrapper.get('[role="status"]').text()).toContain('Farms')
  })
})
