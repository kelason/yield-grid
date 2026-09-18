<template>
  <div class="max-w-5xl mx-auto py-8 px-4">
    <!-- Top Bar: Navigation & Plot Selector -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex items-center gap-4">
        <router-link
          :to="{ name: 'farm-manager' }"
          class="inline-flex items-center gap-1.5 text-sm font-medium text-farm-600 hover:text-farm-700 transition-colors duration-200 group"
        >
          <svg
            class="h-4 w-4 group-hover:-translate-x-0.5 transition-transform duration-200"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
          Back to My Farms
        </router-link>
        <span class="text-gray-300">|</span>
        <router-link
          to="/dashboard"
          class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors duration-200"
        >
          Dashboard Overview
        </router-link>
      </div>

      <!-- Plot Selector dropdown (if multiple plots exist) -->
      <div v-if="farmingStore.allPlots.length > 1" class="flex items-center gap-2">
        <label
          for="plot-selector"
          class="text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap"
        >
          Plot:
        </label>
        <div class="relative">
          <select
            id="plot-selector"
            :value="activePlotId"
            @change="handlePlotChange(Number($event.target.value))"
            class="block w-full rounded-xl border border-gray-200 bg-white py-2 pl-3 pr-8 text-sm font-semibold text-gray-800 shadow-sm focus:border-farm-500 focus:outline-none focus:ring-1 focus:ring-farm-500 transition-all cursor-pointer"
          >
            <option v-for="p in farmingStore.allPlots" :key="p.id" :value="p.id">
              {{ p.name }} ({{ p.farm_name || 'Farm' }}) —
              {{ p.calculated_area ? Number(p.calculated_area).toFixed(2) : 0 }} ha
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Empty State: No Plots in Account -->
    <div
      v-if="!isLoadingPlots && farmingStore.allPlots.length === 0"
      class="text-center py-20 px-6 bg-white rounded-2xl border-2 border-dashed border-gray-200 hover:border-farm-300 transition-colors duration-200 shadow-sm"
    >
      <div class="text-5xl mb-4">🌾</div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">No plots registered yet</h3>
      <p class="text-gray-500 max-w-md mx-auto mb-8 text-sm leading-relaxed">
        You need to create a farm and draw at least one plot before you can receive AI crop
        recommendations.
      </p>
      <router-link
        :to="{ name: 'farm-manager' }"
        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-farm-500 to-farm-600 hover:from-farm-600 hover:to-farm-700 text-white font-semibold rounded-full shadow-sm hover:shadow transition-all duration-200"
      >
        <span>🏡</span>
        <span>Go to My Farms</span>
      </router-link>
    </div>

    <!-- Active Plot View -->
    <div v-else>
      <!-- Header -->
      <header class="mb-8 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-3">
            {{ plotDisplayName }} Recommendations
          </h1>

          <!-- Meta badges -->
          <div class="flex flex-wrap items-center gap-2">
            <span
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-earth-50 text-earth-700 rounded-full text-xs font-semibold border border-earth-200"
            >
              📐 <span>{{ plotArea }} ha</span>
            </span>
            <span
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-earth-50 text-earth-700 rounded-full text-xs font-semibold border border-earth-200 capitalize"
            >
              🌱 <span>{{ plotSoilType }}</span>
            </span>
            <span
              v-if="locationLabel"
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-farm-50 text-farm-700 rounded-full text-xs font-semibold border border-farm-200"
            >
              📍 {{ locationLabel }}
            </span>
            <span
              v-if="currentPlot?.farm_name"
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-50 text-gray-700 rounded-full text-xs font-semibold border border-gray-200"
            >
              🏡 {{ currentPlot.farm_name }}
            </span>
          </div>
        </div>
      </header>

      <!-- Error banner -->
      <AppAlert
        v-if="store.errorMessage"
        type="warning"
        dismissible
        class="mb-6"
        @dismiss="store.errorMessage = ''"
      >
        {{ store.errorMessage }}
      </AppAlert>

      <!-- Analysis progress -->
      <div v-if="store.isAnalyzing" class="mb-10">
        <AnalysisProgress :location="locationLabel" />
      </div>

      <!-- Recommendations -->
      <div v-else>
        <div v-if="store.recommendations.length > 0" class="space-y-5">
          <RecommendationCard
            v-for="rec in store.recommendations"
            :key="rec.id"
            :recommendation="rec"
            @accept="handleAccept"
            @reject="handleReject"
          />
        </div>

        <!-- Empty state -->
        <div
          v-else-if="!store.isLoading"
          class="text-center py-16 px-6 bg-white rounded-2xl border-2 border-dashed border-gray-200 hover:border-farm-300 transition-colors duration-200"
        >
          <div class="text-5xl mb-4">🌾</div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">No recommendations yet</h3>
          <p class="text-gray-500 max-w-md mx-auto mb-8 text-sm leading-relaxed">
            Run our AI advisor to analyse soil conditions and weather patterns and get optimised
            crop recommendations for this
            {{ plotArea !== '0.00' ? plotArea + ' ha' : '' }}
            plot.
          </p>
          <AppButton
            variant="primary"
            size="lg"
            rounded="full"
            :disabled="store.isAnalyzing || !activePlotId"
            @click="triggerAnalysis"
          >
            🌱 Run AI Analysis Now
          </AppButton>
        </div>
      </div>
    </div>

    <!-- Publish Contract Modal -->
    <div
      v-if="showPublishModal && selectedRecommendation"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
      >
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          aria-hidden="true"
          @click="closePublishModal"
        ></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true"
          >&#8203;</span
        >
        <div
          class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full"
        >
          <PublishContractForm
            :recommendation="selectedRecommendation"
            :loading="marketStore.loading?.publish"
            :errors="publishErrors"
            @publish="handlePublishContract"
            @cancel="closePublishModal"
            @clear-errors="publishErrors = {}"
          />
        </div>
      </div>
    </div>
    <ConfirmModal
      :is-open="isConfirmModalOpen"
      :title="confirmModalConfig.title"
      :message="confirmModalConfig.message"
      :type="confirmModalConfig.type"
      @confirm="executeConfirm"
      @cancel="cancelConfirm"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRecommendationStore } from '../stores/recommendationStore'
import { useFarmingStore } from '../stores/farming'
import { useWebSocket } from '../composables/useWebSocket'
import AnalysisProgress from '../components/atoms/AnalysisProgress.vue'
import RecommendationCard from '../components/molecules/RecommendationCard.vue'
import PublishContractForm from '../components/organisms/PublishContractForm.vue'
import AppButton from '../components/atoms/AppButton.vue'
import AppAlert from '../components/atoms/AppAlert.vue'
import ConfirmModal from '../components/molecules/ConfirmModal.vue'
import { useMarketStore } from '../stores/marketStore'

const route = useRoute()
const router = useRouter()
const store = useRecommendationStore()
const farmingStore = useFarmingStore()
const marketStore = useMarketStore()
const { listenToPlot, leavePlot } = useWebSocket()

const selectedPlotId = ref(null)
const isLoadingPlots = ref(true)

const showPublishModal = ref(false)
const selectedRecommendation = ref(null)

const activePlotId = computed(() => {
  if (route.params.id) return Number(route.params.id)
  return selectedPlotId.value
})

const currentPlot = computed(() => {
  return farmingStore.allPlots.find((p) => p.id === activePlotId.value)
})

const plotDisplayName = computed(() => {
  return store.meta?.plot_name || currentPlot.value?.name || 'Plot'
})

const plotArea = computed(() => {
  const area = store.meta?.calculated_area ?? currentPlot.value?.calculated_area
  return area ? Number(area).toFixed(2) : '0.00'
})

const plotSoilType = computed(() => {
  return store.meta?.soil_type || currentPlot.value?.soil_type || 'Unspecified'
})

const locationLabel = computed(() => {
  const parts = [store.meta?.city, store.meta?.state, store.meta?.country].filter(Boolean)
  return parts.join(', ')
})

const triggerAnalysis = async () => {
  if (activePlotId.value) {
    await store.analyzePlot(activePlotId.value)
  }
}

const loadPlotData = async (id) => {
  if (!id) return
  listenToPlot(id, () => {
    store.isAnalyzing = false
    store.fetchRecommendations(id)
  })

  // Load existing recommendations without automatically triggering AI analysis.
  // Re-analysis can only be triggered explicitly via the Re-analyse Plot button.
  await store.fetchRecommendations(id)
}

function handlePlotChange(valOrEvent) {
  const newPlotId = typeof valOrEvent === 'number' ? valOrEvent : Number(valOrEvent?.target?.value)
  if (!newPlotId || newPlotId === activePlotId.value) return
  router.push({ name: 'crop-recommendations', params: { id: newPlotId } })
}

watch(
  () => route.params.id,
  async (newId, oldId) => {
    if (oldId) {
      leavePlot(Number(oldId))
    }
    if (newId) {
      selectedPlotId.value = Number(newId)
      store.recommendations = []
      await loadPlotData(Number(newId))
    }
  },
)

onMounted(async () => {
  isLoadingPlots.value = true
  try {
    await farmingStore.fetchAllPlots()
  } finally {
    isLoadingPlots.value = false
  }

  let targetId = null
  if (route.params.id) {
    targetId = Number(route.params.id)
  } else if (route.query.farmId) {
    const farmPlot = farmingStore.allPlots.find((p) => p.farm_id === Number(route.query.farmId))
    if (farmPlot) targetId = farmPlot.id
  }

  if (!targetId && farmingStore.allPlots.length > 0) {
    targetId = farmingStore.allPlots[0].id
  }

  if (targetId) {
    selectedPlotId.value = targetId
    await loadPlotData(targetId)
  }
})

onUnmounted(() => {
  if (activePlotId.value) {
    leavePlot(activePlotId.value)
  }
})

const handleAccept = (id) => {
  const rec = store.recommendations.find((r) => r.id === id)
  if (rec) {
    selectedRecommendation.value = rec
    showPublishModal.value = true
  }
}

const publishErrors = ref({})

const closePublishModal = () => {
  showPublishModal.value = false
  selectedRecommendation.value = null
  publishErrors.value = {}
}

const handlePublishContract = async (formData) => {
  if (selectedRecommendation.value) {
    try {
      publishErrors.value = {}
      // The backend now automatically marks it as 'accepted' and 'is_published' when successfully created
      await marketStore.publishContract(selectedRecommendation.value.id, formData)

      // Update local state so UI updates
      const index = store.recommendations.findIndex((r) => r.id === selectedRecommendation.value.id)
      if (index !== -1) {
        store.recommendations[index].status = 'accepted'
      }

      closePublishModal()
      router.push({ name: 'farmer-contracts' })
    } catch (error) {
      if (error.response?.status === 422) {
        publishErrors.value = error.response.data.errors || {}
      } else {
        store.errorMessage = 'An unexpected error occurred while publishing the contract.'
      }
    }
  }
}

const isConfirmModalOpen = ref(false)
const confirmModalConfig = ref({ title: '', message: '', type: 'primary' })
let confirmAction = null

const handleReject = (id) => {
  confirmModalConfig.value = {
    title: 'Reject Recommendation',
    message: 'Are you sure you want to reject this recommendation?',
    type: 'danger',
  }
  confirmAction = async () => {
    await store.updateStatus(id, 'rejected')
  }
  isConfirmModalOpen.value = true
}

const executeConfirm = async () => {
  if (confirmAction) await confirmAction()
  isConfirmModalOpen.value = false
}

const cancelConfirm = () => {
  confirmAction = null
  isConfirmModalOpen.value = false
}
</script>
