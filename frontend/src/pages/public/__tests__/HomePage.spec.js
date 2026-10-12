import { afterEach, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createHead, renderDOMHead } from '@unhead/vue/client'
import { nextTick } from 'vue'
import i18n, { setLocale } from '@/i18n'
import HomePage from '../HomePage.vue'
let wrapper
afterEach(() => {
  wrapper?.unmount()
  setLocale('en')
})
it('explains purpose and vision with source links while preserving responsive preloads', async () => {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/', component: HomePage }],
  })
  await router.push('/')
  const head = createHead()
  wrapper = mount(HomePage, { global: { plugins: [router, head] } })
  expect(wrapper.find('h1').text()).toBe('Practical tools. Stronger farming futures.')
  expect(wrapper.findAll('a[href^="https://"]')).toHaveLength(3)
  expect(wrapper.find('a[href="/how-it-works#farmers"]').exists()).toBe(true)
  expect(wrapper.find('a[href="/how-it-works#buyers"]').exists()).toBe(true)
  expect(wrapper.text()).toContain(i18n.global.t('public.home.vision'))
  setLocale('ceb')
  await nextTick()
  renderDOMHead(head)
  expect(document.title).toBe(i18n.global.t('public.home.meta_title'))
  expect(document.head.querySelectorAll('link[rel="preload"][as="image"]')).toHaveLength(2)
})
