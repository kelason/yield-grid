import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notificationStore'
import { useChatEntry } from '../useChatEntry'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('vue-router', () => ({
  useRouter: vi.fn(),
}))

describe('useChatEntry', () => {
  let mockPost
  let mockPush

  beforeEach(() => {
    setActivePinia(createPinia())
    mockPost = vi.fn()
    useApi.mockReturnValue({ get: vi.fn(), post: mockPost })
    mockPush = vi.fn()
    useRouter.mockReturnValue({ push: mockPush })
  })

  it('starts a conversation and routes to the chat inbox with it selected', async () => {
    mockPost.mockResolvedValueOnce({ data: { id: 9 } })

    const { openChat } = useChatEntry()
    await openChat(4)

    expect(mockPost).toHaveBeenCalledWith('/chat/conversations', { recipient_id: 4 })
    expect(mockPush).toHaveBeenCalledWith({ name: 'chat', query: { conversation: 9 } })
  })

  it('toasts a transaction-partner message on 403 without navigating', async () => {
    mockPost.mockRejectedValueOnce({ response: { status: 403 } })

    const { openChat } = useChatEntry()
    await openChat(4)

    const notificationStore = useNotificationStore()
    expect(notificationStore.notifications.at(-1).message).toMatch(/transaction/i)
    expect(mockPush).not.toHaveBeenCalled()
  })

  it('toasts when the recipient is missing without calling the api', async () => {
    const { openChat } = useChatEntry()
    await openChat(undefined)

    expect(mockPost).not.toHaveBeenCalled()
    expect(mockPush).not.toHaveBeenCalled()
    const notificationStore = useNotificationStore()
    expect(notificationStore.notifications.length).toBeGreaterThan(0)
  })
})
