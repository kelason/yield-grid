import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useMarketStore } from '../marketStore'
import { useApi } from '@/composables/useApi'

// Mock the useApi composable
vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('marketStore', () => {
  let store
  let mockGet
  let mockPost

  beforeEach(() => {
    setActivePinia(createPinia())

    mockGet = vi.fn()
    mockPost = vi.fn()
    useApi.mockReturnValue({ get: mockGet, post: mockPost })

    store = useMarketStore()
  })

  it('initializes with empty states', () => {
    expect(store.contracts).toEqual([])
    expect(store.farmerContracts).toEqual([])
    expect(store.buyerPurchases).toEqual([])
  })

  it('fetches market contracts successfully', async () => {
    const mockData = {
      data: [{ id: 1, title: 'Test Contract' }],
      meta: { current_page: 1, last_page: 1, total: 1, per_page: 10 },
    }
    mockGet.mockResolvedValueOnce({ data: mockData })

    await store.fetchMarketContracts()

    expect(mockGet).toHaveBeenCalledWith('/market/contracts?page=1&per_page=12&sort=newest')
    expect(store.contracts).toEqual(mockData.data)
    expect(store.loading.contracts).toBe(false)
  })

  it('handles contract purchased event', () => {
    store.farmerContracts = [
      { id: 1, title: 'Contract 1', status: 'available' },
      { id: 2, title: 'Contract 2', status: 'available' },
    ]

    store.handleContractPurchased({ contract_id: 1 })

    expect(store.farmerContracts[0].status).toBe('sold')
    expect(store.farmerContracts[1].status).toBe('available')
  })

  it('creates manual listing successfully', async () => {
    mockPost.mockResolvedValueOnce({ data: { id: 1, title: 'Manual Listing' } })

    const formData = { title: 'Manual Listing', quantity_kg: 100 }
    const result = await store.createManualListing(formData)

    expect(mockPost).toHaveBeenCalledWith('/farmer/listings', formData)
    expect(result).toEqual({ id: 1, title: 'Manual Listing' })
    expect(store.loading.publish).toBe(false)
  })

  it('approves cash payment successfully', async () => {
    mockPost.mockResolvedValueOnce({ data: { id: 1, cash_payment_status: 'partially_paid' } })
    store.farmerPurchases = [{ id: 1, cash_payment_status: 'pending_approval' }]

    const result = await store.approveCashPayment(1, 'partial', 500)

    expect(mockPost).toHaveBeenCalledWith('/farmer/purchases/1/approve', {
      type: 'partial',
      amount: 500,
    })
    expect(result).toEqual({ id: 1, cash_payment_status: 'partially_paid' })
    expect(store.farmerPurchases[0].cash_payment_status).toBe('partially_paid')
  })
})
