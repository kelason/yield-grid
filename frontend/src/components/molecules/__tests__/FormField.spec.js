import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import { defineComponent, ref, nextTick } from 'vue'
import FormField from '../FormField.vue'

describe('FormField labelSuffix', () => {
  it('updates forwarded native attributes when its parent changes them', async () => {
    const host = mount(
      defineComponent({
        components: { FormField },
        setup: () => ({ step: ref('0.01') }),
        template: '<FormField id="price" label="Price" :step="step" />',
      }),
    )
    host.vm.step = '0.1'
    await nextTick()
    expect(host.get('input').attributes('step')).toBe('0.1')
  })
  it('forwards native control attributes while preserving wrapper layout', () => {
    const wrapper = mount(FormField, {
      props: {
        id: 'quantity',
        label: 'Quantity',
        disabled: true,
        error: 'Required',
        hint: 'In kg',
      },
      attrs: { class: 'sm:col-span-2', step: '0.01', autocomplete: 'off', name: 'quantity' },
    })
    const input = wrapper.get('input')
    expect(input.element.disabled).toBe(true)
    expect(input.attributes('step')).toBe('0.01')
    expect(input.attributes('autocomplete')).toBe('off')
    expect(input.attributes('name')).toBe('quantity')
    expect(wrapper.classes()).toContain('sm:col-span-2')
    expect(input.classes()).not.toContain('sm:col-span-2')
    expect(input.attributes('aria-invalid')).toBe('true')
    expect(input.attributes('aria-describedby')).toContain('quantity-error')
    expect(wrapper.get('#quantity-error').text()).toBe('Required')
  })

  it('uses a bounded textarea with an associated counter for multiline fields', async () => {
    const wrapper = mount(FormField, {
      props: { id: 'message', label: 'Message', multiline: true, maxlength: 4, modelValue: '' },
    })
    await wrapper.get('textarea').setValue('hello')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['hell'])
    await wrapper.setProps({ modelValue: 'hell' })
    expect(wrapper.get('#message-counter').text()).toBe('4 / 4')
  })

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
