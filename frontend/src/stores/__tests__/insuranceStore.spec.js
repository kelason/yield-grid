import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useInsuranceStore } from '../insuranceStore'
import { useNotificationStore } from '../notificationStore'
import { useApi } from '@/composables/useApi'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('../notificationStore', () => ({
  useNotificationStore: vi.fn(),
}))

describe('insuranceStore', () => {
  let store
  let mockGet
  let mockPost
  let mockPut
  let mockPatch
  let mockNotifyError

  beforeEach(() => {
    setActivePinia(createPinia())

    mockGet = vi.fn()
    mockPost = vi.fn()
    mockPut = vi.fn()
    mockPatch = vi.fn()
    useApi.mockReturnValue({ get: mockGet, post: mockPost, put: mockPut, patch: mockPatch })

    mockNotifyError = vi.fn()
    useNotificationStore.mockReturnValue({ error: mockNotifyError })

    store = useInsuranceStore()
  })

  it('loads the dashboard data in one call', async () => {
    mockGet.mockImplementation((url) => {
      if (url === '/farmer/insurance/profile') {
        return Promise.resolve({
          data: { data: { rsbsa_number: 'RSBSA-1', rsbsa_status: 'registered' } },
        })
      }
      if (url === '/farmer/insurance/enrollments') {
        return Promise.resolve({ data: { data: [{ id: 7, status: 'draft' }] } })
      }
      if (url === '/farmer/insurance/reminders') {
        return Promise.resolve({ data: { data: [{ type: 'renewal' }] } })
      }
      if (url === '/farmer/insurance/offices') {
        return Promise.resolve({ data: { data: [{ name: 'PCIC Head Office' }] } })
      }
      return Promise.reject(new Error(`unexpected ${url}`))
    })

    await store.fetchDashboard()

    expect(store.profile.rsbsa_status).toBe('registered')
    expect(store.enrollments).toHaveLength(1)
    expect(store.reminders).toHaveLength(1)
    expect(store.offices).toHaveLength(1)
    expect(store.isLoading).toBe(false)
    expect(store.errorMessage).toBe('')
  })

  it('records an error when dashboard loading fails', async () => {
    mockGet.mockRejectedValue({ response: { data: { message: 'Down.' } } })

    await store.fetchDashboard()

    expect(store.errorMessage).toBeTruthy()
    expect(store.isLoading).toBe(false)
  })

  it('saves the profile and refreshes local state', async () => {
    mockPut.mockResolvedValue({
      data: { data: { rsbsa_number: 'RSBSA-9', rsbsa_status: 'registered' } },
    })

    const updated = await store.saveProfile({ rsbsa_number: 'RSBSA-9', rsbsa_status: 'registered' })

    expect(updated.rsbsa_number).toBe('RSBSA-9')
    expect(store.profile.rsbsa_number).toBe('RSBSA-9')
  })

  it('creates an enrollment and appends it to the list', async () => {
    mockPost.mockResolvedValue({ data: { data: { id: 9, status: 'draft' } } })

    const created = await store.createEnrollment({
      program: 'corn',
      season: 'dry',
      season_year: 2026,
    })

    expect(created.id).toBe(9)
    expect(store.enrollments).toHaveLength(1)
  })

  it('advances an enrollment and updates it in place', async () => {
    store.enrollments = [{ id: 9, status: 'draft' }]
    mockPatch.mockResolvedValue({ data: { data: { id: 9, status: 'documents_ready' } } })

    await store.advanceEnrollment(9, 'documents_ready')

    expect(store.enrollments[0].status).toBe('documents_ready')
  })

  it('loads claims per enrollment', async () => {
    mockGet.mockResolvedValue({ data: { data: [{ id: 3, status: 'draft' }] } })

    await store.fetchClaims(9)

    expect(store.claimsByEnrollment[9]).toHaveLength(1)
  })

  it('downloads the pack directly when it is already ready', async () => {
    store.enrollments = [{ id: 7, pack_status: 'ready' }]
    URL.createObjectURL = vi.fn(() => 'blob:pack')
    URL.revokeObjectURL = vi.fn()
    mockGet.mockResolvedValue({ data: new Blob(['pdf']) })

    const result = await store.generatePackAndDownload(7)

    expect(result).toBe(true)
    expect(mockPost).not.toHaveBeenCalled()
    expect(mockGet).toHaveBeenCalledWith('/farmer/insurance/enrollments/7/pack/download', {
      responseType: 'blob',
    })
    expect(store.errorMessage).toBe('')
  })

  it('shows a friendly wait message when pack requests are throttled', async () => {
    store.enrollments = [{ id: 7, pack_status: 'none' }]
    mockPost.mockRejectedValue({
      response: {
        status: 429,
        headers: { 'retry-after': '7200' },
        data: { message: 'Too Many Attempts.' },
      },
    })

    const result = await store.generatePackAndDownload(7)

    expect(result).toBe(false)
    expect(store.errorMessage).toBe('Too many pack requests. Please try again in about 2 hours.')
    expect(mockNotifyError).toHaveBeenCalledWith(
      'Too many pack requests. Please try again in about 2 hours.',
    )
  })

  it('shows a generic throttled message when no retry hint is present', async () => {
    store.enrollments = [{ id: 7, pack_status: 'none' }]
    mockPost.mockRejectedValue({
      response: { status: 429, headers: {}, data: { message: 'Too Many Attempts.' } },
    })

    const result = await store.generatePackAndDownload(7)

    expect(result).toBe(false)
    expect(store.errorMessage).toBe('Too many pack requests. Please try again later.')
  })

  it('shows the wait message when the pack download itself is throttled', async () => {
    store.enrollments = [{ id: 7, pack_status: 'ready' }]
    mockGet.mockRejectedValue({
      response: {
        status: 429,
        headers: { 'retry-after': '90' },
        data: { message: 'Too Many Attempts.' },
      },
    })

    await store.downloadPack(7)

    expect(store.errorMessage).toBe('Too many pack requests. Please try again in 2 minutes.')
    expect(mockNotifyError).toHaveBeenCalledWith(
      'Too many pack requests. Please try again in 2 minutes.',
    )
  })

  it('reports generation failure instead of a timeout when the pack fails', async () => {
    store.enrollments = [{ id: 7, pack_status: 'none' }]
    mockPost.mockResolvedValue({ data: { message: 'started' } })
    mockGet.mockImplementation((url) => {
      if (url === '/farmer/insurance/enrollments/7') {
        return Promise.resolve({ data: { data: { id: 7, pack_status: 'failed' } } })
      }
      return Promise.reject(new Error(`unexpected ${url}`))
    })

    const result = await store.generatePackAndDownload(7)

    expect(result).toBe(false)
    expect(store.errorMessage).toBe('Pack generation failed. Please try again.')
    expect(mockNotifyError).toHaveBeenCalledWith('Pack generation failed. Please try again.')
  })
})
