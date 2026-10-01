export const PRICE_GUIDE = {
  FAIR_BAND_PCT: 15,
  DEBOUNCE_MS: 400,
  RETRY_DELAY_MS: 8000,
  RETRY_MAX_ATTEMPTS: 1,
  PENDING_MAX_ATTEMPTS: 6,
  CACHE_TTL_MS: 60000,
  GUIDE_ENDPOINT: '/market/prices/guide',
  BATCH_ENDPOINT: '/market/prices/guide/batch',
  COMPARE_ENDPOINT: '/market/prices/guide/compare',
}

export const PRICE_CHECK_STAGES = [
  { key: 'yieldgrid', label: 'YieldGrid price' },
  { key: 'da', label: 'DA price' },
  { key: 'ai', label: 'AI estimate' },
]

export const PRICE_TIER = {
  FARMGATE: 'farmgate',
  WHOLESALE: 'wholesale',
  RETAIL: 'retail',
  ESTIMATE: 'estimate',
}

export const PRICE_TIER_LABELS = {
  farmgate: 'Farmgate',
  wholesale: 'Wholesale',
  retail: 'Retail',
  estimate: 'Market avg',
}

export const PRICE_SOURCE_LABELS = {
  da_bantay_presyo: 'DA Bantay Presyo',
  marketplace_average: 'YieldGrid marketplace',
  manual: 'Reference',
  ai_estimate: 'AI estimate',
}

export const PRICE_MATCH_LABELS = {
  exact: 'Exact match',
  alias: 'Known name',
  fuzzy: 'Corrected spelling',
  ai_corrected: 'AI-corrected',
}
