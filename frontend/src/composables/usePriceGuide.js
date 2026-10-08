import { ref } from 'vue'
import { useApi } from '@/composables/useApi'
import { PRICE_GUIDE } from '@/constants/prices'

const guideCache = new Map()
const inflight = new Map()

function cacheKey(crop, region) {
  return `${String(crop).toLowerCase().trim()}|${region ?? ''}`
}

function compareCacheKey(crop, region, sources) {
  return `compare|${cacheKey(crop, region)}|${[...sources].sort().join(',')}`
}

function readCache(key) {
  const entry = guideCache.get(key)

  if (!entry) {
    return undefined
  }

  if (Date.now() - entry.cachedAt > PRICE_GUIDE.CACHE_TTL_MS) {
    guideCache.delete(key)
    return undefined
  }

  return entry.guide
}

function writeCache(key, guide) {
  guideCache.set(key, { guide, cachedAt: Date.now() })
}

export function usePriceGuide() {
  const api = useApi()
  const loading = ref(false)
  const error = ref(null)

  async function fetchGuide(crop, region = null, { force = false } = {}) {
    const key = cacheKey(crop, region)

    if (!force) {
      const cached = readCache(key)
      if (cached !== undefined) {
        return cached
      }
    }

    if (inflight.has(key)) {
      return inflight.get(key)
    }

    loading.value = true
    const params = { crop }
    if (region) {
      params.region = region
    }

    const request = Promise.resolve()
      .then(() => api.get(PRICE_GUIDE.GUIDE_ENDPOINT, { params }))
      .then((response) => {
        const guide = response.data
        writeCache(key, guide)
        return guide
      })
      .catch((err) => {
        error.value = err
        return { available: false, reason: 'request_failed' }
      })
      .finally(() => {
        inflight.delete(key)
        loading.value = inflight.size > 0
      })

    inflight.set(key, request)
    return request
  }

  async function fetchComparison(crop, region = null, sources = [], { force = false } = {}) {
    const key = compareCacheKey(crop, region, sources)

    if (!force) {
      const cached = readCache(key)
      if (cached !== undefined) {
        return cached
      }
    }

    if (inflight.has(key)) {
      return inflight.get(key)
    }

    loading.value = true
    const params = { crop, sources: [...sources] }
    if (region) {
      params.region = region
    }

    const request = Promise.resolve()
      .then(() => api.get(PRICE_GUIDE.COMPARE_ENDPOINT, { params }))
      .then((response) => {
        const comparison = response.data
        writeCache(key, comparison)
        return comparison
      })
      .catch((err) => {
        error.value = err
        return { available: false, reason: 'request_failed', sections: {} }
      })
      .finally(() => {
        inflight.delete(key)
        loading.value = inflight.size > 0
      })

    inflight.set(key, request)
    return request
  }

  async function prefetchCrops(crops, region = null) {
    const missing = [...new Set((crops ?? []).filter(Boolean))].filter(
      (crop) => readCache(cacheKey(crop, region)) === undefined,
    )

    if (missing.length === 0) {
      return
    }

    const params = { crops: missing }
    if (region) {
      params.region = region
    }

    try {
      const response = await api.get(PRICE_GUIDE.BATCH_ENDPOINT, { params })
      const guides = response.data?.guides ?? {}

      for (const [crop, guide] of Object.entries(guides)) {
        writeCache(cacheKey(crop, region), guide)
      }
    } catch (err) {
      error.value = err
    }
  }

  function clearCache() {
    guideCache.clear()
    inflight.clear()
  }

  return { loading, error, fetchGuide, fetchComparison, prefetchCrops, clearCache }
}
