import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { nextTick } from 'vue'
import { mount } from '@vue/test-utils'
import ContractFilter from '../ContractFilter.vue'

function defaultFilters() {
  return {
    crop: '',
    minPrice: '',
    maxPrice: '',
    harvestBefore: '',
    harvestAfter: '',
    availability: 'all',
    sort: 'newest',
  }
}

function mountFilter() {
  return mount(ContractFilter, { props: { modelValue: defaultFilters() } })
}

describe('ContractFilter.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('searches live after the price inputs change', async () => {
    const wrapper = mountFilter()

    await wrapper.find('#min_price').setValue('100')
    await wrapper.find('#max_price').setValue('500')
    vi.advanceTimersByTime(300)

    expect(wrapper.emitted('search')).toHaveLength(1)
    const synced = wrapper.emitted('update:modelValue')
    expect(synced[synced.length - 1][0]).toMatchObject({ minPrice: '100', maxPrice: '500' })
  })

  it('debounces rapid price input instead of searching on every keystroke', async () => {
    const wrapper = mountFilter()

    await wrapper.find('#max_price').setValue('5')
    vi.advanceTimersByTime(299)
    expect(wrapper.emitted('search')).toBeUndefined()

    await wrapper.find('#max_price').setValue('500')
    vi.advanceTimersByTime(299)
    expect(wrapper.emitted('search')).toBeUndefined()

    vi.advanceTimersByTime(1)
    expect(wrapper.emitted('search')).toHaveLength(1)
  })

  it('applies the price range when Enter is pressed in a price input', async () => {
    const wrapper = mountFilter()

    await wrapper.find('#max_price').setValue('500')
    await wrapper.find('#max_price').trigger('keyup.enter')

    expect(wrapper.emitted('search')).toHaveLength(1)
  })

  it('shows a validation error instead of searching when min exceeds max', async () => {
    const wrapper = mountFilter()

    await wrapper.find('#min_price').setValue('500')
    await wrapper.find('#max_price').setValue('100')
    vi.advanceTimersByTime(300)
    await nextTick()

    expect(wrapper.emitted('search')).toBeUndefined()
    expect(wrapper.text()).toContain('The maximum must be at least the minimum.')
  })
})
