import { afterEach, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createHead, renderDOMHead } from '@unhead/vue/client'
import { nextTick } from 'vue'
import i18n, { setLocale } from '@/i18n'
import { PLATFORM_FEATURES } from '@/constants/platformFeatures'
import { getGuide } from '@/constants/userGuides'
import FeaturesPage from '../FeaturesPage.vue'
import AboutPage from '../AboutPage.vue'
let wrapper
afterEach(() => {
  wrapper?.unmount()
  setLocale('en')
})
it('groups all eight features with valid guide links and reactive metadata', async () => {
  expect(PLATFORM_FEATURES).toHaveLength(8)
  expect(
    ['plan-grow', 'trade-connect', 'farm-support'].map(
      (id) => PLATFORM_FEATURES.filter((feature) => feature.groupId === id).length,
    ),
  ).toEqual([3, 3, 2])
  for (const feature of PLATFORM_FEATURES)
    expect(getGuide(feature.guide.topic).sections.map((section) => section.id)).toContain(
      feature.guide.sectionId,
    )
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/auth/register', name: 'register', component: { template: '<div />' } },
      { path: '/marketplace', name: 'marketplace', component: { template: '<div />' } },
      { path: '/features', component: FeaturesPage },
      {
        path: '/documentation/:topic',
        name: 'documentation-topic',
        component: { template: '<div />' },
      },
    ],
  })
  await router.push('/features')
  const head = createHead()
  wrapper = mount(FeaturesPage, { global: { plugins: [router, head] } })
  expect(wrapper.findAll('a[href^="/documentation/"]')).toHaveLength(8)
  setLocale('tl')
  await nextTick()
  renderDOMHead(head)
  expect(document.title).toContain(i18n.global.t('public.features.title'))
  wrapper.unmount()
  wrapper = mount(AboutPage, { global: { plugins: [router] } })
  for (const feature of PLATFORM_FEATURES)
    expect(wrapper.text()).toContain(i18n.global.t(feature.titleKey))
})
