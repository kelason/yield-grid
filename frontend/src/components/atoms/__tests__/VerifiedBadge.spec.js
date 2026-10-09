import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import VerifiedBadge from '../VerifiedBadge.vue'

describe('VerifiedBadge.vue', () => {
  it('renders the default label with test id and image role', () => {
    const wrapper = mount(VerifiedBadge)
    const badge = wrapper.find('[data-testid="verified-badge"]')
    expect(badge.exists()).toBe(true)
    expect(badge.text()).toBe('Verified')
    expect(badge.attributes('role')).toBe('img')
    expect(badge.attributes('aria-label')).toBe('Verified')
  })

  it('renders a custom label', () => {
    const wrapper = mount(VerifiedBadge, { props: { label: 'Verified farm' } })
    expect(wrapper.find('[data-testid="verified-badge"]').text()).toBe('Verified farm')
  })

  it('uses moss pill classes', () => {
    const wrapper = mount(VerifiedBadge)
    const classes = wrapper.find('[data-testid="verified-badge"]').classes()
    expect(classes).toContain('rounded-full')
    expect(classes.join(' ')).toContain('moss')
  })

  it('sizes sm differently from md', () => {
    const sm = mount(VerifiedBadge, { props: { size: 'sm' } })
    const md = mount(VerifiedBadge)
    expect(sm.find('[data-testid="verified-badge"]').classes()).not.toEqual(
      md.find('[data-testid="verified-badge"]').classes(),
    )
    expect(sm.find('[data-testid="verified-badge"]').classes()).toContain('text-xs')
  })
})
