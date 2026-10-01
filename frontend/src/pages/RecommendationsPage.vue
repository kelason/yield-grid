<template>
  <div class="space-y-6">
    <!-- Top Bar: Navigation & Plot Selector -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex items-center gap-4">
        <router-link
          :to="{ name: 'farm-manager' }"
          class="inline-flex items-center gap-1.5 text-sm font-medium text-moss-600 hover:text-moss-700 transition-colors duration-200 group"
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
        <span class="text-stone-300">|</span>
        <router-link
          to="/dashboard/farmer"
          class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 hover:text-stone-700 transition-colors duration-200"
        >
          Dashboard Overview
        </router-link>
      </div>

      <!-- Plot Selector dropdown (if multiple plots exist) -->
      <div v-if="farmingStore.allPlots.length > 1" class="flex items-center gap-2">
        <label
          for="plot-selector"
          class="text-xs font-bold text-stone-500 uppercase tracking-wider whitespace-nowrap"
        >
          Plot:
        </label>
        <div class="relative">
          <select
            id="plot-selector"
            :value="activePlotId"
            @change="handlePlotChange(Number($event.target.value))"
            class="block w-full rounded-xl border border-stone-200 bg-white py-2 pl-3 pr-8 text-sm font-semibold text-stone-800 shadow-soft focus:border-moss-500 focus:outline-none focus:ring-1 focus:ring-moss-500 transition-all cursor-pointer"
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
      class="text-center py-20 px-6 bg-white rounded-2xl border-2 border-dashed border-stone-200 hover:border-moss-300 transition-colors duration-200 shadow-soft"
    >
      <div class="text-5xl mb-4">🌾</div>
      <h3 class="font-serif text-xl font-bold text-stone-900 mb-2">No plots registered yet</h3>
      <p class="text-stone-500 max-w-md mx-auto mb-8 text-sm leading-relaxed">
        You need to create a farm and draw at least one plot before you can receive AI crop
        recommendations.
      </p>
      <router-link
        :to="{ name: 'farm-manager' }"
        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-moss-500 to-moss-600 hover:from-moss-600 hover:to-moss-700 text-white font-semibold rounded-full shadow-soft hover:shadow transition-all duration-200"
      >
        <span>🏡</span>
        <span>Go to My Farms</span>
      </router-link>
    </div>

    <!-- Loading plots -->
    <div v-else-if="isLoadingPlots" class="space-y-6" role="status" aria-label="Loading plots">
      <div class="animate-pulse motion-reduce:animate-none space-y-4" aria-hidden="true">
        <div class="h-9 bg-stone-200 rounded-xl w-64"></div>
        <div class="flex gap-2">
          <div class="h-7 bg-stone-200 rounded-full w-24"></div>
          <div class="h-7 bg-stone-200 rounded-full w-28"></div>
          <div class="h-7 bg-stone-200 rounded-full w-20"></div>
        </div>
      </div>
      <SkeletonCard v-for="n in RECOMMENDATION_SKELETON_COUNT" :key="n" withAvatar withAction />
      <span class="sr-only">Loading plots...</span>
    </div>

    <!-- Active Plot View -->
    <div v-else>
      <!-- Header -->
      <header class="mb-8 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <h1 class="font-serif text-3xl font-extrabold text-stone-900 tracking-tight mb-3">
            {{ plotDisplayName }} Recommendations
          </h1>

          <!-- Meta badges -->
          <div class="flex flex-wrap items-center gap-2">
            <span
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-stone-50 text-stone-700 rounded-full text-xs font-semibold border border-stone-200"
            >
              📐 <span>{{ plotArea }} ha</span>
            </span>
            <span
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-stone-50 text-stone-700 rounded-full text-xs font-semibold border border-stone-200 capitalize"
            >
              🌱 <span>{{ plotSoilType }}</span>
            </span>
            <span
              v-if="locationLabel"
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-moss-50 text-moss-700 rounded-full text-xs font-semibold border border-moss-200"
            >
              📍 {{ locationLabel }}
            </span>
            <span
              v-if="currentPlot?.farm_name"
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-stone-50 text-stone-700 rounded-full text-xs font-semibold border border-stone-200"
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
        <div
          v-if="store.isLoading"
          class="space-y-5"
          role="status"
          aria-label="Loading recommendations"
        >
          <SkeletonCard v-for="n in RECOMMENDATION_SKELETON_COUNT" :key="n" withAvatar withAction />
          <span class="sr-only">Loading recommendations...</span>
        </div>

        <div v-else-if="store.recommendations.length > 0" class="space-y-5">
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
          v-else
          class="text-center py-16 px-6 bg-white rounded-2xl border-2 border-dashed border-stone-200 hover:border-moss-300 transition-colors duration-200"
        >
          <div class="text-5xl mb-4">🌾</div>
          <h3 class="font-serif text-lg font-bold text-stone-900 mb-2">No recommendations yet</h3>
          <p class="text-stone-500 max-w-md mx-auto mb-8 text-sm leading-relaxed">
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
          class="fixed inset-0 bg-stone-500 bg-opacity-75 transition-opacity"
          aria-hidden="true"
          @click="closePublishModal"
        ></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true"
          >&#8203;</span
        >
        <div
          class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-organic transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full"
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
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :type="config.type"
      @confirm="execute"
      @cancel="cancel"
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
import SkeletonCard from '../components/atoms/SkeletonCard.vue'
import RecommendationCard from '../components/molecules/RecommendationCard.vue'
import PublishContractForm from '../components/organisms/PublishContractForm.vue'
import AppButton from '../components/atoms/AppButton.vue'
import AppAlert from '../components/atoms/AppAlert.vue'
import ConfirmModal from '../components/molecules/ConfirmModal.vue'
import { HTTP_STATUS } from '../constants/http'
import { useMarketStore } from '../stores/marketStore'
import { useConfirmModal } from '../composables/useConfirmModal'

const route = useRoute()
const router = useRouter()
const store = useRecommendationStore()
const farmingStore = useFarmingStore()
const marketStore = useMarketStore()
const { listenToPlot, leavePlot } = useWebSocket()
const { isOpen, config, confirm, execute, cancel } = useConfirmModal()

const selectedPlotId = ref(null)
const isLoadingPlots = ref(true)

const RECOMMENDATION_SKELETON_COUNT = 3

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

const CONTRACT_TITLE_MAX_LENGTH = 255
const CONTRACT_DESCRIPTION_MAX_LENGTH = 5000
const CONTRACT_QUANTITY_MIN_KG = 1
const CONTRACT_QUANTITY_MAX_KG = 99999999
const CONTRACT_PRICE_MIN = 0.01
const CONTRACT_PRICE_MAX = 99999999
const CONTRACT_TOTAL_MAX = 9999999999.99

const validatePublishForm = (formData) => {
  const fieldErrors = {}
  if ((formData.title || '').length > CONTRACT_TITLE_MAX_LENGTH) {
    fieldErrors.title = [`Title cannot exceed ${CONTRACT_TITLE_MAX_LENGTH} characters.`]
  }
  if ((formData.description || '').length > CONTRACT_DESCRIPTION_MAX_LENGTH) {
    fieldErrors.description = [
      `Description cannot exceed ${CONTRACT_DESCRIPTION_MAX_LENGTH} characters.`,
    ]
  }
  const qty = parseFloat(formData.quantity_kg)
  if (!qty || qty < CONTRACT_QUANTITY_MIN_KG) {
    fieldErrors.quantity_kg = ['Please enter a quantity greater than zero.']
  } else if (qty > CONTRACT_QUANTITY_MAX_KG) {
    fieldErrors.quantity_kg = [
      `Quantity cannot exceed ${CONTRACT_QUANTITY_MAX_KG.toLocaleString()} kg.`,
    ]
  }
  const price = parseFloat(formData.price_per_kg)
  if (!price || price < CONTRACT_PRICE_MIN) {
    fieldErrors.price_per_kg = ['Please enter a price greater than zero.']
  } else if (price > CONTRACT_PRICE_MAX) {
    fieldErrors.price_per_kg = [
      `Price cannot exceed ${CONTRACT_PRICE_MAX.toLocaleString()} per kg.`,
    ]
  }
  if (Object.keys(fieldErrors).length === 0 && qty * price > CONTRACT_TOTAL_MAX) {
    fieldErrors.quantity_kg = ['The combined quantity and price exceed the maximum order total.']
  }
  return fieldErrors
}

const handlePublishContract = (formData) => {
  if (!selectedRecommendation.value) return
  publishErrors.value = validatePublishForm(formData)
  if (Object.keys(publishErrors.value).length > 0) return
  confirm(
    {
      title: 'Publish this contract?',
      message: `Publish "${formData.title}" (${formData.quantity_kg} kg at ₱${formData.price_per_kg}/kg) to the marketplace?`,
      confirmText: 'Publish contract',
      type: 'primary',
    },
    async () => {
      try {
        publishErrors.value = {}
        // The backend now automatically marks it as 'accepted' and 'is_published' when successfully created
        await marketStore.publishContract(selectedRecommendation.value.id, formData)

        // Update local state so UI updates
        const index = store.recommendations.findIndex(
          (r) => r.id === selectedRecommendation.value.id,
        )
        if (index !== -1) {
          store.recommendations[index].status = 'accepted'
        }

        closePublishModal()
        router.push({ name: 'farmer-contracts' })
      } catch (error) {
        if (error.response?.status === HTTP_STATUS.UNPROCESSABLE_ENTITY) {
          publishErrors.value = error.response.data.errors || {}
        } else {
          store.errorMessage = 'An unexpected error occurred while publishing the contract.'
        }
      }
    },
  )
}

const handleReject = (id) => {
  confirm(
    {
      title: 'Reject Recommendation',
      message: 'Are you sure you want to reject this recommendation?',
      type: 'danger',
    },
    async () => {
      await store.updateStatus(id, 'rejected')
    },
  )
}
</script>
