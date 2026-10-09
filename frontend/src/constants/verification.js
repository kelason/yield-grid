export const VERIFICATION_STATUS = {
  PENDING: 'pending',
  VERIFIED: 'verified',
  REJECTED: 'rejected',
}

export const ADMIN_VERIFICATIONS_PATH = '/admin/verifications'

export const VERIFICATION_NOTE_MAX_LENGTH = 1000

export const VERIFICATION_METHOD_OPTIONS = [
  { value: 'field_visit', label: 'Field visit' },
  { value: 'phone_check', label: 'Phone check' },
  { value: 'document_check', label: 'Document check' },
  { value: 'other', label: 'Other' },
]

export const VERIFICATION_STATUS_OPTIONS = [
  { value: 'pending', label: 'Pending' },
  { value: 'verified', label: 'Verified' },
  { value: 'rejected', label: 'Rejected' },
]
