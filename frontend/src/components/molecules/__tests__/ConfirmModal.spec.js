import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ConfirmModal from '../ConfirmModal.vue'

describe('ConfirmModal', () => {
  it('blocks confirmation and dismissal until the mutation settles', async () => {
    const wrapper = mount(ConfirmModal, {
      props: { isOpen: true, title: 'Delete farm', message: 'Delete this farm?', loading: true },
      global: { stubs: { teleport: true } },
    })
    expect(wrapper.get('dialog').attributes('aria-busy')).toBe('true')
    const buttons = wrapper.findAll('button')
    expect(buttons.every((button) => button.element.disabled)).toBe(true)
    await wrapper.get('dialog').trigger('cancel')
    expect(wrapper.emitted('cancel')).toBeUndefined()
    await wrapper.setProps({ loading: false })
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Confirm')
      .trigger('click')
    expect(wrapper.emitted('confirm')).toHaveLength(1)
    wrapper.unmount()
  })
})
