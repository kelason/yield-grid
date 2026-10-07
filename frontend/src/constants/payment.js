export const PAYMENT_CONSTANTS = {
  DOWNPAYMENT_PERCENTAGE: 0.1,
}

export const PAYMENT_OPTION = {
  CASH: 'cash',
  PAYMONGO: 'paymongo',
}

export const PAYMENT_STATUS = { PENDING: 'pending', COMPLETED: 'completed', FAILED: 'failed' }

export const CASH_PAYMENT_TYPE = { PARTIAL: 'partial', FULL: 'full' }
export const CASH_PAYMENT_LIMITS = { MIN: 0.01, MAX: 99999999, MAX_LENGTH: 8 }
