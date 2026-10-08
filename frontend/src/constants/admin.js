export const ADMIN_SEARCH_MAX_LENGTH = 100
export const SUSPENSION_REASON_MAX_LENGTH = 500
export const SUSPENDED_CODE = 'account_suspended'
export const SUSPENDED_MESSAGE = 'This account is suspended. Contact support for assistance.'

export const ADMIN_USERS_PATH = '/admin/users'

export const ADMIN_USER_ROLE_OPTIONS = [
  { value: '', label: 'All roles' },
  { value: 'farmer', label: 'Farmer' },
  { value: 'buyer', label: 'Buyer' },
  { value: 'admin', label: 'Admin' },
]

export const ADMIN_USER_STATUS_OPTIONS = [
  { value: '', label: 'All statuses' },
  { value: 'active', label: 'Active' },
  { value: 'suspended', label: 'Suspended' },
]

export const ADMIN_USER_STATUS_FILTER = {
  ALL: '',
  ACTIVE: 'active',
  SUSPENDED: 'suspended',
}
