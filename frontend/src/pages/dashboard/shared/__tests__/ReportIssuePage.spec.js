import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import {
  ADMIN_NAVIGATION,
  BUYER_NAVIGATION,
  FARMER_NAVIGATION,
  RESTRICTED_FEATURES,
} from '@/constants/navigation'
import ReportIssuePage from '../ReportIssuePage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

const mockRoute = { path: '/dashboard/issues', fullPath: '/dashboard/issues', query: {} }

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
}))

const ConfirmModalStub = {
  name: 'ConfirmModal',
  props: ['isOpen', 'title', 'message', 'confirmText', 'loading'],
  emits: ['confirm', 'cancel'],
  template: `
    <div v-if="isOpen" data-testid="issue-confirm-dialog">
      <p>{{ title }}</p>
      <p>{{ message }}</p>
      <button data-testid="issue-confirm-cancel" :disabled="loading" @click="$emit('cancel')">Cancel</button>
      <button data-testid="issue-confirm-submit" :disabled="loading" @click="$emit('confirm')">{{ confirmText }}</button>
    </div>
  `,
}

function openTicket(overrides = {}) {
  return {
    id: '12',
    category: 'technical',
    subject: 'Plot map never loads',
    description: 'The map spinner never resolves.',
    page_path: '/dashboard/farms',
    status: 'open',
    resolution: null,
    resolved_at: null,
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

describe('ReportIssuePage.vue', () => {
  let apiGet
  let apiPost

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
    mockRoute.path = '/dashboard/issues'
    mockRoute.fullPath = '/dashboard/issues'
    mockRoute.query = {}
    apiGet = vi.fn()
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })
    apiGet.mockImplementation(async (url) => {
      if (url === '/issues') return listResponse([])
      throw new Error(`Unexpected GET ${url}`)
    })
  })

  async function mountPage() {
    const wrapper = mount(ReportIssuePage, {
      global: { stubs: { ConfirmModal: ConfirmModalStub } },
    })
    await flushPromises()
    return wrapper
  }

  async function fillValidDraft(wrapper, overrides = {}) {
    const draft = {
      category: 'technical',
      subject: 'Plot map never loads',
      description: 'The map spinner never resolves on my farm.',
      pagePath: '/dashboard/farms',
      ...overrides,
    }
    await wrapper.find('#issue-category').setValue(draft.category)
    await wrapper.find('#issue-subject').setValue(draft.subject)
    await wrapper.find('#issue-description').setValue(draft.description)
    await wrapper.find('#issue-page-path').setValue(draft.pagePath)
    return draft
  }

  async function submitForm(wrapper) {
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()
  }

  function dialog(wrapper) {
    return wrapper.find('[data-testid="issue-confirm-dialog"]')
  }

  it('renders an explicit accessible title and credential warning', async () => {
    const wrapper = await mountPage()

    expect(wrapper.find('h1').text()).toBe('Report an issue')
    expect(wrapper.text()).toContain('Do not include passwords or payment credentials')
  })

  it('prefills safe relative context from the from query and ignores full URLs', async () => {
    mockRoute.query = { from: '/dashboard/chat' }
    const safe = await mountPage()
    expect(safe.find('#issue-page-path').element.value).toBe('/dashboard/chat')

    mockRoute.query = { from: 'https://evil.test/dashboard/chat?token=abc' }
    const unsafe = await mountPage()
    expect(unsafe.find('#issue-page-path').element.value).toBe('')
  })

  it('captures only the router pathname as context, never the query string', async () => {
    mockRoute.path = '/dashboard/chat'
    mockRoute.fullPath = '/dashboard/chat?conversation=9'
    const wrapper = await mountPage()

    await wrapper.find('[data-testid="issue-use-current-page"]').trigger('click')

    expect(wrapper.find('#issue-page-path').element.value).toBe('/dashboard/chat')
  })

  it('blocks empty submit with inline errors and no request', async () => {
    const wrapper = await mountPage()

    await submitForm(wrapper)

    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(false)
    expect(wrapper.text()).toContain('Choose a category.')
    expect(wrapper.text()).toContain('Enter a subject.')
    expect(wrapper.text()).toContain('Enter a description.')
  })

  it('enforces subject, description, and path bounds before confirming', async () => {
    const wrapper = await mountPage()

    await fillValidDraft(wrapper)
    // Assign past maxlength directly: the DOM truncates typed input at the bound.
    wrapper.vm.subject = 's'.repeat(151)
    wrapper.vm.description = 'd'.repeat(5001)
    wrapper.vm.pagePath = `/${'p'.repeat(255)}`
    await submitForm(wrapper)

    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(false)
    const text = wrapper.text()
    expect(text).toContain('150 characters or fewer')
    expect(text).toContain('5,000 characters or fewer')
    expect(text).toContain('255 characters or fewer')
  })

  it('rejects absolute URLs and query strings as page context', async () => {
    const wrapper = await mountPage()

    await fillValidDraft(wrapper, { pagePath: 'https://yieldgrid.test/dashboard?x=1' })
    await submitForm(wrapper)

    expect(apiPost).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Use a relative path like /dashboard/issues.')
  })

  it('cancelling confirmation sends no request', async () => {
    const wrapper = await mountPage()

    await fillValidDraft(wrapper)
    await submitForm(wrapper)
    expect(dialog(wrapper).exists()).toBe(true)

    await wrapper.find('[data-testid="issue-confirm-cancel"]').trigger('click')
    await flushPromises()

    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('sends exactly one request while busy', async () => {
    const wrapper = await mountPage()
    let release
    apiPost.mockReturnValueOnce(
      new Promise((resolve) => {
        release = () => resolve({ data: { data: openTicket() }, status: 201 })
      }),
    )

    await fillValidDraft(wrapper)
    await submitForm(wrapper)
    const confirm = wrapper.find('[data-testid="issue-confirm-submit"]')
    await confirm.trigger('click')
    await confirm.trigger('click')
    release()
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(1)
    const [url, body] = apiPost.mock.calls[0]
    expect(url).toBe('/issues')
    expect(body.category).toBe('technical')
    expect(body.subject).toBe('Plot map never loads')
    expect(body.page_path).toBe('/dashboard/farms')
    expect(body.client_request_id).toMatch(
      /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/,
    )
  })

  it('keeps the failed draft inline with its error', async () => {
    const wrapper = await mountPage()
    apiPost.mockRejectedValueOnce(new Error('Network down'))

    await fillValidDraft(wrapper)
    await submitForm(wrapper)
    await wrapper.find('[data-testid="issue-confirm-submit"]').trigger('click')
    await flushPromises()

    expect(wrapper.find('#issue-subject').element.value).toBe('Plot map never loads')
    expect(wrapper.find('#issue-description').element.value).toContain('spinner')
    expect(wrapper.text()).toContain('Network down')
  })

  it('retries an uncertain failure with the same UUID, then rotates after acceptance', async () => {
    const wrapper = await mountPage()
    apiPost.mockRejectedValueOnce(new Error('timeout of 0ms exceeded'))
    apiPost.mockResolvedValueOnce({ data: { data: openTicket() }, status: 201 })

    await fillValidDraft(wrapper)
    await submitForm(wrapper)
    await wrapper.find('[data-testid="issue-confirm-submit"]').trigger('click')
    await flushPromises()

    await submitForm(wrapper)
    await wrapper.find('[data-testid="issue-confirm-submit"]').trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(2)
    const firstKey = apiPost.mock.calls[0][1].client_request_id
    const secondKey = apiPost.mock.calls[1][1].client_request_id
    expect(secondKey).toBe(firstKey)

    apiPost.mockResolvedValueOnce({ data: { data: openTicket({ id: '13' }) }, status: 201 })
    await fillValidDraft(wrapper, { subject: 'A brand new draft' })
    await submitForm(wrapper)
    await wrapper.find('[data-testid="issue-confirm-submit"]').trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(3)
    expect(apiPost.mock.calls[2][1].client_request_id).not.toBe(firstKey)
  })

  it('shows an accepted receipt with status and refreshes the history', async () => {
    const wrapper = await mountPage()
    const created = openTicket({ id: '12', status: 'open' })
    apiPost.mockResolvedValueOnce({ data: { data: created }, status: 201 })
    apiGet.mockImplementation(async (url) => {
      if (url === '/issues') return listResponse([created])
      throw new Error(`Unexpected GET ${url}`)
    })

    await fillValidDraft(wrapper)
    await submitForm(wrapper)
    await wrapper.find('[data-testid="issue-confirm-submit"]').trigger('click')
    await flushPromises()

    const receipt = wrapper.find('[data-testid="issue-receipt"]')
    expect(receipt.exists()).toBe(true)
    expect(receipt.text()).toContain('12')
    expect(receipt.text()).toContain('Open')
    expect(wrapper.find('#issue-subject').element.value).toBe('')
    expect(wrapper.text()).toContain('Plot map never loads')
  })

  it('starts a deliberate new draft with cleared fields', async () => {
    const wrapper = await mountPage()

    await fillValidDraft(wrapper)
    await wrapper.find('[data-testid="issue-new-draft"]').trigger('click')

    expect(wrapper.find('#issue-subject').element.value).toBe('')
    expect(wrapper.find('#issue-description').element.value).toBe('')
    expect(wrapper.find('#issue-page-path').element.value).toBe('')
  })

  it('retries a failed history load without losing the status filter', async () => {
    apiGet.mockRejectedValueOnce(new Error('Service unavailable'))
    const wrapper = await mountPage()
    expect(wrapper.text()).toContain('Service unavailable')

    const created = openTicket({ status: 'resolved' })
    let lastParams = null
    apiGet.mockImplementation(async (url, config) => {
      if (url === '/issues') {
        lastParams = config?.params ?? {}
        return listResponse([created])
      }
      throw new Error(`Unexpected GET ${url}`)
    })
    wrapper.vm.filters.status = 'resolved'
    await wrapper.find('[data-testid="issues-retry"]').trigger('click')
    await flushPromises()

    expect(lastParams.status).toBe('resolved')
    expect(wrapper.text()).toContain('Plot map never loads')
    expect(wrapper.find('#my-issues-status').element.value).toBe('resolved')
  })

  it('opens own ticket detail without admin-only fields', async () => {
    const created = openTicket({
      resolution: 'Cleared the map cache.',
      resolved_by: '1',
      reporter: { id: '8', name: 'Jose Farmer', email: 'jose@example.test' },
      user_id: '8',
      version: 3,
      client_request_id: '11111111-1111-4111-8111-111111111111',
    })
    apiGet.mockImplementation(async (url) => {
      if (url === '/issues') return listResponse([created])
      if (url === '/issues/12') return { data: { data: created } }
      throw new Error(`Unexpected GET ${url}`)
    })
    const wrapper = await mountPage()

    await wrapper.find('[data-testid="issue-open-12"]').trigger('click')
    await flushPromises()

    const detail = wrapper.find('[aria-label="Issue detail"]')
    expect(detail.exists()).toBe(true)
    expect(detail.text()).toContain('Cleared the map cache.')
    expect(detail.text()).not.toContain('jose@example.test')
    expect(detail.text()).not.toContain('11111111-1111-4111-8111-111111111111')
    expect(wrapper.html()).not.toContain('resolved_by')
    expect(wrapper.html()).not.toContain('user_id')
    expect(wrapper.html()).not.toContain('client_request_id')
  })

  it('renders a safe message when the own ticket is missing', async () => {
    const created = openTicket()
    apiGet.mockImplementation(async (url) => {
      if (url === '/issues') return listResponse([created])
      if (url === '/issues/12') {
        const error = new Error('Not found')
        error.response = { status: 404, data: { message: 'Ticket not found.' } }
        throw error
      }
      throw new Error(`Unexpected GET ${url}`)
    })
    const wrapper = await mountPage()

    await wrapper.find('[data-testid="issue-open-12"]').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('no longer available')
  })

  it('sends a payment issue as a plain ticket with no payment call', async () => {
    const wrapper = await mountPage()
    apiPost.mockResolvedValueOnce({
      data: { data: openTicket({ category: 'payment' }) },
      status: 201,
    })

    await fillValidDraft(wrapper, {
      category: 'payment',
      subject: 'Charged twice',
      description: 'My card shows two identical charges.',
    })
    await submitForm(wrapper)
    await wrapper.find('[data-testid="issue-confirm-submit"]').trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(1)
    expect(apiPost.mock.calls[0][0]).toBe('/issues')
    expect(apiPost.mock.calls[0][1].category).toBe('payment')
  })

  it('links Report an issue from both member navigations and Issues from admin', () => {
    const farmerEntry = FARMER_NAVIGATION.find((entry) => entry.name === 'Report an issue')
    const buyerEntry = BUYER_NAVIGATION.find((entry) => entry.name === 'Report an issue')
    const adminEntry = ADMIN_NAVIGATION.find((entry) => entry.name === 'Issues')

    expect(farmerEntry?.to).toEqual({ name: 'report-issue' })
    expect(buyerEntry?.to).toEqual({ name: 'report-issue' })
    expect(adminEntry?.to).toEqual({ name: 'admin-issues' })
    expect(RESTRICTED_FEATURES).toContain('Report an issue')
  })
})
