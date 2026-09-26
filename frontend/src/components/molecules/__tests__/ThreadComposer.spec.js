import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ThreadComposer from '../ThreadComposer.vue'

describe('ThreadComposer.vue limits', () => {
  function mountComposer() {
    return mount(ThreadComposer, {
      props: { categories: [], tags: [] },
    })
  }

  it('caps the title at 100 characters', () => {
    const wrapper = mountComposer()

    expect(wrapper.find('input[type="text"]').attributes('maxlength')).toBe('100')
  })

  it('caps the details at 5000 characters', () => {
    const wrapper = mountComposer()

    expect(wrapper.find('textarea').attributes('maxlength')).toBe('5000')
  })

  it('shows the live details character count', async () => {
    const wrapper = mountComposer()

    await wrapper.find('textarea').setValue('hello')

    expect(wrapper.text()).toContain('5/5000')
  })

  it('does not submit when details exceed 5000 characters', async () => {
    const wrapper = mountComposer()

    await wrapper.find('textarea').setValue('a'.repeat(5001))

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted()).not.toHaveProperty('submit')
    expect(wrapper.text()).toContain('5001/5000')
  })

  it('emits submit with the form when details are within the limit', async () => {
    const wrapper = mountComposer()

    await wrapper.find('input[type="text"]').setValue('A valid thread title')
    await wrapper.find('textarea').setValue('Valid details body')
    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')[0][0]).toMatchObject({
      title: 'A valid thread title',
      body: 'Valid details body',
    })
  })
})
