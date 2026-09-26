import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useForumStore } from '@/stores/forumStore'
import ThreadPage from '../ThreadPage.vue'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('@/composables/useForumWebSocket', () => ({
  useForumWebSocket: () => ({ listenToThread: vi.fn(), leaveThread: vi.fn() }),
}))

vi.mock('vue-router', () => ({
  useRoute: () => ({ params: { id: '1' } }),
  useRouter: () => ({ push: vi.fn() }),
}))

describe('ThreadPage.vue reply composer', () => {
  const thread = { id: 1, user_id: 9, reply_count: 0, replies: [] }

  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    useApi.mockReturnValue({
      get: vi.fn().mockResolvedValue({ data: thread }),
      post: vi.fn().mockResolvedValue({ data: {} }),
    })
  })

  async function mountPage() {
    const wrapper = mount(ThreadPage, {
      global: {
        plugins: [pinia],
        stubs: { ThreadCard: true, ReplyCard: true },
      },
    })
    await flushPromises()
    return wrapper
  }

  it('caps the reply body at 5000 characters', async () => {
    const wrapper = await mountPage()

    expect(wrapper.find('textarea').attributes('maxlength')).toBe('5000')
  })

  it('shows the live reply character count', async () => {
    const wrapper = await mountPage()

    await wrapper.find('textarea').setValue('hello')

    expect(wrapper.text()).toContain('5/5000')
  })

  it('does not post a reply over 5000 characters', async () => {
    const wrapper = await mountPage()
    const forumStore = useForumStore()
    const createReply = vi.spyOn(forumStore, 'createReply')

    await wrapper.find('textarea').setValue('a'.repeat(5001))
    await wrapper.find('form').trigger('submit.prevent')

    expect(createReply).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('5001/5000')
  })

  it('posts a reply within the limit', async () => {
    const wrapper = await mountPage()
    const forumStore = useForumStore()
    const createReply = vi.spyOn(forumStore, 'createReply')

    await wrapper.find('textarea').setValue('A helpful reply')
    await wrapper.find('form').trigger('submit.prevent')

    expect(createReply).toHaveBeenCalledWith(1, {
      body: 'A helpful reply',
      is_anonymous: false,
    })
  })
})
