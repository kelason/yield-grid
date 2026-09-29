import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useDemandStore } from '../demandStore'
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

describe('Demand Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches demands with filters and pagination', async () => {
    const store = useDemandStore()
    const api = useApi()
    api.get.mockResolvedValueOnce({
      data: {
        data: [{ id: 1, title: 'Tomatoes' }],
        meta: { current_page: 1, last_page: 2, total: 20, per_page: 12 },
      },
    })

    store.filters.crop = 'tomato'
    await store.fetchDemands(1)

    expect(api.get).toHaveBeenCalledWith(expect.stringContaining('/market/demands?'))
    expect(api.get).toHaveBeenCalledWith(expect.stringContaining('crop=tomato'))
    expect(store.demands).toHaveLength(1)
    expect(store.pagination.lastPage).toBe(2)
    expect(store.loading.demands).toBe(false)
  })

  it('appends viewer coordinates for nearest sort', async () => {
    const store = useDemandStore()
    const api = useApi()
    api.get.mockResolvedValueOnce({ data: { data: [], meta: null } })

    store.filters.sort = 'nearest'
    store.viewerLocation = { lat: 14.55, lng: 121.03 }
    await store.fetchDemands(1)

    const url = api.get.mock.calls[0][0]
    expect(url).toContain('sort=nearest')
    expect(url).toContain('lat=14.55')
    expect(url).toContain('lng=121.03')
  })

  it('posts a demand and prepends it to myDemands', async () => {
    const store = useDemandStore()
    const api = useApi()
    api.post.mockResolvedValueOnce({ data: { data: { id: 7, title: 'New' } } })

    const created = await store.postDemand({ title: 'New' })

    expect(api.post).toHaveBeenCalledWith('/buyer/demands', { title: 'New' })
    expect(created.id).toBe(7)
    expect(store.myDemands[0].id).toBe(7)
  })

  it('decides offers and replaces them in local lists', async () => {
    const store = useDemandStore()
    const api = useApi()
    store.demandOffers = [{ id: 3, status: 'pending' }]
    api.post.mockResolvedValueOnce({ data: { data: { id: 3, status: 'accepted' } } })

    await store.decideOffer(3, 'accept')

    expect(api.post).toHaveBeenCalledWith('/buyer/offers/3/accept')
    expect(store.demandOffers[0].status).toBe('accepted')
  })

  it('submits an offer to the demand endpoint', async () => {
    const store = useDemandStore()
    const api = useApi()
    api.post.mockResolvedValueOnce({ data: { data: { id: 9, status: 'pending' } } })

    await store.submitOffer(5, { quantity_kg: 100, price_per_kg: 40 })

    expect(api.post).toHaveBeenCalledWith('/demands/5/offers', {
      quantity_kg: 100,
      price_per_kg: 40,
    })
    expect(store.myOffers[0].id).toBe(9)
  })

  it('routes farmer and buyer cancellations to the right endpoints', async () => {
    const store = useDemandStore()
    const api = useApi()
    api.post.mockResolvedValue({ data: { data: { id: 1, status: 'cancelled' } } })

    await store.cancelOffer(1, true)
    expect(api.post).toHaveBeenCalledWith('/farmer/offers/1/cancel')

    await store.cancelOffer(2, false)
    expect(api.post).toHaveBeenCalledWith('/buyer/offers/2/cancel')
  })
})
