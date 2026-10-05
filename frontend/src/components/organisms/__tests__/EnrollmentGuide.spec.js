import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n, { setInsuranceLocale } from '@/i18n'
import EnrollmentGuide from '../EnrollmentGuide.vue'

describe('EnrollmentGuide', () => {
  beforeEach(() => {
    setInsuranceLocale('en')
  })

  const plots = [{ id: 3, name: 'North Plot', calculated_area: 1.5 }]

  function mountGuide(props = {}) {
    return mount(EnrollmentGuide, {
      props: {
        profile: { rsbsa_number: 'RSBSA-1', rsbsa_status: 'registered' },
        enrollments: [],
        plots,
        ...props,
      },
      global: { plugins: [i18n] },
    })
  }

  it('marks the RSBSA step complete when the profile is registered', () => {
    const done = mountGuide().get('[data-testid="guide-step-rsbsa"]')
    expect(done.classes()).toContain('guide-step-done')

    const pending = mountGuide({
      profile: { rsbsa_number: null, rsbsa_status: 'not_registered' },
    }).get('[data-testid="guide-step-rsbsa"]')
    expect(pending.classes()).not.toContain('guide-step-done')
  })

  it('emits a validated enrollment payload', async () => {
    const wrapper = mountGuide()

    await wrapper.get('[data-testid="guide-plot"] select').setValue('3')
    await wrapper.get('[data-testid="guide-program"] select').setValue('corn')
    await wrapper.get('[data-testid="guide-season"] select').setValue('dry')
    await wrapper.get('[data-testid="guide-year"] input').setValue(2026)
    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('create-enrollment')).toEqual([
      [{ plot_id: 3, program: 'corn', season: 'dry', season_year: 2026, notes: '' }],
    ])
  })

  it('blocks submission outside the allowed year bounds', async () => {
    const wrapper = mountGuide()

    await wrapper.get('[data-testid="guide-plot"] select').setValue('3')
    await wrapper.get('[data-testid="guide-year"] input').setValue(2019)
    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('create-enrollment')).toBeFalsy()
    expect(wrapper.get('[data-testid="guide-error"]').exists()).toBe(true)
  })
})
