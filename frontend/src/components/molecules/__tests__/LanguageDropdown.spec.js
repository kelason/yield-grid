import { setActivePinia, createPinia } from 'pinia'
import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import i18n, { setLocale, LOCALE_KEY } from '@/i18n'
import LanguageDropdown from '../LanguageDropdown.vue'

describe('LanguageDropdown', () => {
  let wrappers = []

  beforeEach(() => {
    setActivePinia(createPinia())
    setLocale('en')
    localStorage.removeItem(LOCALE_KEY)
    wrappers = []
  })

  afterEach(() => {
    wrappers.forEach((wrapper) => wrapper.unmount())
  })

  function mountDropdown() {
    const wrapper = mount(LanguageDropdown, {
      attachTo: document.body,
      global: { plugins: [i18n] },
    })
    wrappers.push(wrapper)
    return wrapper
  }

  it('is closed by default', () => {
    const wrapper = mountDropdown()

    expect(wrapper.get('[data-testid="language-menu"]').attributes('aria-expanded')).toBe('false')
    expect(wrapper.find('[data-testid="locale-ceb"]').exists()).toBe(false)
  })

  it('toggles the menu on click', async () => {
    const wrapper = mountDropdown()
    const toggle = wrapper.get('[data-testid="language-menu"]')

    await toggle.trigger('click')
    expect(toggle.attributes('aria-expanded')).toBe('true')
    expect(wrapper.find('[data-testid="locale-ceb"]').exists()).toBe(true)

    await toggle.trigger('click')
    expect(toggle.attributes('aria-expanded')).toBe('false')
  })

  it('switches locale and persists the choice', async () => {
    const wrapper = mountDropdown()

    await wrapper.get('[data-testid="language-menu"]').trigger('click')
    await wrapper.get('[data-testid="locale-ceb"]').trigger('click')

    expect(i18n.global.locale.value).toBe('ceb')
    expect(localStorage.getItem(LOCALE_KEY)).toBe('ceb')
    expect(wrapper.get('[data-testid="language-menu"]').text()).toContain('CEB')
  })

  it('closes on Escape', async () => {
    const wrapper = mountDropdown()
    const toggle = wrapper.get('[data-testid="language-menu"]')

    await toggle.trigger('click')
    expect(toggle.attributes('aria-expanded')).toBe('true')

    await toggle.trigger('keydown', { key: 'Escape' })
    expect(toggle.attributes('aria-expanded')).toBe('false')
  })

  it('moves focus with arrow keys and marks the selected option', async () => {
    const wrapper = mountDropdown()

    await wrapper.get('[data-testid="language-menu"]').trigger('click')
    await wrapper.get('[data-testid="language-menu"]').trigger('keydown', { key: 'ArrowDown' })

    expect(document.activeElement?.getAttribute('data-testid')).toBe('locale-en')
    expect(wrapper.get('[data-testid="locale-en"]').attributes('aria-current')).toBe('true')
    expect(wrapper.get('[data-testid="locale-ceb"]').attributes('aria-current')).toBeUndefined()
  })
})
