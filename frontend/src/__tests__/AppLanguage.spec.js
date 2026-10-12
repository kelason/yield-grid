import { afterEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'
import { mount } from '@vue/test-utils'
import { createHead, renderDOMHead } from '@unhead/vue/client'
import { createPinia } from 'pinia'
import { setLocale } from '@/i18n'
import App from '../App.vue'

let wrapper
afterEach(() => {
  wrapper?.unmount()
  setLocale('en')
})

describe('document language', () => {
  it('uses the current locale initially and after changes, ignoring unsupported input', async () => {
    const head = createHead()
    setLocale('tl')
    wrapper = mount(App, {
      global: { plugins: [head, createPinia()], stubs: { RouterView: true, ToastContainer: true } },
    })
    renderDOMHead(head)
    expect(document.documentElement.lang).toBe('tl')
    setLocale('ceb')
    await nextTick()
    renderDOMHead(head)
    expect(document.documentElement.lang).toBe('ceb')
    setLocale('es')
    await nextTick()
    renderDOMHead(head)
    expect(document.documentElement.lang).toBe('ceb')
  })
})
