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

  it('shows the live character count', async () => {
    const wrapper = mount(ChatComposer)

    await wrapper.find('textarea').setValue('one two three')

    expect(wrapper.text()).toContain('13/5000')
  })

  it('caps input at the server-side character limit', () => {
    const wrapper = mount(ChatComposer)

    expect(wrapper.find('textarea').attributes('maxlength')).toBe('5000')
  })

  it('does not emit send when the message exceeds 5000 characters', async () => {
    const wrapper = mount(ChatComposer)

    await wrapper.find('textarea').setValue('a'.repeat(5001))

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted()).not.toHaveProperty('send')
    expect(wrapper.text()).toContain('5001/5000')
  })
})
