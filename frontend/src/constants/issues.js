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
  { value: 'technical', label: 'Technical' },
  { value: 'account', label: 'Account' },
  { value: 'marketplace', label: 'Marketplace' },
  { value: 'payment', label: 'Payment' },
  { value: 'other', label: 'Other' },
]

export const ISSUE_CATEGORY_LABELS = {
  technical: 'Technical',
  account: 'Account',
  marketplace: 'Marketplace',
  payment: 'Payment',
  other: 'Other',
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
  { value: '', label: 'All statuses' },
  { value: 'open', label: 'Open' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'closed', label: 'Closed' },
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
    return `Page must be ${ISSUE_PAGE_PATH_MAX_LENGTH} characters or fewer.`
  }
  if (!isSafeIssuePath(trimmed)) return 'Use a relative path like /dashboard/issues.'
  return ''
}

export function validateIssueDraft({ category, subject, description, pagePath }) {
  const errors = { category: '', subject: '', description: '', pagePath: '' }
  if (!Object.values(ISSUE_CATEGORY).includes(category)) {
    errors.category = 'Choose a category.'
  }
  const trimmedSubject = String(subject ?? '').trim()
  if (!trimmedSubject) {
    errors.subject = 'Enter a subject.'
  } else if (trimmedSubject.length > ISSUE_SUBJECT_MAX_LENGTH) {
    errors.subject = `Subject must be ${ISSUE_SUBJECT_MAX_LENGTH} characters or fewer.`
  }
  const trimmedDescription = String(description ?? '').trim()
  if (!trimmedDescription) {
    errors.description = 'Enter a description.'
  } else if (trimmedDescription.length > ISSUE_DESCRIPTION_MAX_LENGTH) {
    errors.description = 'Description must be 5,000 characters or fewer.'
  }
  errors.pagePath = pathError(String(pagePath ?? ''))
  return errors
}

export function validateIssueResolution(resolution, status) {
  if (!ISSUE_CLOSING_STATUSES.includes(status)) return ''
  const trimmed = String(resolution ?? '').trim()
  if (!trimmed) return 'Enter a resolution before resolving or closing.'
  if (trimmed.length > ISSUE_RESOLUTION_MAX_LENGTH) {
    return 'Resolution must be 2,000 characters or fewer.'
  }
  return ''
}
