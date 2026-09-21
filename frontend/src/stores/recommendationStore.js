import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useApi } from '../composables/useApi'

export const useRecommendationStore = defineStore('recommendation', () => {
  const api = useApi()
  const recommendations = ref([])
  const meta = ref({ plot_name: '', city: '', state: '', country: '' })
  const isLoading = ref(false)
  const isAnalyzing = ref(false)
  const errorMessage = ref('')

  const fetchRecommendations = async (plotId) => {
    isLoading.value = true
    try {
      const response = await api.get(`/plots/${plotId}/recommendations`)
      recommendations.value = response.data.data || []
      if (response.data.meta) {
        meta.value = response.data.meta
      }
    } catch (error) {
      console.error('Failed to fetch recommendations:', error)
    } finally {
      isLoading.value = false
    }
  }

  const analyzePlot = async (plotId) => {
    isAnalyzing.value = true
    errorMessage.value = ''
    recommendations.value = [] // Immediately hide previous data when re-analyzing
    try {
      await api.post(`/plots/${plotId}/analyze`, {})
      // On success, isAnalyzing remains true until the WebSocket event is received
    } catch (error) {
      errorMessage.value =
        error.response?.data?.message || 'Rate limit reached or analysis in progress. Please wait.'
      console.error('Failed to analyze plot:', error)
      // Fetch latest existing recommendations if new analysis couldn't be started
      await fetchRecommendations(plotId)
      isAnalyzing.value = false
    }
  }

  const updateStatus = async (recommendationId, status) => {
    try {
      const response = await api.patch(`/recommendations/${recommendationId}/status`, { status })
      if (response.data) {
        const index = recommendations.value.findIndex((r) => r.id === recommendationId)
        if (index !== -1) {
          recommendations.value[index] = response.data
        }
      }
    } catch (error) {
      console.error('Failed to update status:', error)
    }
  }

  return {
    recommendations,
    meta,
    isLoading,
    isAnalyzing,
    errorMessage,
    fetchRecommendations,
    analyzePlot,
    updateStatus,
  }
})
