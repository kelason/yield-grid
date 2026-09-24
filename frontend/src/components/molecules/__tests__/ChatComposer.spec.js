import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ChatComposer from '../ChatComposer.vue'

describe('ChatComposer.vue', () => {
  it('emits send event with message when submitted', async () => {
    const wrapper = mount(ChatComposer)

    const textarea = wrapper.find('textarea')
    await textarea.setValue('Hello world')

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted()).toHaveProperty('send')
    expect(wrapper.emitted('send')[0]).toEqual(['Hello world'])
  })

  it('does not emit send if message is empty', async () => {
    const wrapper = mount(ChatComposer)

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted()).not.toHaveProperty('send')
  })

  it('clears the textarea after sending', async () => {
    const wrapper = mount(ChatComposer)

    const textarea = wrapper.find('textarea')
    await textarea.setValue('Test message')

    await wrapper.find('form').trigger('submit.prevent')

    expect(textarea.element.value).toBe('')
  })
})
