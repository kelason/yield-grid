import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useChatStore } from '@/stores/chatStore'
import ChatPage from '../ChatPage.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

// Realtime sync is owned by the dashboard layout; the chat store still
// imports the websocket composable, so it must be mocked here.
vi.mock('@/composables/useChatWebSocket', () => ({
  useChatWebSocket: () => ({ listenToConversation: vi.fn(), leaveConversation: vi.fn() }),
}))

const routeQuery = { value: {} }

vi.mock('vue-router', () => ({
  useRoute: () => ({ query: routeQuery.value }),
  RouterLink: { template: '<a><slot /></a>' },
}))

describe('ChatPage.vue inbox', () => {
  const conversations = [
    {
      id: 1,
      unread_count: 2,
      last_message_at: '2026-09-25T10:00:00.000000Z',
      other_participant: { id: 2, name: 'Alice', role: 'buyer' },
      latest_message: { body: 'Hi' },
    },
    {
      id: 2,
      unread_count: 3,
      last_message_at: '2026-09-25T09:00:00.000000Z',
      other_participant: { id: 3, name: 'Bob', role: 'farmer' },
      latest_message: { body: 'Hello' },
    },
  ]

  let pinia
  let mockGet

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    vi.clearAllMocks()
    routeQuery.value = {}
    mockGet = vi.fn((url) => {
      if (url === '/chat/conversations') {
        return Promise.resolve({ data: { data: structuredClone(conversations) } })
      }
      // fetchMessages: /chat/conversations/:id?page=1
      return Promise.resolve({ data: { data: [], meta: {} } })
    })
    useApi.mockReturnValue({ get: mockGet, post: vi.fn() })
  })

  async function mountPage() {
    const wrapper = mount(ChatPage, {
      global: {
        plugins: [pinia],
        stubs: {
          ConversationItem: true,
          ChatComposer: true,
          ChatBubble: true,
        },
      },
    })
    await flushPromises()
    return wrapper
  }

  it('asks before sending and retains the draft after a failed confirmed send', async () => {
    const wrapper = await mountPage()
    const store = useChatStore()
    store.sendMessage = vi
      .fn()
      .mockRejectedValue({ response: { data: { message: 'Please retry sending.' } } })
    wrapper.findComponent({ name: 'ChatComposer' }).vm.$emit('send', 'Draft')
    await flushPromises()
    expect(store.sendMessage).not.toHaveBeenCalled()
    expect(wrapper.findComponent(ConfirmModal).props('isOpen')).toBe(true)
    wrapper.findComponent(ConfirmModal).vm.$emit('confirm')
    await flushPromises()
    expect(store.sendMessage).toHaveBeenCalledWith(1, 'Draft')
    expect(wrapper.findComponent({ name: 'ChatComposer' }).props('sentMessage')).toBe('')
    expect(wrapper.text()).toContain('Please retry sending.')
    wrapper.unmount()
  })

  it('shows a failed message load and retries the selected conversation', async () => {
    let fail = true
    mockGet.mockImplementation((url) => {
      if (url === '/chat/conversations')
        return Promise.resolve({ data: { data: structuredClone(conversations) } })
      if (fail) return Promise.reject(new Error('Messages unavailable'))
      return Promise.resolve({
        data: {
          data: [{ id: 7, body: 'Existing message', created_at: '2026-10-07T00:00:00Z' }],
          meta: {},
        },
      })
    })
    const wrapper = await mountPage()
    expect(wrapper.text()).toContain('Failed to load messages. Please retry.')
    expect(wrapper.text()).not.toContain('Say hello to start the conversation')
    fail = false
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Retry messages')
      .trigger('click')
    await flushPromises()
    expect(wrapper.findComponent({ name: 'ChatBubble' }).props('body')).toBe('Existing message')
    expect(wrapper.text()).not.toContain('Failed to load messages. Please retry.')
    expect(useChatStore().activeConversation?.id).toBe(1)
    wrapper.unmount()
  })

  it('loads the inbox and auto-selects the first conversation', async () => {
    const wrapper = await mountPage()
    const store = useChatStore()

    expect(mockGet).toHaveBeenCalledWith('/chat/conversations')
    expect(mockGet).toHaveBeenCalledWith('/chat/conversations/1?page=1')
    expect(store.activeConversation?.id).toBe(1)
    expect(wrapper.findAllComponents({ name: 'ConversationItem' })).toHaveLength(2)
  })

  it('honors the requested conversation query param', async () => {
    routeQuery.value = { conversation: '2' }

    await mountPage()
    const store = useChatStore()

    expect(mockGet).toHaveBeenCalledWith('/chat/conversations/2?page=1')
    expect(store.activeConversation?.id).toBe(2)
  })

  it('selecting a conversation loads its messages and clears its unread count', async () => {
    const wrapper = await mountPage()
    const store = useChatStore()

    const items = wrapper.findAllComponents({ name: 'ConversationItem' })
    items[1].vm.$emit('select', 2)
    await flushPromises()

    expect(mockGet).toHaveBeenCalledWith('/chat/conversations/2?page=1')
    expect(store.conversations.find((c) => c.id === 2).unread_count).toBe(0)
  })
})
