import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

export const ISSUES_PATH = '/issues'
export const ADMIN_ISSUES_PATH = '/admin/issues'

export const ISSUE_CATEGORY = {
  TECHNICAL: 'technical',
  ACCOUNT: 'account',
  MARKETPLACE: 'marketplace',
  PAYMENT: 'payment',
  OTHER: 'other',
}

export const ISSUE_CATEGORY_OPTIONS = [
  { value: 'technical', label: 'Technical', labelKey: 'issues.cat.technical' },
  { value: 'account', label: 'Account', labelKey: 'issues.cat.account' },
  { value: 'marketplace', label: 'Marketplace', labelKey: 'issues.cat.marketplace' },
  { value: 'payment', label: 'Payment', labelKey: 'issues.cat.payment' },
  { value: 'other', label: 'Other', labelKey: 'issues.cat.other' },
]

export const ISSUE_CATEGORY_LABELS = {
  technical: 'Technical',
  account: 'Account',
  marketplace: 'Marketplace',
  payment: 'Payment',
  other: 'Other',
}

export const ISSUE_CATEGORY_LABEL_KEYS = {
  technical: 'issues.cat.technical',
  account: 'issues.cat.account',
  marketplace: 'issues.cat.marketplace',
  payment: 'issues.cat.payment',
  other: 'issues.cat.other',
}

export const ISSUE_STATUS = {
  OPEN: 'open',
  IN_PROGRESS: 'in_progress',
  RESOLVED: 'resolved',
  CLOSED: 'closed',
}

export const ISSUE_STATUS_LABELS = {
  open: 'Open',
  in_progress: 'In progress',
  resolved: 'Resolved',
  closed: 'Closed',
}

export const ISSUE_STATUS_LABEL_KEYS = {
  open: 'issues.status.open',
  in_progress: 'issues.status.in_progress',
  resolved: 'issues.status.resolved',
  closed: 'issues.status.closed',
}

export const ISSUE_SUBJECT_MAX_LENGTH = 150
export const ISSUE_DESCRIPTION_MAX_LENGTH = 5000
export const ISSUE_PAGE_PATH_MAX_LENGTH = 255
export const ISSUE_RESOLUTION_MAX_LENGTH = 2000

export const ISSUE_TRANSITIONS = {
  open: ['in_progress', 'resolved', 'closed'],
  in_progress: ['resolved', 'closed'],
  resolved: ['in_progress', 'closed'],
  closed: ['in_progress'],
}

export const ISSUE_CLOSING_STATUSES = ['resolved', 'closed']

export const ISSUE_STATUS_FILTER_OPTIONS = [
  { value: '', label: 'All statuses', labelKey: 'issues.report.filter_all' },
  { value: 'open', label: 'Open', labelKey: 'issues.status.open' },
  { value: 'in_progress', label: 'In progress', labelKey: 'issues.status.in_progress' },
  { value: 'resolved', label: 'Resolved', labelKey: 'issues.status.resolved' },
  { value: 'closed', label: 'Closed', labelKey: 'issues.status.closed' },
]

export const ISSUE_CATEGORY_FILTER_OPTIONS = [
  { value: '', label: 'All categories' },
  ...ISSUE_CATEGORY_OPTIONS,
]

export const ISSUE_CREDENTIAL_WARNING =
  'Describe what happened. Do not include passwords or payment credentials.'

export function newClientRequestId() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
    const random = Math.floor(Math.random() * 16)
    const value = char === 'x' ? random : (random & 0x3) | 0x8
    return value.toString(16)
  })
}

export function isSafeIssuePath(path) {
  if (typeof path !== 'string' || !path.startsWith('/') || path.startsWith('//')) return false
  if (path.length > ISSUE_PAGE_PATH_MAX_LENGTH) return false
  if (/[\s?#\\]|\/\//.test(path)) return false
  for (const char of path) {
    const code = char.codePointAt(0)
    if (code < 32 || code === 127) return false
  }
  return true
}

function pathError(pagePath) {
  const trimmed = pagePath.trim()
  if (!trimmed) return ''
  if (trimmed.length > ISSUE_PAGE_PATH_MAX_LENGTH) {
    return t('issues.form.err_path_max', { max: ISSUE_PAGE_PATH_MAX_LENGTH })
  }
  if (!isSafeIssuePath(trimmed)) return t('issues.form.err_path')
  return ''
}

export function validateIssueDraft({ category, subject, description, pagePath }) {
  const errors = { category: '', subject: '', description: '', pagePath: '' }
  if (!Object.values(ISSUE_CATEGORY).includes(category)) {
    errors.category = t('issues.form.err_category')
  }
  const trimmedSubject = String(subject ?? '').trim()
  if (!trimmedSubject) {
    errors.subject = t('issues.form.err_subject')
  } else if (trimmedSubject.length > ISSUE_SUBJECT_MAX_LENGTH) {
    errors.subject = t('issues.form.err_subject_max', { max: ISSUE_SUBJECT_MAX_LENGTH })
  }
  const trimmedDescription = String(description ?? '').trim()
  if (!trimmedDescription) {
    errors.description = t('issues.form.err_desc')
  } else if (trimmedDescription.length > ISSUE_DESCRIPTION_MAX_LENGTH) {
    errors.description = t('issues.form.err_desc_max')
  }
  errors.pagePath = pathError(String(pagePath ?? ''))
  return errors
}

export function validateIssueResolution(resolution, status) {
  if (!ISSUE_CLOSING_STATUSES.includes(status)) return ''
  const trimmed = String(resolution ?? '').trim()
  if (!trimmed) return t('issues.form.err_resolution')
  if (trimmed.length > ISSUE_RESOLUTION_MAX_LENGTH) {
    return t('issues.form.err_resolution_max')
  }
  return ''
}
