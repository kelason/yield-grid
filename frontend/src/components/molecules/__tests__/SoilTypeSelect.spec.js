import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import SoilTypeSelect from '../SoilTypeSelect.vue'

describe('SoilTypeSelect', () => {
  it('retains all soil values and emits the native selection with associated details', async () => {
    const wrapper = mount(SoilTypeSelect, { props: { modelValue: 'clay' } })
    expect(wrapper.get('label').attributes('for')).toBe(wrapper.get('select').attributes('id'))
    expect(
      wrapper
        .findAll('option')
        .map((option) => option.element.value)
        .filter(Boolean),
    ).toEqual(['clay', 'sandy', 'loamy', 'silt', 'peat', 'chalky'])
    await wrapper.get('select').setValue('loamy')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['loamy'])
    await wrapper.setProps({ modelValue: 'loamy' })
    expect(wrapper.get('summary').text()).toBe('About Loamy soil')
    expect(wrapper.get('img').attributes('src')).toBe('/images/soils/loamy.jpg')
  })
})
