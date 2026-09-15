<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFarmingStore } from '../../stores/farming'
import AppButton from '../../components/atoms/AppButton.vue'
import AppCard from '../../components/atoms/AppCard.vue'
import FormField from '../../components/molecules/FormField.vue'
import AppSelect from '../../components/atoms/AppSelect.vue'
import AppAlert from '../../components/atoms/AppAlert.vue'
import PlotDrawer from '../../components/organisms/PlotDrawer.vue'

const route = useRoute()
const router = useRouter()
const farmingStore = useFarmingStore()

const farmId = parseInt(route.params.farmId)
const isSaving = ref(false)
const error = ref('')

const soilTypeOptions = [
  { value: 'clay', label: 'Clay' },
  { value: 'sandy', label: 'Sandy' },
  { value: 'loamy', label: 'Loamy' },
  { value: 'silt', label: 'Silt' },
  { value: 'peat', label: 'Peat' },
  { value: 'chalky', label: 'Chalky' },
]

const form = ref({
  name: '',
  soil_type: '',
  coordinates: [],
})

const activeLayer = ref(null)

onMounted(async () => {
  if (!farmingStore.activeFarm || farmingStore.activeFarm.id !== farmId) {
    await farmingStore.fetchFarms()
    const farm = farmingStore.farms.find((f) => f.id === farmId)
    if (farm) {
      farmingStore.setActiveFarm(farm)
    } else {
      router.push({ name: 'farm-manager' })
      return
    }
  }
  farmingStore.fetchPlots(farmId)
})

const farmName = computed(() => farmingStore.activeFarm?.name || 'Loading...')

function handlePlotDrawn({ layer, coordinates }) {
  error.value = ''
  activeLayer.value = layer
  form.value.coordinates = coordinates
  form.value.name = `Plot ${farmingStore.plots?.features?.length ? farmingStore.plots.features.length + 1 : 1}`
}

function handlePlotError(msg) {
  error.value = msg
  if (activeLayer.value && activeLayer.value._map) {
    activeLayer.value._map.removeLayer(activeLayer.value)
  }
  activeLayer.value = null
  form.value.coordinates = []
}

function cancelDrawing() {
  if (activeLayer.value && activeLayer.value._map) {
    activeLayer.value._map.removeLayer(activeLayer.value)
  }
  activeLayer.value = null
  form.value.coordinates = []
  error.value = ''
}

async function savePlot() {
  isSaving.value = true
  error.value = ''
  try {
    await farmingStore.createPlot(farmId, form.value)
    activeLayer.value = null
    form.value.coordinates = []
    form.value.name = ''
    form.value.soil_type = ''
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save plot'
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="h-[calc(100vh-10rem)] flex flex-col md:flex-row gap-6">

    <!-- Map Area -->
    <div class="flex-1 relative rounded-2xl overflow-hidden border border-gray-200 shadow-sm">
      <PlotDrawer
        :existing-plots="farmingStore.plots"
        :farm="farmingStore.activeFarm"
        @plot-drawn="handlePlotDrawn"
        @plot-error="handlePlotError"
      />
    </div>

    <!-- Side Panel -->
    <div class="w-full md:w-80 flex flex-col gap-4">

      <!-- Farm info card -->
      <AppCard variant="muted" padding="p-0">
        <div class="px-4 py-4 border-b border-earth-100">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-lg">🌾</span>
            <h3 class="text-base font-bold text-farm-900">{{ farmName }}</h3>
          </div>
          <p class="text-xs text-farm-700 leading-relaxed">
            Use the polygon tool on the map to draw the boundaries of a new plot.
          </p>
        </div>

        <div
          v-if="farmingStore.activeFarm?.city"
          class="px-4 py-3 flex items-start gap-2"
        >
          <span class="text-sm leading-none mt-0.5">📍</span>
          <div class="text-xs">
            <div class="text-gray-500">Restricted Plotting Area:</div>
            <strong class="font-semibold text-farm-900">
              {{ farmingStore.activeFarm.city }}<span v-if="farmingStore.activeFarm.country">, {{ farmingStore.activeFarm.country }}</span>
            </strong>
          </div>
        </div>
      </AppCard>

      <!-- Plot error -->
      <AppAlert v-if="error && !activeLayer" type="error">
        {{ error }}
      </AppAlert>

      <!-- Save plot form (when drawing active) -->
      <AppCard v-if="activeLayer" padding="p-4">
        <div class="flex items-center gap-2 mb-4">
          <span class="text-lg">📐</span>
          <h4 class="font-bold text-gray-900 text-sm">Save New Plot</h4>
        </div>
        <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>

        <form @submit.prevent="savePlot" class="space-y-4">
          <FormField id="plot-name" label="Plot Name" v-model="form.name" required />
          <AppSelect
            id="plot-soil"
            label="Soil Type"
            v-model="form.soil_type"
            :options="soilTypeOptions"
          />

          <div class="flex flex-col gap-2 pt-2">
            <AppButton type="submit" variant="primary" :loading="isSaving" class="w-full">
              Save Plot
            </AppButton>
            <AppButton type="button" variant="ghost" @click="cancelDrawing" class="w-full">
              Discard
            </AppButton>
          </div>
        </form>
      </AppCard>

      <!-- Plot list (when not drawing) -->
      <AppCard v-else padding="p-0" class="flex-1 overflow-hidden flex flex-col min-h-0">
        <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-earth-50 to-white flex items-center justify-between">
          <h4 class="text-sm font-bold text-gray-900">Existing Plots</h4>
          <span class="text-xs font-medium text-gray-400">
            {{ farmingStore.plots?.features?.length || 0 }} total
          </span>
        </div>
        <div class="flex-1 overflow-y-auto p-2">
          <div v-if="farmingStore.loading" class="space-y-2 p-2">
            <div v-for="n in 3" :key="n" class="animate-pulse h-14 bg-gray-100 rounded-lg"></div>
          </div>
          <div
            v-else-if="!farmingStore.plots?.features?.length"
            class="text-sm text-gray-400 text-center p-6"
          >
            <div class="text-2xl mb-2">🗺️</div>
            No plots drawn yet
          </div>
          <ul v-else class="space-y-2">
            <li
              v-for="feature in farmingStore.plots.features"
              :key="feature.properties.id"
              class="p-3 bg-white border border-gray-100 rounded-xl shadow-sm hover:border-farm-200 transition-all duration-150"
            >
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <div class="font-semibold text-gray-900 text-sm truncate">{{ feature.properties.name }}</div>
                  <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                    <span class="capitalize">{{ feature.properties.soil_type || 'Unknown soil' }}</span>
                    <span>•</span>
                    <span class="font-bold text-farm-600">
                      {{ feature.properties.calculated_area
                        ? Number(feature.properties.calculated_area).toFixed(2)
                        : 0 }} ha
                    </span>
                  </div>
                </div>
                <router-link
                  :to="{ name: 'crop-recommendations', params: { id: feature.properties.id } }"
                  class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-farm-50 hover:bg-farm-100 text-farm-700 text-xs font-semibold transition-colors duration-150 flex-shrink-0"
                >
                  <span>🤖</span>
                  <span>Recommendations</span>
                </router-link>
              </div>
            </li>
          </ul>
        </div>
      </AppCard>

    </div>
  </div>
</template>
