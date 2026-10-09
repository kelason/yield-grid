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

export const REPORT_TARGET_LABEL_KEYS = {
  thread: 'market.report.target_thread',
  reply: 'market.report.target_reply',
  contract: 'market.report.target_contract',
  listing: 'market.report.target_listing',
  demand: 'market.report.target_demand',
}

export const CONTENT_TYPE_OPTIONS = [
  { value: 'thread', label: 'Discussions', labelKey: 'admin.content.type_thread' },
  { value: 'reply', label: 'Replies', labelKey: 'admin.content.type_reply' },
  { value: 'contract', label: 'Forward contracts', labelKey: 'admin.content.type_contract' },
  { value: 'listing', label: 'Harvest listings', labelKey: 'admin.content.type_listing' },
  { value: 'demand', label: 'Buyer demands', labelKey: 'admin.content.type_demand' },
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
  { value: 'spam', label: 'Spam', labelKey: 'market.report.reason_spam' },
  {
    value: 'inappropriate',
    label: 'Inappropriate content',
    labelKey: 'market.report.reason_inappropriate',
  },
  {
    value: 'misinformation',
    label: 'Misinformation',
    labelKey: 'market.report.reason_misinformation',
  },
  { value: 'harassment', label: 'Harassment', labelKey: 'market.report.reason_harassment' },
  { value: 'off_topic', label: 'Off topic', labelKey: 'market.report.reason_off_topic' },
  { value: 'other', label: 'Other', labelKey: 'market.report.reason_other' },
  {
    value: 'suspected_fraud',
    label: 'Suspected fraud',
    labelKey: 'market.report.reason_suspected_fraud',
  },
  {
    value: 'prohibited_item',
    label: 'Prohibited item',
    labelKey: 'market.report.reason_prohibited_item',
  },
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

export const REPORT_STATUS_LABEL_KEYS = {
  open: 'admin.reports.status_open',
  reviewing: 'admin.reports.status_reviewing',
  resolved: 'admin.reports.status_resolved',
  dismissed: 'admin.reports.status_dismissed',
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

export const REPORT_OUTCOME_LABEL_KEYS = {
  hidden: 'admin.reports.outcome_hidden',
  no_action: 'admin.reports.outcome_no_action',
}

export const DECISION_NOTE_MAX_LENGTH = 500

export const CONTENT_VISIBILITY = {
  VISIBLE: 'visible',
  HIDDEN: 'hidden',
}

export const CONTENT_TYPE_FILTER_OPTIONS = [
  { value: '', label: 'All types', labelKey: 'admin.content.type_all' },
  { value: 'thread', label: 'Discussions', labelKey: 'admin.content.type_thread' },
  { value: 'reply', label: 'Replies', labelKey: 'admin.content.type_reply' },
  { value: 'contract', label: 'Forward contracts', labelKey: 'admin.content.type_contract' },
  { value: 'listing', label: 'Harvest listings', labelKey: 'admin.content.type_listing' },
  { value: 'demand', label: 'Buyer demands', labelKey: 'admin.content.type_demand' },
]

export const REPORT_STATUS_FILTER_OPTIONS = [
  { value: '', label: 'All statuses', labelKey: 'admin.reports.filter_all' },
  { value: 'open', label: 'Open', labelKey: 'admin.reports.status_open' },
  { value: 'reviewing', label: 'Reviewing', labelKey: 'admin.reports.status_reviewing' },
  { value: 'resolved', label: 'Resolved', labelKey: 'admin.reports.status_resolved' },
  { value: 'dismissed', label: 'Dismissed', labelKey: 'admin.reports.status_dismissed' },
]

export const REPORT_REASON_FILTER_OPTIONS = [
  { value: '', label: 'All reasons', labelKey: 'admin.reports.reason_all' },
  ...REPORT_REASON_OPTIONS,
]

export const CONTENT_VISIBILITY_FILTER_OPTIONS = [
  { value: '', label: 'All visibility', labelKey: 'admin.content.vis_all' },
  { value: 'visible', label: 'Visible', labelKey: 'admin.content.vis_visible' },
  { value: 'hidden', label: 'Hidden', labelKey: 'admin.content.vis_hidden' },
]
