import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SoilTypeSelect from './SoilTypeSelect.vue'

describe('SoilTypeSelect.vue', () => {
  it('renders label correctly', () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: '',
      },
    })

    expect(wrapper.text()).toContain('Soil Type')
    expect(wrapper.text()).toContain('Select 1 type')
  })

  it('renders selected state correctly when modelValue is provided', () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: 'loamy',
      },
    })

    // The loamy option should have the selected classes
    const loamyOption = wrapper.findAll('[role="radio"]').find((el) => el.text().includes('Loamy'))
    expect(loamyOption.exists()).toBe(true)
    expect(loamyOption.attributes('aria-checked')).toBe('true')
  })

  it('lists all 6 soil types in the grid', () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: '',
      },
    })

    const options = wrapper.findAll('[role="radio"]')
    expect(options).toHaveLength(6)

    const labels = options.map((o) => o.text())
    expect(labels.some((l) => l.includes('Clay'))).toBe(true)
    expect(labels.some((l) => l.includes('Sandy'))).toBe(true)
    expect(labels.some((l) => l.includes('Loamy'))).toBe(true)
    expect(labels.some((l) => l.includes('Silt'))).toBe(true)
    expect(labels.some((l) => l.includes('Peat'))).toBe(true)
    expect(labels.some((l) => l.includes('Chalky'))).toBe(true)
  })

  it('emits update:modelValue when an option is chosen', async () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: '',
      },
    })

    const loamyOption = wrapper.findAll('[role="radio"]').find((el) => el.text().includes('Loamy'))
    await loamyOption.trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['loamy'])
  })

  it('shows tooltip description when question mark button is hovered', async () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: '',
      },
    })

    const clayOption = wrapper.findAll('[role="radio"]').find((el) => el.text().includes('Clay'))

    const qMarkBtn = clayOption.find('button[aria-label="Soil details"]')
    expect(qMarkBtn.exists()).toBe(true)

    // Hover question mark
    await qMarkBtn.trigger('mouseenter')

    expect(wrapper.text()).toContain('Heavy, nutrient-rich soil')
    expect(wrapper.text()).toContain('Palay (Rice), Kangkong')
  })
})
