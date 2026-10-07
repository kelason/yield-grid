import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi, afterEach, beforeEach } from 'vitest'
import L from 'leaflet'
import PlotDrawer from '../PlotDrawer.vue'

const apiGet = vi.hoisted(() => vi.fn())

vi.mock('../../../composables/useApi', () => ({
  useApi: () => ({ get: apiGet }),
}))

describe('PlotDrawer city locating overlay', () => {
  const originalSvg = L.Browser.svg
  beforeEach(() => {
    apiGet.mockResolvedValue({ data: null })
    L.Browser.svg = true
  })
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.restoreAllMocks()
    L.Browser.svg = originalSvg
  })

  it('ignores an old city boundary when the selected farm is cleared before geocoding settles', async () => {
    let resolve
    vi.stubGlobal(
      'fetch',
      vi.fn(
        () =>
          new Promise((done) => {
            resolve = done
          }),
      ),
    )
    const createMap = vi.spyOn(L, 'map')
    const wrapper = mountDrawer({ city: 'Cabanatuan City' })
    await flushPromises()
    const map = createMap.mock.results[0].value
    await wrapper.setProps({ farm: null })
    resolve({
      ok: true,
      json: async () => ({
        features: [{ properties: { extent: [120, 16, 122, 14], osm_value: 'city' } }],
      }),
    })
    await flushPromises()
    expect(map.options.maxBounds).toBeFalsy()
    wrapper.unmount()
  })

  it('keeps the newest farm boundary when an older lookup finishes last', async () => {
    const lookups = []
    vi.stubGlobal(
      'fetch',
      vi.fn(() => new Promise((resolve) => lookups.push(resolve))),
    )
    const createMap = vi.spyOn(L, 'map')
    const wrapper = mountDrawer({ city: 'Old city' })
    await flushPromises()
    await wrapper.setProps({ farm: { city: 'New city' } })
    const response = (extent) => ({
      ok: true,
      json: async () => ({ features: [{ properties: { extent, osm_value: 'city' } }] }),
    })
    lookups[1](response([121, 16, 122, 15]))
    await flushPromises()
    lookups[0](response([120, 14, 121, 13]))
    await flushPromises()
    expect(createMap.mock.results[0].value.options.maxBounds.getSouth()).toBeCloseTo(14.6)
    wrapper.unmount()
  })

  it('preserves the polygon callback coordinates and releases its map on unmount', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        headers: { get: () => 'application/json' },
        json: async () => ({ elements: [] }),
      }),
    )
    const createMap = vi.spyOn(L, 'map')
    const wrapper = mountDrawer({})
    const map = createMap.mock.results[0].value
    const remove = vi.spyOn(map, 'remove')
    const layer = L.polygon([
      [15, 121],
      [15, 121.01],
      [15.01, 121.01],
    ])
    map.fire(L.Draw.Event.CREATED, { layerType: 'polygon', layer })
    await flushPromises()
    expect(wrapper.emitted('plot-drawn')[0]).toEqual([
      { layer, coordinates: layer.toGeoJSON().geometry.coordinates[0] },
    ])
    wrapper.unmount()
    expect(remove).toHaveBeenCalledTimes(1)
  })

  it('updates its measured viewport after a container resize and disconnects its observer', async () => {
    let resized
    const disconnect = vi.fn()
    const observe = vi.fn()
    vi.stubGlobal(
      'ResizeObserver',
      class {
        constructor(callback) {
          resized = callback
        }
        observe(element) {
          observe(element)
        }
        disconnect() {
          disconnect()
        }
      },
    )
    const createMap = vi.spyOn(L, 'map')
    const wrapper = mountDrawer({})
    const map = createMap.mock.results[0].value
    const invalidate = vi.spyOn(map, 'invalidateSize')
    resized()
    expect(invalidate).toHaveBeenCalledTimes(1)
    expect(observe).toHaveBeenCalledWith(wrapper.get('.leaflet-container').element)
    wrapper.unmount()
    expect(disconnect).toHaveBeenCalledTimes(1)
  })

  it('renders restricted-zone names as text rather than HTML', async () => {
    const unsafe = '<img src=x onerror=alert(1)>'
    apiGet.mockResolvedValue({
      data: {
        type: 'FeatureCollection',
        features: [
          {
            type: 'Feature',
            geometry: { type: 'Point', coordinates: [121, 15] },
            properties: { name: unsafe, type: 'house' },
          },
        ],
      },
    })
    const geoJson = vi.spyOn(L, 'geoJSON')
    const wrapper = mountDrawer({})
    await flushPromises()
    const layer = geoJson.mock.results[0].value.getLayers()[0]
    const content = layer.getTooltip().getContent()
    const container = document.createElement('div')
    if (typeof content === 'string') container.innerHTML = content
    else container.append(content)
    expect(container.querySelector('img')).toBeNull()
    expect(container.textContent).toContain(unsafe)
    wrapper.unmount()
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
