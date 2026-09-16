import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useMarketStore } from '../marketStore'
import { useApi } from '@/composables/useApi'

// Mock the useApi composable
vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn()
}))

describe('marketStore', () => {
  let store
  let mockRequest

  beforeEach(() => {
    setActivePinia(createPinia())
    
    mockRequest = vi.fn()
    useApi.mockReturnValue({ request: mockRequest })
    
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
      meta: { current_page: 1, last_page: 1, total: 1, per_page: 10 }
    }
    mockRequest.mockResolvedValueOnce(mockData)

    await store.fetchMarketContracts()

    expect(mockRequest).toHaveBeenCalledWith('/market/contracts?page=1&sort=newest')
    expect(store.contracts).toEqual(mockData.data)
    expect(store.loading.contracts).toBe(false)
  })
  
  it('handles contract purchased event', () => {
    store.farmerContracts = [
      { id: 1, title: 'Contract 1', status: 'available' },
      { id: 2, title: 'Contract 2', status: 'available' }
    ]
    
    store.handleContractPurchased({ contract_id: 1 })
    
    expect(store.farmerContracts[0].status).toBe('sold')
    expect(store.farmerContracts[1].status).toBe('available')
  })
})
