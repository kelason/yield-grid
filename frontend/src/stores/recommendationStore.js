import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useApi } from '../composables/useApi'

export const useRecommendationStore = defineStore('recommendation', () => {
  const api = useApi()
  const recommendations = ref([])
  const meta = ref({ plot_name: '', city: '', state: '', country: '' })
  const isLoading = ref(false)
  const isAnalyzing = ref(false)
  const errorMessage = ref('')
  const taxonomy = ref(null)
  const taxonomyLoading = ref(false)
  const typeFilter = ref('')

  const filteredRecommendations = computed(() => {
    if (!typeFilter.value) return recommendations.value
    return recommendations.value.filter((rec) => rec.produce_type === typeFilter.value)
  })

  const availableTypes = computed(() => {
    const types = recommendations.value.map((rec) => rec.produce_type).filter(Boolean)
    return [...new Set(types)]
  })

  const typeLabel = (slug) => taxonomy.value?.types?.[slug]?.label || slug

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

  const analyzePlot = async (plotId, prefs = {}) => {
    isAnalyzing.value = true
    errorMessage.value = ''
    recommendations.value = [] // Immediately hide previous data when re-analyzing
    try {
      await api.post(`/plots/${plotId}/analyze`, prefs)
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

  const checkCompatibility = async (cropA, cropB) => {
    const response = await api.get('/crop-compatibility', {
      params: { crop_a: cropA, crop_b: cropB },
    })
    return response.data.data
  }

  const fetchTaxonomy = async () => {
    if (taxonomy.value || taxonomyLoading.value) return
    taxonomyLoading.value = true
    try {
      const response = await api.get('/crop-taxonomy')
      taxonomy.value = response.data.data || null
    } catch (error) {
      console.error('Failed to fetch crop taxonomy:', error)
    } finally {
      taxonomyLoading.value = false
    }
  }

  return {
    recommendations,
    meta,
    isLoading,
    isAnalyzing,
    errorMessage,
    taxonomy,
    taxonomyLoading,
    typeFilter,
    filteredRecommendations,
    availableTypes,
    typeLabel,
    fetchRecommendations,
    fetchTaxonomy,
    checkCompatibility,
    analyzePlot,
    updateStatus,
  }
})
