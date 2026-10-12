import { afterEach, describe, expect, it } from 'vitest'
import { computed, nextTick } from 'vue'
import { mount } from '@vue/test-utils'
import { createHead, renderDOMHead } from '@unhead/vue/client'
import { useI18n } from 'vue-i18n'
import { setLocale } from '@/i18n'
import { usePublicPageHead } from '../usePublicPageHead'

let wrapper
afterEach(() => {
  wrapper?.unmount()
  setLocale('en')
})

describe('public page metadata', () => {
  it('keeps page and social metadata synchronized with language changes', async () => {
    const head = createHead()
    wrapper = mount(
      {
        setup() {
          const { t } = useI18n()
          usePublicPageHead({
            title: computed(() => t('public.home.meta_title')),
            description: computed(() => t('public.home.meta_desc')),
          })
          return () => null
        },
      },
      { global: { plugins: [head] } },
    )
    setLocale('ceb')
    await nextTick()
    renderDOMHead(head)
    const title = document.title
    expect(document.querySelector('meta[property="og:title"]').content).toBe(title)
    expect(document.querySelector('meta[name="description"]').content).toBe(
      document.querySelector('meta[property="og:description"]').content,
    )
    expect(title).toContain('YieldGrid')
  })
})
