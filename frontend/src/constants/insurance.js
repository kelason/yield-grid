export const INSURANCE_ENDPOINTS = {
  PROFILE: '/farmer/insurance/profile',
  ENROLLMENTS: '/farmer/insurance/enrollments',
  ENROLLMENT_STATUS: (id) => `/farmer/insurance/enrollments/${id}/status`,
  ENROLLMENT_POLICY_DETAILS: (id) => `/farmer/insurance/enrollments/${id}/policy-details`,
  ENROLLMENT_PACK: (id) => `/farmer/insurance/enrollments/${id}/pack`,
  ENROLLMENT_PACK_DOWNLOAD: (id) => `/farmer/insurance/enrollments/${id}/pack/download`,
  ENROLLMENT_CLAIMS: (id) => `/farmer/insurance/enrollments/${id}/claims`,
  CLAIM_ADVANCE: (id) => `/farmer/insurance/claims/${id}/advance`,
  REMINDERS: '/farmer/insurance/reminders',
  OFFICES: '/farmer/insurance/offices',
}

export const INSURANCE_PROGRAMS = ['rice', 'corn']

export const INSURANCE_SEASONS = ['wet', 'dry']

export const INSURANCE_LIMITS = {
  RSBSA_NUMBER_MAX_LENGTH: 30,
  CIC_NUMBER_MAX_LENGTH: 30,
  PAYOUT_AMOUNT_MAX: 99999999.99,
  NOTES_MAX_LENGTH: 1000,
  CLAIM_DESCRIPTION_MAX_LENGTH: 5000,
  SEASON_YEAR_MIN: 2020,
  SEASON_YEAR_MAX: 2100,
}

export const INSURANCE_PACK = {
  FILENAME: 'YieldGrid-PCIC-Enrollment-Pack.pdf',
  POLL_INTERVAL_MS: 3000,
  POLL_MAX_ATTEMPTS: 20,
}

// 2026 PCIC multi-peril cover for rice/corn total loss, per hectare in PHP.
export const INSURANCE_COVERAGE_PER_HECTARE_PHP = 25000
