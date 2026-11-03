import { afterEach, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createHead } from '@unhead/vue/client'
import { createPinia } from 'pinia'
import { nextTick } from 'vue'
import i18n, { setLocale } from '@/i18n'
import HowItWorksPage from '../HowItWorksPage.vue'

let wrapper
afterEach(() => {
  wrapper?.unmount()
  setLocale('en')
})

async function mountPage(locale) {
  setLocale(locale)
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/how-it-works', component: HowItWorksPage },
      { path: '/documentation/:topic', name: 'documentation-topic', component: HowItWorksPage },
      { path: '/documentation/getting-started', component: HowItWorksPage },
      { path: '/documentation/profile-addresses-language', component: HowItWorksPage },
    ],
  })
  await router.push('/how-it-works')
  wrapper = mount(HowItWorksPage, {
    global: { plugins: [router, i18n, createHead(), createPinia()] },
  })
  await nextTick()
  return wrapper
}

it('embeds the platform walkthrough at the top in every locale', async () => {
  for (const locale of ['en', 'tl', 'ceb']) {
    await mountPage(locale)
    const video = wrapper.find('video')
    expect(video.exists()).toBe(true)
    expect(video.attributes('poster')).toBe('/videos/yieldgridph-tutorial-poster.jpg')
    expect(video.attributes('preload')).toBe('metadata')
    expect(video.attributes('controls')).toBeDefined()
    expect(video.attributes('autoplay')).toBeUndefined()
    expect(video.find('source').attributes('src')).toBe('/videos/yieldgridph-complete-tutorial.mp4')
    expect(wrapper.find('#walkthrough-heading').text()).toBe(
      i18n.global.t('public.how_it_works.video_title'),
    )
    expect(wrapper.text()).not.toContain('public.how_it_works.video_')
    const openLink = wrapper.find('a[href="/videos/yieldgridph-complete-tutorial.mp4"]')
    expect(openLink.exists()).toBe(true)
    wrapper.unmount()
    wrapper = undefined
  }
})
