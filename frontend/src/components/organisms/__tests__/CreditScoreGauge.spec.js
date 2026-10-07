import { mount } from '@vue/test-utils'
import { it, expect, vi, afterEach } from 'vitest'
import CreditScoreGauge from '../CreditScoreGauge.vue'
afterEach(() => vi.unstubAllGlobals())
it('cancels its count-up frame when unmounted', () => {
  vi.stubGlobal(
    'requestAnimationFrame',
    vi.fn(() => 42),
  )
  const cancel = vi.fn()
  vi.stubGlobal('cancelAnimationFrame', cancel)
  const wrapper = mount(CreditScoreGauge, { props: { score: 80, tier: 'good' } })
  wrapper.unmount()
  expect(cancel).toHaveBeenCalledWith(42)
})
it('shows the actual score immediately with reduced motion', async () => {
  vi.stubGlobal(
    'matchMedia',
    vi.fn(() => ({ matches: true })),
  )
  const frame = vi.fn()
  vi.stubGlobal('requestAnimationFrame', frame)
  const wrapper = mount(CreditScoreGauge, { props: { score: 80, tier: 'good' } })
  await wrapper.vm.$nextTick()
  expect(wrapper.get('[data-testid="gauge-score"]').text()).toBe('80')
  expect(frame).not.toHaveBeenCalled()
  wrapper.unmount()
})
