import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useInsurance } from '../useInsurance'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('useInsurance', () => {
  let apiGet
  let apiPost
  let apiPut
  let apiPatch

  beforeEach(() => {
    apiGet = vi.fn()
    apiPost = vi.fn()
    apiPut = vi.fn()
    apiPatch = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost, put: apiPut, patch: apiPatch })
  })

  it('fetches and updates the RSBSA profile', async () => {
    apiGet.mockResolvedValue({
      data: { data: { rsbsa_number: null, rsbsa_status: 'not_registered' } },
    })
    apiPut.mockResolvedValue({
      data: { data: { rsbsa_number: 'RSBSA-1', rsbsa_status: 'registered' } },
    })

    const insurance = useInsurance()
    const profile = await insurance.fetchProfile()

    expect(apiGet).toHaveBeenCalledWith('/farmer/insurance/profile')
    expect(profile.rsbsa_status).toBe('not_registered')

    const updated = await insurance.updateProfile({
      rsbsa_number: 'RSBSA-1',
      rsbsa_status: 'registered',
    })

    expect(apiPut).toHaveBeenCalledWith('/farmer/insurance/profile', {
      rsbsa_number: 'RSBSA-1',
      rsbsa_status: 'registered',
    })
    expect(updated.rsbsa_number).toBe('RSBSA-1')
  })

  it('creates enrollments and advances their status', async () => {
    apiPost.mockResolvedValue({ data: { data: { id: 7, status: 'draft' } } })
    apiPatch.mockResolvedValue({ data: { data: { id: 7, status: 'documents_ready' } } })

    const insurance = useInsurance()
    const created = await insurance.createEnrollment({
      program: 'rice',
      season: 'wet',
      season_year: 2026,
    })

    expect(apiPost).toHaveBeenCalledWith('/farmer/insurance/enrollments', {
      program: 'rice',
      season: 'wet',
      season_year: 2026,
    })
    expect(created.id).toBe(7)

    const advanced = await insurance.advanceEnrollment(7, 'documents_ready')

    expect(apiPatch).toHaveBeenCalledWith('/farmer/insurance/enrollments/7/status', {
      status: 'documents_ready',
    })
    expect(advanced.status).toBe('documents_ready')
  })

  it('requests pack generation and downloads the pack blob', async () => {
    apiPost.mockResolvedValue({ data: { message: 'started' } })
    const blob = new Blob(['%PDF-1.4 fake'], { type: 'application/pdf' })
    apiGet.mockResolvedValue({ data: blob })

    const insurance = useInsurance()
    await insurance.requestPack(7)

    expect(apiPost).toHaveBeenCalledWith('/farmer/insurance/enrollments/7/pack', {})

    const downloaded = await insurance.downloadPack(7)

    expect(apiGet).toHaveBeenCalledWith('/farmer/insurance/enrollments/7/pack/download', {
      responseType: 'blob',
    })
    expect(downloaded).toBe(blob)
  })

  it('files claims and lists reminders and offices', async () => {
    apiPost.mockResolvedValue({ data: { data: { id: 3, status: 'draft' } } })
    apiGet.mockResolvedValue({ data: { data: [] } })

    const insurance = useInsurance()
    const claim = await insurance.createClaim(7, { loss_date: '2026-08-15', cause: 'typhoon' })

    expect(apiPost).toHaveBeenCalledWith('/farmer/insurance/enrollments/7/claims', {
      loss_date: '2026-08-15',
      cause: 'typhoon',
    })
    expect(claim.id).toBe(3)

    await insurance.fetchReminders()
    expect(apiGet).toHaveBeenCalledWith('/farmer/insurance/reminders')

    await insurance.fetchOffices()
    expect(apiGet).toHaveBeenCalledWith('/farmer/insurance/offices')
  })

  it('surfaces a friendly error when the API fails', async () => {
    apiGet.mockRejectedValue({ response: { data: { message: 'Gone.' } } })

    const insurance = useInsurance()

    await expect(insurance.fetchProfile()).rejects.toBeTruthy()
    expect(insurance.error.value).toBe('Gone.')
  })
})
