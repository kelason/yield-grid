import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import FloodRiskLegend from '../FloodRiskLegend.vue'

describe('FloodRiskLegend', () => {
  it('renders four risk steps with swatches', () => {
    const wrapper = mount(FloodRiskLegend, { props: { level: 'medium', available: true } })

    expect(wrapper.text()).toContain('Flood risk')
    const steps = wrapper.findAll('[data-testid="legend-step"]')
    expect(steps).toHaveLength(4)
    expect(wrapper.text()).toContain('Safe')
    expect(wrapper.text()).toContain('Low')
    expect(wrapper.text()).toContain('Medium')
    expect(wrapper.text()).toContain('High')
  })

  it('rings the active step', () => {
    const wrapper = mount(FloodRiskLegend, { props: { level: 'medium', available: true } })

    const steps = wrapper.findAll('[data-testid="legend-step"]')
    expect(steps[2].classes()).toContain('ring-2')
  })

  it('shows an unavailable caption when data is missing', () => {
    const wrapper = mount(FloodRiskLegend, { props: { level: 'unknown', available: false } })

    expect(wrapper.text()).toContain('Hazard data unavailable')
  })

  it('emits toggle from the overlay button', async () => {
    const wrapper = mount(FloodRiskLegend, { props: { level: 'safe', available: true } })

    const button = wrapper.get('[aria-label="Toggle flood overlay"]')
    expect(button.attributes('aria-pressed')).toBeDefined()
    await button.trigger('click')

    expect(wrapper.emitted('toggle')).toHaveLength(1)
  })
})
