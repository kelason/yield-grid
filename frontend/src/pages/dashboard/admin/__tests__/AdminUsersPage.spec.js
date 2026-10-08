import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import AdminUsersPage from '../AdminUsersPage.vue'

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
    '<div v-if="isOpen" data-testid="admin-user-dialog"><slot /><slot name="footer" /></div>',
}

describe('AdminUsersPage.vue', () => {
  const activeUser = {
    id: '7',
    name: 'Maria Farmer',
    email: 'maria@example.test',
    role: 'farmer',
    email_verified_at: '2026-01-01T00:00:00Z',
    suspended_at: null,
    suspended_reason: null,
    created_at: '2026-01-01T00:00:00Z',
  }

  const suspendedUser = {
    id: '9',
    name: 'Jose Buyer',
    email: 'jose@example.test',
    role: 'buyer',
    email_verified_at: '2026-01-01T00:00:00Z',
    suspended_at: '2026-02-01T00:00:00Z',
    suspended_reason: 'Repeated spam reports.',
    created_at: '2026-01-01T00:00:00Z',
  }

  function listResponse(users, meta = {}) {
    return {
      data: {
        data: users,
        meta: { current_page: 1, last_page: 1, total: users.length, ...meta },
      },
    }
  }

  let apiGet
  let apiPost

  beforeEach(() => {
    setActivePinia(createPinia())
    mockRoute.query = {}
    localStorage.clear()
    sessionStorage.clear()
    apiGet = vi.fn().mockResolvedValue(listResponse([activeUser, suspendedUser]))
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
    const wrapper = mount(AdminUsersPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()
    return wrapper
  }

  function dialog(wrapper) {
    return wrapper.find('[data-testid="admin-user-dialog"]')
  }

  function findButton(wrapper, text) {
    return wrapper.findAll('button').find((button) => button.text().trim() === text)
  }

  async function openDialog(wrapper, label) {
    await wrapper.find(`button[aria-label="${label}"]`).trigger('click')
    await flushPromises()
  }

  it('seeds role and suspended filters from the route query', async () => {
    mockRoute.query = { role: 'buyer', suspended: 'suspended' }
    apiGet.mockResolvedValueOnce(listResponse([]))
    await mountPage()

    expect(apiGet).toHaveBeenCalledWith('/admin/users', {
      params: expect.objectContaining({ role: 'buyer', suspended: true }),
    })
  })

  it('seeds the combined members role from the route query', async () => {
    mockRoute.query = { role: 'members' }
    apiGet.mockResolvedValueOnce(listResponse([]))
    await mountPage()

    expect(apiGet).toHaveBeenCalledWith('/admin/users', {
      params: expect.objectContaining({ role: 'members' }),
    })
  })

  it('loads the first page of users on entry', async () => {
    const wrapper = await mountPage()

    expect(apiGet).toHaveBeenCalledWith(
      '/admin/users',
      expect.objectContaining({ params: expect.objectContaining({ page: 1 }) }),
    )
    expect(wrapper.text()).toContain('Maria Farmer')
    expect(wrapper.text()).toContain('Jose Buyer')
    expect(wrapper.find('h1').text()).toBe('Users')
  })

  it('shows loading state while fetching', async () => {
    let resolveFetch
    apiGet.mockReturnValueOnce(
      new Promise((resolve) => {
        resolveFetch = resolve
      }),
    )

    const wrapper = mount(AdminUsersPage, {
      global: { stubs: { AppModal: AppModalStub } },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Loading users')

    resolveFetch(listResponse([activeUser]))
    await flushPromises()
    expect(wrapper.text()).toContain('Maria Farmer')
  })

  it('shows an error with retry and recovers to data', async () => {
    apiGet
      .mockRejectedValueOnce({ response: { status: 500, data: { message: 'Server blew up.' } } })
      .mockResolvedValueOnce(listResponse([activeUser]))

    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('Server blew up.')
    expect(wrapper.find('[role="alert"]').exists()).toBe(true)

    await findButton(wrapper, 'Retry').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Maria Farmer')
    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
  })

  it('keeps filters across a retry', async () => {
    apiGet
      .mockRejectedValueOnce({ response: { status: 500, data: {} } })
      .mockRejectedValueOnce({ response: { status: 500, data: {} } })
      .mockResolvedValueOnce(listResponse([activeUser]))

    const wrapper = await mountPage()

    await wrapper.find('#admin-user-search').setValue('maria')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(wrapper.text()).toContain('Unable to load users.')

    await findButton(wrapper, 'Retry').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Maria Farmer')
    expect(apiGet).toHaveBeenLastCalledWith(
      '/admin/users',
      expect.objectContaining({ params: expect.objectContaining({ search: 'maria' }) }),
    )
  })

  it('shows an empty state when no users match', async () => {
    apiGet.mockResolvedValueOnce(listResponse([]))

    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('No users found')
  })

  it('bounds the search input and validates before fetching', async () => {
    const wrapper = await mountPage()
    const search = wrapper.find('#admin-user-search')

    expect(search.attributes('maxlength')).toBe('100')

    wrapper.vm.filters.search = 'x'.repeat(101)
    await wrapper.vm.$nextTick()
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('100 characters or fewer')
    expect(apiGet).toHaveBeenCalledTimes(1)
  })

  it('applies role and status filters and resets them', async () => {
    const wrapper = await mountPage()
    apiGet.mockClear()

    await wrapper.find('#admin-user-role').setValue('buyer')
    await flushPromises()
    expect(apiGet).toHaveBeenCalledWith(
      '/admin/users',
      expect.objectContaining({ params: expect.objectContaining({ role: 'buyer', page: 1 }) }),
    )

    await wrapper.find('#admin-user-status').setValue('suspended')
    await flushPromises()
    expect(apiGet).toHaveBeenCalledWith(
      '/admin/users',
      expect.objectContaining({
        params: expect.objectContaining({ role: 'buyer', suspended: true, page: 1 }),
      }),
    )

    await findButton(wrapper, 'Reset').trigger('click')
    await flushPromises()
    expect(apiGet).toHaveBeenLastCalledWith(
      '/admin/users',
      expect.objectContaining({
        params: expect.not.objectContaining({ role: 'buyer', suspended: true }),
      }),
    )
  })

  it('paginates through the list', async () => {
    apiGet.mockResolvedValueOnce(
      listResponse([activeUser], { current_page: 1, last_page: 3, total: 25 }),
    )

    const wrapper = await mountPage()

    await findButton(wrapper, 'Next').trigger('click')
    await flushPromises()

    expect(apiGet).toHaveBeenLastCalledWith(
      '/admin/users',
      expect.objectContaining({ params: expect.objectContaining({ page: 2 }) }),
    )
  })

  it('renders null and incomplete users safely', async () => {
    apiGet.mockResolvedValueOnce(listResponse([null, { id: '11' }]))

    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('Unknown user')
    expect(wrapper.find('button[aria-label="Suspend Unknown user"]').exists()).toBe(true)
  })

  it('sends zero POST requests when the confirmation is cancelled', async () => {
    const wrapper = await mountPage()

    await openDialog(wrapper, 'Suspend Maria Farmer')
    expect(dialog(wrapper).exists()).toBe(true)

    await findButton(dialog(wrapper), 'Cancel').trigger('click')
    await flushPromises()

    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('suspends with exactly one POST carrying the trimmed reason', async () => {
    apiPost.mockResolvedValueOnce({
      data: { data: { ...activeUser, suspended_at: '2026-03-01T00:00:00Z' } },
    })
    const wrapper = await mountPage()

    await openDialog(wrapper, 'Suspend Maria Farmer')
    await dialog(wrapper).find('#admin-user-reason').setValue('  Abusive messages.  ')
    await findButton(dialog(wrapper), 'Suspend user').trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(1)
    expect(apiPost).toHaveBeenCalledWith('/admin/users/7/suspend', {
      reason: 'Abusive messages.',
    })
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('restores a suspended user through the unsuspend endpoint', async () => {
    apiPost.mockResolvedValueOnce({ data: { data: { ...suspendedUser, suspended_at: null } } })
    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('Suspended')

    await openDialog(wrapper, 'Restore Jose Buyer')
    await dialog(wrapper).find('#admin-user-reason').setValue('Appeal accepted.')
    await findButton(dialog(wrapper), 'Restore user').trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledTimes(1)
    expect(apiPost).toHaveBeenCalledWith('/admin/users/9/unsuspend', {
      reason: 'Appeal accepted.',
    })
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('keeps decimal-string IDs exact in mutation URLs', async () => {
    const bigId = '9007199254740993'
    apiGet.mockResolvedValueOnce(listResponse([{ ...activeUser, id: bigId }]))
    apiPost.mockResolvedValueOnce({ data: { data: { ...activeUser, id: bigId } } })

    const wrapper = await mountPage()

    await openDialog(wrapper, 'Suspend Maria Farmer')
    await dialog(wrapper).find('#admin-user-reason').setValue('Abusive messages.')
    await findButton(dialog(wrapper), 'Suspend user').trigger('click')
    await flushPromises()

    expect(apiPost).toHaveBeenCalledWith(`/admin/users/${bigId}/suspend`, expect.anything())
    expect(apiPost.mock.calls[0][0]).not.toContain('9007199254740992')
  })

  it('requires a non-blank reason of at most 500 characters', async () => {
    const wrapper = await mountPage()
    const reason = () => dialog(wrapper).find('#admin-user-reason')

    await openDialog(wrapper, 'Suspend Maria Farmer')
    expect(reason().attributes('maxlength')).toBe('500')

    await findButton(dialog(wrapper), 'Suspend user').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Enter a reason')
    expect(apiPost).not.toHaveBeenCalled()

    await reason().setValue('   ')
    await findButton(dialog(wrapper), 'Suspend user').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Enter a reason')
    expect(apiPost).not.toHaveBeenCalled()

    wrapper.vm.reasonDraft = 'y'.repeat(501)
    await wrapper.vm.$nextTick()
    await findButton(dialog(wrapper), 'Suspend user').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('500 characters or fewer')
    expect(apiPost).not.toHaveBeenCalled()
    expect(dialog(wrapper).exists()).toBe(true)
  })

  it('blocks a duplicate submit while the mutation is busy', async () => {
    let resolvePost
    apiPost.mockReturnValueOnce(
      new Promise((resolve) => {
        resolvePost = resolve
      }),
    )
    const wrapper = await mountPage()

    await openDialog(wrapper, 'Suspend Maria Farmer')
    await dialog(wrapper).find('#admin-user-reason').setValue('Abusive messages.')

    const confirm = findButton(dialog(wrapper), 'Suspend user')
    await confirm.trigger('click')
    await confirm.trigger('click')
    expect(apiPost).toHaveBeenCalledTimes(1)

    resolvePost({ data: { data: { ...activeUser, suspended_at: '2026-03-01T00:00:00Z' } } })
    await flushPromises()
    expect(dialog(wrapper).exists()).toBe(false)
  })

  it('keeps the dialog open with the failed reason preserved on API failure', async () => {
    apiPost.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Already suspended.' } },
    })
    const wrapper = await mountPage()

    await openDialog(wrapper, 'Suspend Maria Farmer')
    await dialog(wrapper).find('#admin-user-reason').setValue('Abusive messages.')
    await findButton(dialog(wrapper), 'Suspend user').trigger('click')
    await flushPromises()

    expect(dialog(wrapper).exists()).toBe(true)
    expect(dialog(wrapper).text()).toContain('Already suspended.')
    expect(dialog(wrapper).find('#admin-user-reason').element.value).toBe('Abusive messages.')
    expect(wrapper.vm.reasonDraft).toBe('Abusive messages.')
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

  it('clears storage and reactive auth state on an account_suspended failure', async () => {
    localStorage.setItem('auth_token', 'stale-token')
    apiPost.mockRejectedValueOnce({
      response: {
        status: 403,
        data: {
          message: 'This account is suspended. Contact support for assistance.',
          code: 'account_suspended',
        },
      },
    })
    const wrapper = await mountPage()

    await openDialog(wrapper, 'Suspend Maria Farmer')
    await dialog(wrapper).find('#admin-user-reason').setValue('Abusive messages.')
    await findButton(dialog(wrapper), 'Suspend user').trigger('click')
    await flushPromises()

    const authStore = useAuthStore()
    expect(authStore.token).toBeNull()
    expect(authStore.user).toBeNull()
    expect(localStorage.getItem('auth_token')).toBeNull()
    expect(sessionStorage.getItem('auth_token')).toBeNull()
    expect(dialog(wrapper).text()).toContain('This account is suspended')
  })
})
