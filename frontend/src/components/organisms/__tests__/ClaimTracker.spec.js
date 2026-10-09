import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n, { setLocale } from '@/i18n'
import ClaimTracker from '../ClaimTracker.vue'

describe('ClaimTracker', () => {
  beforeEach(() => {
    setLocale('en')
  })

  const claims = [
    {
      id: 3,
      enrollment_id: 7,
      loss_date: '2026-08-15',
      cause: 'typhoon',
      status: 'draft',
      notice_of_loss_deadline: '2026-08-25',
      notice_of_loss_overdue: true,
      notice_of_loss_filed_at: null,
    },
    {
      id: 4,
      enrollment_id: 7,
      loss_date: '2026-07-01',
      cause: 'flood',
      status: 'paid',
      notice_of_loss_deadline: '2026-07-11',
      notice_of_loss_overdue: false,
      notice_of_loss_filed_at: '2026-07-05T00:00:00Z',
    },
  ]

  function mountTracker(props = {}) {
    return mount(ClaimTracker, {
      props: { claims, enrollmentId: 7, ...props },
      global: { plugins: [i18n] },
    })
  }

  it('highlights overdue notice-of-loss deadlines', () => {
    const wrapper = mountTracker()

    expect(wrapper.findAll('[data-testid="claim-card"]')).toHaveLength(2)
    expect(wrapper.text()).toContain('Overdue — file immediately')
    expect(wrapper.text()).toContain('2026-08-25')
  })

  it('emits file and advance actions', async () => {
    const wrapper = mountTracker()

    await wrapper.get('[data-testid="file-claim"]').trigger('click')
    expect(wrapper.emitted('file-claim')).toBeTruthy()

    await wrapper.get('[data-testid="advance-claim-3"]').trigger('click')
    expect(wrapper.emitted('advance-claim')[0]).toEqual([3, 'notice_of_loss_filed'])
  })

  it('shows an empty state when there are no claims', () => {
    const wrapper = mountTracker({ claims: [] })

    expect(wrapper.get('[data-testid="claims-empty"]').exists()).toBe(true)
  })

  it('emits a null payout when the amount is left empty', async () => {
    const approved = [
      {
        id: 5,
        enrollment_id: 7,
        loss_date: '2026-06-01',
        cause: 'drought',
        status: 'approved',
        notice_of_loss_deadline: '2026-06-11',
        notice_of_loss_overdue: false,
        notice_of_loss_filed_at: '2026-06-03T00:00:00Z',
      },
    ]
    const wrapper = mountTracker({ claims: approved })

    await wrapper.get('[data-testid="mark-paid-5"]').trigger('click')

    expect(wrapper.emitted('record-payout')).toEqual([[5, null]])
  })

  it('emits rejection for in-review claims', async () => {
    const inspecting = [
      {
        id: 6,
        enrollment_id: 7,
        loss_date: '2026-06-01',
        cause: 'drought',
        status: 'field_inspection',
        notice_of_loss_deadline: '2026-06-11',
        notice_of_loss_overdue: false,
        notice_of_loss_filed_at: '2026-06-03T00:00:00Z',
      },
    ]
    const wrapper = mountTracker({ claims: inspecting })

    await wrapper.get('[data-testid="reject-claim-6"]').trigger('click')

    expect(wrapper.emitted('advance-claim')).toEqual([[6, 'rejected']])
  })

  it('emits record-payout with the entered amount for approved claims', async () => {
    const approved = [
      {
        id: 5,
        enrollment_id: 7,
        loss_date: '2026-06-01',
        cause: 'drought',
        status: 'approved',
        notice_of_loss_deadline: '2026-06-11',
        notice_of_loss_overdue: false,
        notice_of_loss_filed_at: '2026-06-03T00:00:00Z',
      },
    ]
    const wrapper = mountTracker({ claims: approved })

    await wrapper.get('[data-testid="payout-amount-5"] input').setValue(25000)
    await wrapper.get('[data-testid="mark-paid-5"]').trigger('click')

    expect(wrapper.emitted('record-payout')).toEqual([[5, 25000]])
  })
})
