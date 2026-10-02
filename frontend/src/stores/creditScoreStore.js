import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useCreditScore } from '../composables/useCreditScore'
import { CREDIT_REPORT_STATUS, CREDIT_TIER_STYLES } from '../constants/creditScoring'

export const useCreditScoreStore = defineStore('creditScore', () => {
  const creditScore = useCreditScore()

  const score = ref(null)
  const history = ref([])
  const isLoading = ref(false)
  const isGeneratingReport = ref(false)
  const isDownloading = ref(false)
  const errorMessage = ref('')
  const pollingCancelled = ref(false)

  const tierStyle = computed(() => CREDIT_TIER_STYLES[score.value?.tier] || CREDIT_TIER_STYLES.new)
  const isReportReady = computed(() => score.value?.report_status === CREDIT_REPORT_STATUS.READY)
  const reportToken = computed(() => score.value?.report_token || null)

  const fetchScore = async () => {
    isLoading.value = true
    errorMessage.value = ''
    try {
      score.value = await creditScore.fetchScore()
      return score.value
    } catch {
      errorMessage.value = creditScore.error.value || 'Failed to load your trust score.'
      return null
    } finally {
      isLoading.value = false
    }
  }

  const fetchHistory = async () => {
    try {
      history.value = await creditScore.fetchHistory()
    } catch (error) {
      console.error('Failed to fetch score history:', error)
    }
  }

  const generateReportAndPoll = async (pollOptions = {}) => {
    isGeneratingReport.value = true
    errorMessage.value = ''
    pollingCancelled.value = false
    try {
      await creditScore.generateReport()
      const status = await creditScore.pollUntilReportReady(fetchScore, {
        isCancelled: () => pollingCancelled.value,
        ...pollOptions,
      })
      if (status === CREDIT_REPORT_STATUS.FAILED) {
        errorMessage.value = 'Report generation failed. Please try again.'
      }
      return status
    } catch {
      errorMessage.value = creditScore.error.value || 'Failed to generate the report.'
      return CREDIT_REPORT_STATUS.FAILED
    } finally {
      isGeneratingReport.value = false
    }
  }

  const cancelPolling = () => {
    pollingCancelled.value = true
  }

  const downloadReport = async () => {
    if (!reportToken.value) {
      return
    }
    isDownloading.value = true
    try {
      const blob = await creditScore.downloadReport(reportToken.value)
      creditScore.saveBlob(blob, 'YieldGrid-Trust-Score-Report.pdf')
    } catch (error) {
      errorMessage.value = 'Failed to download the report. It may have expired.'
      console.error('Failed to download report:', error)
    } finally {
      isDownloading.value = false
    }
  }

  const $reset = () => {
    score.value = null
    history.value = []
    isLoading.value = false
    isGeneratingReport.value = false
    isDownloading.value = false
    errorMessage.value = ''
    pollingCancelled.value = false
  }

  return {
    score,
    history,
    isLoading,
    isGeneratingReport,
    isDownloading,
    errorMessage,
    tierStyle,
    isReportReady,
    reportToken,
    fetchScore,
    fetchHistory,
    generateReportAndPoll,
    cancelPolling,
    downloadReport,
    $reset,
  }
})
