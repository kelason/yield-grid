import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import PriceGuideHint from '../PriceGuideHint.vue'

const fetchComparison = vi.fn()

vi.mock('@/composables/usePriceGuide', () => ({
  usePriceGuide: () => ({ fetchComparison }),
}))

describe('PriceGuideHint', () => {
  const yieldgridSection = {
    available: true,
    price_per_kg: 30,
    listings_count: 4,
    market_name: 'YieldGrid marketplace (4 listings)',
    observed_at: '2026-10-01',
  }

  const daSection = {
    available: true,
    price_per_kg: 24.5,
    tiers: {
      farmgate: {
        price_per_kg: 24.5,
        source: 'da_bantay_presyo',
        market_name: 'National Average',
        observed_at: '2026-10-01',
      },
      retail: {
        price_per_kg: 50,
        source: 'da_bantay_presyo',
        market_name: 'National Average',
        observed_at: '2026-10-01',
      },
    },
    is_stale: false,
    observed_at: '2026-10-01',
  }

  const aiSection = {
    available: true,
    pending: false,
    price_per_kg: 45,
    tiers: {
      retail: {
        price_per_kg: 45,
        source: 'ai_estimate',
        market_name: 'AI estimate',
        observed_at: '2026-10-01',
      },
    },
    is_stale: false,
    observed_at: '2026-10-01',
  }

  const sectionBySource = {
    yieldgrid: yieldgridSection,
    da: daSection,
    ai: aiSection,
  }

  function compareResponse(sections, overrides = {}) {
    return {
      available: true,
      pending: false,
      resolved_from: 'yieldgrid',
      crop_slug: 'rice',
      crop_display_name: 'Rice',
      match_type: 'exact',
      corrected_from: null,
      sections,
      ...overrides,
    }
  }

  function mockAllSources() {
    fetchComparison.mockImplementation((crop, region, sources) => {
      const sections = {}
      for (const source of sources) {
        sections[source] = sectionBySource[source]
      }
      return Promise.resolve(compareResponse(sections))
    })
  }

  beforeEach(() => {
    vi.clearAllMocks()
    vi.useFakeTimers()
    mockAllSources()
  })

  async function mountHint(props = {}) {
    const wrapper = mount(PriceGuideHint, {
      props: { cropName: 'rice', ...props },
    })
    vi.advanceTimersByTime(500)
    await flushPromises()
    return wrapper
  }

  it('loads price sources sequentially in YieldGrid, DA, AI order', async () => {
    const resolvers = {}
    fetchComparison.mockImplementation(
      (crop, region, sources) =>
        new Promise((resolve) => {
          resolvers[sources[0]] = () =>
            resolve(compareResponse({ [sources[0]]: sectionBySource[sources[0]] }))
        }),
    )

    const wrapper = mount(PriceGuideHint, { props: { cropName: 'rice' } })
    vi.advanceTimersByTime(500)
    await flushPromises()

    expect(fetchComparison).toHaveBeenCalledTimes(1)
    expect(fetchComparison).toHaveBeenNthCalledWith(1, 'rice', null, ['yieldgrid'])
    expect(wrapper.text()).toContain('YieldGrid price')
    expect(wrapper.text()).not.toContain('DA price')
    expect(wrapper.text()).not.toContain('AI estimate')

    resolvers.yieldgrid()
    await flushPromises()

    expect(fetchComparison).toHaveBeenNthCalledWith(2, 'rice', null, ['da'])
    expect(wrapper.text()).toContain('DA price')
    expect(wrapper.text()).not.toContain('AI estimate')

    resolvers.da()
    await flushPromises()

    expect(fetchComparison).toHaveBeenNthCalledWith(3, 'rice', null, ['ai'])

    resolvers.ai()
    await flushPromises()

    expect(wrapper.text()).toContain('AI estimate')
    expect(fetchComparison).toHaveBeenCalledTimes(3)
  })

  it('shows all three prices side by side', async () => {
    const wrapper = await mountHint()

    expect(wrapper.text()).toContain('YieldGrid price')
    expect(wrapper.text()).toContain('DA price')
    expect(wrapper.text()).toContain('AI estimate')
    expect(wrapper.text()).toContain('30')
    expect(wrapper.text()).toContain('24.5')
    expect(wrapper.text()).toContain('50')
    expect(wrapper.text()).toContain('45')
  })

  it('keeps available sections when others are missing', async () => {
    fetchComparison.mockImplementation((crop, region, sources) => {
      const sections = {}
      if (sources.includes('yieldgrid')) {
        sections.yieldgrid = { available: false }
      }
      if (sources.includes('da')) {
        sections.da = daSection
      }
      if (sources.includes('ai')) {
        sections.ai = { available: false, pending: false }
      }
      return Promise.resolve(compareResponse(sections, { resolved_from: 'da' }))
    })

    const wrapper = await mountHint()

    expect(wrapper.text()).toContain('DA price')
    expect(wrapper.text()).toContain('24.5')
    expect(wrapper.text()).toMatch(/no yieldgrid price/i)
    expect(wrapper.text()).toMatch(/no ai estimate/i)
    expect(wrapper.text()).not.toMatch(/no price guide/i)
  })

  it('polls the AI section while pending, then shows the estimate', async () => {
    let aiCalls = 0
    fetchComparison.mockImplementation((crop, region, sources) => {
      if (sources.includes('ai')) {
        aiCalls += 1
        const section = aiCalls === 1 ? { available: false, pending: true } : aiSection
        return Promise.resolve(compareResponse({ ai: section }))
      }
      const sections = {}
      for (const source of sources) {
        sections[source] = sectionBySource[source]
      }
      return Promise.resolve(compareResponse(sections))
    })

    const wrapper = await mountHint()
    expect(wrapper.text()).toContain('AI estimate')

    vi.advanceTimersByTime(8000)
    await flushPromises()

    expect(wrapper.text()).toContain('45')
    expect(fetchComparison).toHaveBeenCalledTimes(4)
    expect(fetchComparison).toHaveBeenNthCalledWith(4, 'rice', null, ['ai'], { force: true })
  })

  it('stops polling AI after max attempts', async () => {
    fetchComparison.mockImplementation((crop, region, sources) => {
      if (sources.includes('ai')) {
        return Promise.resolve(
          compareResponse({ ai: { available: false, pending: true } }, { available: false }),
        )
      }
      const sections = {}
      for (const source of sources) {
        sections[source] = sectionBySource[source]
      }
      return Promise.resolve(compareResponse(sections))
    })

    const wrapper = await mountHint()
    for (let i = 0; i < 7; i++) {
      vi.advanceTimersByTime(8000)
      await flushPromises()
    }

    expect(fetchComparison).toHaveBeenCalledTimes(9)
    expect(wrapper.text()).toMatch(/no ai estimate/i)
  })

  it('shows a quiet note when every source is missing', async () => {
    fetchComparison.mockResolvedValue(
      compareResponse(
        {
          yieldgrid: { available: false },
          da: { available: false },
          ai: { available: false, pending: false },
        },
        { available: false, resolved_from: null },
      ),
    )

    const wrapper = await mountHint({ cropName: '1231sadasd' })

    expect(wrapper.text()).toMatch(/no price guide/i)
    expect(wrapper.text()).not.toContain('YieldGrid price')
  })

  it('compares the entered price against the YieldGrid-first reference', async () => {
    const wrapper = await mountHint({ currentPrice: 40 })

    // 40 vs the YieldGrid 30 (not the DA 24.50): 33% above.
    expect(wrapper.text()).toContain('33% above')
  })

  it('notes when the crop name was corrected', async () => {
    fetchComparison.mockImplementation((crop, region, sources) => {
      const sections = {}
      for (const source of sources) {
        sections[source] = sectionBySource[source]
      }
      return Promise.resolve(
        compareResponse(sections, {
          match_type: 'fuzzy',
          corrected_from: 'rcie',
        }),
      )
    })

    const wrapper = await mountHint({ cropName: 'rcie' })

    expect(wrapper.text()).toContain('Rice')
    expect(wrapper.text()).toContain('rcie')
  })

  it('warns when DA prices are stale', async () => {
    fetchComparison.mockImplementation((crop, region, sources) => {
      const sections = {}
      for (const source of sources) {
        sections[source] = sectionBySource[source]
      }
      sections.da = { ...daSection, is_stale: true }
      return Promise.resolve(compareResponse(sections))
    })

    const wrapper = await mountHint()

    expect(wrapper.text()).toMatch(/outdated|stale/i)
  })

  it('shows a spinner while a section loads', async () => {
    fetchComparison.mockImplementationOnce(() => new Promise(() => {}))

    const wrapper = mount(PriceGuideHint, { props: { cropName: 'rice' } })
    vi.advanceTimersByTime(500)
    await flushPromises()

    expect(wrapper.find('.loading-spinner').exists()).toBe(true)
    expect(wrapper.text()).toContain('YieldGrid price')
  })

  it('ignores a stale sequence when the crop changes quickly', async () => {
    let resolveRiceYg
    fetchComparison.mockImplementation((crop, region, sources) => {
      if (crop === 'rice' && sources.includes('yieldgrid')) {
        return new Promise((resolve) => {
          resolveRiceYg = () =>
            resolve(
              compareResponse(
                { yieldgrid: yieldgridSection },
                { crop_slug: 'rice', crop_display_name: 'Rice' },
              ),
            )
        })
      }
      const sections = {}
      for (const source of sources) {
        sections[source] = sectionBySource[source]
      }
      return Promise.resolve(
        compareResponse(sections, { crop_slug: 'potato', crop_display_name: 'Potato' }),
      )
    })

    const wrapper = mount(PriceGuideHint, { props: { cropName: 'rice' } })
    vi.advanceTimersByTime(500)
    await flushPromises()

    await wrapper.setProps({ cropName: 'potato' })
    vi.advanceTimersByTime(500)
    await flushPromises()

    resolveRiceYg()
    await flushPromises()

    expect(wrapper.text()).toContain('Potato')
    expect(wrapper.text()).not.toContain('Rice')
  })

  it('shows how many sources have loaded', async () => {
    fetchComparison.mockImplementationOnce(() => new Promise(() => {}))

    const loading = mount(PriceGuideHint, { props: { cropName: 'rice' } })
    vi.advanceTimersByTime(500)
    await flushPromises()
    expect(loading.text()).toContain('0 of 3')

    const done = await mountHint()
    expect(done.text()).toContain('3 of 3')
  })

  it('renders nothing for an empty crop name', async () => {
    const wrapper = await mountHint({ cropName: '' })

    expect(fetchComparison).not.toHaveBeenCalled()
    expect(wrapper.html()).toBe('<!--v-if-->')
  })
})
