import { setActivePinia, createPinia } from 'pinia'
import { flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useChatStore } from '../chatStore'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

const listenToConversation = vi.fn()
const leaveConversation = vi.fn()

vi.mock('@/composables/useChatWebSocket', () => ({
  useChatWebSocket: () => ({ listenToConversation, leaveConversation }),
}))

describe('chatStore envelopes', () => {
  let mockGet
  let mockPost
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockGet = vi.fn()
    mockPost = vi.fn()
    useApi.mockReturnValue({ get: mockGet, post: mockPost })
    store = useChatStore()
  })

  it('distinguishes a failed inbox load from an empty inbox and permits retry', async () => {
    mockGet.mockRejectedValueOnce(new Error('Offline'))
    await store.fetchConversations()
    expect(store.conversationsError).toContain('Please retry')
    mockGet.mockResolvedValueOnce({ data: { data: [] } })
    await store.fetchConversations()
    expect(store.conversationsError).toBe('')
  })

  it('unwraps the sent message so the bubble renders own style and time', async () => {
    const serverMessage = {
      id: 11,
      body: 'Hello',
      is_own: true,
      created_at: '2026-09-25T10:00:00.000000Z',
    }
    mockPost.mockResolvedValueOnce({ data: { data: serverMessage } })
    store.activeConversation = { id: 5 }
    store.conversations = [{ id: 5, unread_count: 0 }]

    const sent = await store.sendMessage(5, 'Hello')

    expect(sent).toEqual(serverMessage)
    expect(store.messages).toEqual([serverMessage])
    expect(store.messages[0].is_own).toBe(true)
    expect(
      new Date(store.messages[0].created_at).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
      }),
    ).not.toBe('Invalid Date')
    expect(store.conversations[0].latest_message).toEqual(serverMessage)
  })

  it('unwraps a started conversation', async () => {
    mockPost.mockResolvedValueOnce({ data: { data: { id: 9 } } })

    const conv = await store.startConversation(4)

    expect(conv).toEqual({ id: 9 })
    expect(store.conversations).toEqual([{ id: 9 }])
  })

  it('bumps unread and reorders when a background conversation gets a message', () => {
    store.activeConversation = { id: 1 }
    store.conversations = [
      { id: 1, unread_count: 0 },
      { id: 2, unread_count: 0 },
    ]

    store.handleNewMessage({
      id: 50,
      conversation_id: 2,
      body: 'hi',
      created_at: '2026-09-25T11:00:00.000000Z',
    })

    expect(store.conversations[0].id).toBe(2)
    expect(store.conversations[0].unread_count).toBe(1)
    expect(store.totalUnread).toBe(1)
    expect(store.messages).toEqual([])
  })

  it('appends without an unread bump when the active conversation gets a message', () => {
    const message = {
      id: 51,
      conversation_id: 1,
      body: 'hi again',
      created_at: '2026-09-25T11:00:00.000000Z',
    }
    store.activeConversation = { id: 1 }
    store.conversations = [{ id: 1, unread_count: 0 }]

    store.handleNewMessage(message)

    expect(store.messages).toEqual([message])
    expect(store.conversations[0].unread_count).toBe(0)
    expect(store.totalUnread).toBe(0)
  })

  it('subscribes to every conversation exactly once', () => {
    store.conversations = [{ id: 1 }, { id: 2 }]

    store.startRealtimeSync()
    store.startRealtimeSync()

    expect(listenToConversation).toHaveBeenCalledTimes(2)
    expect(listenToConversation.mock.calls.map((call) => call[0])).toEqual([1, 2])
  })

  it('leaves every channel on stop and allows resubscribing', () => {
    store.conversations = [{ id: 1 }, { id: 2 }]
    store.startRealtimeSync()

    store.stopRealtimeSync()

    expect(leaveConversation.mock.calls.map((call) => call[0])).toEqual([1, 2])

    store.startRealtimeSync()
    expect(listenToConversation).toHaveBeenCalledTimes(4)
  })

  it('refetches and subscribes when a message arrives for an unknown conversation', async () => {
    mockGet.mockResolvedValueOnce({ data: { data: [{ id: 9, unread_count: 1 }] } })
    store.conversations = [{ id: 1, unread_count: 0 }]

    store.handleNewMessage({
      id: 60,
      conversation_id: 9,
      body: 'brand new chat',
      created_at: '2026-09-25T11:00:00.000000Z',
    })
    await flushPromises()

    expect(mockGet).toHaveBeenCalledWith('/chat/conversations')
    expect(listenToConversation).toHaveBeenCalledWith(9, expect.anything())
  })

  it('subscribes to a newly started conversation', async () => {
    mockPost.mockResolvedValueOnce({ data: { data: { id: 9 } } })

    await store.startConversation(4)

    expect(listenToConversation).toHaveBeenCalledWith(9, expect.anything())
  })

  it('dedups parallel inbox loads into a single request', async () => {
    mockGet.mockResolvedValue({ data: { data: [] } })

    await Promise.all([store.ensureConversationsLoaded(), store.ensureConversationsLoaded()])

    expect(mockGet).toHaveBeenCalledTimes(1)
  })
})
