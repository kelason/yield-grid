import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import AppSidebar from '../AppSidebar.vue'

const mockRoute = { name: 'farmer-dashboard', path: '/dashboard' }

vi.mock('vue-router', () => ({
  RouterLink: {
    name: 'RouterLink',
    template: '<a><slot /></a>',
    props: ['to'],
  },
  useRoute: () => mockRoute,
}))

vi.mock('../../stores/chatStore', () => ({
  useChatStore: () => ({ totalUnread: 0 }),
}))

function mountSidebar({ role = 'farmer', verified = true } = {}) {
  setActivePinia(createPinia())
  const authStore = useAuthStore()
  authStore.user = {
    id: 1,
    name: 'Test Farmer',
    role,
    email_verified_at: verified ? '2026-01-01T00:00:00Z' : null,
  }
  return mount(AppSidebar, {
    global: {
      stubs: { AppLogo: true, UnreadBadge: true },
    },
  })
}

describe('AppSidebar farmer groups', () => {
  beforeEach(() => {
    mockRoute.name = 'farmer-dashboard'
    mockRoute.path = '/dashboard'
  })

  it('renders related farmer menus as collapsed groups', () => {
    const wrapper = mountSidebar()
    const text = wrapper.text()
    expect(text).toContain('Farm')
    expect(text).toContain('Sell Harvest')
    expect(text).toContain('Buyer Demands')
    expect(text).toContain('Overview')
    expect(text).toContain('Chat')

    const buttons = wrapper.findAll('button[aria-expanded]')
    expect(buttons).toHaveLength(3)
    buttons.forEach((button) => {
      expect(button.attributes('aria-expanded')).toBe('false')
    })
  })

  it('names its collapse control and collapsed navigation links', async () => {
    const wrapper = mountSidebar()
    const collapse = wrapper.find('button[aria-label="Collapse navigation"]')
    expect(collapse.exists()).toBe(true)
    await collapse.trigger('click')
    expect(wrapper.find('a[aria-label="Overview"]').exists()).toBe(true)
  })

  it('expands a group when its header is clicked', async () => {
    const wrapper = mountSidebar()
    const header = wrapper
      .findAll('button[aria-expanded]')
      .find((button) => button.text().includes('Buyer Demands'))

    await header.trigger('click')
    expect(header.attributes('aria-expanded')).toBe('true')

    const text = wrapper.text()
    expect(text).toContain('Crop Demands')
    expect(text).toContain('My Offers')
  })

  it('auto-expands the group containing the active route', async () => {
    mockRoute.name = 'farmer-offers'
    mockRoute.path = '/dashboard/farmer/offers'
    const wrapper = mountSidebar()
    await flushPromises()

    const header = wrapper
      .findAll('button[aria-expanded]')
      .find((button) => button.text().includes('Buyer Demands'))
    expect(header.attributes('aria-expanded')).toBe('true')
  })

  it('hides restricted items for unverified farmers', () => {
    const wrapper = mountSidebar({ verified: false })
    const text = wrapper.text()
    expect(text).not.toContain('My Offers')
    expect(text).not.toContain('Cash Approvals')
    expect(text).not.toContain('Chat')
    expect(text).toContain('Crop Demands')
  })
})

describe('AppSidebar buyer groups', () => {
  beforeEach(() => {
    mockRoute.name = 'buyer-dashboard'
    mockRoute.path = '/dashboard'
  })

  it('renders related buyer menus as collapsed groups', () => {
    const wrapper = mountSidebar({ role: 'buyer' })
    const text = wrapper.text()
    expect(text).toContain('Market')
    expect(text).toContain('Demands')
    expect(text).toContain('Overview')
    expect(text).toContain('Chat')

    const buttons = wrapper.findAll('button[aria-expanded]')
    expect(buttons).toHaveLength(2)
  })

  it('expands a group when its header is clicked', async () => {
    const wrapper = mountSidebar({ role: 'buyer' })
    const header = wrapper
      .findAll('button[aria-expanded]')
      .find((button) => button.text().includes('Market'))

    await header.trigger('click')
    expect(header.attributes('aria-expanded')).toBe('true')

    const text = wrapper.text()
    expect(text).toContain('Browse Market')
    expect(text).toContain('My Purchases')
  })

  it('drops the Demands group entirely for unverified buyers', () => {
    const wrapper = mountSidebar({ role: 'buyer', verified: false })
    const text = wrapper.text()
    expect(text).not.toContain('Demands')
    expect(text).not.toContain('Post Demand')
    expect(text).toContain('Market')
  })
})
