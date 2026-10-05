import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n, { setInsuranceLocale } from '@/i18n'
import RsbsaPanel from '../RsbsaPanel.vue'

describe('RsbsaPanel', () => {
  beforeEach(() => {
    setInsuranceLocale('en')
  })

  function mountPanel(profile = { rsbsa_number: null, rsbsa_status: 'not_registered' }) {
    return mount(RsbsaPanel, {
      props: { profile, isSaving: false },
      global: { plugins: [i18n] },
    })
  }

  it('emits the RSBSA payload on save', async () => {
    const wrapper = mountPanel()

    await wrapper.get('[data-testid="rsbsa-number"] input').setValue('RSBSA-1')
    await wrapper.get('[data-testid="rsbsa-status"] select').setValue('registered')
    await wrapper.get('[data-testid="rsbsa-save"]').trigger('click')

    expect(wrapper.emitted('save-profile')).toEqual([
      [{ rsbsa_number: 'RSBSA-1', rsbsa_status: 'registered' }],
    ])
  })

  it('shows MAO guidance when unregistered', () => {
    const wrapper = mountPanel()

    expect(wrapper.text()).toContain('Municipal Agriculturist Office')
  })
})
