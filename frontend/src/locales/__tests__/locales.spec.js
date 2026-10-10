import { describe, it, expect } from 'vitest'
import i18n, { setLocale, SUPPORTED_LOCALES } from '@/i18n'
import en from '../en'
import tl from '../tl'
import ceb from '../ceb'

const LOCALES = { en, tl, ceb }

function flattenKeys(obj, prefix = '') {
  return Object.entries(obj).flatMap(([key, value]) => {
    const path = prefix === '' ? key : `${prefix}.${key}`
    if (value !== null && typeof value === 'object') {
      return flattenKeys(value, path)
    }
    return [path]
  })
}

function placeholders(str) {
  return [...str.matchAll(/\{(\w+)\}/g)].map((m) => m[1]).sort()
}

function valueAt(obj, path) {
  return path.split('.').reduce((acc, part) => acc[part], obj)
}

describe('insurance locales', () => {
  it('defines identical key sets in English, Tagalog, and Cebuano', () => {
    const enKeys = flattenKeys(en).sort()

    expect(enKeys.length).toBeGreaterThan(20)
    for (const locale of [tl, ceb]) {
      expect(flattenKeys(locale).sort()).toEqual(enKeys)
    }
  })

  it('covers every backend reminder translation key', () => {
    const types = ['enrollment_window', 'notice_of_loss_deadline', 'claim_followup', 'renewal']

    for (const type of types) {
      for (const locale of [en, tl, ceb]) {
        expect(valueAt(locale, `insurance.reminders.${type}.title`)).toBeTruthy()
        expect(valueAt(locale, `insurance.reminders.${type}.message`)).toBeTruthy()
      }
    }
  })

  it('uses identical interpolation placeholders across locales', () => {
    const stringKeys = flattenKeys(en).filter((key) => typeof valueAt(en, key) === 'string')

    expect(stringKeys.length).toBeGreaterThan(0)

    const mismatches = stringKeys.filter((key) => {
      const expected = placeholders(valueAt(en, key)).join(',')
      return [tl, ceb].some((locale) => placeholders(valueAt(locale, key)).join(',') !== expected)
    })

    expect(mismatches).toEqual([])
  })

  it('compiles every message in all locales without syntax errors', () => {
    const previous = i18n.global.locale.value
    const failures = []

    for (const code of SUPPORTED_LOCALES) {
      setLocale(code)
      for (const key of flattenKeys(LOCALES[code])) {
        const message = valueAt(LOCALES[code], key)
        if (typeof message !== 'string') continue
        const params = Object.fromEntries(placeholders(message).map((name) => [name, '0']))
        try {
          i18n.global.t(key, params)
        } catch {
          failures.push(`${code}:${key}`)
        }
      }
    }

    setLocale(previous)
    expect(failures).toEqual([])
  })

  it('covers every shared chrome key in all locales', () => {
    const keys = [
      'common.nav.home',
      'common.nav.about',
      'common.nav.contact',
      'common.nav.dashboard',
      'common.auth.login',
      'common.auth.logout',
      'common.auth.get_started',
      'common.actions.retry',
      'common.actions.save',
      'common.actions.cancel',
      'common.actions.close',
      'common.language.label',
      'common.language.english',
      'common.language.tagalog',
      'common.language.bisaya',
    ]

    for (const [name, locale] of Object.entries(LOCALES)) {
      for (const key of keys) {
        expect(typeof valueAt(locale, key), `${name}:${key}`).toBe('string')
        expect(valueAt(locale, key).length, `${name}:${key}`).toBeGreaterThan(0)
      }
    }
  })
})
