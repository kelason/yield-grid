import { describe, it, expect, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import { setLocale } from '@/i18n'
import { API_DOCS_URL } from '@/constants/apiDocs'
import AppFooter from '../AppFooter.vue'

const mounted = []

afterEach(() => {
  mounted.splice(0).forEach((wrapper) => wrapper.unmount())
  setLocale('en')
})

async function mountFooter() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', component: { template: '<div />' } },
      { path: '/about', component: { template: '<div />' } },
      { path: '/contact', component: { template: '<div />' } },
    ],
  })
  await router.push('/')
  await router.isReady()
  const wrapper = mount(AppFooter, { global: { plugins: [router] } })
  mounted.push(wrapper)
  return wrapper
}

describe('AppFooter', () => {
  it('links About, Contact, then API docs in order', async () => {
    const nav = (await mountFooter()).find('nav[aria-label="Footer navigation"]')
    expect(nav.exists()).toBe(true)

    const links = nav.findAll('a')
    expect(links.map((link) => link.text())).toEqual(['About Us', 'Contact', 'API Docs'])
    expect(links[0].attributes('href')).toBe('/about')
    expect(links[1].attributes('href')).toBe('/contact')
  })

  it('opens the API docs on the API host in a new tab', async () => {
    const nav = (await mountFooter()).find('nav[aria-label="Footer navigation"]')
    const docs = nav.findAll('a').find((link) => link.text() === 'API Docs')

    expect(docs.attributes('href')).toBe(API_DOCS_URL)
    expect(docs.attributes('href')).toMatch(/\/docs$/)
    expect(docs.attributes('target')).toBe('_blank')
    expect(docs.attributes('rel')).toBe('noopener')
  })

  it('keeps the API docs label in every locale', async () => {
    const wrapper = await mountFooter()
    const nav = () => wrapper.find('nav[aria-label]')

    for (const locale of ['tl', 'ceb']) {
      setLocale(locale)
      await wrapper.vm.$nextTick()
      expect(nav().text()).toContain('API Docs')
    }
  })
})
