import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import i18n from '@/i18n'
import { useRecommendationStore } from '@/stores/recommendationStore'
import { useFarmingStore } from '@/stores/farming'
import { useMarketStore } from '@/stores/marketStore'
import { useWebSocket } from '@/composables/useWebSocket'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { HTTP_STATUS } from '@/constants/http'

function createState(options) {
  const selectedPlotId = ref(null)
  const s = {
    ...options,
    store: useRecommendationStore(),
    farmingStore: useFarmingStore(),
    marketStore: useMarketStore(),
    ...useWebSocket(),
    ...useConfirmModal(),
    selectedPlotId,
    isLoadingPlots: ref(true),
    plotsError: ref(''),
    showPublishModal: ref(false),
    selectedRecommendation: ref(null),
    publishErrors: ref({}),
    analysisPrefs: ref({ produce_types: [], subtypes: [], irrigation: null, goal: null }),
  }
  s.activePlotId = computed(() =>
    s.route.params.id ? Number(s.route.params.id) : selectedPlotId.value,
  )
  return s
}
function plotPresentation(s) {
  const currentPlot = computed(() =>
    s.farmingStore.allPlots.find((plot) => plot.id === s.activePlotId.value),
  )
  return {
    currentPlot,
    plotDisplayName: computed(
      () =>
        s.store.meta?.plot_name ||
        currentPlot.value?.name ||
        i18n.global.t('farmer.recommendations.plot_fallback'),
    ),
    plotArea: computed(() => {
      const area = s.store.meta?.calculated_area ?? currentPlot.value?.calculated_area
      return area ? Number(area).toFixed(2) : '0.00'
    }),
    plotSoilType: computed(
      () =>
        s.store.meta?.soil_type ||
        currentPlot.value?.soil_type ||
        i18n.global.t('farmer.recommendations.soil_unspecified'),
    ),
    locationLabel: computed(() =>
      [s.store.meta?.city, s.store.meta?.state, s.store.meta?.country].filter(Boolean).join(', '),
    ),
  }
}
async function loadPlotData(s, id) {
  if (!id) return
  s.listenToPlot(id, () => {
    s.store.isAnalyzing = false
    s.store.fetchRecommendations(id)
  })
  await s.store.fetchRecommendations(id)
}
function initialPlot(s) {
  if (s.route.params.id) return Number(s.route.params.id)
  if (s.route.query.farmId) {
    const plot = s.farmingStore.allPlots.find(
      (item) => item.farm_id === Number(s.route.query.farmId),
    )
    if (plot) return plot.id
  }
  return s.farmingStore.allPlots[0]?.id || null
}
async function initialize(s) {
  s.isLoadingPlots.value = true
  s.plotsError.value = ''
  try {
    await s.farmingStore.fetchAllPlots()
    const id = initialPlot(s)
    if (id) {
      s.selectedPlotId.value = id
      await loadPlotData(s, id)
    }
    await s.store.fetchTaxonomy()
  } catch (error) {
    s.plotsError.value =
      error.response?.data?.message || i18n.global.t('farmer.recommendations.plots_error')
  } finally {
    s.isLoadingPlots.value = false
  }
}
function handlePlotChange(s, value) {
  const id = typeof value === 'number' ? value : Number(value?.target?.value)
  if (!id || id === s.activePlotId.value) return
  s.router.push({ name: 'crop-recommendations', params: { id } })
}
function triggerAnalysis(s, freshPrefs = null) {
  if (!s.activePlotId.value) return
  const prefs =
    freshPrefs && typeof freshPrefs === 'object' && 'produce_types' in freshPrefs
      ? freshPrefs
      : s.analysisPrefs.value
  s.confirm(
    {
      title: i18n.global.t('farmer.recommendations.analyze_title'),
      message: i18n.global.t('farmer.recommendations.analyze_message'),
      confirmText: i18n.global.t('farmer.recommendations.analyze_confirm'),
      type: 'primary',
    },
    () => s.store.analyzePlot(s.activePlotId.value, prefs),
  )
}
function handleAccept(s, id) {
  const recommendation = s.store.recommendations.find((item) => item.id === id)
  if (!recommendation) return
  s.selectedRecommendation.value = recommendation
  s.showPublishModal.value = true
}
function closePublishModal(s) {
  if (s.isExecuting.value) return
  s.showPublishModal.value = false
  s.selectedRecommendation.value = null
  s.publishErrors.value = {}
}
const CONTRACT_TITLE_MAX_LENGTH = 255
const CONTRACT_DESCRIPTION_MAX_LENGTH = 5000
const CONTRACT_QUANTITY_MIN_KG = 1
const CONTRACT_QUANTITY_MAX_KG = 99999999
const CONTRACT_PRICE_MIN = 0.01
const CONTRACT_PRICE_MAX = 99999999
const CONTRACT_TOTAL_MAX = 9999999999.99

function validatePublishForm(formData) {
  const fieldErrors = {}
  if ((formData.title || '').length > CONTRACT_TITLE_MAX_LENGTH) {
    fieldErrors.title = [
      i18n.global.t('farmer.recommendations.publish_title_long', {
        max: CONTRACT_TITLE_MAX_LENGTH,
      }),
    ]
  }
  if ((formData.description || '').length > CONTRACT_DESCRIPTION_MAX_LENGTH) {
    fieldErrors.description = [
      i18n.global.t('farmer.recommendations.publish_desc_long', {
        max: CONTRACT_DESCRIPTION_MAX_LENGTH,
      }),
    ]
  }
  validatePublishAmounts(formData, fieldErrors)
  return fieldErrors
}

function validatePublishAmounts(formData, fieldErrors) {
  const qty = parseFloat(formData.quantity_kg)
  if (!qty || qty < CONTRACT_QUANTITY_MIN_KG) {
    fieldErrors.quantity_kg = [i18n.global.t('farmer.demands.qty_required')]
  } else if (qty > CONTRACT_QUANTITY_MAX_KG) {
    fieldErrors.quantity_kg = [
      i18n.global.t('farmer.recommendations.publish_qty_exceeds', {
        max: CONTRACT_QUANTITY_MAX_KG.toLocaleString(),
      }),
    ]
  }
  const price = parseFloat(formData.price_per_kg)
  if (!price || price < CONTRACT_PRICE_MIN) {
    fieldErrors.price_per_kg = [i18n.global.t('farmer.demands.price_required')]
  } else if (price > CONTRACT_PRICE_MAX) {
    fieldErrors.price_per_kg = [
      i18n.global.t('farmer.recommendations.publish_price_exceeds', {
        max: CONTRACT_PRICE_MAX.toLocaleString(),
      }),
    ]
  }
  validatePublishTotal(qty, price, fieldErrors)
}
function validatePublishTotal(qty, price, fieldErrors) {
  if (Object.keys(fieldErrors).length === 0 && qty * price > CONTRACT_TOTAL_MAX) {
    fieldErrors.quantity_kg = [i18n.global.t('farmer.demands.total_exceeds')]
  }
}

async function performPublish(s, id, formData) {
  try {
    s.publishErrors.value = {}
    await s.marketStore.publishContract(id, formData)
    const recommendation = s.store.recommendations.find((item) => item.id === id)
    if (recommendation) recommendation.status = 'accepted'
    s.showPublishModal.value = false
    s.selectedRecommendation.value = null
    s.router.push({ name: 'farmer-contracts' })
  } catch (error) {
    if (error.response?.status === HTTP_STATUS.UNPROCESSABLE_ENTITY)
      s.publishErrors.value = error.response.data.errors || {}
    else {
      const message =
        error.response?.data?.message || i18n.global.t('farmer.recommendations.publish_error')
      s.store.errorMessage = message
      s.publishErrors.value = { form: [message] }
    }
  }
}
function handlePublishContract(s, formData) {
  const id = s.selectedRecommendation.value?.id
  if (!id) return
  s.publishErrors.value = validatePublishForm(formData)
  if (Object.keys(s.publishErrors.value).length > 0) return
  s.confirm(
    {
      title: i18n.global.t('farmer.recommendations.publish_title_confirm'),
      message: i18n.global.t('farmer.recommendations.publish_message', {
        title: formData.title,
        qty: formData.quantity_kg,
        price: formData.price_per_kg,
      }),
      confirmText: i18n.global.t('farmer.recommendations.publish_confirm'),
      type: 'primary',
    },
    () => performPublish(s, id, { ...formData }),
  )
}
function routeLifecycle(s) {
  watch(
    () => s.route.params.id,
    async (next, previous) => {
      if (previous) s.leavePlot(Number(previous))
      if (!next) return
      s.selectedPlotId.value = Number(next)
      s.store.recommendations = []
      await loadPlotData(s, Number(next))
    },
  )
  onMounted(() => initialize(s))
  onUnmounted(() => {
    if (s.activePlotId.value) s.leavePlot(s.activePlotId.value)
  })
}
export function useRecommendationAnalysis(options) {
  const s = createState(options)
  routeLifecycle(s)
  return {
    ...s,
    ...plotPresentation(s),
    retryPlots: () => initialize(s),
    retryRecommendations: () => s.store.fetchRecommendations(s.activePlotId.value),
    triggerAnalysis: (prefs) => triggerAnalysis(s, prefs),
    handlePlotChange: (value) => handlePlotChange(s, value),
    handleAccept: (id) => handleAccept(s, id),
    closePublishModal: () => closePublishModal(s),
    handlePublishContract: (formData) => handlePublishContract(s, formData),
    handleReject: (id) =>
      s.confirm(
        {
          title: i18n.global.t('farmer.recommendations.reject_title'),
          message: i18n.global.t('farmer.recommendations.reject_message'),
          type: 'danger',
        },
        () => s.store.updateStatus(id, 'rejected'),
      ),
  }
}
