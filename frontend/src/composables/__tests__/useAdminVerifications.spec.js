import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAdminVerifications } from '../useAdminVerifications'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('useAdminVerifications', () => {
  let apiGet
  let apiPost

  function listResponse(data) {
    return {
      data: {
        data,
        meta: { current_page: 2, last_page: 4, total: data.length },
      },
    }
  }

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
    apiGet = vi.fn()
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })
  })

  it('fetches the queue with scope and status params', async () => {
    const queue = useAdminVerifications()
    apiGet.mockResolvedValueOnce(listResponse([{ id: 1, type: 'farm' }]))

    await queue.fetchQueue(1)

    expect(apiGet).toHaveBeenCalledWith('/admin/verifications', {
      params: { page: 1, per_page: 10, scope: 'farms', status: 'pending' },
    })
    expect(queue.list.value).toEqual([{ id: 1, type: 'farm' }])
    expect(queue.pagination.value).toEqual({ currentPage: 2, lastPage: 4, total: 1 })
    expect(queue.loading.value).toBe(false)
  })

  it('sets error and empties the list when the queue fails', async () => {
    const queue = useAdminVerifications()
    queue.list.value = [{ id: 1, type: 'farm' }]
    apiGet.mockRejectedValueOnce({ response: { status: 500, data: {} }, message: 'boom' })

    await queue.fetchQueue(1)

    expect(queue.list.value).toEqual([])
    expect(queue.error.value).toBeTruthy()
    expect(queue.loading.value).toBe(false)
  })

  it('loads detail into selected', async () => {
    const queue = useAdminVerifications()
    apiGet.mockResolvedValueOnce({ data: { data: { id: 7, type: 'farm', name: 'Green Acres' } } })

    const selected = await queue.fetchDetail('farms', 7)

    expect(apiGet).toHaveBeenCalledWith('/admin/verifications/farms/7')
    expect(selected).toEqual({ id: 7, type: 'farm', name: 'Green Acres' })
    expect(queue.detailLoading.value).toBe(false)
  })

  it('sets a friendly detail error when the record is gone', async () => {
    const queue = useAdminVerifications()
    apiGet.mockRejectedValueOnce({ response: { status: 404, data: {} } })

    const selected = await queue.fetchDetail('plots', 9)

    expect(selected).toBeNull()
    expect(queue.detailError.value).toBeTruthy()
  })

  it('posts verify decisions with method and note', async () => {
    const queue = useAdminVerifications()
    queue.list.value = [{ id: 7, type: 'farm', verification_status: 'pending' }]
    apiPost.mockResolvedValueOnce({ data: { data: { id: 7, verification_status: 'verified' } } })

    await queue.decide('farms', 7, 'verify', { method: 'field_visit', note: 'Visited' })

    expect(apiPost).toHaveBeenCalledWith('/admin/verifications/farms/7/verify', {
      method: 'field_visit',
      note: 'Visited',
    })
    expect(queue.list.value[0].verification_status).toBe('verified')
  })

  it('replaces the selected row after a decision', async () => {
    const queue = useAdminVerifications()
    queue.selected.value = { id: 7, type: 'plot', verification_status: 'pending' }
    apiPost.mockResolvedValueOnce({ data: { data: { id: 7, verification_status: 'rejected' } } })

    await queue.decide('plots', 7, 'reject', { reason: 'No such place' })

    expect(apiPost).toHaveBeenCalledWith('/admin/verifications/plots/7/reject', {
      reason: 'No such place',
    })
    expect(queue.selected.value.verification_status).toBe('rejected')
  })

  it('throws a readable error when a decision fails', async () => {
    const queue = useAdminVerifications()
    apiPost.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Cannot verify a verified record.' } },
    })

    await expect(queue.decide('farms', 7, 'verify', { method: 'other' })).rejects.toThrow(
      'Cannot verify a verified record.',
    )
    expect(queue.mutating.value).toBe(false)
  })

  it('resets detail state', () => {
    const queue = useAdminVerifications()
    queue.selected.value = { id: 1 }
    queue.detailError.value = 'stale'

    queue.resetDetail()

    expect(queue.selected.value).toBeNull()
    expect(queue.detailError.value).toBe('')
  })
})
