import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import PublishContractForm from '../PublishContractForm.vue'
import AppInput from '@/components/atoms/AppInput.vue'

describe('PublishContractForm', () => {
  const recommendation = { id: 1, crop_name: 'Rice', projected_yield: '2000 kg' }

  function mountForm(props = {}) {
    return mount(PublishContractForm, {
      props: { recommendation, loading: false, errors: {}, ...props },
      global: { stubs: { PriceGuideHint: true } },
    })
  }

  it('renders every field with shared AppInput atoms like post-harvest', () => {
    const wrapper = mountForm()

    for (const label of [
      'Listing Title',
      'Description',
      'Quantity (kg)',
      'Price per kg (₱)',
      'Estimated Harvest Date',
      'Listing Expiry Date',
    ]) {
      expect(wrapper.text()).toContain(label)
    }

    expect(wrapper.findAllComponents(AppInput).length).toBe(5)
  })

  it('emits publish with the form data on submit', async () => {
    const wrapper = mountForm()

    await wrapper.find('#title').setValue('Rice forward contract')
    await wrapper.find('#quantity').setValue('500')
    await wrapper.find('#price').setValue('25')
    await wrapper.find('form').trigger('submit')

    expect(wrapper.emitted('publish')).toHaveLength(1)
    const payload = wrapper.emitted('publish')[0][0]
    expect(payload.title).toBe('Rice forward contract')
    // String-compared: raw v-model and AppInput agree in browsers (strings);
    // VTU coerces number inputs for raw v-model only.
    expect(String(payload.quantity_kg)).toBe('500')
    expect(String(payload.price_per_kg)).toBe('25')
  })

  it('displays backend validation errors under the matching fields', () => {
    const wrapper = mountForm({
      errors: {
        title: ['Title is required.'],
        quantity_kg: ['Please enter a quantity greater than zero.'],
      },
    })

    expect(wrapper.text()).toContain('Title is required.')
    expect(wrapper.text()).toContain('Please enter a quantity greater than zero.')
  })

  it('emits cancel without publishing', async () => {
    const wrapper = mountForm()

    await wrapper.find('button[type="button"]').trigger('click')

    expect(wrapper.emitted('cancel')).toHaveLength(1)
    expect(wrapper.emitted('publish')).toBeUndefined()
  })

  it('bounds numeric inputs on both ends', () => {
    const wrapper = mountForm()

    const quantity = wrapper.find('#quantity').attributes()
    expect(quantity.min).toBe('1')
    expect(quantity.max).toBe('99999999')

    const price = wrapper.find('#price').attributes()
    expect(price.min).toBe('0.01')
    expect(price.max).toBe('99999999')
  })
})
