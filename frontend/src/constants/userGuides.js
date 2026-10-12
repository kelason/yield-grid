import guideText from '@/locales/guides/en'
import { USER_ROLES } from './roles'

export const GUIDE_AUDIENCES = { BOTH: 'both', FARMER: 'farmer', BUYER: 'buyer' }
export const GUIDE_GROUPS = ['start', 'grow', 'trade', 'support'].map((id) => ({
  id,
  titleKey: `guideUi.groups.${id}`,
}))

function sectionReferences(topic, id) {
  const text = guideText[topic].sections[id]
  const prefix = `guides.${topic}.sections.${id}`
  return {
    id,
    titleKey: `${prefix}.title`,
    introKey: `${prefix}.intro`,
    steps: Object.keys(text.steps).map((step) => ({
      id: step,
      instructionKey: `${prefix}.steps.${step}.instruction`,
      resultKey: `${prefix}.steps.${step}.result`,
    })),
    noteKeys: Object.keys(text.notes).map((key) => `${prefix}.notes.${key}`),
    recoveryKeys: Object.keys(text.recovery).map((key) => `${prefix}.recovery.${key}`),
  }
}

function guideReferences([id, groupId, audience, relatedTopics, action]) {
  return {
    id,
    groupId,
    audience,
    titleKey: `guides.${id}.title`,
    summaryKey: `guides.${id}.summary`,
    prerequisiteKeys: Object.keys(guideText[id].prerequisites).map(
      (key) => `guides.${id}.prerequisites.${key}`,
    ),
    sections: Object.keys(guideText[id].sections).map((section) => sectionReferences(id, section)),
    relatedTopics,
    ...(action ? { action } : {}),
  }
}

export const USER_GUIDES = [
  [
    'buying-harvests',
    'trade',
    GUIDE_AUDIENCES.BUYER,
    ['payments-and-purchases', 'demands-and-offers'],
    { role: USER_ROLES.BUYER, to: { name: 'buyer-marketplace' }, labelKey: 'nav.browse_market' },
  ],
  [
    'payments-and-purchases',
    'trade',
    GUIDE_AUDIENCES.BOTH,
    ['buying-harvests', 'demands-and-offers'],
    { role: USER_ROLES.BUYER, to: { name: 'buyer-purchases' }, labelKey: 'nav.my_purchases' },
  ],
  [
    'demands-and-offers',
    'trade',
    GUIDE_AUDIENCES.BOTH,
    ['payments-and-purchases', 'profile-addresses-language'],
    { role: USER_ROLES.BUYER, to: { name: 'buyer-demands' }, labelKey: 'nav.my_demands' },
  ],
  ['price-guide', 'trade', GUIDE_AUDIENCES.BOTH, ['selling-harvests', 'demands-and-offers']],
  [
    'community-and-messaging',
    'support',
    GUIDE_AUDIENCES.BOTH,
    ['demands-and-offers', 'troubleshooting-and-support'],
  ],
  [
    'farms-and-plots',
    'grow',
    GUIDE_AUDIENCES.FARMER,
    ['crop-advisor', 'selling-harvests'],
    { role: USER_ROLES.FARMER, to: { name: 'farm-manager' }, labelKey: 'nav.my_farms' },
  ],
  [
    'crop-advisor',
    'grow',
    GUIDE_AUDIENCES.FARMER,
    ['farms-and-plots', 'selling-harvests'],
    { role: USER_ROLES.FARMER, to: { name: 'recommendations' }, labelKey: 'nav.recommendations' },
  ],
  [
    'selling-harvests',
    'trade',
    GUIDE_AUDIENCES.FARMER,
    ['price-guide', 'payments-and-purchases', 'crop-advisor'],
    {
      role: USER_ROLES.FARMER,
      to: { name: 'farmer-new-listing' },
      labelKey: 'farmer.listing.title',
    },
  ],
  [
    'verification-and-trust-score',
    'support',
    GUIDE_AUDIENCES.FARMER,
    ['getting-started', 'farms-and-plots'],
    { role: USER_ROLES.FARMER, to: { name: 'credit-score' }, labelKey: 'farmer.credit.title' },
  ],
  [
    'insurance-and-rsbsa',
    'support',
    GUIDE_AUDIENCES.FARMER,
    ['farms-and-plots', 'troubleshooting-and-support'],
    { role: USER_ROLES.FARMER, to: { name: 'insurance' }, labelKey: 'nav.crop_insurance' },
  ],
  [
    'getting-started',
    'start',
    GUIDE_AUDIENCES.BOTH,
    ['profile-addresses-language', 'troubleshooting-and-support'],
  ],
  [
    'profile-addresses-language',
    'start',
    GUIDE_AUDIENCES.BOTH,
    ['getting-started', 'troubleshooting-and-support'],
  ],
  [
    'troubleshooting-and-support',
    'support',
    GUIDE_AUDIENCES.BOTH,
    ['getting-started', 'crop-advisor', 'payments-and-purchases', 'community-and-messaging'],
  ],
].map(guideReferences)

export function getGuide(topic) {
  return USER_GUIDES.find((guide) => guide.id === topic)
}
