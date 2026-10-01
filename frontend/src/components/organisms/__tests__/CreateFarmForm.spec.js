import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import CreateFarmForm from '../CreateFarmForm.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'

describe('CreateFarmForm.vue', () => {
  async function fillValid(wrapper) {
    await wrapper.find('#farm-name').setValue('Green Acres')
    await wrapper.find('#farm-city').setValue('Springfield')
    await wrapper.find('#farm-area').setValue(150.5)
  }

  it('opens the confirmation modal and emits submit only after confirm', async () => {
    const wrapper = mount(CreateFarmForm)
    await fillValid(wrapper)

    await wrapper.find('form').trigger('submit.prevent')
    expect(wrapper.findComponent(ConfirmModal).props('isOpen')).toBe(true)
    expect(wrapper.emitted('submit')).toBeUndefined()

    await wrapper.findComponent(ConfirmModal).vm.$emit('confirm')

    expect(wrapper.emitted('submit')).toHaveLength(1)
    expect(wrapper.emitted('submit')[0][0]).toMatchObject({
      name: 'Green Acres',
      city: 'Springfield',
    })
  })

  it('blocks submit when the name is missing', async () => {
    const wrapper = mount(CreateFarmForm)
    await wrapper.find('#farm-city').setValue('Springfield')

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.html()).toContain('Please enter a farm name.')
    expect(wrapper.emitted('submit')).toBeUndefined()
  })

  it('blocks total area outside 0..1000000', async () => {
    const wrapper = mount(CreateFarmForm)
    await wrapper.find('#farm-name').setValue('Green Acres')
    await wrapper.find('#farm-area').setValue(-5)

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.html()).toContain('must be between 0 and 1000000')
    expect(wrapper.emitted('submit')).toBeUndefined()
  })
})
