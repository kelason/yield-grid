import i18n from '@/i18n'

export const CATALOG_LIMITS = {
  PRICE_MIN: 0,
  PRICE_MAX: 9999999999.99,
  PRICE_MAX_LENGTH: 13,
  CROP_MAX_LENGTH: 100,
}

export function catalogFilterError(filters, minKey, maxKey) {
  if ((filters.crop || '').length > CATALOG_LIMITS.CROP_MAX_LENGTH)
    return i18n.global.t('farmer.common.filter_crop_too_long')
  for (const key of [minKey, maxKey]) {
    const value = filters[key]
    if (value === '' || value == null) continue
    const amount = Number(value)
    if (
      !Number.isFinite(amount) ||
      amount < CATALOG_LIMITS.PRICE_MIN ||
      amount > CATALOG_LIMITS.PRICE_MAX
    )
      return i18n.global.t('farmer.common.filter_price_range')
  }
  if (
    filters[minKey] !== '' &&
    filters[maxKey] !== '' &&
    filters[minKey] != null &&
    filters[maxKey] != null &&
    Number(filters[minKey]) > Number(filters[maxKey])
  )
    return i18n.global.t('farmer.common.filter_max_min')
  return ''
}
