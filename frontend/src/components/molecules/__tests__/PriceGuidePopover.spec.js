import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, afterEach } from 'vitest'
import PriceGuidePopover from '../PriceGuidePopover.vue'

const PriceGuideHintStub = {
  name: 'PriceGuideHint',
  props: ['cropName', 'currentPrice', 'regionCode'],
  template: '<div class="hint-stub">{{ cropName }}|{{ currentPrice }}|{{ regionCode }}</div>',
}

function mountPopover(props = {}) {
  return mount(PriceGuidePopover, {
    props: { cropName: 'Rice', currentPrice: 50, ...props },
    global: { stubs: { PriceGuideHint: PriceGuideHintStub } },
    attachTo: document.body,
  })
}

function panelEl() {
  return document.body.querySelector('[role="dialog"][aria-label="Price guide"]')
}

describe('PriceGuidePopover', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('renders the trigger icon without opening the panel', () => {
    const wrapper = mountPopover()
    const trigger = wrapper.find('button[aria-label="Show price guide"]')
    expect(trigger.exists()).toBe(true)
    expect(trigger.text()).toContain('Price Guide')
    expect(panelEl()).toBeNull()
    wrapper.unmount()
  })

  it('opens the panel on click and passes crop props to the hint', async () => {
    const wrapper = mountPopover({ cropName: 'Tomato', currentPrice: 42.5 })
    await wrapper.find('button[aria-label="Show price guide"]').trigger('click')
    await flushPromises()

    const panel = panelEl()
    expect(panel).not.toBeNull()
    expect(panel.querySelector('.hint-stub').textContent).toBe('Tomato|42.5|')
    expect(wrapper.find('button[aria-label="Show price guide"]').attributes('aria-expanded')).toBe(
      'true',
    )
    wrapper.unmount()
  })

  it('toggles closed on a second click', async () => {
    const wrapper = mountPopover()
    const trigger = wrapper.find('button[aria-label="Show price guide"]')
    await trigger.trigger('click')
    await flushPromises()
    expect(panelEl()).not.toBeNull()

    await trigger.trigger('click')
    await flushPromises()
    expect(panelEl()).toBeNull()
    wrapper.unmount()
  })

  it('closes on Escape', async () => {
    const wrapper = mountPopover()
    await wrapper.find('button[aria-label="Show price guide"]').trigger('click')
    await flushPromises()
    expect(panelEl()).not.toBeNull()

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await flushPromises()
    expect(panelEl()).toBeNull()
    wrapper.unmount()
  })

  it('closes on outside mousedown', async () => {
    const wrapper = mountPopover()
    await wrapper.find('button[aria-label="Show price guide"]').trigger('click')
    await flushPromises()
    expect(panelEl()).not.toBeNull()

    document.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
    await flushPromises()
    expect(panelEl()).toBeNull()
    wrapper.unmount()
  })

  it('shows a note instead of the hint when no crop is entered', async () => {
    const wrapper = mountPopover({ cropName: '  ' })
    await wrapper.find('button[aria-label="Show price guide"]').trigger('click')
    await flushPromises()

    const panel = panelEl()
    expect(panel).not.toBeNull()
    expect(panel.textContent).toContain('Enter a crop name to load the price guide.')
    expect(panel.querySelector('.hint-stub')).toBeNull()
    wrapper.unmount()
  })
})
