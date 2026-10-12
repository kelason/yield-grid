import { getGuide } from './userGuides'

const PREFIX = 'public.how_it_works'
const dimensions = {
  'manual-listing': [308, 477],
  'plot-setup': [356, 422],
  'buyer-checkout': [358, 992],
  recommendations: [358, 418],
}
const screenshotIds = Object.keys(dimensions)
export const TUTORIAL_SCREENSHOTS = Object.fromEntries(
  screenshotIds.map((id) => {
    const key = {
      'plot-setup': 'plot',
      recommendations: 'recommendations',
      'manual-listing': 'manual',
      'buyer-checkout': 'checkout',
    }[id]
    return [
      id,
      {
        id,
        src: `/images/guides/${id}-en.png`,
        width: dimensions[id][0],
        height: dimensions[id][1],
        altKey: `${PREFIX}.${key}_alt`,
        captionKey: `${PREFIX}.${key}_caption`,
      },
    ]
  }),
)

function step([topic, sectionId, screenshotId]) {
  const section = getGuide(topic).sections.find((item) => item.id === sectionId)
  return {
    ...section.steps[0],
    id: `${topic}-${sectionId}`,
    titleKey: section.titleKey,
    guide: { topic, sectionId },
    screenshotId,
  }
}

export const TUTORIAL_JOURNEYS = [
  [
    'plan-a-crop',
    'farmer',
    'plan',
    [
      ['farms-and-plots', 'create-farm'],
      ['farms-and-plots', 'save-a-plot', 'plot-setup'],
      ['crop-advisor', 'request-analysis'],
      ['crop-advisor', 'read-results', 'recommendations'],
      ['crop-advisor', 'publish-a-contract'],
      ['selling-harvests', 'manage-listings'],
    ],
  ],
  [
    'sell-a-harvest',
    'farmer',
    'sell',
    [
      ['selling-harvests', 'manual-listing', 'manual-listing'],
      ['selling-harvests', 'optional-source'],
      ['selling-harvests', 'review-and-publish'],
      ['selling-harvests', 'manage-listings'],
    ],
  ],
  [
    'buy-a-harvest',
    'buyer',
    'buy',
    [
      ['buying-harvests', 'browse-and-filter'],
      ['buying-harvests', 'inspect-listing'],
      ['buying-harvests', 'review-checkout', 'buyer-checkout'],
      ['buying-harvests', 'track-purchase'],
    ],
  ],
  [
    'request-a-crop',
    'buyer',
    'request',
    [
      ['profile-addresses-language', 'saved-addresses'],
      ['demands-and-offers', 'post-demand'],
      ['demands-and-offers', 'decide-offers'],
      ['demands-and-offers', 'pay-and-deliver'],
      ['demands-and-offers', 'complete-and-settle'],
    ],
  ],
].map(([id, role, key, steps]) => ({
  id,
  role,
  titleKey: `${PREFIX}.${key}_title`,
  introKey: `${PREFIX}.${key}_intro`,
  steps: steps.map(step),
}))
