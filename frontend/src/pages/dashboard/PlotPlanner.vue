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
    <div class="flex-1 relative rounded-lg overflow-hidden border border-gray-200">
      <PlotDrawer
        :existing-plots="farmingStore.plots"
        :farm="farmingStore.activeFarm"
        @plot-drawn="handlePlotDrawn"
        @plot-error="handlePlotError"
      />
    </div>

    <!-- Side Panel -->
    <div class="w-full md:w-80 flex flex-col gap-4">
      <AppCard padding="p-4" class="bg-farm-50 border-farm-100">
        <h3 class="text-lg font-medium text-farm-900">{{ farmName }}</h3>
        <p class="text-sm text-farm-700 mt-1">
          Use the polygon tool on the map to draw the boundaries of a new plot.
        </p>

        <div
          v-if="farmingStore.activeFarm?.city"
          class="mt-3 p-2.5 bg-white/90 rounded-md border border-farm-200 text-xs text-farm-800 flex items-start gap-2 shadow-sm"
        >
          <span class="text-base leading-none">📍</span>
          <div>
            <div class="text-gray-500 font-normal">Restricted Plotting Area:</div>
            <strong class="font-semibold text-farm-900">
              {{ farmingStore.activeFarm.city
              }}<span v-if="farmingStore.activeFarm.country"
                >, {{ farmingStore.activeFarm.country }}</span
              >
            </strong>
          </div>
        </div>
      </AppCard>

      <!-- Plot error banner when not in active drawing modal -->
      <AppAlert v-if="error && !activeLayer" type="error">
        {{ error }}
      </AppAlert>

      <!-- Draw Form -->
      <AppCard padding="p-4" v-if="activeLayer">
        <h4 class="font-medium text-gray-900 mb-4">Save New Plot</h4>
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

      <!-- Plot List (if not drawing) -->
      <AppCard padding="p-0" v-else class="flex-1 overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
          <h4 class="font-medium text-gray-900">Existing Plots</h4>
        </div>
        <div class="flex-1 overflow-y-auto p-2">
          <div v-if="farmingStore.loading" class="flex justify-center p-4">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-farm-600"></div>
          </div>
          <div
            v-else-if="!farmingStore.plots?.features?.length"
            class="text-sm text-gray-500 text-center p-4"
          >
            No plots have been drawn on this farm yet.
          </div>
          <ul v-else class="space-y-2">
            <li
              v-for="feature in farmingStore.plots.features"
              :key="feature.properties.id"
              class="p-3 bg-white border border-gray-100 rounded-md shadow-sm"
            >
              <div class="font-medium text-gray-900 text-sm">{{ feature.properties.name }}</div>
              <div class="flex justify-between mt-1 text-xs text-gray-500">
                <span class="capitalize">{{ feature.properties.soil_type || 'Unknown soil' }}</span>
                <span class="font-medium text-farm-600"
                  >{{
                    feature.properties.calculated_area
                      ? Number(feature.properties.calculated_area).toFixed(2)
                      : 0
                  }}
                  ha</span
                >
              </div>
            </li>
          </ul>
        </div>
      </AppCard>
    </div>
  </div>
</template>
