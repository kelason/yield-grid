import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import AdminOverviewPage from '../AdminOverviewPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

const RouterLinkStub = {
  name: 'RouterLink',
  props: ['to'],
  template: '<a :data-to="JSON.stringify(to)"><slot /></a>',
}

function overviewBody(overrides = {}) {
  return {
    users: { members_total: 120, members_suspended: 4 },
    content: {
      thread: { total: 30, visible: 27, hidden: 3 },
      reply: { total: 90, visible: 85, hidden: 5 },
      contract: { total: 12, visible: 11, hidden: 1 },
      listing: { total: 20, visible: 19, hidden: 1 },
      demand: { total: 8, visible: 8, hidden: 0 },
    },
    inquiries: { unread: 3, read: 5, replied: 7, closed: 9, failed_replies: 1 },
    reports: { open: 2, reviewing: 1, resolved: 6, dismissed: 2 },
    issues: { open: 4, in_progress: 2, resolved: 10, closed: 12 },
    generated_at: '2026-10-08T08:00:00+08:00',
    ...overrides,
  }
}

function overviewPayload(overrides = {}) {
  return { data: { data: overviewBody(overrides) } }
}

function serverError() {
  const error = new Error()
  error.response = { status: 500, data: {} }
  return error
}

describe('AdminOverviewPage.vue', () => {
  let apiGet

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
    apiGet = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: vi.fn() })
  })

  async function mountPage(payload = overviewPayload()) {
    apiGet.mockResolvedValue(payload)
    const wrapper = mount(AdminOverviewPage, {
      global: { stubs: { RouterLink: RouterLinkStub } },
    })
    await flushPromises()
    return wrapper
  }

  function linkTarget(wrapper, testid) {
    const link = wrapper.find(`[data-testid="${testid}"]`)
    expect(link.exists()).toBe(true)
    return JSON.parse(link.attributes('data-to'))
  }

  it('fetches the overview on entry and renders every count', async () => {
    const wrapper = await mountPage()

    expect(apiGet).toHaveBeenCalledTimes(1)
    expect(apiGet).toHaveBeenCalledWith('/admin/overview')

    const text = wrapper.text()
    for (const value of ['120', '4', '30', '90', '12', '20', '8']) {
      expect(text).toContain(value)
    }
    expect(text).toContain('Updated at')
  })

  it('links every card to its matching filtered list', async () => {
    const wrapper = await mountPage()

    expect(linkTarget(wrapper, 'overview-users-total')).toEqual({
      name: 'admin-users',
      query: { role: 'members' },
    })
    expect(linkTarget(wrapper, 'overview-users-suspended')).toEqual({
      name: 'admin-users',
      query: { suspended: 'suspended' },
    })
    for (const type of ['thread', 'reply', 'contract', 'listing', 'demand']) {
      expect(linkTarget(wrapper, `overview-content-${type}`)).toEqual({
        name: 'admin-content',
        query: { type },
      })
    }
    for (const status of ['unread', 'read', 'replied', 'closed']) {
      expect(linkTarget(wrapper, `overview-inquiries-${status}`)).toEqual({
        name: 'admin-inquiries',
        query: { status },
      })
    }
    expect(linkTarget(wrapper, 'overview-inquiries-failed')).toEqual({
      name: 'admin-inquiries',
      query: { delivery: 'failed' },
    })
    for (const status of ['open', 'reviewing', 'resolved', 'dismissed']) {
      expect(linkTarget(wrapper, `overview-reports-${status}`)).toEqual({
        name: 'admin-reports',
        query: { status },
      })
    }
    for (const status of ['open', 'in_progress', 'resolved', 'closed']) {
      expect(linkTarget(wrapper, `overview-issues-${status}`)).toEqual({
        name: 'admin-issues',
        query: { status },
      })
    }
  })

  it('shows loading state before the overview arrives', async () => {
    apiGet.mockReturnValue(new Promise(() => {}))
    const wrapper = mount(AdminOverviewPage, {
      global: { stubs: { RouterLink: RouterLinkStub } },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Loading overview')
    expect(wrapper.find('[data-testid="overview-users-total"]').exists()).toBe(false)
  })

  it('shows an error with retry instead of zero placeholders when loading fails', async () => {
    apiGet.mockRejectedValueOnce(serverError())
    const wrapper = mount(AdminOverviewPage, {
      global: { stubs: { RouterLink: RouterLinkStub } },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Unable to load the overview.')
    expect(wrapper.find('[data-testid="overview-users-total"]').exists()).toBe(false)

    apiGet.mockResolvedValueOnce(overviewPayload())
    await wrapper.find('[data-testid="overview-retry"]').trigger('click')
    await flushPromises()

    expect(apiGet).toHaveBeenCalledTimes(2)
    expect(wrapper.text()).toContain('120')
  })

  it('keeps the last successful timestamp until a refresh result arrives', async () => {
    const wrapper = await mountPage()
    expect(wrapper.text()).toContain('2026-10-08')

    let resolveRefresh
    apiGet.mockReturnValueOnce(
      new Promise((resolve) => {
        resolveRefresh = resolve
      }),
    )
    await wrapper.find('[data-testid="overview-refresh"]').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('2026-10-08')
    expect(wrapper.text()).toContain('120')

    resolveRefresh(overviewPayload({ generated_at: '2026-10-08T09:30:00+08:00' }))
    await flushPromises()
    expect(apiGet).toHaveBeenCalledTimes(2)
  })

  it('keeps prior data and timestamp when a refresh fails', async () => {
    const wrapper = await mountPage()

    apiGet.mockRejectedValueOnce(serverError())
    await wrapper.find('[data-testid="overview-refresh"]').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('120')
    expect(wrapper.text()).toContain('2026-10-08')
    expect(wrapper.text()).toContain('Unable to load the overview.')
    expect(wrapper.find('[data-testid="overview-retry"]').exists()).toBe(true)
  })

  it('renders unknown for missing buckets instead of zero', async () => {
    const payload = overviewPayload()
    delete payload.data.data.content.reply
    delete payload.data.data.inquiries
    const wrapper = await mountPage(payload)

    const replyCard = wrapper.find('[data-testid="overview-content-reply"]')
    expect(replyCard.exists()).toBe(true)
    expect(replyCard.text()).toContain('—')
    expect(wrapper.find('[data-testid="overview-inquiries-unread"]').exists()).toBe(false)
  })

  it('clears the session when the overview request is unauthorized', async () => {
    const authStore = useAuthStore()
    authStore.$patch({ token: 'stale-token', user: { id: 1, role: 'admin' } })
    const error = new Error('Unauthorized')
    error.response = { status: 401, data: {} }
    apiGet.mockRejectedValue(error)

    mount(AdminOverviewPage, {
      global: { stubs: { RouterLink: RouterLinkStub } },
    })
    await flushPromises()

    expect(authStore.token).toBeNull()
  })
})
