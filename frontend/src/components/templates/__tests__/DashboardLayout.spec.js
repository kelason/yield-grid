import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import { useChatStore } from '@/stores/chatStore'
import i18n from '@/i18n'
import DashboardLayout from '../DashboardLayout.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

const listenToConversation = vi.fn()
const leaveConversation = vi.fn()

vi.mock('@/composables/useChatWebSocket', () => ({
  useChatWebSocket: () => ({ listenToConversation, leaveConversation }),
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
  RouterLink: { template: '<a><slot /></a>' },
  RouterView: { template: '<div />' },
}))

describe('DashboardLayout.vue chat sync', () => {
  const conversations = [
    { id: 1, unread_count: 1, last_message_at: '2026-09-25T10:00:00.000000Z' },
    { id: 2, unread_count: 2, last_message_at: '2026-09-25T09:00:00.000000Z' },
  ]

  let pinia
  let mockGet

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    vi.clearAllMocks()
    mockGet = vi.fn().mockResolvedValue({ data: { data: structuredClone(conversations) } })
    useApi.mockReturnValue({ get: mockGet, post: vi.fn() })

    const authStore = useAuthStore()
    authStore.token = 'test-token'
    authStore.user = { id: 7, name: 'Cara', email_verified_at: '2026-01-01T00:00:00.000000Z' }
  })

  async function mountLayout() {
    const wrapper = mount(DashboardLayout, {
      global: {
        plugins: [pinia, i18n],
        stubs: {
          AppSidebar: true,
          AppButton: true,
          EmailVerificationBanner: true,
        },
      },
    })
    await flushPromises()
    return wrapper
  }

  it('loads the inbox so the sidebar badge survives a refresh on any page', async () => {
    await mountLayout()
    const chatStore = useChatStore()

    expect(mockGet).toHaveBeenCalledWith('/chat/conversations')
    expect(chatStore.totalUnread).toBe(3)
  })

  it('subscribes to every conversation, including background ones', async () => {
    await mountLayout()

    const subscribedIds = listenToConversation.mock.calls.map((call) => call[0])
    expect(subscribedIds).toContain(1)
    expect(subscribedIds).toContain(2)
  })

  it('bumps the sidebar total when a background message arrives', async () => {
    await mountLayout()
    const chatStore = useChatStore()
    chatStore.conversations.forEach((c) => (c.unread_count = 0))

    const bgCall = listenToConversation.mock.calls.find((call) => call[0] === 2)
    expect(bgCall).toBeDefined()
    bgCall[1].onMessage({
      message: {
        id: 99,
        conversation_id: 2,
        body: 'New from Bob',
        created_at: '2026-09-25T11:00:00.000000Z',
      },
    })

    expect(chatStore.conversations.find((c) => c.id === 2).unread_count).toBe(1)
    expect(chatStore.totalUnread).toBe(1)
  })

  it('leaves every channel on unmount', async () => {
    const wrapper = await mountLayout()

    wrapper.unmount()

    const leftIds = leaveConversation.mock.calls.map((call) => call[0])
    expect(leftIds).toContain(1)
    expect(leftIds).toContain(2)
  })

  it('skips chat sync for unverified users (chat endpoints return 403)', async () => {
    const authStore = useAuthStore()
    authStore.user = { id: 7, name: 'Cara', email_verified_at: null }

    await mountLayout()

    expect(mockGet).not.toHaveBeenCalled()
    expect(listenToConversation).not.toHaveBeenCalled()
  })

  it('makes zero chat or bootstrap requests for a verified admin', async () => {
    const authStore = useAuthStore()
    authStore.user = {
      id: 1,
      name: 'Op Admin',
      role: 'admin',
      email_verified_at: '2026-01-01T00:00:00Z',
    }

    await mountLayout()

    expect(mockGet).not.toHaveBeenCalled()
    expect(listenToConversation).not.toHaveBeenCalled()
  })
})
