import { VERIFICATION_STATUS } from '@/constants/verification'

export function isFarmVerified(farm) {
  return farm?.verification_status === VERIFICATION_STATUS.VERIFIED
}

export function isPlotVerified(plot, farm) {
  return (
    plot?.verification_status === VERIFICATION_STATUS.VERIFIED &&
    farm?.verification_status === VERIFICATION_STATUS.VERIFIED
  )
}

export function isListingVerified(item) {
  return item?.is_from_verified_farm === true
}

export function isRecommendationVerified(recommendation) {
  return recommendation?.is_from_verified_source === true
}
