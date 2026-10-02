import { ref } from 'vue'
import { useApi } from '@/composables/useApi'
import { CREDIT_SCORE, CREDIT_REPORT_STATUS } from '@/constants/creditScoring'

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

export function useCreditScore() {
  const api = useApi()
  const loading = ref(false)
  const error = ref(null)

  async function fetchScore() {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(CREDIT_SCORE.SCORE_ENDPOINT)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load your trust score.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchHistory() {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(CREDIT_SCORE.HISTORY_ENDPOINT)
      return response.data.data || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load score history.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function generateReport() {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(CREDIT_SCORE.REPORT_ENDPOINT, {})
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to start report generation.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function downloadReport(token) {
    const response = await api.get(CREDIT_SCORE.REPORT_DOWNLOAD_PATH(token), {
      responseType: 'blob',
    })
    return response.data
  }

  function saveBlob(blob, filename) {
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  }

  async function pollUntilReportReady(
    refreshScore,
    {
      intervalMs = CREDIT_SCORE.REPORT_POLL_INTERVAL_MS,
      maxAttempts = CREDIT_SCORE.REPORT_POLL_MAX_ATTEMPTS,
      isCancelled = () => false,
    } = {},
  ) {
    for (let attempt = 0; attempt < maxAttempts; attempt++) {
      if (isCancelled()) {
        return CREDIT_REPORT_STATUS.GENERATING
      }
      await sleep(intervalMs)
      if (isCancelled()) {
        return CREDIT_REPORT_STATUS.GENERATING
      }
      const score = await refreshScore()
      const status = score?.report_status
      if (status === CREDIT_REPORT_STATUS.READY || status === CREDIT_REPORT_STATUS.FAILED) {
        return status
      }
    }
    return CREDIT_REPORT_STATUS.GENERATING
  }

  return {
    loading,
    error,
    fetchScore,
    fetchHistory,
    generateReport,
    downloadReport,
    saveBlob,
    pollUntilReportReady,
  }
}
