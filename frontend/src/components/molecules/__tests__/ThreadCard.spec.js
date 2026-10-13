import { setActivePinia, createPinia } from 'pinia'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n from '@/i18n'
import { useAuthStore } from '@/stores/auth'
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

describe('ThreadCard.vue mobile meta row', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    useAuthStore().user = { id: 2, name: 'Cara Santos', role: 'buyer' }
  })

  it('wraps the meta row and keeps the Report button intact', () => {
    const wrapper = mount(ThreadCard, {
      props: {
        thread: {
          id: 8,
          title: 'Watering tips',
          body: 'Conserve water.',
          vote_score: 12,
          user_vote: 0,
          has_accepted_reply: false,
          last_activity_at: '2026-10-07T00:00:00Z',
          reply_count: 24,
          tags: [],
          category: { id: 1, name: 'Crop Protection & Pest Control' },
          author: { id: 9, name: 'Juan Dela Cruz Mendoza', avatar_url: null },
        },
      },
      global: {
        plugins: [i18n],
        stubs: { RouterLink: RouterLinkStub },
      },
    })

    const report = wrapper.find('button[aria-label="Report this discussion"]')
    expect(report.exists()).toBe(true)
    expect(report.classes()).toContain('whitespace-nowrap')
    expect(report.classes()).toContain('shrink-0')
    expect(report.element.parentElement.classList.contains('flex-wrap')).toBe(true)
  })
})
