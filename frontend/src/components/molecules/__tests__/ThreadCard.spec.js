import { setActivePinia, createPinia } from 'pinia'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import ThreadCard from '../ThreadCard.vue'

describe('ThreadCard.vue author profile link', () => {
  let pinia

  const baseThread = {
    id: 1,
    title: 'Harvest tips',
    body: 'Rotate your crops.',
    vote_score: 5,
    user_vote: 0,
    has_accepted_reply: false,
    last_activity_at: '2026-01-01T00:00:00Z',
    reply_count: 2,
    tags: [],
    category: null,
  }

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
  })

  function mountCard(thread) {
    const wrapper = mount(ThreadCard, {
      props: { thread },
      global: {
        plugins: [pinia],
        stubs: { RouterLink: RouterLinkStub },
      },
    })
    const links = wrapper.findAllComponents(RouterLinkStub)
    return { wrapper, authorLink: links[1] }
  }

  it('links a named author to their profile', () => {
    const { wrapper, authorLink } = mountCard({
      ...baseThread,
      author: { id: 7, name: 'Maria Farmer', avatar_url: null },
    })

    expect(authorLink.props('to')).toEqual({
      name: 'user-profile',
      params: { userId: 7 },
    })
    expect(wrapper.text()).toContain('Maria Farmer')
  })

  it('links an anonymous author to the anonymous profile state', () => {
    const { authorLink } = mountCard({
      ...baseThread,
      author: { name: 'Anonymous Farmer', avatar_url: null },
    })

    expect(authorLink.props('to')).toEqual({
      name: 'user-profile',
      params: { userId: 'anonymous' },
    })
  })
})
