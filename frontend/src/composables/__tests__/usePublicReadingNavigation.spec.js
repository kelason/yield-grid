import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { usePublicReadingNavigation } from '../usePublicReadingNavigation'

let wrapper
afterEach(() => {
  wrapper?.unmount()
  vi.restoreAllMocks()
  document.body.innerHTML = ''
})

async function mountReading(mainRef) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/guide', component: { template: '<div />' }, meta: { publicReading: true } },
      { path: '/other', component: { template: '<div />' }, meta: { publicReading: true } },
      { path: '/dashboard', component: { template: '<div />' } },
    ],
  })
  await router.push('/guide')
  let reading
  wrapper = mount(
    {
      setup() {
        reading = usePublicReadingNavigation({ mainRef })
        return () => null
      },
    },
    { global: { plugins: [router] } },
  )
  await flushPromises()
  return { router, reading }
}

describe('public reading lifecycle', () => {
  it('waits safely for a missing main and stops pending work on unmount', async () => {
    const scroll = vi.spyOn(window, 'scrollTo').mockImplementation(() => {})
    const { reading } = await mountReading(ref(null))
    wrapper.unmount()
    await reading.onPageReady()
    expect(scroll).not.toHaveBeenCalled()
  })

  it('does not install global scroll restoration or scroll dashboard content', async () => {
    const main = document.createElement('main')
    main.innerHTML = '<h1 tabindex="-1">Guide</h1>'
    document.body.append(main)
    const mainScroll = vi.fn()
    main.scrollTo = mainScroll
    const scroll = vi.spyOn(window, 'scrollTo').mockImplementation(() => {})
    const original = history.scrollRestoration
    const { router } = await mountReading(ref(main))
    scroll.mockClear()
    await router.push('/dashboard')
    await flushPromises()
    expect(scroll).not.toHaveBeenCalled()
    expect(mainScroll).not.toHaveBeenCalled()
    expect(history.scrollRestoration).toBe(original)
  })
})
