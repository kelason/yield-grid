import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SortSelect from '../SortSelect.vue'

describe('SortSelect', () => {
  it('associates the sort label with the native select and preserves string values', async () => {
    const wrapper = mount(SortSelect, {
      props: {
        id: 'sort',
        label: 'Sort by',
        modelValue: 'newest',
        options: [{ value: 'price', label: 'Price' }],
      },
    })
    expect(wrapper.get('label').attributes('for')).toBe('sort')
    await wrapper.get('select').setValue('price')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['price'])
  })
})
