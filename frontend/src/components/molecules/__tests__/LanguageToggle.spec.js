import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n, { setInsuranceLocale, INSURANCE_LOCALE_KEY } from '@/i18n'
import LanguageToggle from '../LanguageToggle.vue'

describe('LanguageToggle', () => {
  beforeEach(() => {
    setInsuranceLocale('en')
    localStorage.removeItem(INSURANCE_LOCALE_KEY)
  })

  function mountToggle() {
    return mount(LanguageToggle, { global: { plugins: [i18n] } })
  }

  it('switches the locale and persists the choice', async () => {
    const wrapper = mountToggle()

    await wrapper.get('[data-testid="locale-tl"]').trigger('click')

    expect(i18n.global.locale.value).toBe('tl')
    expect(localStorage.getItem(INSURANCE_LOCALE_KEY)).toBe('tl')

    await wrapper.get('[data-testid="locale-en"]').trigger('click')

    expect(i18n.global.locale.value).toBe('en')
  })

  it('marks the active locale', () => {
    const wrapper = mountToggle()

    expect(wrapper.get('[data-testid="locale-en"]').classes()).toContain('bg-moss-600')
    expect(wrapper.get('[data-testid="locale-tl"]').classes()).not.toContain('bg-moss-600')
  })
})
