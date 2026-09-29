import { ref } from 'vue'
import { useApi } from './useApi'
import { GEO_CONSTANTS } from '../constants/geo'

export function haversineKm(fromLat, fromLng, toLat, toLng) {
  const toRad = (deg) => (deg * Math.PI) / 180
  const latDelta = toRad(toLat - fromLat)
  const lngDelta = toRad(toLng - fromLng)
  const a =
    Math.sin(latDelta / 2) ** 2 +
    Math.cos(toRad(fromLat)) * Math.cos(toRad(toLat)) * Math.sin(lngDelta / 2) ** 2
  return 2 * GEO_CONSTANTS.EARTH_RADIUS_KM * Math.asin(Math.min(1, Math.sqrt(a)))
}

export function useGeo() {
  const api = useApi()
  const loading = ref(false)

  async function fetchRegions() {
    const { data } = await api.get('/geo/regions')
    return data.data
  }

  async function fetchProvinces(regionCode = null) {
    const params = regionCode ? `?region_code=${encodeURIComponent(regionCode)}` : ''
    const { data } = await api.get(`/geo/provinces${params}`)
    return data.data
  }

  async function fetchCities({ provinceCode = null, regionCode = null } = {}) {
    const query = new URLSearchParams()
    if (provinceCode) query.append('province_code', provinceCode)
    if (regionCode) query.append('region_code', regionCode)
    const suffix = query.toString() ? `?${query.toString()}` : ''
    const { data } = await api.get(`/geo/cities-municipalities${suffix}`)
    return data.data
  }

  async function fetchBarangays(cityCode) {
    const { data } = await api.get(
      `/geo/barangays?city_municipality_code=${encodeURIComponent(cityCode)}`,
    )
    return data.data
  }

  async function fetchCenter({ barangay, cityMunicipality, province = null }) {
    loading.value = true
    try {
      const query = new URLSearchParams()
      query.append('barangay', barangay)
      query.append('city_municipality', cityMunicipality)
      if (province) query.append('province', province)
      const { data } = await api.get(`/geo/center?${query.toString()}`)
      return data.data
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    fetchRegions,
    fetchProvinces,
    fetchCities,
    fetchBarangays,
    fetchCenter,
  }
}
