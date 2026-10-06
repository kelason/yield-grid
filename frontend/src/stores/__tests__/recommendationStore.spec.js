import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useRecommendationStore } from '../recommendationStore'
import { useApi } from '@/composables/useApi'

vi.mock('@/composables/useApi', () => {
  const getMock = vi.fn()
  const postMock = vi.fn()
  const patchMock = vi.fn()
  return {
    useApi: () => ({
      get: getMock,
      post: postMock,
      patch: patchMock,
      defaults: { baseURL: 'http://localhost:3000/api/v1' },
    }),
  }
})

describe('Recommendation Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('posts an empty body when analyzing without preferences', async () => {
    const store = useRecommendationStore()
    const api = useApi()
    api.post.mockResolvedValueOnce({ data: {} })

    await store.analyzePlot(4)

    expect(api.post).toHaveBeenCalledWith('/plots/4/analyze', {})
    expect(store.isAnalyzing).toBe(true)
  })

  it('posts preferences when analyzing with them', async () => {
    const store = useRecommendationStore()
    const api = useApi()
    api.post.mockResolvedValueOnce({ data: {} })
    const prefs = {
      produce_types: ['fruit'],
      subtypes: ['citrus'],
      irrigation: 'limited',
      goal: 'quick_cash',
    }

    await store.analyzePlot(4, prefs)

    expect(api.post).toHaveBeenCalledWith('/plots/4/analyze', prefs)
  })

  it('filters recommendations by produce type', () => {
    const store = useRecommendationStore()
    store.recommendations = [
      { id: 1, produce_type: 'fruit' },
      { id: 2, produce_type: 'vegetable' },
      { id: 3, produce_type: null },
    ]

    expect(store.filteredRecommendations).toHaveLength(3)

    store.typeFilter = 'fruit'

    expect(store.filteredRecommendations.map((rec) => rec.id)).toEqual([1])
  })

  it('lists the produce types present in the results', () => {
    const store = useRecommendationStore()
    store.recommendations = [
      { id: 1, produce_type: 'fruit' },
      { id: 2, produce_type: 'vegetable' },
      { id: 3, produce_type: null },
    ]

    expect(store.availableTypes).toEqual(['fruit', 'vegetable'])
  })

  it('labels produce types from the taxonomy with slug fallback', () => {
    const store = useRecommendationStore()
    store.taxonomy = { types: { vegetable: { label: 'Vegetables' } } }

    expect(store.typeLabel('vegetable')).toBe('Vegetables')
    expect(store.typeLabel('field_crop')).toBe('field_crop')
  })

  it('checks crop compatibility via the API', async () => {
    const store = useRecommendationStore()
    const api = useApi()
    const payload = { verdict: 'avoid', rotation: {}, companion: {} }
    api.get.mockResolvedValueOnce({ data: { data: payload } })

    const result = await store.checkCompatibility('tomato', 'eggplant')

    expect(api.get).toHaveBeenCalledWith('/crop-compatibility', {
      params: { crop_a: 'tomato', crop_b: 'eggplant' },
    })
    expect(result).toEqual(payload)
  })

  it('loads the crop taxonomy for the preference form', async () => {
    const store = useRecommendationStore()
    const api = useApi()
    const taxonomy = { types: { fruit: { label: 'Fruits', subtypes: {} } } }
    api.get.mockResolvedValueOnce({ data: { data: taxonomy } })

    const promise = store.fetchTaxonomy()
    expect(store.taxonomyLoading).toBe(true)
    await promise

    expect(api.get).toHaveBeenCalledWith('/crop-taxonomy')
    expect(store.taxonomy).toEqual(taxonomy)
    expect(store.taxonomyLoading).toBe(false)
  })
})
