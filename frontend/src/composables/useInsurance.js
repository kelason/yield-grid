import { ref } from 'vue'
import { useApi } from '@/composables/useApi'
import { INSURANCE_ENDPOINTS } from '@/constants/insurance'

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

export function useInsurance() {
  const api = useApi()
  const loading = ref(false)
  const error = ref(null)

  function failure(err, fallback) {
    error.value = err.response?.data?.message || fallback
    throw err
  }

  async function fetchProfile() {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(INSURANCE_ENDPOINTS.PROFILE)
      return response.data.data
    } catch (err) {
      failure(err, 'Failed to load your insurance profile.')
    } finally {
      loading.value = false
    }
  }

  async function updateProfile(payload) {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(INSURANCE_ENDPOINTS.PROFILE, payload)
      return response.data.data
    } catch (err) {
      failure(err, 'Failed to save your RSBSA details.')
    } finally {
      loading.value = false
    }
  }

  async function fetchEnrollments() {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(INSURANCE_ENDPOINTS.ENROLLMENTS)
      return response.data.data || []
    } catch (err) {
      failure(err, 'Failed to load your enrollments.')
    } finally {
      loading.value = false
    }
  }

  async function fetchEnrollment(id) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`${INSURANCE_ENDPOINTS.ENROLLMENTS}/${id}`)
      return response.data.data
    } catch (err) {
      failure(err, 'Failed to load the enrollment.')
    } finally {
      loading.value = false
    }
  }

  async function createEnrollment(payload) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(INSURANCE_ENDPOINTS.ENROLLMENTS, payload)
      return response.data.data
    } catch (err) {
      failure(err, 'Failed to create the enrollment.')
    } finally {
      loading.value = false
    }
  }

  async function advanceEnrollment(id, status) {
    loading.value = true
    error.value = null
    try {
      const response = await api.patch(INSURANCE_ENDPOINTS.ENROLLMENT_STATUS(id), { status })
      return response.data.data
    } catch (err) {
      failure(err, 'Failed to update the enrollment status.')
    } finally {
      loading.value = false
    }
  }

  async function updatePolicyDetails(id, payload) {
    loading.value = true
    error.value = null
    try {
      const response = await api.patch(INSURANCE_ENDPOINTS.ENROLLMENT_POLICY_DETAILS(id), payload)
      return response.data.data
    } catch (err) {
      failure(err, 'Failed to record the policy details.')
    } finally {
      loading.value = false
    }
  }

  async function requestPack(id) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(INSURANCE_ENDPOINTS.ENROLLMENT_PACK(id), {})
      return response.data
    } catch (err) {
      failure(err, 'Failed to start pack generation.')
    } finally {
      loading.value = false
    }
  }

  async function downloadPack(id) {
    const response = await api.get(INSURANCE_ENDPOINTS.ENROLLMENT_PACK_DOWNLOAD(id), {
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

  async function fetchClaims(enrollmentId) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(INSURANCE_ENDPOINTS.ENROLLMENT_CLAIMS(enrollmentId))
      return response.data.data || []
    } catch (err) {
      failure(err, 'Failed to load claims.')
    } finally {
      loading.value = false
    }
  }

  async function createClaim(enrollmentId, payload) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(INSURANCE_ENDPOINTS.ENROLLMENT_CLAIMS(enrollmentId), payload)
      return response.data.data
    } catch (err) {
      failure(err, 'Failed to file the claim.')
    } finally {
      loading.value = false
    }
  }

  async function advanceClaim(id, payload) {
    loading.value = true
    error.value = null
    try {
      const response = await api.patch(INSURANCE_ENDPOINTS.CLAIM_ADVANCE(id), payload)
      return response.data.data
    } catch (err) {
      failure(err, 'Failed to advance the claim.')
    } finally {
      loading.value = false
    }
  }

  async function fetchReminders() {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(INSURANCE_ENDPOINTS.REMINDERS)
      return response.data.data || []
    } catch (err) {
      failure(err, 'Failed to load reminders.')
    } finally {
      loading.value = false
    }
  }

  async function fetchOffices() {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(INSURANCE_ENDPOINTS.OFFICES)
      return response.data.data || []
    } catch (err) {
      failure(err, 'Failed to load the office directory.')
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    fetchProfile,
    updateProfile,
    fetchEnrollments,
    fetchEnrollment,
    createEnrollment,
    advanceEnrollment,
    updatePolicyDetails,
    requestPack,
    downloadPack,
    saveBlob,
    fetchClaims,
    createClaim,
    advanceClaim,
    fetchReminders,
    fetchOffices,
    sleep,
  }
}
