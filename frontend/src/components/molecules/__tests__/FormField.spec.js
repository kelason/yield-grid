import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import FormField from '../FormField.vue'

describe('FormField labelSuffix', () => {
  it('renders label suffix content next to the label', () => {
    const wrapper = mount(FormField, {
      props: { id: 'price', label: 'Target price per kg (₱)' },
      slots: { labelSuffix: '<button class="suffix-stub">i</button>' },
    })

    expect(wrapper.find('label[for="price"]').exists()).toBe(true)
    expect(wrapper.find('.suffix-stub').exists()).toBe(true)
  })

  it('renders no suffix when the slot is not provided', () => {
    const wrapper = mount(FormField, {
      props: { id: 'price', label: 'Target price per kg (₱)' },
    })

    expect(wrapper.find('label[for="price"]').text()).toContain('Target price per kg (₱)')
    expect(wrapper.find('.suffix-stub').exists()).toBe(false)
  })
})
