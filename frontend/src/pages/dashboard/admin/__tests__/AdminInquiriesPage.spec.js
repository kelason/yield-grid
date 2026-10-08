import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import AdminInquiriesPage from '../AdminInquiriesPage.vue'
import AdminInquiryDetail from '@/components/organisms/AdminInquiryDetail.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

const AppModalStub = {
  name: 'AppModal',
  props: ['isOpen', 'title', 'busy'],
  template:
    '<div v-if="isOpen" data-testid="admin-inquiry-dialog"><slot /><slot name="footer" /></div>',
}

const STALE_CREATED_AT = '2026-01-01T00:00:00Z'

describe('AdminInquiriesPage.vue', () => {
  function makeUnreadInquiry() {
    return {
      id: '3',
      name: 'Ana Buyer',
      email: 'ana@example.test',
      subject: 'Damaged harvest',
      message: 'My order arrived damaged.',
      status: 'unread',
      replied_at: null,
      created_at: '2026-03-01T00:00:00Z',
      replies: [],
    }
  }

  function makeFailedReply() {
    return {
      id: '11',
      message_id: '5',
      admin_id: '1',
      recipient: 'jose@example.test',
      body: 'Sorry to hear that. We are looking into it.',
      delivery_status: 'failed',
      attempts: 3,
      delivery_generation: 0,
      sent_at: null,
      error_code: 'transport_failed',
      created_at: STALE_CREATED_AT,
    }
  }

  function makeReadInquiry(failed) {
    return {
      id: '5',
      name: 'Jose Farmer',
      email: 'jose@example.test',
      subject: 'Payout question',
      message: 'When will my payout arrive?',
      status: 'read',
      replied_at: null,
      created_at: '2026-03-02T00:00:00Z',
      replies: [failed],
    }
  }

  function listResponse(messages, meta = {}) {
    return {
      data: {
        data: messages,
        meta: { current_page: 1, last_page: 1, total: messages.length, ...meta },
      },
    }
  }

  function detailResponse(message) {
    return { data: { data: message } }
  }

  let apiGet
  let apiPost
  let unreadInquiry
  let failedReply
  let readInquiry

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
    unreadInquiry = makeUnreadInquiry()
    failedReply = makeFailedReply()
    readInquiry = makeReadInquiry(failedReply)
    apiGet = vi.fn(async (url) => {
      if (url === '/admin/contact-messages') {
        return listResponse([unreadInquiry, readInquiry])
      }
      if (url === '/admin/contact-messages/3') return detailResponse(unreadInquiry)
      if (url === '/admin/contact-messages/5') return detailResponse(readInquiry)
      throw new Error(`Unexpected GET ${url}`)
    })
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })

    const authStore = useAuthStore()
    authStore.token = 'admin-token'
    authStore.user = { id: '1', role: 'admin', email_verified_at: '2026-01-01T00:00:00Z' }
  })

  afterEach(() => {
    localStorage.clear()
    sessionStorage.clear()
  })

  async function mountPage() {
    const wrapper = mount(AdminInquiriesPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()
    return wrapper
  }

  function dialog(wrapper) {
    return wrapper.find('[data-testid="admin-inquiry-dialog"]')
  }

  function detail(wrapper) {
    return wrapper.find('[data-testid="admin-inquiry-detail"]')
  }

  function findButton(wrapper, text) {
    return wrapper.findAll('button').find((button) => button.text().trim() === text)
  }

  async function openDetail(wrapper, label) {
    await wrapper.find(`button[aria-label="${label}"]`).trigger('click')
    await flushPromises()
  }

  async function confirmDialog(wrapper, text) {
    await findButton(dialog(wrapper), text).trigger('click')
    await flushPromises()
  }

  it('loads the first page of inquiries on entry', async () => {
    const wrapper = await mountPage()

    expect(apiGet).toHaveBeenCalledWith(
      '/admin/contact-messages',
      expect.objectContaining({ params: expect.objectContaining({ page: 1 }) }),
    )
    expect(wrapper.find('h1').text()).toBe('Inquiries')
    expect(wrapper.text()).toContain('Ana Buyer')
    expect(wrapper.text()).toContain('Jose Farmer')
  })

  it('shows loading state while fetching', async () => {
    let resolveFetch
    apiGet.mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          resolveFetch = resolve
        }),
    )

    const wrapper = mount(AdminInquiriesPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Loading inquiries')

    resolveFetch(listResponse([unreadInquiry]))
    await flushPromises()
    expect(wrapper.text()).toContain('Ana Buyer')
  })

  it('shows an error with retry and recovers to data', async () => {
    apiGet.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'Server blew up.' } },
    })

    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('Server blew up.')
    expect(wrapper.find('[role="alert"]').exists()).toBe(true)

    await findButton(wrapper, 'Retry').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Ana Buyer')
  })

  it('keeps filters across a retry', async () => {
    const wrapper = await mountPage()

    apiGet.mockRejectedValueOnce({ response: { status: 500, data: {} } })
    await wrapper.find('#admin-inquiry-status').setValue('unread')
    await flushPromises()
    expect(wrapper.text()).toContain('Unable to load inquiries.')

    await findButton(wrapper, 'Retry').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Ana Buyer')
    expect(apiGet).toHaveBeenLastCalledWith(
      '/admin/contact-messages',
      expect.objectContaining({ params: expect.objectContaining({ status: 'unread' }) }),
    )
  })

  it('shows an empty state when no inquiries match', async () => {
    apiGet.mockResolvedValueOnce(listResponse([]))

    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('No inquiries found')
  })

  it('bounds the search input and validates before fetching', async () => {
    const wrapper = await mountPage()
    const search = wrapper.find('#admin-inquiry-search')

    expect(search.attributes('maxlength')).toBe('100')

    wrapper.vm.filters.search = 'x'.repeat(101)
    await wrapper.vm.$nextTick()
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('100 characters or fewer')
    expect(apiGet).toHaveBeenCalledTimes(1)
  })

  it('applies the status filter and resets it', async () => {
    const wrapper = await mountPage()
    apiGet.mockClear()

    await wrapper.find('#admin-inquiry-status').setValue('read')
    await flushPromises()
    expect(apiGet).toHaveBeenCalledWith(
      '/admin/contact-messages',
      expect.objectContaining({ params: expect.objectContaining({ status: 'read', page: 1 }) }),
    )

    await findButton(wrapper, 'Reset').trigger('click')
    await flushPromises()
    expect(apiGet).toHaveBeenLastCalledWith(
      '/admin/contact-messages',
      expect.objectContaining({
        params: expect.not.objectContaining({ status: 'read' }),
      }),
    )
  })

  it('paginates through the list', async () => {
    apiGet.mockResolvedValueOnce(
      listResponse([unreadInquiry], { current_page: 1, last_page: 3, total: 25 }),
    )

    const wrapper = await mountPage()

    await findButton(wrapper, 'Next').trigger('click')
    await flushPromises()

    expect(apiGet).toHaveBeenLastCalledWith(
      '/admin/contact-messages',
      expect.objectContaining({ params: expect.objectContaining({ page: 2 }) }),
    )
  })

  it('renders null and incomplete inquiries safely', async () => {
    apiGet.mockResolvedValueOnce(listResponse([null, 'nope', { id: '9' }]))

    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('Unknown inquiry')
    expect(wrapper.find('button[aria-label="View inquiry from Unknown inquiry"]').exists()).toBe(
      true,
    )
  })

  it('opens the detail with a GET and zero POST requests', async () => {
    const wrapper = await mountPage()

    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    expect(apiGet).toHaveBeenCalledWith('/admin/contact-messages/3')
    expect(apiPost).not.toHaveBeenCalled()
    expect(detail(wrapper).exists()).toBe(true)
    expect(detail(wrapper).text()).toContain('Damaged harvest')
    expect(detail(wrapper).text()).toContain('My order arrived damaged.')
    expect(detail(wrapper).text()).toContain('ana@example.test')
  })

  it('renders the original inquiry and reply history with delivery labels', async () => {
    const wrapper = await mountPage()

    await openDetail(wrapper, 'View inquiry from Jose Farmer')

    expect(detail(wrapper).text()).toContain('When will my payout arrive?')
    expect(detail(wrapper).text()).toContain('Sorry to hear that. We are looking into it.')
    expect(detail(wrapper).text()).toContain('Failed')
  })

  it('renders HTML-like content as text, never markup', async () => {
    const hostile = {
      ...unreadInquiry,
      id: '7',
      message: '<script>alert("xss")</script>',
      replies: [
        {
          ...failedReply,
          id: '12',
          message_id: '7',
          body: '<img src=x onerror=alert(1)>',
        },
      ],
    }
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/contact-messages') return listResponse([hostile])
      if (url === '/admin/contact-messages/7') return detailResponse(hostile)
      throw new Error(`Unexpected GET ${url}`)
    })

    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    expect(detail(wrapper).html()).toContain('&lt;script&gt;')
    expect(detail(wrapper).html()).toContain('&lt;img')
    expect(wrapper.findAll('script')).toHaveLength(0)
  })

  it('marks an inquiry read only after explicit confirmation', async () => {
    apiPost.mockResolvedValueOnce({ data: { data: { ...unreadInquiry, status: 'read' } } })
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await findButton(detail(wrapper), 'Mark read').trigger('click')
    await flushPromises()
    expect(dialog(wrapper).exists()).toBe(true)
    expect(apiPost).not.toHaveBeenCalled()

    await confirmDialog(wrapper, 'Mark read')

    expect(apiPost).toHaveBeenCalledTimes(1)
    expect(apiPost).toHaveBeenCalledWith('/admin/contact-messages/3/read')
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it.each([
    ['Mark read', 'Mark read'],
    ['Close inquiry', 'Close inquiry'],
  ])('cancelling a %s confirmation sends zero POST requests', async (open, confirm) => {
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await findButton(detail(wrapper), open).trigger('click')
    await flushPromises()
    expect(dialog(wrapper).exists()).toBe(true)
    expect(dialog(wrapper).text()).toContain(confirm)

    await findButton(dialog(wrapper), 'Cancel').trigger('click')
    await flushPromises()

    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('cancelling a reopen confirmation sends zero POST requests', async () => {
    const closed = { ...unreadInquiry, status: 'closed' }
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/contact-messages') return listResponse([closed])
      if (url === '/admin/contact-messages/3') return detailResponse(closed)
      throw new Error(`Unexpected GET ${url}`)
    })
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await findButton(detail(wrapper), 'Reopen inquiry').trigger('click')
    await flushPromises()
    expect(dialog(wrapper).exists()).toBe(true)

    await findButton(dialog(wrapper), 'Cancel').trigger('click')
    await flushPromises()

    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('cancelling a reply confirmation sends zero POST requests and keeps the draft', async () => {
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await detail(wrapper).find('#admin-inquiry-reply').setValue('Thanks for writing to us.')
    await findButton(detail(wrapper), 'Send reply').trigger('click')
    await flushPromises()
    expect(dialog(wrapper).exists()).toBe(true)

    await findButton(dialog(wrapper), 'Cancel').trigger('click')
    await flushPromises()

    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(false)
    expect(detail(wrapper).find('#admin-inquiry-reply').element.value).toBe(
      'Thanks for writing to us.',
    )
  })

  it('cancelling a retry confirmation sends zero POST requests', async () => {
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Jose Farmer')

    await wrapper.find('button[aria-label="Retry delivery of reply 11"]').trigger('click')
    await flushPromises()
    expect(dialog(wrapper).exists()).toBe(true)

    await findButton(dialog(wrapper), 'Cancel').trigger('click')
    await flushPromises()

    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('blocks a duplicate submit while the mutation is busy', async () => {
    let resolvePost
    apiPost.mockReturnValueOnce(
      new Promise((resolve) => {
        resolvePost = resolve
      }),
    )
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await findButton(detail(wrapper), 'Mark read').trigger('click')
    await flushPromises()

    const confirm = findButton(dialog(wrapper), 'Mark read')
    await confirm.trigger('click')
    await confirm.trigger('click')
    expect(apiPost).toHaveBeenCalledTimes(1)

    resolvePost({ data: { data: { ...unreadInquiry, status: 'read' } } })
    await flushPromises()
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('retries a failed reply through the parent-scoped endpoint', async () => {
    apiPost.mockResolvedValueOnce({
      data: { data: { ...failedReply, delivery_status: 'queued', error_code: null } },
    })
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Jose Farmer')

    await wrapper.find('button[aria-label="Retry delivery of reply 11"]').trigger('click')
    await flushPromises()
    await confirmDialog(wrapper, 'Retry delivery')

    expect(apiPost).toHaveBeenCalledTimes(1)
    expect(apiPost).toHaveBeenCalledWith('/admin/contact-messages/5/replies/11/retry')
  })

  it('keeps the same request key across an uncertain network retry', async () => {
    apiPost.mockRejectedValueOnce(new Error('Network error')).mockResolvedValueOnce({
      data: {
        data: { ...failedReply, id: '20', message_id: '3', delivery_status: 'queued' },
      },
    })
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await detail(wrapper).find('#admin-inquiry-reply').setValue('Thanks for writing to us.')
    await findButton(detail(wrapper), 'Send reply').trigger('click')
    await flushPromises()
    await confirmDialog(wrapper, 'Queue reply')

    expect(dialog(wrapper).exists()).toBe(true)
    expect(dialog(wrapper).text()).toContain('Network error')

    await confirmDialog(wrapper, 'Queue reply')

    expect(apiPost).toHaveBeenCalledTimes(2)
    const firstKey = apiPost.mock.calls[0][1].client_request_id
    const secondKey = apiPost.mock.calls[1][1].client_request_id
    expect(firstKey).toMatch(
      /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i,
    )
    expect(secondKey).toBe(firstKey)
  })

  it('shows Queued, not Sent, after the reply is accepted', async () => {
    apiPost.mockResolvedValueOnce({
      data: {
        data: {
          id: '20',
          message_id: '3',
          admin_id: '1',
          recipient: 'ana@example.test',
          body: 'Thanks for writing to us.',
          delivery_status: 'queued',
          attempts: 0,
          delivery_generation: 0,
          sent_at: null,
          error_code: null,
          created_at: new Date().toISOString(),
        },
      },
    })
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await detail(wrapper).find('#admin-inquiry-reply').setValue('Thanks for writing to us.')
    await findButton(detail(wrapper), 'Send reply').trigger('click')
    await flushPromises()
    await confirmDialog(wrapper, 'Queue reply')

    expect(detail(wrapper).text()).toContain('Queued')
    expect(detail(wrapper).text()).not.toContain('Sent')
    expect(detail(wrapper).text()).toContain('Thanks for writing to us.')
  })

  it('exposes Retry for a failed delivery and updates the same row', async () => {
    apiPost.mockResolvedValueOnce({
      data: { data: { ...failedReply, delivery_status: 'queued', error_code: null } },
    })
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Jose Farmer')

    const repliesBefore = wrapper.vm.selected.replies.length

    await wrapper.find('button[aria-label="Retry delivery of reply 11"]').trigger('click')
    await flushPromises()
    await confirmDialog(wrapper, 'Retry delivery')

    expect(wrapper.vm.selected.replies).toHaveLength(repliesBefore)
    expect(wrapper.vm.selected.replies[0].id).toBe('11')
    expect(wrapper.vm.selected.replies[0].delivery_status).toBe('queued')
    expect(detail(wrapper).text()).toContain('Queued')
  })

  it('disables close and retry while a reply is live sending', async () => {
    const sending = {
      ...failedReply,
      id: '13',
      delivery_status: 'sending',
      error_code: null,
      created_at: new Date().toISOString(),
    }
    const live = { ...readInquiry, replies: [sending] }
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/contact-messages') return listResponse([live])
      if (url === '/admin/contact-messages/5') return detailResponse(live)
      throw new Error(`Unexpected GET ${url}`)
    })

    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Jose Farmer')

    expect(detail(wrapper).text()).toContain('Sending')
    expect(findButton(detail(wrapper), 'Close inquiry').element.disabled).toBe(true)
    expect(wrapper.find('button[aria-label="Retry delivery of reply 13"]').element.disabled).toBe(
      true,
    )
  })

  it('shows Retry for a stale queued reply', async () => {
    const staleQueued = {
      ...failedReply,
      id: '14',
      delivery_status: 'queued',
      error_code: null,
      created_at: STALE_CREATED_AT,
    }
    const stale = { ...readInquiry, replies: [staleQueued] }
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/contact-messages') return listResponse([stale])
      if (url === '/admin/contact-messages/5') return detailResponse(stale)
      throw new Error(`Unexpected GET ${url}`)
    })

    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Jose Farmer')

    const retry = wrapper.find('button[aria-label="Retry delivery of reply 14"]')
    expect(retry.exists()).toBe(true)
    expect(retry.element.disabled).toBe(false)
  })

  it('shows a duplicate-delivery caution and Retry for a stale sending reply', async () => {
    const staleSending = {
      ...failedReply,
      id: '15',
      delivery_status: 'sending',
      error_code: null,
      created_at: STALE_CREATED_AT,
    }
    const stale = { ...readInquiry, replies: [staleSending] }
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/contact-messages') return listResponse([stale])
      if (url === '/admin/contact-messages/5') return detailResponse(stale)
      throw new Error(`Unexpected GET ${url}`)
    })

    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Jose Farmer')

    const retry = wrapper.find('button[aria-label="Retry delivery of reply 15"]')
    expect(retry.exists()).toBe(true)
    expect(retry.element.disabled).toBe(false)
    expect(detail(wrapper).text()).toContain('may already have been delivered')

    await retry.trigger('click')
    await flushPromises()
    expect(dialog(wrapper).text()).toContain('may already have been delivered')
  })

  it('keeps the draft and shows an inline dialog error when the reply is rejected', async () => {
    apiPost.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Another reply is already being delivered.' } },
    })
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await detail(wrapper).find('#admin-inquiry-reply').setValue('Thanks for writing to us.')
    await findButton(detail(wrapper), 'Send reply').trigger('click')
    await flushPromises()
    await confirmDialog(wrapper, 'Queue reply')

    expect(dialog(wrapper).exists()).toBe(true)
    expect(dialog(wrapper).text()).toContain('Another reply is already being delivered.')
    expect(wrapper.vm.draft).toBe('Thanks for writing to us.')
  })

  it('bounds the reply body and validates before confirming', async () => {
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    const composer = detail(wrapper).find('#admin-inquiry-reply')
    expect(composer.attributes('maxlength')).toBe('5000')

    await findButton(detail(wrapper), 'Send reply').trigger('click')
    await flushPromises()
    expect(detail(wrapper).text()).toContain('Enter a reply')
    expect(dialog(wrapper).exists()).toBe(false)
    expect(apiPost).not.toHaveBeenCalled()

    await composer.setValue('hello')
    expect(detail(wrapper).find('#admin-inquiry-reply-counter').text()).toContain('5 / 5000')

    wrapper.vm.draft = 'y'.repeat(5001)
    await wrapper.vm.$nextTick()
    await findButton(detail(wrapper), 'Send reply').trigger('click')
    await flushPromises()
    expect(detail(wrapper).text()).toContain('5,000 characters or fewer')
    expect(apiPost).not.toHaveBeenCalled()
  })

  it('regenerates the request key only after an accepted receipt', async () => {
    const queuedReply = {
      ...failedReply,
      id: '21',
      message_id: '3',
      delivery_status: 'queued',
      error_code: null,
      created_at: new Date().toISOString(),
    }
    apiPost.mockResolvedValueOnce({ data: { data: queuedReply } }).mockResolvedValueOnce({
      data: { data: { ...queuedReply, id: '22', delivery_status: 'queued' } },
    })
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await detail(wrapper).find('#admin-inquiry-reply').setValue('First reply.')
    await findButton(detail(wrapper), 'Send reply').trigger('click')
    await flushPromises()
    await confirmDialog(wrapper, 'Queue reply')
    const firstKey = apiPost.mock.calls[0][1].client_request_id

    wrapper.vm.selected.replies = [{ ...queuedReply, delivery_status: 'sent' }]
    await wrapper.vm.$nextTick()

    await detail(wrapper).find('#admin-inquiry-reply').setValue('Second reply.')
    await findButton(detail(wrapper), 'Send reply').trigger('click')
    await flushPromises()
    await confirmDialog(wrapper, 'Queue reply')

    expect(apiPost).toHaveBeenCalledTimes(2)
    expect(apiPost.mock.calls[1][1].client_request_id).not.toBe(firstKey)
  })

  it('clears the draft when another inquiry is selected', async () => {
    const wrapper = await mountPage()
    await openDetail(wrapper, 'View inquiry from Ana Buyer')

    await detail(wrapper).find('#admin-inquiry-reply').setValue('Unsent draft.')
    await openDetail(wrapper, 'View inquiry from Jose Farmer')

    expect(detail(wrapper).text()).toContain('Payout question')
    expect(wrapper.vm.draft).toBe('')
  })

  it('clears storage and reactive auth state when the list fetch returns 401', async () => {
    localStorage.setItem('auth_token', 'stale-token')
    apiGet.mockRejectedValueOnce({
      response: { status: 401, data: { message: 'Unauthenticated.' } },
    })

    await mountPage()

    const authStore = useAuthStore()
    expect(authStore.token).toBeNull()
    expect(authStore.user).toBeNull()
    expect(localStorage.getItem('auth_token')).toBeNull()
    expect(sessionStorage.getItem('auth_token')).toBeNull()
  })
})

describe('AdminInquiryDetail.vue', () => {
  it('renders a null message and malformed replies safely', () => {
    const wrapper = mount(AdminInquiryDetail, {
      props: { message: null, replies: [null, 'nope', {}], draft: '', busy: false },
    })

    expect(wrapper.text()).toContain('Select an inquiry')
  })

  it('emits draft, transition, reply and retry events', async () => {
    const message = {
      id: '5',
      name: 'Jose Farmer',
      email: 'jose@example.test',
      subject: 'Payout question',
      message: 'When will my payout arrive?',
      status: 'read',
      replies: [],
    }
    const failed = {
      id: '11',
      message_id: '5',
      recipient: 'jose@example.test',
      body: 'Sorry.',
      delivery_status: 'failed',
      created_at: STALE_CREATED_AT,
    }
    const wrapper = mount(AdminInquiryDetail, {
      props: { message, replies: [failed], draft: '', busy: false },
    })

    await wrapper.find('#admin-inquiry-reply').setValue('Hello')
    expect(wrapper.emitted('update:draft')).toBeTruthy()

    await wrapper
      .findAll('button')
      .find((button) => button.text().trim() === 'Close inquiry')
      .trigger('click')
    expect(wrapper.emitted('transition')).toEqual([['close']])

    await wrapper
      .findAll('button')
      .find((button) => button.text().trim() === 'Send reply')
      .trigger('click')
    expect(wrapper.emitted('reply')).toHaveLength(1)

    await wrapper.find('button[aria-label="Retry delivery of reply 11"]').trigger('click')
    expect(wrapper.emitted('retry')).toEqual([[failed]])
  })
})
