import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi, afterEach } from 'vitest'
import PlotDrawer from '../PlotDrawer.vue'

vi.mock('../../../composables/useApi', () => ({
  useApi: () => ({ get: vi.fn().mockResolvedValue({ data: null }) }),
}))

describe('PlotDrawer city locating overlay', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.restoreAllMocks()
  })

  function mountDrawer(farm) {
    return mount(PlotDrawer, {
      props: {
        farm,
        existingPlots: { type: 'FeatureCollection', features: [] },
      },
      attachTo: document.body,
    })
  }

  it('shows a locating overlay with the city name while geocoding the farm city', async () => {
    let resolveFetch
    const pending = new Promise((resolve) => {
      resolveFetch = resolve
    })
    vi.stubGlobal('fetch', vi.fn().mockReturnValue(pending))

    const wrapper = mountDrawer({ city: 'Cabanatuan City', state: 'Nueva Ecija' })
    await flushPromises()

    const overlay = wrapper.find('[data-testid="city-locating-overlay"]')
    expect(overlay.exists()).toBe(true)
    expect(overlay.text()).toContain('Cabanatuan City')

    resolveFetch({ ok: true, json: async () => ({ features: [] }) })
    await flushPromises()
    wrapper.unmount()
  })

  it('hides the overlay once geocoding finishes', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({ ok: true, json: async () => ({ features: [] }) }),
    )

    const wrapper = mountDrawer({ city: 'Cabanatuan City' })
    await flushPromises()

    expect(wrapper.find('[data-testid="city-locating-overlay"]').exists()).toBe(false)
    wrapper.unmount()
  })

  it('renders the overlay without crashing when the farm becomes null mid-geocode', async () => {
    let resolveFetch
    const pending = new Promise((resolve) => {
      resolveFetch = resolve
    })
    vi.stubGlobal('fetch', vi.fn().mockReturnValue(pending))

    const wrapper = mountDrawer({ city: 'Cabanatuan City', state: 'Nueva Ecija' })
    await flushPromises()
    expect(wrapper.find('[data-testid="city-locating-overlay"]').exists()).toBe(true)

    await wrapper.setProps({ farm: null })
    expect(wrapper.find('[data-testid="city-locating-overlay"]').exists()).toBe(true)

    resolveFetch({ ok: true, json: async () => ({ features: [] }) })
    await flushPromises()
    wrapper.unmount()
  })

  it('never shows the overlay when the farm has no city', async () => {
    const fetchMock = vi.fn()
    vi.stubGlobal('fetch', fetchMock)

    const wrapper = mountDrawer({})
    await flushPromises()

    expect(fetchMock).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="city-locating-overlay"]').exists()).toBe(false)
    wrapper.unmount()
  })
})
