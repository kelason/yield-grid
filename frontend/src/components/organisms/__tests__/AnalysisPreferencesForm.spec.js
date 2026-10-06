import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AnalysisPreferencesForm from '../AnalysisPreferencesForm.vue'

const taxonomy = {
  types: {
    vegetable: {
      label: 'Vegetables',
      subtypes: {
        fruiting: { label: 'Fruiting Vegetables', crops: ['tomato'], crop_count: 7 },
      },
    },
    fruit: {
      label: 'Fruits',
      subtypes: {
        citrus: { label: 'Citrus', crops: ['calamansi'], crop_count: 3 },
      },
    },
  },
  irrigation_levels: [
    { value: 'none', label: 'No irrigation (rainfed only)' },
    { value: 'reliable', label: 'Reliable irrigation' },
  ],
  goals: [
    { value: 'quick_cash', label: 'Quick cash turnover' },
    { value: 'max_profit', label: 'Maximize profit' },
  ],
}

function mountForm(props = {}) {
  return mount(AnalysisPreferencesForm, {
    props: { taxonomy, ...props },
  })
}

describe('AnalysisPreferencesForm.vue', () => {
  it('renders type and subtype options from the taxonomy', () => {
    const wrapper = mountForm()

    expect(wrapper.text()).toContain('Vegetables')
    expect(wrapper.text()).toContain('Fruiting Vegetables')
    expect(wrapper.text()).toContain('Citrus')
  })

  it('emits subtype selections as preferences', async () => {
    const wrapper = mountForm()
    const citrus = wrapper
      .findAll('[data-test="subtype-chip"]')
      .find((chip) => chip.text().includes('Citrus'))

    await citrus.trigger('click')

    expect(wrapper.emitted('update:preferences')[0][0]).toMatchObject({
      subtypes: ['citrus'],
    })
  })

  it('emits irrigation and goal selections', async () => {
    const wrapper = mountForm()

    await wrapper.find('[data-test="irrigation-select"] select').setValue('none')
    await wrapper.find('[data-test="goal-select"] select').setValue('quick_cash')

    const last = wrapper.emitted('update:preferences').at(-1)[0]
    expect(last).toMatchObject({ irrigation: 'none', goal: 'quick_cash' })
  })

  it('requests analysis with the current preferences', async () => {
    const wrapper = mountForm()
    const citrus = wrapper
      .findAll('[data-test="subtype-chip"]')
      .find((chip) => chip.text().includes('Citrus'))
    await citrus.trigger('click')

    await wrapper.find('[data-test="run-analysis"]').trigger('click')

    expect(wrapper.emitted('request-analysis')[0][0]).toMatchObject({
      subtypes: ['citrus'],
      irrigation: null,
      goal: null,
    })
  })

  it('renders safely without taxonomy data', () => {
    const wrapper = mountForm({ taxonomy: null })

    expect(wrapper.findAll('[data-test="subtype-chip"]')).toHaveLength(0)
    expect(wrapper.text()).toContain('Loading')
  })
})
