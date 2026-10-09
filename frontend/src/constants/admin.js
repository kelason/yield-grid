export const ADMIN_SEARCH_MAX_LENGTH = 100
export const SUSPENSION_REASON_MAX_LENGTH = 500
export const SUSPENDED_CODE = 'account_suspended'
export const SUSPENDED_MESSAGE = 'This account is suspended. Contact support for assistance.'

export const ADMIN_USERS_PATH = '/admin/users'

export const ADMIN_OVERVIEW_PATH = '/admin/overview'

export const ADMIN_INQUIRIES_PATH = '/admin/contact-messages'
export const CONTACT_REPLY_BODY_MAX_LENGTH = 5000
export const CONTACT_REPLY_STALE_SECONDS = 300

export const ADMIN_INQUIRY_STATUS_OPTIONS = [
  { value: '', label: 'All statuses', labelKey: 'admin.inquiry.filter_all' },
  { value: 'unread', label: 'Unread', labelKey: 'admin.inquiry.status_unread' },
  { value: 'read', label: 'Read', labelKey: 'admin.inquiry.status_read' },
  { value: 'replied', label: 'Replied', labelKey: 'admin.inquiry.status_replied' },
  { value: 'closed', label: 'Closed', labelKey: 'admin.inquiry.status_closed' },
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

export const REPLY_DELIVERY_LABEL_KEYS = {
  queued: 'admin.inquiry.delivery_queued',
  sending: 'admin.inquiry.delivery_sending',
  sent: 'admin.inquiry.delivery_sent',
  failed: 'admin.inquiry.delivery_failed',
}

export const ADMIN_INQUIRY_DELIVERY_OPTIONS = [
  { value: '', label: 'All deliveries', labelKey: 'admin.inquiry.delivery_all' },
  {
    value: REPLY_DELIVERY_STATUS.FAILED,
    label: 'Failed replies',
    labelKey: 'admin.inquiry.failed_replies',
  },
]

export const DUPLICATE_DELIVERY_CAUTION =
  'The previous attempt may already have been delivered; retrying can send a duplicate.'

export const ADMIN_USER_ROLE_MEMBERS = 'members'

export const ADMIN_USER_ROLE_OPTIONS = [
  { value: '', label: 'All roles', labelKey: 'admin.users.role_all' },
  { value: ADMIN_USER_ROLE_MEMBERS, label: 'Members', labelKey: 'admin.users.role_members' },
  { value: 'farmer', label: 'Farmer', labelKey: 'admin.users.role_farmer' },
  { value: 'buyer', label: 'Buyer', labelKey: 'admin.users.role_buyer' },
  { value: 'admin', label: 'Admin', labelKey: 'admin.users.role_admin' },
]

export const ADMIN_USER_STATUS_OPTIONS = [
  { value: '', label: 'All statuses', labelKey: 'admin.users.status_all' },
  { value: 'active', label: 'Active', labelKey: 'admin.users.status_active' },
  { value: 'suspended', label: 'Suspended', labelKey: 'admin.users.status_suspended' },
]

export const ADMIN_USER_STATUS_FILTER = {
  ALL: '',
  ACTIVE: 'active',
  SUSPENDED: 'suspended',
}
