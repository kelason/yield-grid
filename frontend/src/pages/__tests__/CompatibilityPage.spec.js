import { describe, it, expect, vi } from 'vitest'
import { nextTick } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import CompatibilityPage from '../CompatibilityPage.vue'
import CompatibilityChecker from '../../components/organisms/CompatibilityChecker.vue'
import { useRecommendationStore } from '../../stores/recommendationStore'

function mountPage() {
  setActivePinia(createPinia())
  const store = useRecommendationStore()
  store.fetchTaxonomy = vi.fn().mockResolvedValue()
  store.checkCompatibility = vi.fn().mockResolvedValue(null)

  const wrapper = mount(CompatibilityPage, {
    global: {
      stubs: {
        CompatibilityChecker: {
          name: 'CompatibilityChecker',
          props: ['taxonomy', 'result', 'checking', 'error'],
          template: '<div />',
        },
      },
    },
  })

  return { wrapper, store }
}

function checker(wrapper) {
  return wrapper.findComponent(CompatibilityChecker)
}

describe('CompatibilityPage.vue', () => {
  it('loads the taxonomy on mount', async () => {
    const { store } = mountPage()
    await flushPromises()

    expect(store.fetchTaxonomy).toHaveBeenCalled()
  })

  it('passes the check result to the checker', async () => {
    const { wrapper, store } = mountPage()
    await flushPromises()

    const payload = { verdict: 'caution', rotation: {}, companion: {} }
    store.checkCompatibility.mockResolvedValue(payload)
    checker(wrapper).vm.$emit('check-compatibility', { cropA: 'tomato', cropB: 'lettuce' })
    await flushPromises()
    await nextTick()

    expect(store.checkCompatibility).toHaveBeenCalledWith('tomato', 'lettuce')
    expect(checker(wrapper).props('result')).toEqual(payload)
    expect(checker(wrapper).props('checking')).toBe(false)
  })

  it('shows a friendly message when the check fails', async () => {
    const { wrapper, store } = mountPage()
    await flushPromises()

    store.checkCompatibility.mockRejectedValue(new Error('boom'))
    checker(wrapper).vm.$emit('check-compatibility', { cropA: 'tomato', cropB: 'lettuce' })
    await flushPromises()
    await nextTick()

    expect(checker(wrapper).props('error')).toContain('Could not check compatibility')
    expect(checker(wrapper).props('checking')).toBe(false)
  })
})
