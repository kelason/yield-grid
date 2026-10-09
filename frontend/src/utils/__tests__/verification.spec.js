import { describe, it, expect } from 'vitest'
import {
  isFarmVerified,
  isPlotVerified,
  isListingVerified,
  isRecommendationVerified,
} from '../verification'
import { VERIFICATION_STATUS } from '@/constants/verification'

describe('verification utils', () => {
  it('detects verified farms null-safely', () => {
    expect(isFarmVerified({ verification_status: 'verified' })).toBe(true)
    expect(isFarmVerified({ verification_status: 'pending' })).toBe(false)
    expect(isFarmVerified({ verification_status: 'rejected' })).toBe(false)
    expect(isFarmVerified({})).toBe(false)
    expect(isFarmVerified(null)).toBe(false)
    expect(isFarmVerified(undefined)).toBe(false)
  })

  it('hides plot verification until the parent farm is verified', () => {
    const plot = { verification_status: 'verified' }
    expect(isPlotVerified(plot, { verification_status: 'pending' })).toBe(false)
    expect(isPlotVerified(plot, { verification_status: 'verified' })).toBe(true)
    expect(isPlotVerified(plot, null)).toBe(false)
  })

  it('requires the plot itself to be verified', () => {
    const farm = { verification_status: 'verified' }
    expect(isPlotVerified({ verification_status: 'pending' }, farm)).toBe(false)
    expect(isPlotVerified(null, farm)).toBe(false)
    expect(isPlotVerified(undefined, farm)).toBe(false)
  })

  it('detects verified listings by flag only', () => {
    expect(isListingVerified({ is_from_verified_farm: true })).toBe(true)
    expect(isListingVerified({ is_from_verified_farm: false })).toBe(false)
    expect(isListingVerified({})).toBe(false)
    expect(isListingVerified(null)).toBe(false)
  })

  it('detects verified recommendations by flag only', () => {
    expect(isRecommendationVerified({ is_from_verified_source: true })).toBe(true)
    expect(isRecommendationVerified({ is_from_verified_source: false })).toBe(false)
    expect(isRecommendationVerified(null)).toBe(false)
  })

  it('exposes the status values matching the backend enum', () => {
    expect(VERIFICATION_STATUS).toEqual({
      PENDING: 'pending',
      VERIFIED: 'verified',
      REJECTED: 'rejected',
    })
  })
})
