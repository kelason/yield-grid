import { createI18n } from 'vue-i18n'
import en from './locales/en'
import tl from './locales/tl'

export const INSURANCE_LOCALE_KEY = 'yieldgrid-insurance-locale'
export const INSURANCE_DEFAULT_LOCALE = 'en'
export const INSURANCE_SUPPORTED_LOCALES = ['en', 'tl']

function initialLocale() {
  try {
    const saved = localStorage.getItem(INSURANCE_LOCALE_KEY)
    if (INSURANCE_SUPPORTED_LOCALES.includes(saved)) {
      return saved
    }
  } catch {
    // Storage unavailable (SSR/tests) — fall through to default.
  }
  return INSURANCE_DEFAULT_LOCALE
}

const i18n = createI18n({
  legacy: false,
  locale: initialLocale(),
  fallbackLocale: INSURANCE_DEFAULT_LOCALE,
  messages: { en, tl },
})

export function setInsuranceLocale(locale) {
  if (!INSURANCE_SUPPORTED_LOCALES.includes(locale)) {
    return
  }
  i18n.global.locale.value = locale
  try {
    localStorage.setItem(INSURANCE_LOCALE_KEY, locale)
  } catch {
    // Storage unavailable — locale still applies for this session.
  }
}

export default i18n
