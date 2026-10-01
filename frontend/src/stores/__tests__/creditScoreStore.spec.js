import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useCreditScoreStore } from '../creditScoreStore'
import { useApi } from '@/composables/useApi'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('creditScoreStore', () => {
  const scorePayload = {
    overall_score: 78,
    tier: 'good',
    tier_label: 'Magaling',
    dimension_scores: { plot_activity: 80 },
    improvement_tips: [],
    report_status: 'none',
    report_token: null,
  }

  let store
  let mockGet
  let mockPost

  beforeEach(() => {
    setActivePinia(createPinia())

    mockGet = vi.fn()
    mockPost = vi.fn()
    useApi.mockReturnValue({ get: mockGet, post: mockPost })

    store = useCreditScoreStore()
  })

  it('fetches and stores the trust score', async () => {
    mockGet.mockResolvedValue({ data: { data: scorePayload } })

    await store.fetchScore()

    expect(store.score).toEqual(scorePayload)
    expect(store.isLoading).toBe(false)
    expect(store.errorMessage).toBe('')
  })

  it('records an error message when fetching fails', async () => {
    mockGet.mockRejectedValue({ response: { data: { message: 'Too many requests.' } } })

    await store.fetchScore()

    expect(store.score).toBeNull()
    expect(store.errorMessage).toBe('Too many requests.')
    expect(store.isLoading).toBe(false)
  })

  it('fetches score history', async () => {
    mockGet.mockResolvedValue({ data: { data: [{ overall_score: 70 }, { overall_score: 78 }] } })

    await store.fetchHistory()

    expect(store.history).toHaveLength(2)
  })

  it('exposes the tier style and report readiness as getters', async () => {
    mockGet.mockResolvedValue({
      data: { data: { ...scorePayload, report_status: 'ready', report_token: 'token-123' } },
    })

    await store.fetchScore()

    expect(store.tierStyle.bar).toBe('bg-moss-400')
    expect(store.isReportReady).toBe(true)
    expect(store.reportToken).toBe('token-123')
  })

  it('generates a report and polls until it is ready', async () => {
    mockPost.mockResolvedValue({ data: { message: 'Report generation started.' } })
    mockGet.mockResolvedValue({
      data: { data: { ...scorePayload, report_status: 'ready', report_token: 'token-123' } },
    })

    const status = await store.generateReportAndPoll({ intervalMs: 1 })

    expect(mockPost).toHaveBeenCalledWith('/farmer/credit-score/report', {})
    expect(status).toBe('ready')
    expect(store.isReportReady).toBe(true)
    expect(store.isGeneratingReport).toBe(false)
  })

  it('reports failure when report generation fails', async () => {
    mockPost.mockRejectedValue({ response: { data: { message: 'Daily limit reached.' } } })

    const status = await store.generateReportAndPoll()

    expect(status).toBe('failed')
    expect(store.errorMessage).toBe('Daily limit reached.')
    expect(store.isGeneratingReport).toBe(false)
  })
})
