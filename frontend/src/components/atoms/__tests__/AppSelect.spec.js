import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppSelect from '../AppSelect.vue'

function mountSelect(props = {}, slots = {}) {
  return mount(AppSelect, {
    props: { id: 'crop-select', modelValue: '', ...props },
    slots,
  })
}

describe('AppSelect.vue', () => {
  it('renders options with a label and placeholder', () => {
    const wrapper = mountSelect({
      label: 'Crop',
      options: [
        { value: 'a', label: 'Alpha' },
        { value: 'b', label: 'Beta' },
      ],
    })

    expect(wrapper.text()).toContain('Crop')
    expect(wrapper.find('select').text()).toContain('Select an option')
    expect(wrapper.find('select').text()).toContain('Alpha')
  })

  it('renders slotted options without forcing a label', () => {
    const wrapper = mountSelect(
      {},
      { default: '<option value="a">Alpha</option><option value="b">Beta</option>' },
    )

    expect(wrapper.find('select').text()).toContain('Alpha')
    expect(wrapper.find('select').text()).not.toContain('Select an option')
  })

  it('emits the selected value on change', async () => {
    const wrapper = mountSelect({ options: [{ value: 'b', label: 'Beta' }] })

    await wrapper.find('select').setValue('b')

    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['b'])
  })

  it('binds id and disabled', () => {
    const wrapper = mountSelect({ disabled: true, options: [] })
    const select = wrapper.find('select')

    expect(select.attributes('id')).toBe('crop-select')
    expect(select.attributes('disabled')).toBeDefined()
  })

  it('connects validation feedback to the select', () => {
    const wrapper = mountSelect({ error: 'Choose a crop', options: [] })
    expect(wrapper.get('select').attributes('aria-invalid')).toBe('true')
    expect(wrapper.get('select').attributes('aria-describedby')).toContain('crop-select-error')
    expect(wrapper.get('#crop-select-error').text()).toBe('Choose a crop')
  })
})
