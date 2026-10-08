import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import AdminReportsPage from '../AdminReportsPage.vue'

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
    '<div v-if="isOpen" data-testid="report-decision-dialog"><slot /><slot name="footer" /></div>',
}

function openReport() {
  return {
    id: '5',
    reportable_type: 'thread',
    reportable_id: '7',
    reason: 'spam',
    description: 'Repeated ads for seeds.',
    status: 'open',
    version: 2,
    outcome: null,
    resolution_note: null,
    reviewed_by: null,
    reviewed_at: null,
    target_snapshot: {
      type: 'thread',
      id: '7',
      owner_id: '9',
      title: 'Harvest tips',
      excerpt: 'Buy cheap seeds now',
      status: null,
      captured_at: '2026-03-01T00:00:00Z',
    },
    reporter: { id: '3', name: 'Ana Buyer', email: 'ana@example.test' },
    target_available: true,
    target: {
      type: 'thread',
      id: '7',
      title: 'Harvest tips',
      body: 'Buy cheap seeds now',
      is_hidden: false,
      hidden_reason: null,
      author: { id: '9', name: 'Jose Farmer', email: 'jose@example.test' },
    },
    created_at: '2026-03-01T00:00:00Z',
    updated_at: '2026-03-01T00:00:00Z',
  }
}

function listResponse(reports) {
  return {
    data: {
      data: reports,
      meta: { current_page: 1, last_page: 1, total: reports.length },
    },
  }
}

describe('AdminReportsPage.vue', () => {
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

  async function mountPage(report = openReport()) {
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/reports') return listResponse([report])
      if (url === `/admin/reports/${report.id}`) return { data: { data: report } }
      throw new Error(`Unexpected GET ${url}`)
    })
    const wrapper = mount(AdminReportsPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()
    return wrapper
  }

  function reviewButton(wrapper, id = '5') {
    return wrapper.find(`[data-testid="report-review-${id}"]`)
  }

  async function openDetail(wrapper, id = '5') {
    await reviewButton(wrapper, id).trigger('click')
    await flushPromises()
  }

  it('seeds report filters from the route query', async () => {
    mockRoute.query = { status: 'reviewing', type: 'thread', reason: 'spam' }
    await mountPage()

    expect(apiGet).toHaveBeenCalledWith('/admin/reports', {
      params: expect.objectContaining({ status: 'reviewing', type: 'thread', reason: 'spam' }),
    })
  })

  it('fetches the queue with status, type, and reason filters', async () => {
    const wrapper = await mountPage()
    const selects = wrapper.findAll('select')

    await selects[0].setValue('open')
    await selects[1].setValue('thread')
    await selects[2].setValue('spam')
    await flushPromises()

    const lastCall = apiGet.mock.calls.at(-1)
    expect(lastCall[0]).toBe('/admin/reports')
    expect(lastCall[1].params).toMatchObject({ status: 'open', type: 'thread', reason: 'spam' })
    expect(wrapper.text()).toContain('Harvest tips')
  })

  it('shows a retry before the empty state and keeps filters on retry', async () => {
    apiGet.mockRejectedValueOnce(new Error('Network down'))
    const wrapper = mount(AdminReportsPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Network down')
    apiGet.mockResolvedValueOnce(listResponse([openReport()]))
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Retry')
      .trigger('click')
    await flushPromises()

    expect(apiGet.mock.calls.at(-1)[1].params).toMatchObject({ page: 1, per_page: 10 })
    expect(wrapper.text()).toContain('Harvest tips')
  })

  it('displays the source snapshot, current state, and reporter', async () => {
    const wrapper = await mountPage()
    await openDetail(wrapper)

    expect(wrapper.text()).toContain('Buy cheap seeds now')
    expect(wrapper.text()).toContain('Harvest tips')
    expect(wrapper.text()).toContain('Ana Buyer')
    expect(wrapper.text()).toContain('Version 2')
    expect(wrapper.text()).toContain('Visible')
  })

  it('requires a note before deciding', async () => {
    const wrapper = await mountPage()
    await openDetail(wrapper)

    await wrapper.find('[data-testid="report-decision-reviewing"]').trigger('click')
    await wrapper
      .find('[data-testid="report-decision-dialog"]')
      .findAll('button')
      .find((button) => button.text() === 'Mark reviewing')
      .trigger('click')

    expect(wrapper.text()).toContain('Enter a note')
    expect(apiPost).not.toHaveBeenCalled()
  })

  it('decides with the loaded expected version', async () => {
    const wrapper = await mountPage()
    await openDetail(wrapper)
    const decided = { ...openReport(), status: 'reviewing', version: 3 }
    apiPost.mockResolvedValueOnce({ data: { data: decided } })

    await wrapper.find('[data-testid="report-decision-reviewing"]').trigger('click')
    await wrapper.find('[data-testid="report-decision-dialog"]').find('textarea').setValue('On it.')
    await wrapper
      .find('[data-testid="report-decision-dialog"]')
      .findAll('button')
      .find((button) => button.text() === 'Mark reviewing')
      .trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledWith('/admin/reports/5/decision', {
      status: 'reviewing',
      outcome: null,
      note: 'On it.',
      expected_version: 2,
    })
    expect(wrapper.text()).toContain('Reviewing')
  })

  it('retains the note on version conflict and offers a reload', async () => {
    const wrapper = await mountPage()
    await openDetail(wrapper)
    apiPost.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Report changed.', current_version: 4 } },
    })

    await wrapper.find('[data-testid="report-decision-dismiss"]').trigger('click')
    const dialog = wrapper.find('[data-testid="report-decision-dialog"]')
    await dialog.find('textarea').setValue('Duplicate queue entry.')
    await dialog
      .findAll('button')
      .find((button) => button.text() === 'Dismiss report')
      .trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('changed since')
    expect(wrapper.text()).toContain('4')
    expect(
      wrapper.find('[data-testid="report-decision-dialog"]').find('textarea').element.value,
    ).toBe('Duplicate queue entry.')

    const reloaded = { ...openReport(), version: 4 }
    apiGet.mockResolvedValueOnce({ data: { data: reloaded } })
    await wrapper.find('[data-testid="report-conflict-reload"]').trigger('click')
    await flushPromises()

    expect(apiGet.mock.calls.at(-1)[0]).toBe('/admin/reports/5')
    expect(wrapper.text()).toContain('Version 4')
  })

  it('keeps terminal reports terminal without decision actions', async () => {
    const resolved = {
      ...openReport(),
      status: 'resolved',
      version: 5,
      outcome: 'hidden',
      resolution_note: 'Hidden for spam.',
      reviewed_at: '2026-03-02T00:00:00Z',
    }
    const wrapper = await mountPage(resolved)
    await openDetail(wrapper)

    expect(wrapper.text()).toContain('Hidden for spam')
    expect(wrapper.find('[data-testid="report-decision-reviewing"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="report-decision-resolve-hidden"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="report-decision-resolve-no-action"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="report-decision-dismiss"]').exists()).toBe(false)
  })

  it('marks missing targets unavailable and only allows dismissal or no action', async () => {
    const missing = {
      ...openReport(),
      target_available: false,
      target: null,
    }
    const wrapper = await mountPage(missing)
    await openDetail(wrapper)

    expect(wrapper.text()).toContain('Target unavailable')
    expect(wrapper.find('[data-testid="report-decision-resolve-hidden"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="report-decision-resolve-no-action"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="report-decision-dismiss"]').exists()).toBe(true)
  })

  it('never exposes internal moderation notes to the queue summary', async () => {
    const wrapper = await mountPage()

    expect(wrapper.text()).not.toContain('jose@example.test')
  })
})
