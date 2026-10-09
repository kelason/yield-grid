import { ref } from 'vue'
import i18n from '@/i18n'
import { useForumStore } from '@/stores/forumStore'

const t = (...args) => i18n.global.t(...args)
import {
  DESCRIPTION_COUNT_LABEL,
  REPORT_DESCRIPTION_MAX_LENGTH,
  REPORT_REASON,
} from '@/constants/reporting'

export function reportTargetKey(type, id) {
  return `${type}:${String(id)}`
}

function errorMessage(error, t) {
  return error?.response?.data?.message || error?.message || t('market.report.err_submit')
}

export function useContentReport() {
  const forumStore = useForumStore()

  const target = ref(null)
  const reason = ref('')
  const description = ref('')
  const busy = ref(false)
  const error = ref('')
  const fieldError = ref('')
  let inflight = null

  function currentKey() {
    return target.value
      ? reportTargetKey(target.value.reportable_type, target.value.reportable_id)
      : ''
  }

  function resetDraft() {
    reason.value = ''
    description.value = ''
    error.value = ''
    fieldError.value = ''
  }

  function openReport(candidate) {
    if (!candidate || !candidate.reportable_type || candidate.reportable_id == null) return
    const nextKey = reportTargetKey(candidate.reportable_type, candidate.reportable_id)
    if (nextKey !== currentKey()) resetDraft()
    target.value = {
      reportable_type: candidate.reportable_type,
      reportable_id: candidate.reportable_id,
      title: candidate.title ?? '',
    }
  }

  function closeReport() {
    if (busy.value) return
    target.value = null
    resetDraft()
  }

  function validate() {
    fieldError.value = ''
    if (!reason.value) {
      fieldError.value = t('market.report.err_reason')
      return false
    }
    if (description.value.length > REPORT_DESCRIPTION_MAX_LENGTH) {
      fieldError.value = t('market.report.err_desc_max', { max: DESCRIPTION_COUNT_LABEL })
      return false
    }
    if (reason.value === REPORT_REASON.OTHER && !description.value.trim()) {
      fieldError.value = t('market.report.err_desc_other')
      return false
    }
    return true
  }

  function payloadDescription() {
    const trimmed = description.value.trim()
    return trimmed === '' ? null : trimmed
  }

  async function runSubmit(key) {
    busy.value = true
    error.value = ''
    try {
      const receipt = await forumStore.reportContent(
        target.value.reportable_type,
        target.value.reportable_id,
        reason.value,
        payloadDescription(),
      )
      if (key === currentKey()) error.value = ''
      return receipt
    } catch (err) {
      const message = errorMessage(err, t)
      if (key === currentKey()) error.value = message
      throw new Error(message, { cause: err })
    } finally {
      busy.value = false
      inflight = null
    }
  }

  function submit() {
    if (inflight) return inflight
    if (!target.value) return Promise.reject(new Error(t('market.report.err_no_target')))
    if (!validate()) return Promise.reject(new Error(fieldError.value))
    inflight = runSubmit(currentKey())
    return inflight
  }

  return {
    target,
    reason,
    description,
    busy,
    error,
    fieldError,
    openReport,
    closeReport,
    validate,
    submit,
  }
}
