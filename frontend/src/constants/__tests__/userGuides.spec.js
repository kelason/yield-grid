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
  it('provides the complete buying and offer lifecycle with addressable steps', () => {
    expect(USER_GUIDES).toHaveLength(13)
    expect(getGuide('demands-and-offers').sections.map((section) => section.id)).toEqual([
      'post-demand',
      'submit-offer',
      'decide-offers',
      'pay-and-deliver',
      'complete-and-settle',
    ])
    for (const id of [
      'buying-harvests',
      'payments-and-purchases',
      'price-guide',
      'community-and-messaging',
    ])
      expect(getGuide(id)).toBeDefined()
  })
  it('provides farm planning and direct selling as separately addressable guides', () => {
    for (const id of [
      'farms-and-plots',
      'crop-advisor',
      'selling-harvests',
      'verification-and-trust-score',
      'insurance-and-rsbsa',
    ]) {
      expect(getGuide(id)).toBeDefined()
    }
    expect(getGuide('selling-harvests').sections.map((section) => section.id)).toContain(
      'optional-source',
    )
  })
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

it.each(['en', 'tl', 'ceb'])('%s procedures use the visible localized controls', (locale) => {
  const catalog = catalogs[locale]
  const controls = [
    ['farms-and-plots', 'create-farm', 'nav.my_farms'],
    ['crop-advisor', 'request-analysis', 'nav.recommendations'],
    ['crop-advisor', 'compare-crops', 'nav.compatibility'],
    ['crop-advisor', 'publish-a-contract', 'farmer.rec_card.accept_contract'],
    ['selling-harvests', 'manual-listing', 'nav.post_harvest'],
    ['selling-harvests', 'manual-listing', 'nav.my_contracts'],
    ['selling-harvests', 'manage-listings', 'nav.cash_approvals'],
    ['verification-and-trust-score', 'read-your-score', 'nav.trust_score'],
    ['insurance-and-rsbsa', 'rsbsa-records', 'nav.crop_insurance'],
    ['buying-harvests', 'browse-and-filter', 'nav.browse_market'],
    ['buying-harvests', 'inspect-listing', 'farmer.contract_card.view_details'],
    ['buying-harvests', 'track-purchase', 'nav.my_purchases'],
    ['payments-and-purchases', 'statuses-and-cancellation', 'nav.my_purchases'],
    ['demands-and-offers', 'post-demand', 'nav.post_demand'],
    ['demands-and-offers', 'submit-offer', 'nav.crop_demands'],
    ['demands-and-offers', 'decide-offers', 'nav.my_demands'],
    ['troubleshooting-and-support', 'contact-support', 'nav.report_issue'],
    ['troubleshooting-and-support', 'contact-support', 'common.nav.contact'],
  ]
  expect(
    catalog.guides['demands-and-offers'].sections['submit-offer'].steps.follow.result,
  ).toContain(catalog.nav.my_offers)
  for (const [topic, section, label] of controls)
    expect(catalog.guides[topic].sections[section].steps.follow.instruction).toContain(
      valueAt(catalog, label),
    )
})
