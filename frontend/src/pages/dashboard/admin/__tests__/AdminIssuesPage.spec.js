import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import AdminIssuesPage from '../AdminIssuesPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

const mockRoute = { query: {} }

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
}))

const AppModalStub = {
  name: 'AppModal',
  props: ['isOpen', 'title', 'busy'],
  template:
    '<div v-if="isOpen" data-testid="issue-transition-dialog"><slot /><slot name="footer" /></div>',
}

function adminTicket(overrides = {}) {
  return {
    id: '21',
    user_id: '8',
    category: 'marketplace',
    subject: 'Listing shows the wrong price',
    description: 'My listing price changed overnight.',
    page_path: '/dashboard/contracts',
    status: 'open',
    version: 2,
    resolution: null,
    resolved_by: null,
    resolved_at: null,
    reporter: { id: '8', name: 'Jose Farmer', email: 'jose@example.test' },
    created_at: '2026-03-01T00:00:00Z',
    updated_at: '2026-03-01T00:00:00Z',
    ...overrides,
  }
}

function listResponse(tickets) {
  return {
    data: {
      data: tickets,
      meta: { current_page: 1, last_page: 1, total: tickets.length },
    },
  }
}

function versionConflict(currentVersion = 5) {
  const error = new Error('This ticket changed since you loaded it.')
  error.response = {
    status: 409,
    data: { message: 'This ticket changed since you loaded it.', current_version: currentVersion },
  }
  return error
}

describe('AdminIssuesPage.vue', () => {
  let apiGet
  let apiPost

  beforeEach(() => {
    setActivePinia(createPinia())
    mockRoute.query = {}
    localStorage.clear()
    sessionStorage.clear()
    apiGet = vi.fn()
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })
  })

  async function mountPage(ticket = adminTicket()) {
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/issues') return listResponse([ticket])
      if (url === `/admin/issues/${ticket.id}`) return { data: { data: ticket } }
      throw new Error(`Unexpected GET ${url}`)
    })
    const wrapper = mount(AdminIssuesPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()
    return wrapper
  }

  async function openDetail(wrapper, id = '21') {
    await wrapper.find(`[data-testid="issue-review-${id}"]`).trigger('click')
    await flushPromises()
  }

  function dialog(wrapper) {
    return wrapper.find('[data-testid="issue-transition-dialog"]')
  }

  it('seeds issue filters from the route query', async () => {
    mockRoute.query = { status: 'open', category: 'payment' }
    await mountPage()

    expect(apiGet).toHaveBeenCalledWith('/admin/issues', {
      params: expect.objectContaining({ status: 'open', category: 'payment' }),
    })
  })

  it('renders an explicit accessible title', async () => {
    const wrapper = await mountPage()

    expect(wrapper.find('h1').text()).toBe('Issues')
  })

  it('fetches the inbox with status, category, and search filters', async () => {
    const wrapper = await mountPage()

    await wrapper.find('#admin-issue-status').setValue('open')
    await wrapper.find('#admin-issue-category').setValue('payment')
    await wrapper.find('#admin-issue-search').setValue('refund')
    await wrapper.find('[data-testid="issue-filters"]').trigger('submit.prevent')
    await flushPromises()

    const calls = apiGet.mock.calls.filter(([url]) => url === '/admin/issues')
    expect(calls.length).toBeGreaterThan(0)
    const params = calls[calls.length - 1][1].params
    expect(params.status).toBe('open')
    expect(params.category).toBe('payment')
    expect(params.search).toBe('refund')
  })

  it('rejects overlong search without a request', async () => {
    const wrapper = await mountPage()
    const before = apiGet.mock.calls.length

    expect(wrapper.find('#admin-issue-search').attributes('maxlength')).toBe('100')
    // Assign past maxlength directly: the DOM truncates typed input at the bound.
    wrapper.vm.filters.search = 's'.repeat(101)
    await wrapper.find('[data-testid="issue-filters"]').trigger('submit.prevent')
    await flushPromises()

    expect(apiGet.mock.calls.length).toBe(before)
    expect(wrapper.text()).toContain('100 characters or fewer')
  })

  it('shows reporter identity only in the admin detail', async () => {
    const wrapper = await mountPage()

    await openDetail(wrapper)

    const detail = wrapper.find('[aria-label="Issue detail"]')
    expect(detail.text()).toContain('Jose Farmer')
    expect(detail.text()).toContain('jose@example.test')
  })

  it('renders a safe reporter label when the author is deleted', async () => {
    const wrapper = await mountPage(adminTicket({ reporter: null, user_id: null }))

    await openDetail(wrapper)

    expect(wrapper.find('[aria-label="Issue detail"]').text()).toContain('Former member')
  })

  it('requires a bounded resolution when resolving', async () => {
    const wrapper = await mountPage()

    await openDetail(wrapper)
    await wrapper.find('[data-testid="issue-transition-resolved"]').trigger('click')
    expect(dialog(wrapper).exists()).toBe(true)

    await wrapper.find('[data-testid="issue-transition-confirm"]').trigger('click')
    await flushPromises()
    expect(apiPost).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Enter a resolution')

    // Assign past maxlength directly: the DOM truncates typed input at the bound.
    wrapper.vm.resolutionDraft = 'r'.repeat(2001)
    await wrapper.find('[data-testid="issue-transition-confirm"]').trigger('click')
    await flushPromises()
    expect(apiPost).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('2,000 characters or fewer')
  })

  it('resolves with the resolution and expected version', async () => {
    const wrapper = await mountPage()
    const resolved = adminTicket({
      status: 'resolved',
      version: 3,
      resolution: 'Price cache cleared.',
      resolved_by: '1',
      resolved_at: '2026-03-02T00:00:00Z',
    })
    apiPost.mockResolvedValueOnce({ data: { data: resolved } })

    await openDetail(wrapper)
    await wrapper.find('[data-testid="issue-transition-resolved"]').trigger('click')
    await wrapper.find('#issue-resolution').setValue('Price cache cleared.')
    await wrapper.find('[data-testid="issue-transition-confirm"]').trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(1)
    expect(apiPost.mock.calls[0][0]).toBe('/admin/issues/21/transition')
    expect(apiPost.mock.calls[0][1]).toEqual({
      status: 'resolved',
      resolution: 'Price cache cleared.',
      expected_version: 2,
    })
    expect(wrapper.find('[aria-label="Issue detail"]').text()).toContain('Price cache cleared.')
  })

  it('moves open tickets to in progress without a resolution payload', async () => {
    const wrapper = await mountPage()
    apiPost.mockResolvedValueOnce({
      data: { data: adminTicket({ status: 'in_progress', version: 3 }) },
    })

    await openDetail(wrapper)
    await wrapper.find('[data-testid="issue-transition-in_progress"]').trigger('click')
    await wrapper.find('[data-testid="issue-transition-confirm"]').trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(1)
    expect(apiPost.mock.calls[0][1]).toEqual({
      status: 'in_progress',
      resolution: null,
      expected_version: 2,
    })
  })

  it('reopening clears the current resolution', async () => {
    const resolved = adminTicket({
      status: 'resolved',
      version: 3,
      resolution: 'Price cache cleared.',
      resolved_by: '1',
      resolved_at: '2026-03-02T00:00:00Z',
    })
    const wrapper = await mountPage(resolved)
    const reopened = adminTicket({ status: 'in_progress', version: 4 })
    apiPost.mockResolvedValueOnce({ data: { data: reopened } })

    await openDetail(wrapper)
    expect(wrapper.text()).toContain('Reopen ticket')

    await wrapper.find('[data-testid="issue-transition-in_progress"]').trigger('click')
    await wrapper.find('[data-testid="issue-transition-confirm"]').trigger('click')
    await flushPromises()

    expect(apiPost.mock.calls[0][1].resolution).toBeNull()
    const detail = wrapper.find('[aria-label="Issue detail"]')
    expect(detail.text()).not.toContain('Price cache cleared.')
    expect(detail.text()).toContain('In progress')
  })

  it('cancelling a transition sends no request', async () => {
    const wrapper = await mountPage()

    await openDetail(wrapper)
    await wrapper.find('[data-testid="issue-transition-closed"]').trigger('click')
    expect(dialog(wrapper).exists()).toBe(true)

    await wrapper.find('[data-testid="issue-transition-cancel"]').trigger('click')
    await flushPromises()

    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('sends exactly one transition while busy', async () => {
    const wrapper = await mountPage()
    let release
    apiPost.mockReturnValueOnce(
      new Promise((resolve) => {
        release = () => resolve({ data: { data: adminTicket({ status: 'closed', version: 3 }) } })
      }),
    )

    await openDetail(wrapper)
    await wrapper.find('[data-testid="issue-transition-closed"]').trigger('click')
    await wrapper.find('#issue-resolution').setValue('Duplicate of ticket 20.')
    const confirm = wrapper.find('[data-testid="issue-transition-confirm"]')
    await confirm.trigger('click')
    await confirm.trigger('click')
    release()
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(1)
  })

  it('surfaces version conflicts with reload and keeps the draft', async () => {
    const wrapper = await mountPage()
    apiPost.mockRejectedValueOnce(versionConflict(5))

    await openDetail(wrapper)
    await wrapper.find('[data-testid="issue-transition-resolved"]').trigger('click')
    await wrapper.find('#issue-resolution').setValue('Price cache cleared.')
    await wrapper.find('[data-testid="issue-transition-confirm"]').trigger('click')
    await flushPromises()

    expect(dialog(wrapper).text()).toContain('changed since it was loaded')
    const reload = wrapper.find('[data-testid="issue-conflict-reload"]')
    expect(reload.exists()).toBe(true)
    expect(reload.text()).toContain('5')

    const latest = adminTicket({ version: 5 })
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/issues/21') return { data: { data: latest } }
      if (url === '/admin/issues') return listResponse([latest])
      throw new Error(`Unexpected GET ${url}`)
    })
    await reload.trigger('click')
    await flushPromises()

    expect(wrapper.find('#issue-resolution').element.value).toBe('Price cache cleared.')

    apiPost.mockResolvedValueOnce({ data: { data: adminTicket({ version: 6 }) } })
    await wrapper.find('[data-testid="issue-transition-confirm"]').trigger('click')
    await flushPromises()
    expect(apiPost.mock.calls[1][1].expected_version).toBe(5)
  })

  it('retries a failed inbox load and renders missing detail safely', async () => {
    apiGet.mockRejectedValueOnce(new Error('Service unavailable'))
    const wrapper = await mount(AdminIssuesPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()
    expect(wrapper.text()).toContain('Service unavailable')

    const ticket = adminTicket()
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/issues') return listResponse([ticket])
      if (url === '/admin/issues/21') {
        const error = new Error('Not found')
        error.response = { status: 404, data: { message: 'Ticket not found.' } }
        throw error
      }
      throw new Error(`Unexpected GET ${url}`)
    })
    await wrapper.find('[data-testid="issues-retry"]').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Listing shows the wrong price')

    await openDetail(wrapper)
    expect(wrapper.text()).toContain('no longer available')
  })
})
