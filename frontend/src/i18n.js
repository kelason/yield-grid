import { createI18n } from 'vue-i18n'
import ceb from './locales/ceb'
import en from './locales/en'
import tl from './locales/tl'
import {
  BROWSER_LOCALE_MAP,
  DEFAULT_LOCALE,
  LEGACY_INSURANCE_LOCALE_KEY,
  LOCALE_KEY,
  SUPPORTED_LOCALES,
} from './constants/locale'

export { SUPPORTED_LOCALES, DEFAULT_LOCALE, LOCALE_KEY, LEGACY_INSURANCE_LOCALE_KEY }

function readStored(key) {
  try {
    return localStorage.getItem(key)
  } catch {
    return null
  }
}

function writeStored(key, value) {
  try {
    localStorage.setItem(key, value)
  } catch {
    // Storage unavailable — locale still applies for this session.
  }
}

function removeStored(key) {
  try {
    localStorage.removeItem(key)
  } catch {
    // Storage unavailable — nothing to clean up.
  }
}

function browserLocale() {
  const tag = typeof navigator === 'undefined' ? '' : navigator.language || ''
  return BROWSER_LOCALE_MAP[tag.toLowerCase()] || null
}

function initialLocale() {
  const saved = readStored(LOCALE_KEY)
  if (SUPPORTED_LOCALES.includes(saved)) {
    return saved
  }
  const legacy = readStored(LEGACY_INSURANCE_LOCALE_KEY)
  if (SUPPORTED_LOCALES.includes(legacy)) {
    writeStored(LOCALE_KEY, legacy)
    removeStored(LEGACY_INSURANCE_LOCALE_KEY)
    return legacy
  }
  return browserLocale() || DEFAULT_LOCALE
}

const i18n = createI18n({
  legacy: false,
  locale: initialLocale(),
  fallbackLocale: DEFAULT_LOCALE,
  messages: { en, tl, ceb },
})

export function setLocale(locale) {
  if (!SUPPORTED_LOCALES.includes(locale)) {
    return
  }
  i18n.global.locale.value = locale
  writeStored(LOCALE_KEY, locale)
}

export default i18n
