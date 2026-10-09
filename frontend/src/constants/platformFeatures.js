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
    titleKey: 'public.feature_advisor_title',
    description:
      'Explore soil- and location-informed recommendations, then compare crops for rotation or intercropping.',
    descKey: 'public.feature_advisor_desc',
  },
  {
    icon: MapIcon,
    title: 'Farm & Plot Planning',
    titleKey: 'public.feature_planning_title',
    description:
      'Map farm boundaries, organize plots and plan your growing areas in one workspace.',
    descKey: 'public.feature_planning_desc',
  },
  {
    icon: ShoppingBagIcon,
    title: 'Harvest Marketplace',
    titleKey: 'public.feature_market_title',
    description:
      'Trade available harvests or forward contracts, and manage purchases and payment confirmations.',
    descKey: 'public.feature_market_desc',
  },
  {
    icon: SunIcon,
    title: 'Weather Insights',
    titleKey: 'public.feature_weather_title',
    description: 'Bring local weather conditions into your crop planning decisions.',
    descKey: 'public.feature_weather_desc',
  },
  {
    icon: ClipboardDocumentListIcon,
    title: 'Buyer Demands & Offers',
    titleKey: 'public.feature_demands_title',
    description:
      'Post crop requirements, receive farmer offers and keep delivery details connected to your orders.',
    descKey: 'public.feature_demands_desc',
  },
  {
    icon: ShieldCheckIcon,
    title: 'Crop Insurance & RSBSA',
    titleKey: 'public.feature_insurance_title',
    description:
      'Organize RSBSA details, follow insurance enrollment steps and track claims, reminders and policy records.',
    descKey: 'public.feature_insurance_desc',
  },
  {
    icon: ChartBarIcon,
    title: 'Farmer Trust Score',
    titleKey: 'public.feature_score_title',
    description:
      'Review your score and its breakdown, follow improvement tips and generate a shareable farmer report.',
    descKey: 'public.feature_score_desc',
  },
  {
    icon: ChatBubbleLeftRightIcon,
    title: 'Community & Messaging',
    titleKey: 'public.feature_community_title',
    description:
      'Share practical knowledge in discussions and talk directly with farmers and buyers about your trades.',
    descKey: 'public.feature_community_desc',
  },
]
