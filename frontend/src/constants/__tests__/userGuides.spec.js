import { describe, expect, it } from 'vitest'
import en from '@/locales/en'
import tl from '@/locales/tl'
import ceb from '@/locales/ceb'
import { USER_GUIDES, getGuide } from '../userGuides'

const catalogs = { en, tl, ceb }
const valueAt = (catalog, key) => key.split('.').reduce((value, part) => value?.[part], catalog)

function textKeys(value) {
  return Object.entries(value).flatMap(([key, item]) => {
    if (key.endsWith('Key')) return [item]
    if (key.endsWith('Keys')) return item
    if (item && typeof item === 'object') return textKeys(item)
    return []
  })
}

describe('user guide destinations', () => {
  it('resolves known topics and rejects unknown topic input', () => {
    expect(getGuide('getting-started').sections.map((section) => section.id)).toContain(
      'verify-email',
    )
    expect(getGuide('not-a-guide')).toBeUndefined()
    expect(getGuide('__proto__')).toBeUndefined()
  })

  it('keeps topic and section destinations unique and all related guides reachable', () => {
    expect(new Set(USER_GUIDES.map((guide) => guide.id)).size).toBe(USER_GUIDES.length)
    for (const guide of USER_GUIDES) {
      expect(new Set(guide.sections.map((section) => section.id)).size).toBe(guide.sections.length)
      for (const related of guide.relatedTopics) expect(getGuide(related)).toBeDefined()
    }
  })

  it('provides real text for every guide reference in every language', () => {
    for (const [locale, catalog] of Object.entries(catalogs)) {
      for (const key of textKeys(USER_GUIDES)) {
        expect({ locale, key, text: valueAt(catalog, key) }).toEqual({
          locale,
          key,
          text: expect.any(String),
        })
        expect(valueAt(catalog, key).trim()).not.toBe('')
      }
    }
  })
})
