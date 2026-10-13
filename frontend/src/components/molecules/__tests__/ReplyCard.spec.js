import { setActivePinia, createPinia } from 'pinia'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import ReplyCard from '../ReplyCard.vue'

describe('ReplyCard.vue mobile action row', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    useAuthStore().user = { id: 2, name: 'Cara Santos', role: 'buyer' }
  })

  it('wraps the action row and keeps the Report button intact', () => {
    const wrapper = mount(ReplyCard, {
      props: {
        reply: {
          id: 10,
          body: 'Mulch heavily around the base of each plant.',
          vote_score: 3,
          user_vote: 0,
          is_accepted: false,
          created_at: '2026-10-07T01:00:00Z',
          author: { id: 9, name: 'Juan Dela Cruz Mendoza', role: 'farmer', avatar_url: null },
        },
      },
      global: {
        plugins: [i18n],
        stubs: { RouterLink: RouterLinkStub },
      },
    })

    const report = wrapper.find('button[aria-label="Report this reply"]')
    expect(report.exists()).toBe(true)
    expect(report.classes()).toContain('whitespace-nowrap')
    expect(report.classes()).toContain('shrink-0')
    expect(report.element.parentElement.classList.contains('flex-wrap')).toBe(true)
  })
})
