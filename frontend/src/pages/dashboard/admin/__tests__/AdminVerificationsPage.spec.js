import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import AdminVerificationsPage from '../AdminVerificationsPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

const ConfirmModalStub = {
  name: 'ConfirmModal',
  props: ['isOpen', 'title', 'message', 'confirmText', 'type', 'loading'],
  emits: ['confirm', 'cancel'],
  template: `
    <div v-if="isOpen" data-testid="confirm-dialog">
      <p data-testid="confirm-message">{{ message }}</p>
      <button data-testid="dialog-confirm" @click="$emit('confirm')">confirm</button>
      <button data-testid="dialog-cancel" @click="$emit('cancel')">cancel</button>
    </div>
  `,
}

function farmEntry(overrides = {}) {
  return {
    id: 7,
    type: 'farm',
    name: 'Green Acres',
    verification_status: 'pending',
    verification_method: null,
    verification_note: null,
    farmer: { id: 3, name: 'Jose Farmer', email: 'jose@example.test' },
    ...overrides,
  }
}

function listResponse(data) {
  return {
    data: {
      data,
      meta: { current_page: 1, last_page: 1, total: data.length },
    },
  }
}

describe('AdminVerificationsPage', () => {
  let apiGet
  let apiPost

  function mountPage() {
    return mount(AdminVerificationsPage, {
      global: {
        stubs: { VerificationPlotMap: true, ConfirmModal: ConfirmModalStub },
      },
    })
  }

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
    apiGet = vi.fn()
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })
  })

  it('renders queue rows from the mocked response', async () => {
    apiGet.mockResolvedValueOnce(
      listResponse([farmEntry(), farmEntry({ id: 8, name: 'River Plot' })]),
    )
    const wrapper = mountPage()
    await flushPromises()

    const rows = wrapper.findAll('[data-testid="verification-row"]')
    expect(rows).toHaveLength(2)
    expect(rows[0].text()).toContain('Green Acres')
  })

  it('retries the queue after a load failure', async () => {
    apiGet.mockRejectedValueOnce({ response: { status: 500, data: {} }, message: 'boom' })
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="queue-error"]').exists()).toBe(true)
    apiGet.mockResolvedValueOnce(listResponse([farmEntry()]))
    await wrapper.find('[data-testid="queue-retry"]').trigger('click')
    await flushPromises()

    expect(wrapper.findAll('[data-testid="verification-row"]')).toHaveLength(1)
  })

  it('switches scope tabs and refetches the queue', async () => {
    apiGet.mockResolvedValue(listResponse([]))
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('[data-testid="scope-plots"]').trigger('click')
    await flushPromises()

    expect(apiGet).toHaveBeenLastCalledWith('/admin/verifications', {
      params: { page: 1, per_page: 10, scope: 'plots', status: 'pending' },
    })
  })

  it('opens the detail panel with method and note fields on row click', async () => {
    apiGet.mockResolvedValueOnce(listResponse([farmEntry()]))
    apiGet.mockResolvedValueOnce({ data: { data: farmEntry() } })
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('[data-testid="verification-row"]').trigger('click')
    await flushPromises()

    expect(apiGet).toHaveBeenCalledWith('/admin/verifications/farms/7')
    expect(wrapper.find('[data-testid="verification-detail"]').exists()).toBe(true)
    expect(wrapper.find('#verify-method').exists()).toBe(true)
    expect(wrapper.find('#verify-note').exists()).toBe(true)
  })

  it('blocks verify without a method and keeps the dialog closed', async () => {
    apiGet.mockResolvedValueOnce(listResponse([farmEntry()]))
    apiGet.mockResolvedValueOnce({ data: { data: farmEntry() } })
    const wrapper = mountPage()
    await flushPromises()
    await wrapper.find('[data-testid="verification-row"]').trigger('click')
    await flushPromises()

    await wrapper.find('[data-testid="decision-verify"]').trigger('click')
    await flushPromises()

    expect(wrapper.find('[data-testid="confirm-dialog"]').exists()).toBe(false)
    expect(wrapper.find('#verify-method-error').exists()).toBe(true)
    expect(apiPost).not.toHaveBeenCalled()
  })

  it('opens confirm on verify and fires the decision', async () => {
    apiGet.mockResolvedValueOnce(listResponse([farmEntry()]))
    apiGet.mockResolvedValueOnce({ data: { data: farmEntry() } })
    apiPost.mockResolvedValueOnce({
      data: { data: farmEntry({ verification_status: 'verified' }) },
    })
    const wrapper = mountPage()
    await flushPromises()
    await wrapper.find('[data-testid="verification-row"]').trigger('click')
    await flushPromises()

    await wrapper.find('#verify-method').setValue('field_visit')
    await wrapper.find('#verify-note').setValue('Visited today')
    await wrapper.find('[data-testid="decision-verify"]').trigger('click')
    await flushPromises()

    expect(wrapper.find('[data-testid="confirm-dialog"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="confirm-message"]').text()).toContain('Green Acres')
    await wrapper.find('[data-testid="dialog-confirm"]').trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledWith('/admin/verifications/farms/7/verify', {
      method: 'field_visit',
      note: 'Visited today',
    })
    expect(wrapper.find('[data-testid="confirm-dialog"]').exists()).toBe(false)
  })

  it('keeps the draft and shows the error in the dialog after a failed confirm', async () => {
    apiGet.mockResolvedValueOnce(listResponse([farmEntry()]))
    apiGet.mockResolvedValueOnce({ data: { data: farmEntry() } })
    apiPost.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Cannot verify a verified record.' } },
    })
    const wrapper = mountPage()
    await flushPromises()
    await wrapper.find('[data-testid="verification-row"]').trigger('click')
    await flushPromises()

    await wrapper.find('#verify-method').setValue('field_visit')
    await wrapper.find('#verify-note').setValue('Visited today')
    await wrapper.find('[data-testid="decision-verify"]').trigger('click')
    await flushPromises()
    await wrapper.find('[data-testid="dialog-confirm"]').trigger('click')
    await flushPromises()

    expect(wrapper.find('[data-testid="confirm-dialog"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="confirm-message"]').text()).toContain(
      'Cannot verify a verified record.',
    )
    expect(wrapper.find('#verify-method').element.value).toBe('field_visit')
    expect(wrapper.find('#verify-note').element.value).toBe('Visited today')
  })
})
