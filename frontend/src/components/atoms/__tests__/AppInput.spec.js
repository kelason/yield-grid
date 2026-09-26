import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import AppInput from '../AppInput.vue'

describe('AppInput.vue maxlength', () => {
  it('clamps typing past maxlength and emits the capped value', async () => {
    const wrapper = mount(AppInput, {
      props: { id: 'qty', type: 'number', maxlength: 4, modelValue: '' },
    })

    const input = wrapper.find('input')
    await input.setValue('12345')

    expect(input.element.value).toBe('1234')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['1234'])

    // Typing another char while at the cap keeps the display capped
    await input.setValue('12349')

    expect(input.element.value).toBe('1234')
  })

  it('leaves input untouched without maxlength', async () => {
    const wrapper = mount(AppInput, {
      props: { id: 'qty', type: 'number', modelValue: '' },
    })

    const input = wrapper.find('input')
    await input.setValue('123456789')

    expect(input.element.value).toBe('123456789')
  })
})
