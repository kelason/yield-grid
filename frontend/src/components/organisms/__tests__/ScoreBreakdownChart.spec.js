import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import ScoreBreakdownChart from '../ScoreBreakdownChart.vue'
import { CREDIT_DIMENSIONS } from '@/constants/creditScoring'

const SCORES = {
  plot_activity: 80,
  recommendation_adherence: 65,
  contract_fulfillment: 95,
  offer_reliability: 75,
  transaction_volume: 60,
  platform_tenure: 50,
}

function mountChart() {
  return mount(ScoreBreakdownChart, { props: { dimensionScores: SCORES } })
}

function infoButton(wrapper, label) {
  return wrapper.find(`button[aria-label="About ${label}"]`)
}

describe('ScoreBreakdownChart', () => {
  beforeEach(() => {
    vi.stubGlobal('requestAnimationFrame', (callback) => {
      callback()
      return 0
    })
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('renders every dimension label with its score', () => {
    const wrapper = mountChart()

    for (const dimension of CREDIT_DIMENSIONS) {
      expect(wrapper.text()).toContain(dimension.label)
      expect(wrapper.text()).toContain(`${SCORES[dimension.key]}/100`)
      expect(infoButton(wrapper, dimension.label).exists()).toBe(true)
    }
  })

  it('hides all descriptions initially', () => {
    const wrapper = mountChart()

    for (const dimension of CREDIT_DIMENSIONS) {
      expect(wrapper.find(`#dimension-info-${dimension.key}`).exists()).toBe(false)
      expect(infoButton(wrapper, dimension.label).attributes('aria-expanded')).toBe('false')
    }
  })

  it('reveals the description when its info button is clicked', async () => {
    const wrapper = mountChart()
    const dimension = CREDIT_DIMENSIONS[0]

    await infoButton(wrapper, dimension.label).trigger('click')

    const panel = wrapper.find(`#dimension-info-${dimension.key}`)
    expect(panel.exists()).toBe(true)
    expect(panel.text()).toBe(dimension.description)
    expect(infoButton(wrapper, dimension.label).attributes('aria-expanded')).toBe('true')
  })

  it('collapses on a second click and switches between rows', async () => {
    const wrapper = mountChart()
    const [first, second] = CREDIT_DIMENSIONS

    await infoButton(wrapper, first.label).trigger('click')
    expect(wrapper.find(`#dimension-info-${first.key}`).exists()).toBe(true)

    await infoButton(wrapper, first.label).trigger('click')
    expect(wrapper.find(`#dimension-info-${first.key}`).exists()).toBe(false)

    await infoButton(wrapper, first.label).trigger('click')
    await infoButton(wrapper, second.label).trigger('click')
    expect(wrapper.find(`#dimension-info-${first.key}`).exists()).toBe(false)
    expect(wrapper.find(`#dimension-info-${second.key}`).exists()).toBe(true)
  })
})
