import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SoilTypeSelect from './SoilTypeSelect.vue'

describe('SoilTypeSelect.vue', () => {
  it('renders placeholder and label correctly when no value is selected', () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: '',
        label: 'Soil Type',
      },
    })

    expect(wrapper.text()).toContain('Soil Type')
    expect(wrapper.text()).toContain('Select soil type...')
    expect(wrapper.text()).toContain('Select 1 type')
  })

  it('renders selected soil details when modelValue is provided', () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: 'loamy',
      },
    })

    expect(wrapper.text()).toContain('Loamy')
    expect(wrapper.text()).toContain('Optimal & Fertile')

    const img = wrapper.find('button[aria-haspopup="listbox"] img')
    expect(img.exists()).toBe(true)
    expect(img.attributes('src')).toBe('/images/soils/loamy.jpg')
  })

  it('opens dropdown menu on trigger click and lists all 6 soil types', async () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: '',
      },
    })

    const triggerBtn = wrapper.find('button[aria-haspopup="listbox"]')
    await triggerBtn.trigger('click')

    const options = wrapper.findAll('[role="option"]')
    expect(options).toHaveLength(6)

    const expectedSoils = ['Clay', 'Sandy', 'Loamy', 'Silt', 'Peat', 'Chalky']
    expectedSoils.forEach((soil) => {
      expect(wrapper.text()).toContain(soil)
    })
  })

  it('emits update:modelValue and closes dropdown when an option is chosen', async () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: '',
      },
    })

    const triggerBtn = wrapper.find('button[aria-haspopup="listbox"]')
    await triggerBtn.trigger('click')

    const loamyOption = wrapper.findAll('[role="option"]').find((el) => el.text().includes('Loamy'))

    expect(loamyOption).toBeDefined()
    await loamyOption.trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['loamy'])
    expect(wrapper.find('[role="listbox"]').exists()).toBe(false)
  })

  it('shows tooltip description when question mark button is hovered', async () => {
    const wrapper = mount(SoilTypeSelect, {
      props: {
        modelValue: '',
      },
    })

    // Open dropdown
    await wrapper.find('button[aria-haspopup="listbox"]').trigger('click')

    const clayOption = wrapper.findAll('[role="option"]').find((el) => el.text().includes('Clay'))

    const qMarkBtn = clayOption.find('button[aria-label="Soil details"]')
    expect(qMarkBtn.exists()).toBe(true)

    // Hover question mark
    await qMarkBtn.trigger('mouseenter')

    expect(wrapper.text()).toContain('Heavy, nutrient-rich soil')
    expect(wrapper.text()).toContain('Rice, broccoli, cabbage')
  })
})
