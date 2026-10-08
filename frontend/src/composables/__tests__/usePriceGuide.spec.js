import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { usePriceGuide } from '../usePriceGuide'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('usePriceGuide', () => {
  const riceGuide = {
    available: true,
    crop_slug: 'rice',
    crop_display_name: 'Rice',
    match_type: 'exact',
    tiers: {
      retail: { price_per_kg: 50, source: 'da_bantay_presyo' },
      farmgate: { price_per_kg: 24.5, source: 'da_bantay_presyo' },
    },
  }

  let apiGet

  beforeEach(() => {
    apiGet = vi.fn()
    useApi.mockReturnValue({ get: apiGet })
    usePriceGuide().clearCache()
  })

  it('fetches a single crop guide from the API', async () => {
    apiGet.mockResolvedValue({ data: riceGuide })

    const { fetchGuide } = usePriceGuide()
    const guide = await fetchGuide('rice')

    expect(apiGet).toHaveBeenCalledWith('/market/prices/guide', {
      params: { crop: 'rice' },
    })
    expect(guide).toEqual(riceGuide)
  })

  it('serves repeat lookups from cache without refetching', async () => {
    apiGet.mockResolvedValue({ data: riceGuide })

    const { fetchGuide } = usePriceGuide()
    await fetchGuide('Rice')
    await fetchGuide('rice')

    expect(apiGet).toHaveBeenCalledTimes(1)
  })

  it('prefetches many crops in one batch call', async () => {
    apiGet.mockResolvedValue({ data: { guides: { rice: riceGuide } } })

    const { prefetchCrops, fetchGuide } = usePriceGuide()
    await prefetchCrops(['rice', 'tomato'])
    const guide = await fetchGuide('rice')

    expect(apiGet).toHaveBeenCalledTimes(1)
    expect(apiGet).toHaveBeenCalledWith(
      '/market/prices/guide/batch',
      expect.objectContaining({ params: expect.objectContaining({ crops: ['rice', 'tomato'] }) }),
    )
    expect(guide).toEqual(riceGuide)
  })

  it('resolves quietly when a prefetch request fails', async () => {
    apiGet.mockRejectedValue(new Error('network down'))

    const { prefetchCrops, error } = usePriceGuide()
    await expect(prefetchCrops(['rice'])).resolves.toBeUndefined()

    expect(error.value?.message).toBe('network down')
    expect(apiGet).toHaveBeenCalledTimes(1)
  })

  it('caches misses briefly, then refetches after the TTL', async () => {
    vi.useFakeTimers()
    try {
      apiGet.mockResolvedValue({
        data: { available: false, reason: 'unknown_crop', pending: true },
      })

      const { fetchGuide } = usePriceGuide()
      await fetchGuide('rice')
      await fetchGuide('rice')
      expect(apiGet).toHaveBeenCalledTimes(1)

      vi.advanceTimersByTime(61000)
      await fetchGuide('rice')
      expect(apiGet).toHaveBeenCalledTimes(2)
    } finally {
      vi.useRealTimers()
    }
  })

  it('force bypasses the cache so polling sees fresh data', async () => {
    apiGet
      .mockResolvedValueOnce({ data: { available: false, reason: 'unknown_crop', pending: true } })
      .mockResolvedValueOnce({ data: riceGuide })

    const { fetchGuide } = usePriceGuide()
    const first = await fetchGuide('rice')
    const second = await fetchGuide('rice', null, { force: true })

    expect(apiGet).toHaveBeenCalledTimes(2)
    expect(first.available).toBe(false)
    expect(second).toEqual(riceGuide)
  })

  it('returns an unavailable guide when the request fails', async () => {
    apiGet.mockRejectedValue(new Error('network down'))

    const { fetchGuide } = usePriceGuide()
    const guide = await fetchGuide('rice')

    expect(guide.available).toBe(false)
    expect(guide.reason).toBe('request_failed')
  })

  it('fetches one comparison source at a time', async () => {
    const comparison = { available: true, sections: { da: { available: true } } }
    apiGet.mockResolvedValue({ data: comparison })

    const { fetchComparison } = usePriceGuide()
    const result = await fetchComparison('rice', null, ['da'])

    expect(apiGet).toHaveBeenCalledWith('/market/prices/guide/compare', {
      params: { crop: 'rice', sources: ['da'] },
    })
    expect(result).toEqual(comparison)
  })

  it('caches each comparison source separately', async () => {
    apiGet.mockResolvedValue({ data: { available: true, sections: {} } })

    const { fetchComparison } = usePriceGuide()
    await fetchComparison('rice', null, ['yieldgrid'])
    await fetchComparison('rice', null, ['yieldgrid'])
    await fetchComparison('rice', null, ['da'])

    expect(apiGet).toHaveBeenCalledTimes(2)
  })

  it('force bypasses the comparison cache when polling', async () => {
    apiGet
      .mockResolvedValueOnce({ data: { available: false, sections: { ai: { pending: true } } } })
      .mockResolvedValueOnce({ data: { available: true, sections: {} } })

    const { fetchComparison } = usePriceGuide()
    await fetchComparison('rice', null, ['ai'])
    await fetchComparison('rice', null, ['ai'], { force: true })

    expect(apiGet).toHaveBeenCalledTimes(2)
  })

  it('returns empty sections when a comparison request fails', async () => {
    apiGet.mockRejectedValue(new Error('network down'))

    const { fetchComparison } = usePriceGuide()
    const result = await fetchComparison('rice', null, ['ai'])

    expect(result.available).toBe(false)
    expect(result.reason).toBe('request_failed')
    expect(result.sections).toEqual({})
  })
})
