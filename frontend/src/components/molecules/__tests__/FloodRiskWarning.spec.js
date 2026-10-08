import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import FloodRiskWarning from '../FloodRiskWarning.vue'

describe('FloodRiskWarning', () => {
  const assessment = {
    level: 'high',
    label: 'High',
    advice: ['Build drainage canals before planting season.', 'Use elevated beds.'],
  }

  it('renders nothing without an assessment', () => {
    const wrapper = mount(FloodRiskWarning, { props: { assessment: null } })

    expect(wrapper.find('[data-testid="flood-warning"]').exists()).toBe(false)
  })

  it('shows the level title and advice', () => {
    const wrapper = mount(FloodRiskWarning, { props: { assessment } })

    const warning = wrapper.get('[data-testid="flood-warning"]')
    expect(warning.text()).toContain('High flood risk')
    expect(warning.text()).toContain('Build drainage canals before planting season.')
    expect(warning.text()).toContain('Use elevated beds.')
  })

  it('emits dismiss from Proceed anyway', async () => {
    const wrapper = mount(FloodRiskWarning, { props: { assessment } })

    await wrapper.get('[data-testid="flood-warning-dismiss"]').trigger('click')

    expect(wrapper.emitted('dismiss')).toHaveLength(1)
  })
})
