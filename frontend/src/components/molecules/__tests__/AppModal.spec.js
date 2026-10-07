import { describe, it, expect, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import AppModal from '../AppModal.vue'

const mounted = []
afterEach(() => mounted.splice(0).forEach((wrapper) => wrapper.unmount()))

describe('AppModal', () => {
  it('ignores a queued native close event when the dialog has already reopened', async () => {
    const wrapper = mount(AppModal, {
      props: { isOpen: true, title: 'Send message' },
      global: { stubs: { teleport: true } },
    })
    mounted.push(wrapper)
    const dialog = wrapper.get('dialog')
    // jsdom does not provide native modality; represent the reopened platform state.
    dialog.element.open = true
    await dialog.trigger('close')
    expect(wrapper.emitted('close')).toBeUndefined()
    dialog.element.open = false
    await dialog.trigger('close')
    expect(wrapper.emitted('close')).toHaveLength(1)
  })

  it('names the dialog and suppresses close requests while busy', async () => {
    const wrapper = mount(AppModal, {
      props: { isOpen: true, title: 'Edit address', busy: true },
      global: { stubs: { teleport: true } },
    })
    mounted.push(wrapper)
    const dialog = wrapper.get('dialog')
    const titleId = dialog.attributes('aria-labelledby')
    expect(wrapper.get(`#${titleId}`).text()).toBe('Edit address')
    await dialog.trigger('cancel')
    expect(wrapper.emitted('close')).toBeUndefined()
    await wrapper.setProps({ busy: false })
    await dialog.trigger('cancel')
    expect(wrapper.emitted('close')).toHaveLength(1)
  })
})
