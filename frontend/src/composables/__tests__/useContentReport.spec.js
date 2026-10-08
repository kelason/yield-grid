import { describe, it, expect, beforeEach, vi } from 'vitest'
import { reportTargetKey, useContentReport } from '../useContentReport'
import { useForumStore } from '@/stores/forumStore'

vi.mock('@/stores/forumStore', () => ({
  useForumStore: vi.fn(),
}))

describe('reportTargetKey', () => {
  it('distinguishes colliding numeric ids across types', () => {
    expect(reportTargetKey('contract', 7)).not.toBe(reportTargetKey('listing', 7))
    expect(reportTargetKey('thread', '7')).not.toBe(reportTargetKey('reply', '7'))
  })

  it('matches the same target across string and number ids', () => {
    expect(reportTargetKey('contract', 7)).toBe(reportTargetKey('contract', '7'))
  })
})

describe('useContentReport', () => {
  let reportContent

  beforeEach(() => {
    reportContent = vi.fn()
    useForumStore.mockReturnValue({ reportContent })
  })

  function openThread(report, overrides = {}) {
    report.openReport({
      reportable_type: 'thread',
      reportable_id: 7,
      title: 'Harvest tips',
      ...overrides,
    })
  }

  it('opens a target while retaining its original id representation', () => {
    const report = useContentReport()
    openThread(report)

    expect(report.target.value).toEqual({
      reportable_type: 'thread',
      reportable_id: 7,
      title: 'Harvest tips',
    })
    expect(report.busy.value).toBe(false)
  })

  it('resets the draft and errors when the target changes', () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    report.description.value = 'Some detail'
    report.error.value = 'stale failure'

    report.openReport({ reportable_type: 'listing', reportable_id: 7, title: 'Rice lot' })

    expect(report.target.value.reportable_type).toBe('listing')
    expect(report.reason.value).toBe('')
    expect(report.description.value).toBe('')
    expect(report.error.value).toBe('')
    expect(report.fieldError.value).toBe('')
  })

  it('retains the draft when reopening the same target', () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    report.description.value = 'Some detail'

    openThread(report, { reportable_id: '7' })

    expect(report.reason.value).toBe('spam')
    expect(report.description.value).toBe('Some detail')
  })

  it('rejects submit without a request when no reason is chosen', async () => {
    const report = useContentReport()
    openThread(report)

    await expect(report.submit()).rejects.toThrow('reason')
    expect(reportContent).not.toHaveBeenCalled()
    expect(report.fieldError.value).toContain('reason')
  })

  it('rejects descriptions over 1000 characters', async () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    report.description.value = 'a'.repeat(1001)

    await expect(report.submit()).rejects.toThrow('1,000')
    expect(reportContent).not.toHaveBeenCalled()
  })

  it('accepts a 1000-character description', async () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    report.description.value = 'a'.repeat(1000)
    reportContent.mockResolvedValue({ id: '1', status: 'open' })

    await expect(report.submit()).resolves.toEqual({ id: '1', status: 'open' })
    expect(reportContent).toHaveBeenCalledTimes(1)
  })

  it('requires a description when the reason is other', async () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'other'
    report.description.value = '   '

    await expect(report.submit()).rejects.toThrow('description')
    expect(reportContent).not.toHaveBeenCalled()
  })

  it('submits the draft and clears the previous error', async () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    report.description.value = '  Repeated ads  '
    report.error.value = 'stale failure'
    reportContent.mockResolvedValue({ id: '1', status: 'open' })

    const receipt = await report.submit()

    expect(reportContent).toHaveBeenCalledWith('thread', 7, 'spam', 'Repeated ads')
    expect(receipt).toEqual({ id: '1', status: 'open' })
    expect(report.error.value).toBe('')
    expect(report.busy.value).toBe(false)
  })

  it('sends exactly one request for duplicate submits', async () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    let release
    reportContent.mockReturnValueOnce(
      new Promise((resolve) => {
        release = resolve
      }),
    )

    const first = report.submit()
    const second = report.submit()
    expect(report.busy.value).toBe(true)
    release({ id: '1', status: 'open' })
    await Promise.all([first, second])

    expect(reportContent).toHaveBeenCalledTimes(1)
    expect(report.busy.value).toBe(false)
  })

  it('retains the description and target when submit fails', async () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    report.description.value = 'Repeated ads'
    reportContent.mockRejectedValue(new Error('Request failed with status code 429'))

    await expect(report.submit()).rejects.toThrow('429')
    expect(report.error.value).toContain('429')
    expect(report.description.value).toBe('Repeated ads')
    expect(report.target.value).toMatchObject({ reportable_type: 'thread', reportable_id: 7 })
    expect(report.busy.value).toBe(false)
  })

  it('ignores a late response for a superseded target', async () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    let release
    reportContent.mockReturnValueOnce(
      new Promise((resolve) => {
        release = resolve
      }),
    )

    const pending = report.submit()
    report.openReport({ reportable_type: 'reply', reportable_id: 9, title: 'A reply' })
    report.error.value = 'new target problem'
    release({ id: '1', status: 'open' })
    await pending

    expect(report.target.value).toMatchObject({ reportable_type: 'reply', reportable_id: 9 })
    expect(report.error.value).toBe('new target problem')
    expect(report.busy.value).toBe(false)
  })

  it('clears state on cancel and never posts', () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    report.description.value = 'draft'

    report.closeReport()

    expect(report.target.value).toBeNull()
    expect(report.reason.value).toBe('')
    expect(report.description.value).toBe('')
    expect(reportContent).not.toHaveBeenCalled()
  })

  it('ignores cancel while a submit is in flight', async () => {
    const report = useContentReport()
    openThread(report)
    report.reason.value = 'spam'
    let release
    reportContent.mockReturnValueOnce(
      new Promise((resolve) => {
        release = resolve
      }),
    )

    const pending = report.submit()
    report.closeReport()
    expect(report.target.value).not.toBeNull()
    release({ id: '1', status: 'open' })
    await pending
  })
})
