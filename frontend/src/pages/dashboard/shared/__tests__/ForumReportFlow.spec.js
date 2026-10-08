import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises, RouterLinkStub } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import ForumPage from '../ForumPage.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import ContentReportForm from '@/components/organisms/ContentReportForm.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    ...actual,
    useRoute: () => ({ query: {} }),
    useRouter: () => ({ replace: vi.fn() }),
  }
})

const AppModalStub = {
  name: 'AppModal',
  props: ['isOpen', 'title', 'busy'],
  template: '<div v-if="isOpen" data-testid="forum-dialog"><slot /><slot name="footer" /></div>',
}

describe('ForumPage.vue report flow', () => {
  const thread = {
    id: 1,
    user_id: 9,
    title: 'Harvest tips',
    body: 'Rotate your crops.',
    vote_score: 5,
    user_vote: 0,
    has_accepted_reply: false,
    last_activity_at: '2026-01-01T00:00:00Z',
    reply_count: 2,
    tags: [],
    category: null,
    author: { id: 9, name: 'Maria Farmer', avatar_url: null },
  }

  let pinia
  let apiGet
  let apiPost

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    localStorage.clear()
    sessionStorage.clear()
    apiGet = vi.fn(async (url) => {
      if (url === '/forum/categories') return { data: { data: [] } }
      if (url === '/forum/tags') return { data: { data: [] } }
      if (url === '/forum/threads') {
        return { data: { data: [thread], meta: { current_page: 1, last_page: 1, total: 1 } } }
      }
      throw new Error(`Unexpected GET ${url}`)
    })
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })
    const authStore = useAuthStore()
    authStore.token = 'test-token'
    authStore.user = { id: 3, role: 'buyer', email_verified_at: '2026-01-01T00:00:00Z' }
  })

  async function mountPage() {
    const wrapper = mount(ForumPage, {
      global: {
        plugins: [pinia],
        stubs: { AppModal: AppModalStub, RouterLink: RouterLinkStub },
      },
    })
    await flushPromises()
    return wrapper
  }

  function reportButton(wrapper) {
    return wrapper.findAll('button').find((button) => button.text().includes('Report'))
  }

  async function openReport(wrapper) {
    await reportButton(wrapper).trigger('click')
    await wrapper.vm.$nextTick()
  }

  async function fillAndSubmit(wrapper, reason = 'spam', description = 'Repeated ads') {
    const form = wrapper.findComponent(ContentReportForm)
    await form.find('select').setValue(reason)
    await form.find('textarea').setValue(description)
    await form.find('form').trigger('submit')
    await wrapper.vm.$nextTick()
  }

  async function confirmDialog(wrapper) {
    const confirm = wrapper.findComponent(ConfirmModal)
    await confirm
      .findAll('button')
      .find((button) => button.text() === 'Send report')
      .trigger('click')
    await flushPromises()
  }

  it('submits a thread report through the shared confirmation', async () => {
    const wrapper = await mountPage()
    apiPost.mockResolvedValueOnce({ data: { data: { id: '1', status: 'open' } } })

    await openReport(wrapper)
    expect(wrapper.findComponent(ContentReportForm).exists()).toBe(true)
    await fillAndSubmit(wrapper)
    expect(wrapper.findComponent(ConfirmModal).props('isOpen')).toBe(true)
    await confirmDialog(wrapper)

    expect(apiPost).toHaveBeenCalledTimes(1)
    expect(apiPost).toHaveBeenCalledWith('/reports', {
      reportable_type: 'thread',
      reportable_id: '1',
      reason: 'spam',
      description: 'Repeated ads',
    })
    expect(wrapper.findComponent(ContentReportForm).exists()).toBe(false)
  })

  it('sends zero requests when the report is cancelled', async () => {
    const wrapper = await mountPage()

    await openReport(wrapper)
    await wrapper
      .findComponent(ContentReportForm)
      .findAll('button')
      .find((button) => button.text() === 'Cancel')
      .trigger('click')
    await wrapper.vm.$nextTick()

    expect(apiPost).not.toHaveBeenCalled()
    expect(wrapper.findComponent(ContentReportForm).exists()).toBe(false)
  })

  it('keeps the draft and shows the error when submit fails', async () => {
    const wrapper = await mountPage()
    apiPost.mockRejectedValueOnce({
      response: { status: 429, data: { message: 'Too many reports. Slow down.' } },
    })

    await openReport(wrapper)
    await fillAndSubmit(wrapper)
    await confirmDialog(wrapper)

    const form = wrapper.findComponent(ContentReportForm)
    expect(form.exists()).toBe(true)
    expect(form.find('textarea').element.value).toBe('Repeated ads')
    expect(form.find('[role="alert"]').text()).toContain('Too many reports')
  })
})
