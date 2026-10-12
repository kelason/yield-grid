import guideText from '@/locales/guides/en'

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

function guideReferences([id, groupId, audience, relatedTopics]) {
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
  }
}

export const USER_GUIDES = [
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
    ['getting-started', 'profile-addresses-language'],
  ],
].map(guideReferences)

export function getGuide(topic) {
  return USER_GUIDES.find((guide) => guide.id === topic)
}
