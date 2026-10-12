import { afterEach, describe, expect, it } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createHead, renderDOMHead } from '@unhead/vue/client'
import { nextTick } from 'vue'
import i18n, { setLocale } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import DocumentationPage from '../DocumentationPage.vue'

let wrapper
afterEach(() => {
  wrapper?.unmount()
  setLocale('en')
})

async function openGuide(path, role) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/documentation', name: 'documentation', component: DocumentationPage },
      { path: '/documentation/:topic', name: 'documentation-topic', component: DocumentationPage },
      { path: '/contact', component: { template: '<div />' } },
      { path: '/auth/register', component: { template: '<div />' } },
      { path: '/auth/login', component: { template: '<div />' } },
    ],
  })
  const pinia = createPinia()
  if (role) Object.assign(useAuthStore(pinia), { token: 'test', user: { id: 9, role } })
  const head = createHead()
  await router.push(path)
  await router.isReady()
  wrapper = mount(DocumentationPage, { global: { plugins: [router, pinia, head] } })
  await flushPromises()
  return { router, head }
}

describe('DocumentationPage', () => {
  it('shows an index with working topic destinations', async () => {
    await openGuide('/documentation')
    expect(wrapper.findAll('h1')).toHaveLength(1)
    expect(wrapper.find('a[href="/documentation/getting-started"]').exists()).toBe(true)
  })

  it('recovers from unknown guide URLs without rendering a missing article', async () => {
    await openGuide('/documentation/not-a-guide')
    expect(wrapper.find('h1').text()).toBe('Guide not found')
    expect(wrapper.find('a[href="/documentation"]').exists()).toBe(true)
  })

  it('updates article and metadata when topic and language change without a remount', async () => {
    const { router, head } = await openGuide('/documentation/getting-started')
    await router.push('/documentation/profile-addresses-language')
    setLocale('tl')
    await nextTick()
    renderDOMHead(head)
    expect(wrapper.find('h1').text()).toBe(i18n.global.t('guides.profile-addresses-language.title'))
    expect(document.title).toContain(wrapper.find('h1').text())
    expect(wrapper.findAll('h1')).toHaveLength(1)
  })

  it('keeps help readable for a signed-in member of either role', async () => {
    await openGuide('/documentation/getting-started', 'buyer')
    expect(wrapper.find('#verify-email').exists()).toBe(true)
    expect(wrapper.text()).not.toContain('guides.getting-started')
  })
})
