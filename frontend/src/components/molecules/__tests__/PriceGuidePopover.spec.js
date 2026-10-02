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

const VIEWPORT_HEIGHT_PX = 768
const PANEL_OFFSET_PX = 8

function mockViewportHeight() {
  Object.defineProperty(window, 'innerHeight', {
    configurable: true,
    value: VIEWPORT_HEIGHT_PX,
  })
}

function mockTriggerRect(trigger, { top, bottom, left }) {
  trigger.element.getBoundingClientRect = () => ({
    top,
    bottom,
    left,
    right: left,
    x: left,
    y: top,
    width: 0,
    height: bottom - top,
  })
}

function mockPanelSize(panel, { width, height }) {
  Object.defineProperty(panel, 'offsetWidth', { configurable: true, value: width })
  Object.defineProperty(panel, 'offsetHeight', { configurable: true, value: height })
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

  it('opens below the trigger at its natural height when the panel fits', async () => {
    mockViewportHeight()
    const wrapper = mountPopover()
    const trigger = wrapper.find('button[aria-label="Show price guide"]')
    mockTriggerRect(trigger, { top: 100, bottom: 120, left: 100 })
    await trigger.trigger('click')
    await flushPromises()

    mockPanelSize(panelEl(), { width: 416, height: 400 })
    window.dispatchEvent(new Event('resize'))
    await flushPromises()

    const panel = panelEl()
    expect(panel.style.top).toBe(`${120 + PANEL_OFFSET_PX}px`)
    expect(panel.style.bottom).toBe('')
    expect(panel.style.maxHeight).toBe('')
    wrapper.unmount()
  })

  it('flips above the trigger when the panel does not fit below', async () => {
    mockViewportHeight()
    const wrapper = mountPopover()
    const trigger = wrapper.find('button[aria-label="Show price guide"]')
    mockTriggerRect(trigger, { top: 700, bottom: 720, left: 100 })
    await trigger.trigger('click')
    await flushPromises()

    mockPanelSize(panelEl(), { width: 416, height: 500 })
    window.dispatchEvent(new Event('resize'))
    await flushPromises()

    const panel = panelEl()
    expect(panel.style.top).toBe('')
    expect(panel.style.bottom).toBe(`${VIEWPORT_HEIGHT_PX - 700 + PANEL_OFFSET_PX}px`)
    wrapper.unmount()
  })

  it('stays below the trigger when below has more room, even if neither side fits', async () => {
    mockViewportHeight()
    const wrapper = mountPopover()
    const trigger = wrapper.find('button[aria-label="Show price guide"]')
    mockTriggerRect(trigger, { top: 60, bottom: 80, left: 100 })
    await trigger.trigger('click')
    await flushPromises()

    mockPanelSize(panelEl(), { width: 416, height: 700 })
    window.dispatchEvent(new Event('resize'))
    await flushPromises()

    const panel = panelEl()
    expect(panel.style.top).toBe(`${80 + PANEL_OFFSET_PX}px`)
    expect(panel.style.bottom).toBe('')
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
