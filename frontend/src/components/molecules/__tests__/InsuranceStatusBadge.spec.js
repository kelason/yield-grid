import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n, { setLocale } from '@/i18n'
import InsuranceStatusBadge from '../InsuranceStatusBadge.vue'

describe('InsuranceStatusBadge', () => {
  beforeEach(() => {
    setLocale('en')
  })

  function mountBadge(props) {
    return mount(InsuranceStatusBadge, {
      props,
      global: { plugins: [i18n] },
    })
  }

  it('renders the translated enrollment status with the active style', () => {
    const badge = mountBadge({ status: 'active', kind: 'enrollment' }).get(
      '[data-testid="insurance-status-badge"]',
    )

    expect(badge.text()).toBe('Active')
    expect(badge.classes()).toContain('bg-moss-100')
  })

  it('renders the translated claim status with the warning style', () => {
    const badge = mountBadge({ status: 'notice_of_loss_filed', kind: 'claim' }).get(
      '[data-testid="insurance-status-badge"]',
    )

    expect(badge.text()).toBe('Notice of Loss filed')
    expect(badge.classes()).toContain('bg-harvest-100')
  })

  it('translates labels when the locale changes', () => {
    setLocale('tl')
    const badge = mountBadge({ status: 'active', kind: 'enrollment' }).get(
      '[data-testid="insurance-status-badge"]',
    )

    expect(badge.text()).toBe('Aktibo')
  })

  it('falls back to neutral styling for unknown statuses', () => {
    const badge = mountBadge({ status: 'mystery', kind: 'enrollment' }).get(
      '[data-testid="insurance-status-badge"]',
    )

    expect(badge.classes()).toContain('bg-stone-100')
  })
})
