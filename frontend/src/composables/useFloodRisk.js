import { ref } from 'vue'
import { useApi } from '@/composables/useApi'
import { DESIGN_FLOOD_COLORS } from '@/constants/designTokens'

const FALLBACK_ERROR = 'Flood risk is unavailable right now.'
const UNKNOWN_LEVEL = 'unknown'

export function floodZoneStyle(hazardClass) {
  const entry = DESIGN_FLOOD_COLORS[hazardClass] || DESIGN_FLOOD_COLORS.unknown

  return {
    color: entry.color,
    weight: 1,
    fillColor: entry.fill,
    fillOpacity: 0.35,
    interactive: false,
  }
}

function readErrorMessage(error) {
  return error?.response?.data?.message || FALLBACK_ERROR
}

export function useFloodRisk() {
  const api = useApi()
  const level = ref(UNKNOWN_LEVEL)
  const label = ref('')
  const advice = ref([])
  const withinCoverage = ref(true)
  const assessedAt = ref(null)
  const isLoading = ref(false)
  const error = ref('')

  function applyPayload(payload) {
    level.value = payload?.level || UNKNOWN_LEVEL
    label.value = payload?.label || ''
    advice.value = payload?.advice || []
    withinCoverage.value = payload?.within_coverage ?? true
    assessedAt.value = payload?.assessed_at || null
  }

  function reset() {
    level.value = UNKNOWN_LEVEL
    label.value = ''
    advice.value = []
    withinCoverage.value = true
    assessedAt.value = null
    isLoading.value = false
    error.value = ''
  }

  async function runAssessment(request) {
    isLoading.value = true
    error.value = ''
    try {
      const response = await request()
      applyPayload(response?.data?.data)
    } catch (requestError) {
      error.value = readErrorMessage(requestError)
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPreview(coordinates) {
    await runAssessment(() => api.post('/plots/flood-risk/preview', { coordinates }))
  }

  async function fetchStored(plotId) {
    await runAssessment(() => api.get(`/plots/${plotId}/flood-risk`))
  }

  async function refreshRisk(plotId) {
    await runAssessment(() => api.post(`/plots/${plotId}/flood-risk/refresh`))
  }

  async function fetchZones(bbox) {
    isLoading.value = true
    error.value = ''
    try {
      const response = await api.get(`/flood-hazard-zones?bbox=${bbox.join(',')}`)
      return response?.data || null
    } catch (requestError) {
      error.value = readErrorMessage(requestError)
      return null
    } finally {
      isLoading.value = false
    }
  }

  return {
    level,
    label,
    advice,
    withinCoverage,
    assessedAt,
    isLoading,
    error,
    fetchPreview,
    fetchStored,
    refreshRisk,
    fetchZones,
    reset,
  }
}
