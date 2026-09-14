import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useApi } from '../composables/useApi'

export const useFarmingStore = defineStore('farming', () => {
  const api = useApi()

  const farms = ref([])
  const loading = ref(false)
  const activeFarm = ref(null)
  const plots = ref([])

  async function fetchFarms() {
    loading.value = true
    try {
      const response = await api.get('/farms')
      farms.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function createFarm(payload) {
    const response = await api.post('/farms', payload)
    farms.value.unshift(response.data.data)
    return response.data.data
  }

  async function fetchPlots(farmId) {
    loading.value = true
    try {
      const response = await api.get(`/farms/${farmId}/plots`)
      // API returns a GeoJSON FeatureCollection
      plots.value = response.data
    } finally {
      loading.value = false
    }
  }

  async function createPlot(farmId, payload) {
    const response = await api.post(`/farms/${farmId}/plots`, payload)
    // Refresh plots to get the new ST_Area calculation from backend
    await fetchPlots(farmId)
    return response.data.data
  }

  function setActiveFarm(farm) {
    activeFarm.value = farm
  }

  return {
    farms,
    loading,
    activeFarm,
    plots,
    fetchFarms,
    createFarm,
    fetchPlots,
    createPlot,
    setActiveFarm
  }
})
