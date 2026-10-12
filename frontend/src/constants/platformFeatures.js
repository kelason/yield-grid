import {
  LightBulbIcon,
  MapIcon,
  ShoppingBagIcon,
  SunIcon,
  ClipboardDocumentListIcon,
  ShieldCheckIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'
export const PLATFORM_FEATURES = [
  {
    icon: LightBulbIcon,
    title: 'AI Crop Advisor',
    id: 'advisor',
    groupId: 'plan-grow',
    audienceKey: 'guideUi.audiences.farmer',
    guide: { topic: 'crop-advisor', sectionId: 'request-analysis' },
    titleKey: 'public.feature_advisor_title',
    description:
      'Explore soil- and location-informed recommendations, then compare crops for rotation or intercropping.',
    descKey: 'public.feature_advisor_desc',
  },
  {
    icon: MapIcon,
    title: 'Farm & Plot Planning',
    id: 'planning',
    groupId: 'plan-grow',
    audienceKey: 'guideUi.audiences.farmer',
    guide: { topic: 'farms-and-plots', sectionId: 'create-farm' },
    titleKey: 'public.feature_planning_title',
    description:
      'Map farm boundaries, organize plots and plan your growing areas in one workspace.',
    descKey: 'public.feature_planning_desc',
  },
  {
    icon: ShoppingBagIcon,
    title: 'Harvest Marketplace',
    id: 'market',
    groupId: 'trade-connect',
    audienceKey: 'guideUi.audiences.both',
    guide: { topic: 'selling-harvests', sectionId: 'manual-listing' },
    titleKey: 'public.feature_market_title',
    description:
      'Trade available harvests or forward contracts, and manage purchases and payment confirmations.',
    descKey: 'public.feature_market_desc',
  },
  {
    icon: SunIcon,
    title: 'Weather Insights',
    id: 'weather',
    groupId: 'plan-grow',
    audienceKey: 'guideUi.audiences.farmer',
    guide: { topic: 'crop-advisor', sectionId: 'read-results' },
    titleKey: 'public.feature_weather_title',
    description: 'Bring local weather conditions into your crop planning decisions.',
    descKey: 'public.feature_weather_desc',
  },
  {
    icon: ClipboardDocumentListIcon,
    title: 'Buyer Demands & Offers',
    id: 'demands',
    groupId: 'trade-connect',
    audienceKey: 'guideUi.audiences.both',
    guide: { topic: 'demands-and-offers', sectionId: 'post-demand' },
    titleKey: 'public.feature_demands_title',
    description:
      'Post crop requirements, receive farmer offers and keep delivery details connected to your orders.',
    descKey: 'public.feature_demands_desc',
  },
  {
    icon: ShieldCheckIcon,
    title: 'Crop Insurance & RSBSA',
    id: 'insurance',
    groupId: 'farm-support',
    audienceKey: 'guideUi.audiences.farmer',
    guide: { topic: 'insurance-and-rsbsa', sectionId: 'prepare-enrollment' },
    titleKey: 'public.feature_insurance_title',
    description:
      'Organize RSBSA details, follow insurance enrollment steps and track claims, reminders and policy records.',
    descKey: 'public.feature_insurance_desc',
  },
  {
    icon: ChartBarIcon,
    title: 'Farmer Trust Score',
    id: 'score',
    groupId: 'farm-support',
    audienceKey: 'guideUi.audiences.farmer',
    guide: { topic: 'verification-and-trust-score', sectionId: 'read-your-score' },
    titleKey: 'public.feature_score_title',
    description:
      'Review your score and its breakdown, follow improvement tips and generate a shareable farmer report.',
    descKey: 'public.feature_score_desc',
  },
  {
    icon: ChatBubbleLeftRightIcon,
    title: 'Community & Messaging',
    id: 'community',
    groupId: 'trade-connect',
    audienceKey: 'guideUi.audiences.both',
    guide: { topic: 'community-and-messaging', sectionId: 'forum-participation' },
    titleKey: 'public.feature_community_title',
    description:
      'Share practical knowledge in discussions and talk directly with farmers and buyers about your trades.',
    descKey: 'public.feature_community_desc',
  },
]

export const FEATURE_GROUPS = ['plan-grow', 'trade-connect', 'farm-support'].map((id) => ({
  id,
  titleKey: `public.features.groups.${id}`,
}))
