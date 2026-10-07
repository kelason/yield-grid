import { DESIGN_COLORS } from './designTokens'

export const CREDIT_SCORE = {
  SCORE_ENDPOINT: '/farmer/credit-score',
  HISTORY_ENDPOINT: '/farmer/credit-score/history',
  REPORT_ENDPOINT: '/farmer/credit-score/report',
  REPORT_DOWNLOAD_PATH: (token) => `/farmer/credit-score/report/${token}/download`,
  REPORT_POLL_INTERVAL_MS: 3000,
  REPORT_POLL_MAX_ATTEMPTS: 20,
  MIN_SCORE: 0,
  MAX_SCORE: 100,
}

export const CREDIT_TIER = {
  EXCELLENT: 'excellent',
  GOOD: 'good',
  FAIR: 'fair',
  DEVELOPING: 'developing',
  NEW_FARMER: 'new',
}

export const CREDIT_TIER_STYLES = {
  excellent: {
    pill: 'bg-moss-100 text-moss-800 border-moss-200',
    ring: DESIGN_COLORS.moss[500],
    bar: 'bg-moss-500',
  },
  good: {
    pill: 'bg-moss-100 text-moss-800 border-moss-200',
    ring: DESIGN_COLORS.moss[400],
    bar: 'bg-moss-400',
  },
  fair: {
    pill: 'bg-harvest-100 text-harvest-800 border-harvest-200',
    ring: DESIGN_COLORS.harvest[500],
    bar: 'bg-harvest-500',
  },
  developing: {
    pill: 'bg-harvest-100 text-harvest-800 border-harvest-200',
    ring: DESIGN_COLORS.harvest[600],
    bar: 'bg-harvest-600',
  },
  new: {
    pill: 'bg-stone-100 text-stone-800 border-stone-200',
    ring: DESIGN_COLORS.stone[400],
    bar: 'bg-stone-400',
  },
}

export const CREDIT_DIMENSIONS = [
  {
    key: 'plot_activity',
    label: 'Plot Activity',
    weightPct: 15,
    description:
      'Grows with more active plots that have mapped boundaries and soil types, plus a bonus for requesting AI recommendations.',
  },
  {
    key: 'recommendation_adherence',
    label: 'AI Recommendations',
    weightPct: 15,
    description:
      'Measures how many AI crop recommendations you accept and follow through into marketplace contracts.',
  },
  {
    key: 'contract_fulfillment',
    label: 'Contract Fulfillment',
    weightPct: 25,
    description:
      'Tracks sold-through forward contracts and sales volume, with a clean record free of cancellations.',
  },
  {
    key: 'offer_reliability',
    label: 'Offer Reliability',
    weightPct: 20,
    description:
      'Reflects accepted offers that reach delivery and completion on time, with few withdrawals.',
  },
  {
    key: 'transaction_volume',
    label: 'Transaction Volume',
    weightPct: 15,
    description:
      'Sums completed sales value and transaction count, rewarding steady selling month after month.',
  },
  {
    key: 'platform_tenure',
    label: 'Platform Tenure',
    weightPct: 10,
    description:
      'Rewards account age plus a complete profile: verified email, phone number, delivery address, and avatar.',
  },
]

export const CREDIT_REPORT_STATUS = {
  NONE: 'none',
  GENERATING: 'generating',
  READY: 'ready',
  FAILED: 'failed',
}
