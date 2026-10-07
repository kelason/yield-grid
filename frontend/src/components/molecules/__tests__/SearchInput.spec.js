import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SearchInput from '../SearchInput.vue'

describe('SearchInput', () => {
  it('associates its accessible label with the input and emits search text', async () => {
    const wrapper = mount(SearchInput, {
      props: { id: 'crop', label: 'Crop search', modelValue: '' },
    })
    expect(wrapper.get('label').attributes('for')).toBe('crop')
    await wrapper.get('input').setValue('rice')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['rice'])
  })
})
