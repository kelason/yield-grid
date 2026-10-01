import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useCreditScore } from '../useCreditScore'
import { CREDIT_REPORT_STATUS } from '@/constants/creditScoring'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('useCreditScore', () => {
  const scorePayload = {
    overall_score: 78,
    tier: 'good',
    tier_label: 'Magaling',
    dimension_scores: { plot_activity: 80 },
    improvement_tips: ['Complete your plot boundaries.'],
    report_status: 'none',
  }

  let apiGet
  let apiPost

  beforeEach(() => {
    apiGet = vi.fn()
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })
  })

  it('fetches the trust score from the API', async () => {
    apiGet.mockResolvedValue({ data: { data: scorePayload } })

    const { fetchScore, loading, error } = useCreditScore()
    const score = await fetchScore()

    expect(apiGet).toHaveBeenCalledWith('/farmer/credit-score')
    expect(score).toEqual(scorePayload)
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('surfaces a friendly error when fetching fails', async () => {
    apiGet.mockRejectedValue({ response: { data: { message: 'Too many requests.' } } })

    const { fetchScore, error } = useCreditScore()

    await expect(fetchScore()).rejects.toBeTruthy()
    expect(error.value).toBe('Too many requests.')
  })

  it('fetches score history from the API', async () => {
    apiGet.mockResolvedValue({ data: { data: [{ overall_score: 78 }] } })

    const { fetchHistory } = useCreditScore()
    const history = await fetchHistory()

    expect(apiGet).toHaveBeenCalledWith('/farmer/credit-score/history')
    expect(history).toHaveLength(1)
  })

  it('starts report generation', async () => {
    apiPost.mockResolvedValue({ data: { message: 'Report generation started.' } })

    const { generateReport } = useCreditScore()
    await generateReport()

    expect(apiPost).toHaveBeenCalledWith('/farmer/credit-score/report', {})
  })

  it('resolves when the polled report becomes ready', async () => {
    const { pollUntilReportReady } = useCreditScore()
    const refreshScore = vi.fn().mockResolvedValue({ report_status: CREDIT_REPORT_STATUS.READY })

    const status = await pollUntilReportReady(refreshScore, { intervalMs: 1, maxAttempts: 3 })

    expect(status).toBe(CREDIT_REPORT_STATUS.READY)
    expect(refreshScore).toHaveBeenCalledTimes(1)
  })

  it('keeps polling while the report is generating and stops at the limit', async () => {
    const { pollUntilReportReady } = useCreditScore()
    const refreshScore = vi
      .fn()
      .mockResolvedValue({ report_status: CREDIT_REPORT_STATUS.GENERATING })

    const status = await pollUntilReportReady(refreshScore, { intervalMs: 1, maxAttempts: 3 })

    expect(status).toBe(CREDIT_REPORT_STATUS.GENERATING)
    expect(refreshScore).toHaveBeenCalledTimes(3)
  })

  it('stops polling when cancelled', async () => {
    const { pollUntilReportReady } = useCreditScore()
    const refreshScore = vi.fn()

    const status = await pollUntilReportReady(refreshScore, {
      intervalMs: 1,
      maxAttempts: 5,
      isCancelled: () => true,
    })

    expect(status).toBe(CREDIT_REPORT_STATUS.GENERATING)
    expect(refreshScore).not.toHaveBeenCalled()
  })
})
