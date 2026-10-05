import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n, { setInsuranceLocale } from '@/i18n'
import ClaimForm from '../ClaimForm.vue'

describe('ClaimForm', () => {
  beforeEach(() => {
    setInsuranceLocale('en')
  })

  function mountForm() {
    return mount(ClaimForm, {
      props: { isSaving: false },
      global: { plugins: [i18n] },
    })
  }

  it('emits a validated claim payload', async () => {
    const wrapper = mountForm()

    await wrapper.get('[data-testid="claim-loss-date"] input').setValue('2026-08-15')
    await wrapper.get('[data-testid="claim-cause"] select').setValue('flood')
    await wrapper.get('[data-testid="claim-description"]').setValue('Submerged for three days')
    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('submit-claim')).toEqual([
      [{ loss_date: '2026-08-15', cause: 'flood', description: 'Submerged for three days' }],
    ])
  })

  it('blocks submission without a loss date', async () => {
    const wrapper = mountForm()

    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('submit-claim')).toBeFalsy()
    expect(wrapper.get('[data-testid="claim-error"]').exists()).toBe(true)
  })

  it('blocks a future loss date', async () => {
    const wrapper = mountForm()
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)

    expect(wrapper.get('[data-testid="claim-loss-date"] input').attributes('max')).toBe(
      new Date().toLocaleDateString('en-CA'),
    )

    await wrapper
      .get('[data-testid="claim-loss-date"] input')
      .setValue(tomorrow.toLocaleDateString('en-CA'))
    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('submit-claim')).toBeFalsy()
    expect(wrapper.get('[data-testid="claim-error"]').exists()).toBe(true)
  })

  it('allows descriptions up to 5000 characters', async () => {
    const wrapper = mountForm()

    expect(wrapper.get('[data-testid="claim-description"]').attributes('maxlength')).toBe('5000')
    expect(wrapper.text()).toContain('/ 5000')

    await wrapper.get('[data-testid="claim-loss-date"] input').setValue('2026-08-15')
    await wrapper.get('[data-testid="claim-description"]').setValue('d'.repeat(5001))
    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('submit-claim')).toBeFalsy()
    expect(wrapper.get('[data-testid="claim-error"]').exists()).toBe(true)
  })

  it('emits cancel when the farmer backs out', async () => {
    const wrapper = mountForm()

    await wrapper.get('[data-testid="claim-cancel"]').trigger('click')

    expect(wrapper.emitted('cancel')).toBeTruthy()
  })
})
