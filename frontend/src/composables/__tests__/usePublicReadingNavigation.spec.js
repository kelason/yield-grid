import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { usePublicReadingNavigation } from '../usePublicReadingNavigation'

let wrapper
afterEach(() => {
  wrapper?.unmount()
  vi.restoreAllMocks()
  vi.unstubAllGlobals()
  document.body.innerHTML = ''
  history.replaceState(null, '')
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

it('does not steal focus when a user interacts before the page finishes entering', async () => {
  const layout = document.createElement('div')
  layout.className = 'page-enter-active'
  layout.innerHTML = '<button>Open menu</button><main><h1 tabindex="-1">Guide</h1></main>'
  document.body.append(layout)
  vi.spyOn(window, 'scrollTo').mockImplementation(() => {})
  const { reading, router } = await mountReading(ref(layout.querySelector('main')))
  await router.push('/other')
  await flushPromises()
  const button = layout.querySelector('button')
  button.focus()
  button.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }))
  layout.classList.remove('page-enter-active')
  await reading.onPageReady()
  expect(document.activeElement).toBe(button)
})

it('starts fresh when a discarded forward position is reused and preserves unrelated history state', async () => {
  const main = document.createElement('main')
  main.innerHTML = '<h1 tabindex="-1">Guide</h1>'
  document.body.append(main)
  history.replaceState({ position: 41, otherState: 'keep' }, '')
  const scroll = vi.spyOn(window, 'scrollTo').mockImplementation(() => {})
  await mountReading(ref(main))
  vi.spyOn(window, 'scrollY', 'get').mockReturnValue(1100)
  window.dispatchEvent(new Event('scroll'))
  wrapper.unmount()
  history.replaceState({ position: 41, otherState: 'keep' }, '')
  await mountReading(ref(main))
  expect(scroll).toHaveBeenLastCalledWith({ top: 0, left: 0, behavior: 'instant' })
  expect(history.state.otherState).toBe('keep')
  history.replaceState(null, '')
})

it('leaves a dashboard history entry untouched while its route transition is pending', async () => {
  const main = document.createElement('main')
  main.innerHTML = '<h1 tabindex="-1">Guide</h1>'
  document.body.append(main)
  history.replaceState({ position: 55, current: '/guide' }, '')
  vi.spyOn(window, 'scrollTo').mockImplementation(() => {})
  await mountReading(ref(main))
  const dashboardEntry = { position: 54, current: '/dashboard' }
  history.replaceState(dashboardEntry, '')
  window.dispatchEvent(new PopStateEvent('popstate'))
  window.dispatchEvent(new Event('scroll'))
  expect(history.state).toEqual(dashboardEntry)
})

it('keeps public pages readable when secure-context UUID support is unavailable', async () => {
  vi.stubGlobal('crypto', {})
  const main = document.createElement('main')
  main.innerHTML = '<h1 tabindex="-1">Guide</h1>'
  document.body.append(main)
  history.replaceState({ position: 60, current: '/guide' }, '')
  vi.spyOn(window, 'scrollTo').mockImplementation(() => {})
  await mountReading(ref(main))
  expect(document.activeElement).toBe(main.querySelector('h1'))
  expect(history.state.yieldgridReadingEntry).toEqual(expect.any(String))
})
