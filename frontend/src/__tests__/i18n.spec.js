import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

const LOCALE_KEY = 'yieldgrid-locale'
const LEGACY_KEY = 'yieldgrid-insurance-locale'

async function freshI18n() {
  vi.resetModules()
  return await import('@/i18n')
}

describe('global locale module', () => {
  const realLanguage = Object.getOwnPropertyDescriptor(window.navigator, 'language')

  beforeEach(() => {
    localStorage.clear()
    vi.resetModules()
  })

  afterEach(() => {
    if (realLanguage) {
      Object.defineProperty(window.navigator, 'language', realLanguage)
    }
    localStorage.clear()
    vi.unstubAllGlobals()
  })

  it('supports exactly en, tl, and ceb', async () => {
    const { SUPPORTED_LOCALES } = await freshI18n()

    expect([...SUPPORTED_LOCALES]).toEqual(['en', 'tl', 'ceb'])
  })

  it('sets and persists the locale', async () => {
    const i18nModule = await freshI18n()
    const i18n = i18nModule.default

    i18nModule.setLocale('ceb')

    expect(i18n.global.locale.value).toBe('ceb')
    expect(localStorage.getItem(LOCALE_KEY)).toBe('ceb')
  })

  it('ignores unsupported locales', async () => {
    const i18nModule = await freshI18n()
    const i18n = i18nModule.default

    i18nModule.setLocale('es')

    expect(i18n.global.locale.value).toBe('en')
    expect(localStorage.getItem(LOCALE_KEY)).toBeNull()
  })

  it('migrates the legacy insurance locale key once', async () => {
    localStorage.setItem(LEGACY_KEY, 'tl')

    const i18nModule = await freshI18n()

    expect(i18nModule.default.global.locale.value).toBe('tl')
    expect(localStorage.getItem(LOCALE_KEY)).toBe('tl')
    expect(localStorage.getItem(LEGACY_KEY)).toBeNull()
  })

  it('falls back to English for garbage stored values', async () => {
    localStorage.setItem(LOCALE_KEY, 'es')

    const i18nModule = await freshI18n()

    expect(i18nModule.default.global.locale.value).toBe('en')
  })

  it('uses the browser language when nothing is stored', async () => {
    Object.defineProperty(window.navigator, 'language', { value: 'ceb-PH', configurable: true })

    const i18nModule = await freshI18n()

    expect(i18nModule.default.global.locale.value).toBe('ceb')
  })
})
