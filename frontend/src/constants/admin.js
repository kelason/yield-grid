export const ADMIN_SEARCH_MAX_LENGTH = 100
export const SUSPENSION_REASON_MAX_LENGTH = 500
export const SUSPENDED_CODE = 'account_suspended'
export const SUSPENDED_MESSAGE = 'This account is suspended. Contact support for assistance.'

export const ADMIN_USERS_PATH = '/admin/users'

export const ADMIN_INQUIRIES_PATH = '/admin/contact-messages'
export const CONTACT_REPLY_BODY_MAX_LENGTH = 5000
export const CONTACT_REPLY_STALE_SECONDS = 300

export const ADMIN_INQUIRY_STATUS_OPTIONS = [
  { value: '', label: 'All statuses' },
  { value: 'unread', label: 'Unread' },
  { value: 'read', label: 'Read' },
  { value: 'replied', label: 'Replied' },
  { value: 'closed', label: 'Closed' },
]

export const CONTACT_INQUIRY_STATUS = {
  UNREAD: 'unread',
  READ: 'read',
  REPLIED: 'replied',
  CLOSED: 'closed',
}

export const REPLY_DELIVERY_STATUS = {
  QUEUED: 'queued',
  SENDING: 'sending',
  SENT: 'sent',
  FAILED: 'failed',
}

export const REPLY_DELIVERY_LABELS = {
  queued: 'Queued',
  sending: 'Sending',
  sent: 'Sent',
  failed: 'Failed',
}

export const DUPLICATE_DELIVERY_CAUTION =
  'The previous attempt may already have been delivered; retrying can send a duplicate.'

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
