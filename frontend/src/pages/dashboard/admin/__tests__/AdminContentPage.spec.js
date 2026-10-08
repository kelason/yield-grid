import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import AdminContentPage from '../AdminContentPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

const AppModalStub = {
  name: 'AppModal',
  props: ['isOpen', 'title', 'busy'],
  template:
    '<div v-if="isOpen" data-testid="content-moderation-dialog"><slot /><slot name="footer" /></div>',
}

function contractItem() {
  return {
    type: 'contract',
    id: '7',
    title: 'Corn lot',
    description: 'Fresh corn',
    crop_name: 'Corn',
    status: 'available',
    author: { id: '9', name: 'Jose Farmer', email: 'jose@example.test' },
    is_hidden: false,
    hidden_at: null,
    hidden_by: null,
    hidden_reason: null,
    moderation_root_id: null,
    affected_root_id: '7',
    is_effectively_hidden: false,
  }
}

function cloneItem() {
  return {
    ...contractItem(),
    id: '9',
    title: 'Corn lot split',
    moderation_root_id: '7',
    affected_root_id: '7',
  }
}

function listResponse(items) {
  return {
    data: {
      data: items,
      meta: { current_page: 1, last_page: 1, total: items.length },
    },
  }
}

describe('AdminContentPage.vue', () => {
  let apiGet
  let apiPost

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
    apiGet = vi.fn()
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })
  })

  async function mountPage() {
    apiGet.mockImplementation(async (url) => {
      if (url === '/admin/content/thread') return listResponse([])
      if (url === '/admin/content/contract') return listResponse([contractItem(), cloneItem()])
      if (url === '/admin/content/contract/7') return { data: { data: contractItem() } }
      if (url === '/admin/content/contract/9') return { data: { data: cloneItem() } }
      if (url.startsWith('/admin/content/')) return listResponse([])
      throw new Error(`Unexpected GET ${url}`)
    })
    const wrapper = mount(AdminContentPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()
    return wrapper
  }

  async function selectType(wrapper, type) {
    await wrapper.find(`[data-testid="content-type-${type}"]`).trigger('click')
    await flushPromises()
  }

  async function inspectItem(wrapper, id) {
    await wrapper.find(`[data-testid="content-inspect-${id}"]`).trigger('click')
    await flushPromises()
  }

  it('browses each of the five content types', async () => {
    const wrapper = await mountPage()

    for (const type of ['thread', 'reply', 'contract', 'listing', 'demand']) {
      expect(wrapper.find(`[data-testid="content-type-${type}"]`).exists()).toBe(true)
    }

    await selectType(wrapper, 'contract')

    expect(apiGet).toHaveBeenCalledWith('/admin/content/contract', {
      params: { page: 1, per_page: 10 },
    })
    expect(wrapper.text()).toContain('Corn lot')
  })

  it('filters by search and visibility', async () => {
    const wrapper = await mountPage()
    await selectType(wrapper, 'contract')

    await wrapper.find('input').setValue('corn')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(apiGet.mock.calls.at(-1)).toEqual([
      '/admin/content/contract',
      { params: { page: 1, per_page: 10, search: 'corn' } },
    ])

    await wrapper.find('select').setValue('hidden')
    await flushPromises()

    expect(apiGet.mock.calls.at(-1)[1].params).toMatchObject({ visibility: 'hidden' })
  })

  it('shows a retry before the empty state', async () => {
    apiGet.mockRejectedValueOnce(new Error('Network down'))
    const wrapper = mount(AdminContentPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Network down')
    expect(wrapper.text()).not.toContain('Nothing here')
  })

  it('inspects detail through the admin endpoint with moderation state', async () => {
    const wrapper = await mountPage()
    await selectType(wrapper, 'contract')
    await inspectItem(wrapper, '7')

    expect(apiGet).toHaveBeenCalledWith('/admin/content/contract/7')
    expect(wrapper.text()).toContain('Corn lot')
    expect(wrapper.text()).toContain('Visible')
    expect(wrapper.text()).toContain('Jose Farmer')
  })

  it('requires a reason before hiding', async () => {
    const wrapper = await mountPage()
    await selectType(wrapper, 'contract')
    await inspectItem(wrapper, '7')

    await wrapper.find('[data-testid="content-hide"]').trigger('click')
    await wrapper
      .find('[data-testid="content-moderation-dialog"]')
      .findAll('button')
      .find((button) => button.text() === 'Hide content')
      .trigger('click')

    expect(wrapper.text()).toContain('Enter a reason')
    expect(apiPost).not.toHaveBeenCalled()
  })

  it('hides content with a reason and refreshes visibility', async () => {
    const wrapper = await mountPage()
    await selectType(wrapper, 'contract')
    await inspectItem(wrapper, '7')
    apiPost.mockResolvedValueOnce({
      data: {
        data: { ...contractItem(), is_hidden: true, hidden_reason: 'Spam.' },
        meta: { selected_id: '7', affected_root_id: '7' },
      },
    })

    await wrapper.find('[data-testid="content-hide"]').trigger('click')
    const dialog = wrapper.find('[data-testid="content-moderation-dialog"]')
    await dialog.find('textarea').setValue('Spam.')
    await dialog
      .findAll('button')
      .find((button) => button.text() === 'Hide content')
      .trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledWith('/admin/content/contract/7/hide', { reason: 'Spam.' })
    expect(wrapper.text()).toContain('Hidden')
  })

  it('names the affected split family when moderating a clone', async () => {
    const wrapper = await mountPage()
    await selectType(wrapper, 'contract')
    await inspectItem(wrapper, '9')

    await wrapper.find('[data-testid="content-hide"]').trigger('click')

    const dialog = wrapper.find('[data-testid="content-moderation-dialog"]')
    expect(dialog.text()).toContain('7')
    expect(dialog.text()).toContain('split family')
    expect(dialog.text()).toContain('future split')
  })

  it('restores hidden content', async () => {
    const wrapper = await mountPage()
    await selectType(wrapper, 'contract')
    apiGet.mockResolvedValueOnce({
      data: { data: { ...contractItem(), is_hidden: true, hidden_reason: 'Spam.' } },
    })
    await inspectItem(wrapper, '7')
    apiPost.mockResolvedValueOnce({
      data: {
        data: { ...contractItem(), is_hidden: false },
        meta: { selected_id: '7', affected_root_id: '7' },
      },
    })

    await wrapper.find('[data-testid="content-restore"]').trigger('click')
    const dialog = wrapper.find('[data-testid="content-moderation-dialog"]')
    await dialog.find('textarea').setValue('Appeal upheld.')
    await dialog
      .findAll('button')
      .find((button) => button.text() === 'Restore content')
      .trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledWith('/admin/content/contract/7/restore', {
      reason: 'Appeal upheld.',
    })
    expect(wrapper.text()).toContain('Visible')
  })

  it('surfaces repeated-state conflicts without losing the reason', async () => {
    const wrapper = await mountPage()
    await selectType(wrapper, 'contract')
    await inspectItem(wrapper, '7')
    apiPost.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Content is already hidden.' } },
    })

    await wrapper.find('[data-testid="content-hide"]').trigger('click')
    const dialog = wrapper.find('[data-testid="content-moderation-dialog"]')
    await dialog.find('textarea').setValue('Spam.')
    await dialog
      .findAll('button')
      .find((button) => button.text() === 'Hide content')
      .trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('already hidden')
    expect(
      wrapper.find('[data-testid="content-moderation-dialog"]').find('textarea').element.value,
    ).toBe('Spam.')
  })
})
