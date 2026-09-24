import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useForumStore } from '../forumStore'

const mockGet = vi.fn()
const mockPost = vi.fn()

// Mock the API composable
vi.mock('@/composables/useApi', () => ({
  useApi: () => ({
    get: mockGet,
    post: mockPost,
  }),
}))

describe('forumStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches categories and updates state', async () => {
    const store = useForumStore()
    const mockCategories = [{ id: 1, name: 'General' }]

    mockGet.mockResolvedValueOnce({ data: { data: mockCategories } })

    await store.fetchCategories()

    expect(mockGet).toHaveBeenCalledWith('/forum/categories')
    expect(store.categories).toEqual(mockCategories)
  })

  it('handles new replies via socket', () => {
    const store = useForumStore()

    store.currentThread = {
      id: 1,
      replies: [],
      reply_count: 0,
    }

    const newReply = { id: 10, thread_id: 1, body: 'Real-time reply' }

    store.handleNewReply(newReply)

    expect(store.currentThread.replies).toHaveLength(1)
    expect(store.currentThread.replies[0]).toEqual(newReply)
    expect(store.currentThread.reply_count).toBe(1)
  })
})
