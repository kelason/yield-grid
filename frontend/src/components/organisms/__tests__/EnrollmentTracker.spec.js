import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n, { setInsuranceLocale } from '@/i18n'
import EnrollmentTracker from '../EnrollmentTracker.vue'

describe('EnrollmentTracker', () => {
  beforeEach(() => {
    setInsuranceLocale('en')
  })

  const enrollments = [
    {
      id: 7,
      plot_id: 3,
      program: 'rice',
      season: 'wet',
      season_year: 2026,
      status: 'draft',
      pack_status: 'ready',
      notes: null,
    },
    {
      id: 8,
      plot_id: null,
      program: 'corn',
      season: 'dry',
      season_year: 2026,
      status: 'active',
      pack_status: 'ready',
      notes: null,
    },
  ]

  function mountTracker(props = {}) {
    return mount(EnrollmentTracker, {
      props: { enrollments, ...props },
      global: { plugins: [i18n] },
    })
  }

  it('renders one card per enrollment with its status', () => {
    const wrapper = mountTracker()

    expect(wrapper.findAll('[data-testid="enrollment-card"]')).toHaveLength(2)
    expect(wrapper.text()).toContain('Draft')
    expect(wrapper.text()).toContain('Active')
  })

  it('emits actions for pack download and status advance', async () => {
    const wrapper = mountTracker()

    await wrapper.get('[data-testid="request-pack-7"]').trigger('click')
    expect(wrapper.emitted('request-pack')).toEqual([[7]])

    await wrapper.get('[data-testid="advance-enrollment-7"]').trigger('click')
    expect(wrapper.emitted('advance-enrollment')[0][0]).toBe(7)
  })

  it('renders no per-enrollment claims button', () => {
    const wrapper = mountTracker()

    expect(wrapper.find('[data-testid="request-pack-7"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="view-claims-7"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="view-claims-8"]').exists()).toBe(false)
  })

  it('disables the advance button until the pack is ready', () => {
    const wrapper = mountTracker({
      enrollments: [
        {
          id: 9,
          plot_id: 3,
          program: 'rice',
          season: 'wet',
          season_year: 2026,
          status: 'draft',
          pack_status: 'none',
          notes: null,
        },
      ],
    })

    expect(wrapper.get('[data-testid="advance-enrollment-9"]').attributes('disabled')).toBeDefined()
    expect(wrapper.text()).toContain('Download the enrollment pack first.')
  })

  it('separates destructive actions from workflow actions', () => {
    const wrapper = mountTracker()

    const dangerZone = wrapper.get('[data-testid="danger-zone-7"]')
    expect(dangerZone.find('[data-testid="cancel-enrollment-7"]').exists()).toBe(true)
    expect(dangerZone.text()).toContain('Cancel enrollment')
    expect(wrapper.find('[data-testid="danger-zone-8"]').exists()).toBe(false)
  })

  it('shows an empty state when there are no enrollments', () => {
    const wrapper = mountTracker({ enrollments: [] })

    expect(wrapper.get('[data-testid="enrollments-empty"]').exists()).toBe(true)
  })

  it('emits record-policy-details with validated CIC payload', async () => {
    const submitted = [
      {
        id: 11,
        plot_id: 3,
        program: 'rice',
        season: 'wet',
        season_year: 2026,
        status: 'submitted_to_mao',
        pack_status: 'ready',
        notes: null,
      },
    ]
    const wrapper = mountTracker({ enrollments: submitted })

    await wrapper.get('[data-testid="record-cic-11"]').trigger('click')
    await wrapper.get('[data-testid="cic-number-11"] input').setValue('CIC-2026-1')
    await wrapper.get('[data-testid="cic-coverage-11"] input').setValue(37500)
    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('record-policy-details')).toEqual([
      [
        11,
        { cic_number: 'CIC-2026-1', coverage_amount_php: 37500, enrolled_at: '', expires_at: '' },
      ],
    ])
  })

  it('emits cancellation and rejection for eligible enrollments', async () => {
    const submitted = [
      {
        id: 11,
        plot_id: 3,
        program: 'rice',
        season: 'wet',
        season_year: 2026,
        status: 'submitted_to_mao',
        pack_status: 'ready',
        notes: null,
      },
    ]
    const wrapper = mountTracker({ enrollments: submitted })

    await wrapper.get('[data-testid="cancel-enrollment-11"]').trigger('click')
    expect(wrapper.emitted('advance-enrollment')).toEqual([[11, 'cancelled']])

    await wrapper.get('[data-testid="reject-enrollment-11"]').trigger('click')
    expect(wrapper.emitted('advance-enrollment')[1]).toEqual([11, 'rejected'])
  })

  it('blocks policy details with an empty CIC number', async () => {
    const submitted = [
      {
        id: 11,
        plot_id: 3,
        program: 'rice',
        season: 'wet',
        season_year: 2026,
        status: 'submitted_to_mao',
        pack_status: 'ready',
        notes: null,
      },
    ]
    const wrapper = mountTracker({ enrollments: submitted })

    await wrapper.get('[data-testid="record-cic-11"]').trigger('click')
    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('record-policy-details')).toBeFalsy()
    expect(wrapper.get('[data-testid="cic-error-11"]').exists()).toBe(true)
  })

  it('disables the pack buttons while a pack is being generated', () => {
    const wrapper = mountTracker({ isPackBusy: true })

    expect(wrapper.get('[data-testid="request-pack-7"]').attributes('disabled')).toBeDefined()
    expect(wrapper.get('[data-testid="request-pack-8"]').attributes('disabled')).toBeDefined()
  })
})
