import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ScoreTierBadge from '../ScoreTierBadge.vue'

describe('ScoreTierBadge', () => {
  it('renders the tier label with tier-specific styling', () => {
    const wrapper = mount(ScoreTierBadge, {
      props: { tier: 'excellent', label: 'Napakahusay' },
    })

    const badge = wrapper.get('[data-testid="tier-badge"]')
    expect(badge.text()).toBe('Napakahusay')
    expect(badge.classes()).toContain('bg-moss-100')
    expect(badge.classes()).toContain('rounded-full')
  })

  it('falls back to the neutral style for unknown tiers', () => {
    const wrapper = mount(ScoreTierBadge, {
      props: { tier: 'mystery' },
    })

    const badge = wrapper.get('[data-testid="tier-badge"]')
    expect(badge.text()).toBe('mystery')
    expect(badge.classes()).toContain('bg-stone-100')
  })
})
