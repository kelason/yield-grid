import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppTextarea from '../AppTextarea.vue'

describe('AppTextarea', () => {
  it('caps pasted text and emits the displayed string', async () => {
    const wrapper = mount(AppTextarea, { props: { id: 'notes', maxlength: 4 } })
    await wrapper.get('textarea').setValue('longer')
    expect(wrapper.element.value).toBe('long')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['long'])
    await wrapper.setProps({ modelValue: 'new' })
    expect(wrapper.element.value).toBe('new')
  })
})
