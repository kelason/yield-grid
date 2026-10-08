import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useFloodRisk, floodZoneStyle } from '../useFloodRisk'
import { DESIGN_FLOOD_COLORS, DESIGN_STATUS_COLORS } from '@/constants/designTokens'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('useFloodRisk', () => {
  const riskPayload = {
    level: 'high',
    label: 'High',
    legend_token: 'high',
    advice: ['Build drainage canals before planting season.'],
    within_coverage: true,
    assessed_at: '2026-10-08T00:00:00Z',
  }

  let apiGet
  let apiPost

  beforeEach(() => {
    apiGet = vi.fn()
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })
  })

  it('previews risk for drawn coordinates', async () => {
    apiPost.mockResolvedValue({ data: { data: riskPayload } })
    const { level, label, advice, fetchPreview } = useFloodRisk()

    await fetchPreview([
      [4, 4],
      [4, 8],
      [8, 8],
      [8, 4],
    ])

    expect(apiPost).toHaveBeenCalledWith('/plots/flood-risk/preview', {
      coordinates: [
        [4, 4],
        [4, 8],
        [8, 8],
        [8, 4],
      ],
    })
    expect(level.value).toBe('high')
    expect(label.value).toBe('High')
    expect(advice.value).toEqual(['Build drainage canals before planting season.'])
  })

  it('keeps prior state and sets fallback error on failure', async () => {
    apiPost.mockRejectedValueOnce(new Error('Network down'))
    const { level, error, fetchPreview } = useFloodRisk()

    await fetchPreview([
      [0, 0],
      [0, 2],
      [2, 0],
    ])

    expect(level.value).toBe('unknown')
    expect(error.value).toBe('Flood risk is unavailable right now.')
  })

  it('fetches stored risk and refreshes it', async () => {
    apiGet.mockResolvedValue({ data: { data: riskPayload } })
    apiPost.mockResolvedValue({
      data: { data: { ...riskPayload, level: 'low', label: 'Low' } },
    })
    const { level, fetchStored, refreshRisk } = useFloodRisk()

    await fetchStored(7)
    expect(apiGet).toHaveBeenCalledWith('/plots/7/flood-risk')
    expect(level.value).toBe('high')

    await refreshRisk(7)
    expect(apiPost).toHaveBeenCalledWith('/plots/7/flood-risk/refresh')
    expect(level.value).toBe('low')
  })

  it('fetches zones for a bbox', async () => {
    const collection = { type: 'FeatureCollection', features: [] }
    apiGet.mockResolvedValue({ data: collection })
    const { fetchZones } = useFloodRisk()

    const result = await fetchZones([120, 14, 121, 15])

    expect(apiGet).toHaveBeenCalledWith('/flood-hazard-zones?bbox=120,14,121,15')
    expect(result).toEqual(collection)
  })

  it('returns null zones on failure', async () => {
    apiGet.mockRejectedValueOnce(new Error('Network down'))
    const { error, fetchZones } = useFloodRisk()

    const result = await fetchZones([120, 14, 121, 15])

    expect(result).toBeNull()
    expect(error.value).toBe('Flood risk is unavailable right now.')
  })

  it('resets to unknown defaults', async () => {
    apiPost.mockResolvedValue({ data: { data: riskPayload } })
    const { level, error, fetchPreview, reset } = useFloodRisk()

    await fetchPreview([
      [4, 4],
      [4, 8],
      [8, 8],
      [8, 4],
    ])
    reset()

    expect(level.value).toBe('unknown')
    expect(error.value).toBe('')
  })
})

describe('floodZoneStyle', () => {
  it('styles high zones with the error fill', () => {
    expect(floodZoneStyle('high')).toEqual({
      color: DESIGN_STATUS_COLORS.error,
      weight: 1,
      fillColor: DESIGN_STATUS_COLORS.errorFill,
      fillOpacity: 0.35,
      interactive: false,
    })
  })

  it('falls back to unknown for unexpected classes', () => {
    expect(floodZoneStyle('bogus')).toEqual({
      color: DESIGN_FLOOD_COLORS.unknown.color,
      weight: 1,
      fillColor: DESIGN_FLOOD_COLORS.unknown.fill,
      fillOpacity: 0.35,
      interactive: false,
    })
  })
})
