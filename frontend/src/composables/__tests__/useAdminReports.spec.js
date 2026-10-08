import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import { useAdminReports } from '../useAdminReports'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('useAdminReports', () => {
  let apiGet
  let apiPost

  function listResponse(data, meta = {}) {
    return {
      data: {
        data,
        meta: { current_page: 2, last_page: 4, total: 20, ...meta },
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

  it('fetches the queue with status, type, and reason filters', async () => {
    const reports = useAdminReports()
    apiGet.mockResolvedValueOnce(listResponse([{ id: '1' }]))
    reports.filters.value = { status: 'open', type: 'thread', reason: 'spam' }

    await reports.fetchReports(2)

    expect(apiGet).toHaveBeenCalledWith('/admin/reports', {
      params: { page: 2, per_page: 10, status: 'open', type: 'thread', reason: 'spam' },
    })
    expect(reports.list.value).toEqual([{ id: '1' }])
    expect(reports.pagination.value).toEqual({ currentPage: 2, lastPage: 4, total: 20 })
    expect(reports.loading.value).toBe(false)
  })

  it('omits empty filters and surfaces list failures with retryable state', async () => {
    const reports = useAdminReports()
    apiGet.mockRejectedValueOnce(new Error('Network down'))

    await reports.fetchReports(1)

    expect(apiGet).toHaveBeenCalledWith('/admin/reports', { params: { page: 1, per_page: 10 } })
    expect(reports.list.value).toEqual([])
    expect(reports.error.value).toContain('Network down')
    expect(reports.loading.value).toBe(false)
  })

  it('loads report detail', async () => {
    const reports = useAdminReports()
    const detail = { id: '5', status: 'open', version: 2 }
    apiGet.mockResolvedValueOnce({ data: { data: detail } })

    await reports.fetchReport('5')

    expect(apiGet).toHaveBeenCalledWith('/admin/reports/5')
    expect(reports.selected.value).toEqual(detail)
    expect(reports.detailLoading.value).toBe(false)
  })

  it('ignores a late detail response for a superseded report', async () => {
    const reports = useAdminReports()
    let releaseFirst
    apiGet.mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          releaseFirst = resolve
        }),
    )
    apiGet.mockResolvedValueOnce({ data: { data: { id: '6', version: 1 } } })

    const first = reports.fetchReport('5')
    const second = reports.fetchReport('6')
    await second
    releaseFirst({ data: { data: { id: '5', version: 9 } } })
    await first

    expect(reports.selected.value).toEqual({ id: '6', version: 1 })
    expect(reports.detailLoading.value).toBe(false)
  })

  it('decides a report and refreshes the queue entry', async () => {
    const reports = useAdminReports()
    reports.list.value = [{ id: '5', status: 'open', version: 2 }]
    reports.selected.value = { id: '5', status: 'open', version: 2 }
    const decided = { id: '5', status: 'reviewing', version: 3 }
    apiPost.mockResolvedValueOnce({ data: { data: decided } })

    const result = await reports.decide('5', {
      status: 'reviewing',
      outcome: null,
      note: 'Looking into this.',
      expected_version: 2,
    })

    expect(apiPost).toHaveBeenCalledWith('/admin/reports/5/decision', {
      status: 'reviewing',
      outcome: null,
      note: 'Looking into this.',
      expected_version: 2,
    })
    expect(result).toEqual(decided)
    expect(reports.selected.value).toEqual(decided)
    expect(reports.list.value).toEqual([decided])
    expect(reports.mutating.value).toBe(false)
  })

  it('exposes the conflicted version on a stale decision', async () => {
    const reports = useAdminReports()
    reports.selected.value = { id: '5', status: 'open', version: 2 }
    apiPost.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Report changed.', current_version: 4 } },
    })

    const failure = await reports
      .decide('5', { status: 'dismissed', outcome: null, note: 'Duplicate.', expected_version: 2 })
      .catch((error) => error)

    expect(failure.isVersionConflict).toBe(true)
    expect(failure.currentVersion).toBe(4)
    expect(failure.message).toContain('Report changed')
    expect(reports.selected.value.version).toBe(2)
    expect(reports.mutating.value).toBe(false)
  })

  it('clears the session when the admin token is revoked', async () => {
    const reports = useAdminReports()
    const authStore = useAuthStore()
    authStore.user = { id: 1, role: 'admin' }
    apiGet.mockRejectedValueOnce({ response: { status: 401, data: {} } })

    await reports.fetchReports(1)

    expect(authStore.user).toBeNull()
  })
})
