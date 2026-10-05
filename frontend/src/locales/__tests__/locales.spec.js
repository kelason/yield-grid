import { describe, it, expect } from 'vitest'
import en from '../en'
import tl from '../tl'

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
  it('defines identical key sets in English and Tagalog', () => {
    const enKeys = flattenKeys(en).sort()
    const tlKeys = flattenKeys(tl).sort()

    expect(tlKeys).toEqual(enKeys)
    expect(enKeys.length).toBeGreaterThan(20)
  })

  it('covers every backend reminder translation key', () => {
    const types = ['enrollment_window', 'notice_of_loss_deadline', 'claim_followup', 'renewal']

    for (const type of types) {
      for (const locale of [en, tl]) {
        expect(valueAt(locale, `insurance.reminders.${type}.title`)).toBeTruthy()
        expect(valueAt(locale, `insurance.reminders.${type}.message`)).toBeTruthy()
      }
    }
  })

  it('uses identical interpolation placeholders across locales', () => {
    const stringKeys = flattenKeys(en).filter((key) => typeof valueAt(en, key) === 'string')

    expect(stringKeys.length).toBeGreaterThan(0)

    const mismatches = stringKeys.filter((key) => {
      const enPlaceholders = placeholders(valueAt(en, key)).join(',')
      const tlPlaceholders = placeholders(valueAt(tl, key)).join(',')
      return enPlaceholders !== tlPlaceholders
    })

    expect(mismatches).toEqual([])
  })
})
