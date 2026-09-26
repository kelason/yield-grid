import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useChatStore } from '../chatStore'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('chatStore envelopes', () => {
  let mockPost
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    mockPost = vi.fn()
    useApi.mockReturnValue({ get: vi.fn(), post: mockPost })
    store = useChatStore()
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
})
