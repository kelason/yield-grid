import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { setLocale } from '@/i18n'
import { API_DOCS_URL } from '@/constants/apiDocs'
import AppNavbar from '../AppNavbar.vue'

const mounted = []
let breakpointChanged

beforeEach(() => {
  vi.stubGlobal('matchMedia', () => ({
    matches: false,
    addEventListener: (_, listener) => {
      breakpointChanged = listener
    },
    removeEventListener: () => {},
  }))
})

afterEach(() => {
  mounted.splice(0).forEach((wrapper) => wrapper.unmount())
  vi.unstubAllGlobals()
  setLocale('en')
})

async function mountNavbar({ authenticated = false, role = 'farmer', userId = 7 } = {}) {
  const pinia = createPinia()
  setActivePinia(pinia)
  const authStore = useAuthStore()
  authStore.token = authenticated ? 'test-token' : null
  authStore.user = authenticated ? { id: userId, name: 'Cara', role } : null
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      ...[
        '/',
        '/features',
        '/how-it-works',
        '/documentation',
        '/documentation/:topic',
        '/about',
        '/contact',
        '/auth/login',
        '/auth/register',
        '/dashboard',
      ].map((path) => ({
        path,
        component: { template: '<div />' },
      })),
      {
        path: '/dashboard/users/:userId',
        name: 'user-profile',
        component: { template: '<div />' },
      },
    ],
  })
  await router.push('/')
  await router.isReady()
  const wrapper = mount(AppNavbar, {
    global: {
      plugins: [pinia, router],
      stubs: { teleport: true },
    },
  })
  mounted.push(wrapper)
  return wrapper
}

async function openMenu(wrapper) {
  await wrapper.find('button[aria-label="Open menu"]').trigger('click')
  return wrapper.find('nav[aria-label="Mobile navigation"]')
}

describe('AppNavbar public links', () => {
  it('links learning pages before About and external API docs', async () => {
    const nav = (await mountNavbar()).find('nav[aria-label="Main navigation"]')
    const links = nav.findAll('a')

    expect(links.map((link) => link.text())).toEqual([
      'Features',
      'How It Works',
      'Documentation',
      'About',
      'API Docs',
    ])

    const docs = links[4]
    expect(docs.attributes('href')).toBe(API_DOCS_URL)
    expect(docs.attributes('target')).toBe('_blank')
    expect(docs.attributes('rel')).toBe('noopener')
  })
})

describe('AppNavbar mobile menu', () => {
  it('links API docs after Contact and closes the menu on click', async () => {
    const wrapper = await mountNavbar()
    const nav = await openMenu(wrapper)
    const siteLinks = nav.findAll('a').slice(0, 7)

    expect(siteLinks.map((link) => link.text())).toEqual([
      'Home',
      'Features',
      'How It Works',
      'Documentation',
      'About',
      'Contact',
      'API Docs',
    ])

    const docs = siteLinks[6]
    expect(docs.attributes('href')).toBe(API_DOCS_URL)
    expect(docs.attributes('target')).toBe('_blank')
    await docs.trigger('click')
    await flushPromises()
    expect(wrapper.find('nav[aria-label="Mobile navigation"]').exists()).toBe(false)
  })

  it('separates site links from auth actions with dividers', async () => {
    const nav = await openMenu(await mountNavbar())
    expect(nav.exists()).toBe(true)

    const dividers = nav.findAll('hr')
    expect(dividers).toHaveLength(2)
    dividers.forEach((divider) => {
      expect(divider.attributes('aria-hidden')).toBe('true')
      expect(divider.classes()).toContain('border-stone-200')
    })

    const text = nav.text()
    expect(text.indexOf('Home')).toBeGreaterThanOrEqual(0)
    expect(text.indexOf('Log in')).toBeGreaterThan(text.indexOf('Contact'))

    expect(nav.find('a[aria-current="page"]').text()).toBe('Home')
  })

  it('renders guest auth actions as full-width buttons', async () => {
    const nav = await openMenu(await mountNavbar())
    const links = nav.findAll('a')
    const login = links.find((link) => link.text() === 'Log in')
    expect(login).toBeTruthy()
    expect(login.classes()).toContain('justify-center')
    expect(login.classes()).toContain('border-moss-300')
    expect(nav.text()).toContain('Get Started')
    expect(nav.find('[data-testid="language-menu"]').exists()).toBe(true)
  })

  it('renders dashboard and logout actions when authenticated', async () => {
    const nav = await openMenu(await mountNavbar({ authenticated: true }))
    expect(nav.text()).toContain('Dashboard')
    expect(nav.text()).toContain('Log out')
    expect(nav.text()).not.toContain('Get Started')
  })

  it.each(['farmer', 'buyer'])(
    'exposes the %s own profile and address management',
    async (role) => {
      const nav = await openMenu(await mountNavbar({ authenticated: true, role }))
      expect(nav.get('a[aria-label="Cara profile"]').attributes('href')).toBe('/dashboard/users/7')
    },
  )

  it('allows authentication state to load without a user record', async () => {
    const wrapper = await mountNavbar({ authenticated: true })
    useAuthStore().user = null
    const nav = await openMenu(wrapper)
    expect(nav.text()).toContain('Dashboard')
    expect(nav.find('a[href^="/dashboard/users/"]').exists()).toBe(false)
  })

  it('closes when choosing the current route', async () => {
    const wrapper = await mountNavbar()
    const nav = await openMenu(wrapper)
    await nav.get('a[aria-current="page"]').trigger('click')
    await flushPromises()
    expect(wrapper.find('nav[aria-label="Mobile navigation"]').exists()).toBe(false)
  })

  it('closes when switching to a desktop viewport', async () => {
    const wrapper = await mountNavbar()
    await openMenu(wrapper)
    breakpointChanged?.({ matches: true })
    await flushPromises()
    expect(wrapper.find('nav[aria-label="Mobile navigation"]').exists()).toBe(false)
  })

  it('keeps the menu open while choosing a locale', async () => {
    const wrapper = await mountNavbar()
    const nav = await openMenu(wrapper)
    await nav.get('[data-testid="language-menu"]').trigger('click')
    await nav.get('[data-testid="locale-ceb"]').trigger('click')
    expect(wrapper.find('nav[aria-label="Mobile nga nabigasyon"]').exists()).toBe(true)
    expect(wrapper.find('nav[aria-label="Mobile nga nabigasyon"]').text()).toContain('Mahitungod')
  })

  it('closes navigation before asking for logout confirmation', async () => {
    const wrapper = await mountNavbar({ authenticated: true })
    const nav = await openMenu(wrapper)
    await nav
      .findAll('button')
      .find((button) => button.text() === 'Log out')
      .trigger('click')
    expect(wrapper.find('nav[aria-label="Mobile navigation"]').exists()).toBe(false)
    expect(wrapper.findAll('dialog h2').map((title) => title.text())).toEqual(['Log out?'])
    expect(useAuthStore().isAuthenticated).toBe(true)
  })
})

it('marks Documentation current for a topic without marking other public links', async () => {
  const wrapper = await mountNavbar()
  await wrapper.vm.$router.push('/documentation/getting-started')
  await flushPromises()
  expect(wrapper.get('nav[aria-label="Main navigation"] a[aria-current="page"]').text()).toBe(
    'Documentation',
  )
})

it('uses the drawer when measured desktop content cannot fit and cleans up its observer', async () => {
  let measure
  const disconnect = vi.fn()
  vi.stubGlobal('matchMedia', () => ({
    matches: true,
    addEventListener() {},
    removeEventListener() {},
  }))
  vi.stubGlobal(
    'ResizeObserver',
    class {
      constructor(callback) {
        measure = callback
      }
      observe() {}
      disconnect = disconnect
    },
  )
  const wrapper = await mountNavbar()
  const row = wrapper.get('header > div').element
  Object.defineProperty(row, 'clientWidth', { value: 600, configurable: true })
  vi.spyOn(row.children[0], 'getBoundingClientRect').mockReturnValue({ width: 200 })
  vi.spyOn(row.children[1], 'getBoundingClientRect').mockReturnValue({ width: 400 })
  vi.spyOn(row.children[2], 'getBoundingClientRect').mockReturnValue({ width: 300 })
  measure?.()
  await flushPromises()
  expect(wrapper.get('button[aria-label="Open menu"]').classes()).not.toContain('xl:hidden')
  expect(wrapper.get('nav[aria-label="Main navigation"]').classes()).toContain('xl:invisible')
  await openMenu(wrapper)
  Object.defineProperty(row, 'clientWidth', { value: 1400 })
  measure?.()
  await flushPromises()
  expect(wrapper.find('nav[aria-label="Mobile navigation"]').exists()).toBe(true)
  expect(wrapper.get('button[aria-label="Open menu"]').classes()).not.toContain('xl:hidden')
  await wrapper.get('button[aria-label="Close dialog"]').trigger('click')
  expect(wrapper.get('button[aria-label="Open menu"]').classes()).toContain('xl:hidden')
  wrapper.unmount()
  expect(disconnect).toHaveBeenCalledOnce()
})
