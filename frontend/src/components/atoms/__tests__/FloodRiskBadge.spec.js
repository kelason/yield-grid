import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import FloodRiskBadge from '../FloodRiskBadge.vue'

describe('FloodRiskBadge', () => {
  it('renders the level label with an accessible name', () => {
    const wrapper = mount(FloodRiskBadge, { props: { level: 'low' } })

    const badge = wrapper.get('[data-testid="flood-badge"]')
    expect(badge.text()).toBe('Low')
    expect(badge.attributes('aria-label')).toBe('Low flood risk')
    expect(badge.classes()).toContain('rounded-full')
  })

  it('defaults to Unknown', () => {
    const wrapper = mount(FloodRiskBadge, { props: {} })

    const badge = wrapper.get('[data-testid="flood-badge"]')
    expect(badge.text()).toBe('Unknown')
    expect(badge.attributes('aria-label')).toBe('Unknown flood risk')
  })

  it('falls back to Unknown for unexpected levels', () => {
    const wrapper = mount(FloodRiskBadge, { props: { level: 'bogus' } })

    expect(wrapper.get('[data-testid="flood-badge"]').text()).toBe('Unknown')
  })
})
