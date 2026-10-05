import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useInsurance } from '../composables/useInsurance'
import { HTTP_STATUS } from '../constants/http'
import { INSURANCE_PACK } from '../constants/insurance'
import { useNotificationStore } from './notificationStore'

const SECONDS_PER_MINUTE = 60
const SECONDS_PER_HOUR = 3600

const tooManyAttemptsMessage = (retryAfter) => {
  const seconds = Number(retryAfter)
  if (Number.isFinite(seconds) && seconds > 0) {
    const hours = Math.ceil(seconds / SECONDS_PER_HOUR)
    if (hours > 1) {
      return `Too many pack requests. Please try again in about ${hours} hours.`
    }
    const minutes = Math.ceil(seconds / SECONDS_PER_MINUTE)
    if (minutes > 1) {
      return `Too many pack requests. Please try again in ${minutes} minutes.`
    }
    return 'Too many pack requests. Please try again in a minute.'
  }
  return 'Too many pack requests. Please try again later.'
}

export const useInsuranceStore = defineStore('insurance', () => {
  const insurance = useInsurance()
  const notificationStore = useNotificationStore()

  const profile = ref(null)
  const enrollments = ref([])
  const claimsByEnrollment = ref({})
  const reminders = ref([])
  const offices = ref([])
  const isLoading = ref(false)
  const isSaving = ref(false)
  const isGeneratingPack = ref(false)
  const isDownloading = ref(false)
  const errorMessage = ref('')

  const fetchDashboard = async () => {
    isLoading.value = true
    errorMessage.value = ''
    try {
      const [fetchedProfile, fetchedEnrollments, fetchedReminders, fetchedOffices] =
        await Promise.all([
          insurance.fetchProfile(),
          insurance.fetchEnrollments(),
          insurance.fetchReminders(),
          insurance.fetchOffices(),
        ])
      profile.value = fetchedProfile
      enrollments.value = fetchedEnrollments
      reminders.value = fetchedReminders
      offices.value = fetchedOffices
    } catch {
      errorMessage.value = insurance.error.value || 'Failed to load crop insurance data.'
    } finally {
      isLoading.value = false
    }
  }

  const saveProfile = async (payload) => {
    isSaving.value = true
    errorMessage.value = ''
    try {
      profile.value = await insurance.updateProfile(payload)
      return profile.value
    } catch {
      errorMessage.value = insurance.error.value || 'Failed to save RSBSA details.'
      return null
    } finally {
      isSaving.value = false
    }
  }

  const createEnrollment = async (payload) => {
    isSaving.value = true
    errorMessage.value = ''
    try {
      const created = await insurance.createEnrollment(payload)
      enrollments.value = [created, ...enrollments.value]
      return created
    } catch {
      errorMessage.value = insurance.error.value || 'Failed to create the enrollment.'
      return null
    } finally {
      isSaving.value = false
    }
  }

  const advanceEnrollment = async (id, status) => {
    isSaving.value = true
    errorMessage.value = ''
    try {
      const updated = await insurance.advanceEnrollment(id, status)
      enrollments.value = enrollments.value.map((item) => (item.id === id ? updated : item))
      return updated
    } catch {
      errorMessage.value = insurance.error.value || 'Failed to update the enrollment.'
      return null
    } finally {
      isSaving.value = false
    }
  }

  const updatePolicyDetails = async (id, payload) => {
    isSaving.value = true
    errorMessage.value = ''
    try {
      const updated = await insurance.updatePolicyDetails(id, payload)
      enrollments.value = enrollments.value.map((item) => (item.id === id ? updated : item))
      return updated
    } catch {
      errorMessage.value = insurance.error.value || 'Failed to record the policy details.'
      return null
    } finally {
      isSaving.value = false
    }
  }

  const failPack = (message) => {
    errorMessage.value = message
    notificationStore.error(message)
  }

  const generatePackAndDownload = async (id) => {
    isGeneratingPack.value = true
    errorMessage.value = ''
    try {
      const current = enrollments.value.find((item) => item.id === id)
      if (current?.pack_status === 'ready') {
        await downloadPack(id)
        return true
      }
      await insurance.requestPack(id)
      const ready = await pollPackReady(id)
      if (!ready) {
        return false
      }
      await downloadPack(id)
      return true
    } catch (err) {
      if (err?.response?.status === HTTP_STATUS.TOO_MANY_REQUESTS) {
        failPack(tooManyAttemptsMessage(err.response.headers?.['retry-after']))
      } else {
        failPack(insurance.error.value || 'Failed to generate the enrollment pack.')
      }
      return false
    } finally {
      isGeneratingPack.value = false
    }
  }

  const pollPackReady = async (id) => {
    for (let attempt = 0; attempt < INSURANCE_PACK.POLL_MAX_ATTEMPTS; attempt++) {
      const enrollment = await insurance.fetchEnrollment(id)
      enrollments.value = enrollments.value.map((item) =>
        item.id === id ? { ...item, ...enrollment } : item,
      )
      if (enrollment.pack_status === 'ready') {
        return true
      }
      if (enrollment.pack_status === 'failed') {
        failPack('Pack generation failed. Please try again.')
        return false
      }
      await insurance.sleep(INSURANCE_PACK.POLL_INTERVAL_MS)
    }
    failPack('Pack generation is taking too long. Please try downloading again later.')
    return false
  }

  const downloadPack = async (id) => {
    isDownloading.value = true
    try {
      const blob = await insurance.downloadPack(id)
      insurance.saveBlob(blob, INSURANCE_PACK.FILENAME)
    } catch (error) {
      if (error?.response?.status === HTTP_STATUS.TOO_MANY_REQUESTS) {
        failPack(tooManyAttemptsMessage(error.response.headers?.['retry-after']))
      } else {
        failPack('Failed to download the pack. It may have expired.')
      }
      console.error('Failed to download enrollment pack:', error)
    } finally {
      isDownloading.value = false
    }
  }

  const fetchClaims = async (enrollmentId) => {
    try {
      const claims = await insurance.fetchClaims(enrollmentId)
      claimsByEnrollment.value = { ...claimsByEnrollment.value, [enrollmentId]: claims }
      return claims
    } catch (error) {
      errorMessage.value = insurance.error.value || 'Failed to load claims.'
      console.error('Failed to fetch claims:', error)
      return []
    }
  }

  const createClaim = async (enrollmentId, payload) => {
    isSaving.value = true
    errorMessage.value = ''
    try {
      const created = await insurance.createClaim(enrollmentId, payload)
      const existing = claimsByEnrollment.value[enrollmentId] || []
      claimsByEnrollment.value = {
        ...claimsByEnrollment.value,
        [enrollmentId]: [created, ...existing],
      }
      return created
    } catch {
      errorMessage.value = insurance.error.value || 'Failed to file the claim.'
      return null
    } finally {
      isSaving.value = false
    }
  }

  const advanceClaim = async (enrollmentId, claimId, payload) => {
    isSaving.value = true
    errorMessage.value = ''
    try {
      const updated = await insurance.advanceClaim(claimId, payload)
      const existing = claimsByEnrollment.value[enrollmentId] || []
      claimsByEnrollment.value = {
        ...claimsByEnrollment.value,
        [enrollmentId]: existing.map((item) => (item.id === claimId ? updated : item)),
      }
      return updated
    } catch {
      errorMessage.value = insurance.error.value || 'Failed to advance the claim.'
      return null
    } finally {
      isSaving.value = false
    }
  }

  const $reset = () => {
    profile.value = null
    enrollments.value = []
    claimsByEnrollment.value = {}
    reminders.value = []
    offices.value = []
    isLoading.value = false
    isSaving.value = false
    isGeneratingPack.value = false
    isDownloading.value = false
    errorMessage.value = ''
  }

  return {
    profile,
    enrollments,
    claimsByEnrollment,
    reminders,
    offices,
    isLoading,
    isSaving,
    isGeneratingPack,
    isDownloading,
    errorMessage,
    fetchDashboard,
    saveProfile,
    createEnrollment,
    advanceEnrollment,
    updatePolicyDetails,
    generatePackAndDownload,
    downloadPack,
    fetchClaims,
    createClaim,
    advanceClaim,
    $reset,
  }
})
