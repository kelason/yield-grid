import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import { nextTick } from 'vue'
import i18n, { setLocale } from '@/i18n'
import { useNotificationStore } from '@/stores/notificationStore'
import ToastContainer from '../ToastContainer.vue'

describe('ToastContainer key rendering', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    setLocale('en')
  })

  function mountContainer() {
    return mount(ToastContainer, { global: { plugins: [i18n] } })
  }

  it('renders keyed toasts in the active language and re-renders on switch', async () => {
    const store = useNotificationStore()
    store.addNotification({ type: 'info', messageKey: 'common.actions.retry', duration: 0 })
    const wrapper = mountContainer()
    await flushPromises()

    expect(wrapper.text()).toContain('Try again')

    setLocale('ceb')
    await nextTick()
    expect(wrapper.text()).toContain('Sulayi pag-usab')

    setLocale('tl')
    await nextTick()
    expect(wrapper.text()).toContain('Subukan ulit')
    expect(store.notifications).toHaveLength(1)
  })

  it('renders legacy plain messages unchanged', async () => {
    const store = useNotificationStore()
    store.error('Something broke')
    const wrapper = mountContainer()
    await flushPromises()

    expect(wrapper.text()).toContain('Something broke')
  })
})
