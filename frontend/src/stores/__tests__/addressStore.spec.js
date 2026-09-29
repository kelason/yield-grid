import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useAddressStore } from '../addressStore'
import { useApi } from '@/composables/useApi'

vi.mock('@/composables/useApi', () => {
  const getMock = vi.fn()
  const postMock = vi.fn()
  const putMock = vi.fn()
  const deleteMock = vi.fn()
  return {
    useApi: () => ({
      get: getMock,
      post: postMock,
      put: putMock,
      delete: deleteMock,
      defaults: { baseURL: 'http://localhost:3000/api/v1' },
    }),
  }
})

describe('Address Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches addresses and computes the default', async () => {
    const store = useAddressStore()
    const api = useApi()
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, is_default: false },
          { id: 2, is_default: true },
        ],
      },
    })

    await store.fetchAddresses()

    expect(store.addresses).toHaveLength(2)
    expect(store.defaultAddress.id).toBe(2)
    expect(store.hasAddress).toBe(true)
  })

  it('clears the old default when a new default is created', async () => {
    const store = useAddressStore()
    const api = useApi()
    store.addresses = [{ id: 1, is_default: true }]
    api.post.mockResolvedValueOnce({ data: { data: { id: 2, is_default: true } } })

    await store.createAddress({ region_code: '130000000' })

    expect(store.addresses.find((a) => a.id === 1).is_default).toBe(false)
    expect(store.defaultAddress.id).toBe(2)
  })

  it('updates an address in place', async () => {
    const store = useAddressStore()
    const api = useApi()
    store.addresses = [{ id: 1, street: 'Old', is_default: true }]
    api.put.mockResolvedValueOnce({
      data: { data: { id: 1, street: 'New', is_default: true } },
    })

    await store.updateAddress(1, { street: 'New' })

    expect(api.put).toHaveBeenCalledWith('/user/addresses/1', { street: 'New' })
    expect(store.addresses[0].street).toBe('New')
  })

  it('removes a deleted address', async () => {
    const store = useAddressStore()
    const api = useApi()
    store.addresses = [{ id: 1 }, { id: 2 }]
    api.delete.mockResolvedValueOnce({ data: {} })

    await store.deleteAddress(1)

    expect(store.addresses.map((a) => a.id)).toEqual([2])
  })
})
