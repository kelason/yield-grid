import { describe, it, expect, vi } from 'vitest'
import { nextTick } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import RecommendationsPage from '../RecommendationsPage.vue'
import AnalysisPreferencesForm from '../../components/organisms/AnalysisPreferencesForm.vue'
import { useRecommendationStore } from '../../stores/recommendationStore'
import { useFarmingStore } from '../../stores/farming'

vi.mock('vue-router', () => ({
  useRoute: () => ({ params: { id: '4' }, query: {} }),
  useRouter: () => ({ push: vi.fn() }),
}))

vi.mock('../../composables/useWebSocket', () => ({
  useWebSocket: () => ({ listenToPlot: vi.fn(), leavePlot: vi.fn() }),
}))

vi.mock('../../composables/useConfirmModal', async () => {
  const { ref } = await import('vue')
  return {
    useConfirmModal: () => ({
      isOpen: ref(false),
      config: ref({}),
      confirm: vi.fn((config, onConfirm) => onConfirm()),
      execute: vi.fn(),
      cancel: vi.fn(),
    }),
  }
})

const FormStub = { name: 'AnalysisPreferencesForm', template: '<div data-test="prefs-form" />' }

function mountPage() {
  setActivePinia(createPinia())
  const recStore = useRecommendationStore()
  recStore.fetchRecommendations = vi.fn().mockResolvedValue()
  recStore.fetchTaxonomy = vi.fn().mockResolvedValue()
  recStore.analyzePlot = vi.fn().mockResolvedValue()
  const farmingStore = useFarmingStore()
  farmingStore.allPlots = [
    { id: 4, name: 'Plot 4', farm_id: 1, soil_type: 'clay', calculated_area: 2.0 },
  ]
  farmingStore.fetchAllPlots = vi.fn().mockResolvedValue(farmingStore.allPlots)

  const wrapper = mount(RecommendationsPage, {
    global: {
      stubs: {
        AnalysisPreferencesForm: FormStub,
        RecommendationCard: true,
        PublishContractForm: true,
        AnalysisProgress: true,
        SkeletonCard: true,
        ConfirmModal: true,
      },
    },
  })

  return { wrapper, recStore, analyzePlot: recStore.analyzePlot }
}

function formStub(wrapper) {
  return wrapper.findComponent(AnalysisPreferencesForm)
}

describe('RecommendationsPage.vue', () => {
  it('sends the fresh preferences emitted with run-analysis', async () => {
    const { wrapper, analyzePlot } = mountPage()
    await flushPromises()

    const fresh = {
      produce_types: ['fruit'],
      subtypes: ['citrus'],
      irrigation: null,
      goal: null,
    }
    formStub(wrapper).vm.$emit('request-analysis', fresh)
    await flushPromises()

    expect(analyzePlot).toHaveBeenCalledWith(4, fresh)
  })

  it('falls back to the synced preferences for the empty-state button', async () => {
    const { wrapper, analyzePlot } = mountPage()
    await flushPromises()

    const synced = {
      produce_types: ['vegetable'],
      subtypes: [],
      irrigation: 'limited',
      goal: null,
    }
    formStub(wrapper).vm.$emit('update:preferences', synced)
    await wrapper
      .findAll('button')
      .find((button) => button.text().includes('Run AI Analysis Now'))
      .trigger('click')
    await flushPromises()

    expect(analyzePlot).toHaveBeenCalledWith(4, synced)
  })

  it('shows taxonomy labels on the type filter chips', async () => {
    const { wrapper, recStore } = mountPage()
    await flushPromises()

    recStore.taxonomy = {
      types: { vegetable: { label: 'Vegetables' }, fruit: { label: 'Fruits' } },
    }
    recStore.recommendations = [
      { id: 1, produce_type: 'vegetable' },
      { id: 2, produce_type: 'fruit' },
    ]
    await nextTick()

    const chips = wrapper.findAll('button').map((button) => button.text())
    expect(chips).toContain('Vegetables')
    expect(chips).toContain('Fruits')
  })

  it('explains an empty filter match instead of showing nothing', async () => {
    const { wrapper, recStore } = mountPage()
    await flushPromises()

    recStore.recommendations = [{ id: 1, produce_type: 'vegetable' }]
    recStore.typeFilter = 'fruit'
    await nextTick()

    expect(wrapper.find('[data-test="filter-empty-hint"]').text()).toContain(
      'No recommendations match this filter.',
    )

    recStore.typeFilter = ''
    await nextTick()

    expect(wrapper.find('[data-test="filter-empty-hint"]').exists()).toBe(false)
  })
})
