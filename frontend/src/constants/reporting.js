export const REPORTS_PATH = '/reports'
export const ADMIN_REPORTS_PATH = '/admin/reports'
export const ADMIN_CONTENT_PATH = '/admin/content'

export const REPORT_TARGET_TYPE = {
  THREAD: 'thread',
  REPLY: 'reply',
  CONTRACT: 'contract',
  LISTING: 'listing',
  DEMAND: 'demand',
}

export const REPORT_TARGET_LABELS = {
  thread: 'Discussion',
  reply: 'Reply',
  contract: 'Forward contract',
  listing: 'Harvest listing',
  demand: 'Buyer demand',
}

export const CONTENT_TYPE_OPTIONS = [
  { value: 'thread', label: 'Discussions' },
  { value: 'reply', label: 'Replies' },
  { value: 'contract', label: 'Forward contracts' },
  { value: 'listing', label: 'Harvest listings' },
  { value: 'demand', label: 'Buyer demands' },
]

export const REPORT_REASON = {
  SPAM: 'spam',
  INAPPROPRIATE: 'inappropriate',
  MISINFORMATION: 'misinformation',
  HARASSMENT: 'harassment',
  OFF_TOPIC: 'off_topic',
  OTHER: 'other',
  SUSPECTED_FRAUD: 'suspected_fraud',
  PROHIBITED_ITEM: 'prohibited_item',
}

export const REPORT_REASON_OPTIONS = [
  { value: 'spam', label: 'Spam' },
  { value: 'inappropriate', label: 'Inappropriate content' },
  { value: 'misinformation', label: 'Misinformation' },
  { value: 'harassment', label: 'Harassment' },
  { value: 'off_topic', label: 'Off topic' },
  { value: 'other', label: 'Other' },
  { value: 'suspected_fraud', label: 'Suspected fraud' },
  { value: 'prohibited_item', label: 'Prohibited item' },
]

export const REPORT_DESCRIPTION_MAX_LENGTH = 1000
export const DESCRIPTION_COUNT_LABEL = '1,000'

export const REPORT_STATUS = {
  OPEN: 'open',
  REVIEWING: 'reviewing',
  RESOLVED: 'resolved',
  DISMISSED: 'dismissed',
}

export const REPORT_STATUS_LABELS = {
  open: 'Open',
  reviewing: 'Reviewing',
  resolved: 'Resolved',
  dismissed: 'Dismissed',
}

export const TERMINAL_REPORT_STATUSES = ['resolved', 'dismissed']

export const REPORT_OUTCOME = {
  HIDDEN: 'hidden',
  NO_ACTION: 'no_action',
}

export const REPORT_OUTCOME_LABELS = {
  hidden: 'Hidden',
  no_action: 'No action',
}

export const DECISION_NOTE_MAX_LENGTH = 500

export const CONTENT_VISIBILITY = {
  VISIBLE: 'visible',
  HIDDEN: 'hidden',
}

export const CONTENT_TYPE_FILTER_OPTIONS = [
  { value: '', label: 'All types' },
  { value: 'thread', label: 'Discussions' },
  { value: 'reply', label: 'Replies' },
  { value: 'contract', label: 'Forward contracts' },
  { value: 'listing', label: 'Harvest listings' },
  { value: 'demand', label: 'Buyer demands' },
]

export const REPORT_STATUS_FILTER_OPTIONS = [
  { value: '', label: 'All statuses' },
  { value: 'open', label: 'Open' },
  { value: 'reviewing', label: 'Reviewing' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'dismissed', label: 'Dismissed' },
]

export const REPORT_REASON_FILTER_OPTIONS = [
  { value: '', label: 'All reasons' },
  ...REPORT_REASON_OPTIONS,
]

export const CONTENT_VISIBILITY_FILTER_OPTIONS = [
  { value: '', label: 'All visibility' },
  { value: 'visible', label: 'Visible' },
  { value: 'hidden', label: 'Hidden' },
]
