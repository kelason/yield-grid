<script setup>
import { ref, shallowRef, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useFarmingStore } from '../../stores/farming'
import { useAuthStore } from '../../stores/auth'
import AppButton from '../../components/atoms/AppButton.vue'
import AppCard from '../../components/atoms/AppCard.vue'
import FormField from '../../components/molecules/FormField.vue'
import SoilTypeSelect from '../../components/molecules/SoilTypeSelect.vue'
import AppAlert from '../../components/atoms/AppAlert.vue'
import ConfirmModal from '../../components/molecules/ConfirmModal.vue'
import { usePendingConfirmation } from '@/composables/useConfirmModal'
import PageHeader from '@/components/molecules/PageHeader.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import AppSkeleton from '@/components/atoms/AppSkeleton.vue'
import { MapPinIcon, ChartBarIcon } from '@heroicons/vue/24/outline'
import PlotDrawer from '../../components/organisms/PlotDrawer.vue'
import VerifiedBadge from '@/components/atoms/VerifiedBadge.vue'
import { isPlotVerified } from '@/utils/verification'

const PLOT_NAME_MAX_LENGTH = 255

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const farmingStore = useFarmingStore()
const authStore = useAuthStore()

const farmId = parseInt(route.params.farmId)
const isSaving = ref(false)
const error = ref('')

const form = ref({
  name: '',
  soil_type: '',
  coordinates: [],
})

const activeLayer = shallowRef(null)
const plotDrawerRef = ref(null)
const pendingConfirm = ref(null)
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)
const PLOT_SKELETON_COUNT = 3

const confirmConfig = computed(() => ({
  title: t('farmer.plots.confirm_title'),
  message: t('farmer.plots.confirm_message', { name: form.value.name, farm: farmName.value }),
  confirmText: t('farmer.plots.confirm_button'),
  type: 'primary',
}))

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
  farmingStore.fetchAllPlots()
})

const farmName = computed(() => farmingStore.activeFarm?.name || t('farmer.plots.loading_farm'))

const plotVerificationById = computed(() => {
  const byId = new Map()
  for (const plot of farmingStore.allPlots ?? []) {
    byId.set(plot?.id, plot)
  }
  return byId
})

function isFeatureVerified(feature) {
  const plot = plotVerificationById.value.get(feature?.properties?.id)
  return isPlotVerified(plot, plot?.farm)
}

function handlePlotDrawn({ layer, coordinates }) {
  error.value = ''
  activeLayer.value = layer
  form.value.coordinates = coordinates
  form.value.name = t('farmer.plots.auto_name', {
    number: farmingStore.plots?.features?.length ? farmingStore.plots.features.length + 1 : 1,
  })
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

function validatePlot() {
  if (!form.value.name.trim()) return t('farmer.plots.name_required')
  if (form.value.name.length > PLOT_NAME_MAX_LENGTH)
    return t('farmer.plots.name_too_long', { max: PLOT_NAME_MAX_LENGTH })
  if (!form.value.soil_type) return t('farmer.plots.soil_required')
  if (!form.value.coordinates.length) return t('farmer.plots.boundary_required')
  return ''
}

function requestSavePlot() {
  error.value = validatePlot()
  if (error.value) return
  pendingConfirm.value = { ...form.value }
}

const savePlot = () => execute(performSavePlot)

async function performSavePlot(payload) {
  isSaving.value = true
  error.value = ''
  try {
    await farmingStore.createPlot(farmId, payload)
    activeLayer.value = null
    form.value.coordinates = []
    form.value.name = ''
    form.value.soil_type = ''
  } catch (e) {
    error.value = e.response?.data?.message || t('farmer.plots.save_error')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      :title="t('farmer.plots.title')"
      :description="t('farmer.plots.description', { farm: farmName })"
    />
    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_20rem] gap-6">
      <!-- Map Area -->
      <div
        class="relative h-[55vh] min-h-80 lg:h-[calc(100vh-16rem)] rounded-2xl overflow-hidden border border-stone-200 shadow-soft"
      >
        <PlotDrawer
          ref="plotDrawerRef"
          :existing-plots="farmingStore.plots"
          :farm="farmingStore.activeFarm"
          @plot-drawn="handlePlotDrawn"
          @plot-error="handlePlotError"
        />
      </div>

      <!-- Side Panel -->
      <div class="w-full flex flex-col gap-4">
        <!-- Farm info card -->
        <AppCard variant="muted" padding="p-0">
          <div class="px-4 py-4 border-b border-stone-300">
            <div class="flex items-center gap-2 mb-1">
              <ChartBarIcon class="h-5 w-5 text-moss-700" aria-hidden="true" />
              <h3 class="font-serif text-base font-bold text-moss-900">{{ farmName }}</h3>
            </div>
            <p class="text-xs text-moss-700 leading-relaxed">
              {{ t('farmer.plots.draw_hint') }}
            </p>
          </div>

          <div v-if="farmingStore.activeFarm?.city" class="px-4 py-3 flex items-start gap-2">
            <MapPinIcon class="h-5 w-5 text-moss-700" aria-hidden="true" />
            <div class="text-xs">
              <div class="text-stone-500">{{ t('farmer.plots.restricted_area') }}</div>
              <strong class="font-semibold text-moss-900">
                {{ farmingStore.activeFarm.city
                }}<span v-if="farmingStore.activeFarm.country"
                  >, {{ farmingStore.activeFarm.country }}</span
                >
              </strong>
            </div>
          </div>
        </AppCard>

        <!-- Plot error -->
        <AppAlert v-if="error && !activeLayer" type="error">
          {{ error }}
        </AppAlert>

        <!-- Save plot form (when drawing active) -->
        <AppCard v-if="activeLayer" padding="p-4" class="!overflow-visible">
          <div class="flex items-center gap-2 mb-4">
            <ChartBarIcon class="h-5 w-5 text-moss-700" aria-hidden="true" />
            <h2 class="font-serif text-xl font-bold text-stone-900">
              {{ t('farmer.plots.save_title') }}
            </h2>
          </div>
          <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>

          <form @submit.prevent="requestSavePlot" class="space-y-4">
            <FormField
              id="plot-name"
              :label="t('farmer.plots.name_label')"
              v-model="form.name"
              required
              :maxlength="PLOT_NAME_MAX_LENGTH"
            />
            <SoilTypeSelect id="plot-soil" v-model="form.soil_type" required />

            <div class="flex flex-col gap-2 pt-2">
              <AppButton
                type="submit"
                variant="primary"
                :loading="isSaving"
                class="w-full"
                :disabled="!authStore.isEmailVerified"
              >
                {{ t('farmer.plots.save_button') }}
              </AppButton>
              <AppButton type="button" variant="ghost" @click="cancelDrawing" class="w-full">
                {{ t('farmer.plots.discard') }}
              </AppButton>
            </div>
          </form>
        </AppCard>

        <!-- Plot list (when not drawing) -->
        <AppCard v-else padding="p-0" class="flex-1 overflow-hidden flex flex-col min-h-0">
          <div
            class="px-4 py-3 border-b border-stone-100 bg-stone-50 flex items-center justify-between"
          >
            <h2 class="font-serif text-xl font-bold text-stone-900">
              {{ t('farmer.plots.existing') }}
            </h2>
            <span class="text-xs font-medium text-stone-500">
              {{ t('farmer.plots.total', { count: farmingStore.plots?.features?.length || 0 }) }}
            </span>
          </div>
          <div class="flex-1 overflow-y-auto p-2">
            <LoadingState
              v-if="farmingStore.loading"
              :label="t('farmer.plots.loading')"
              class="space-y-2 p-2"
              ><AppSkeleton v-for="n in PLOT_SKELETON_COUNT" :key="n" class="h-14 w-full"
            /></LoadingState>
            <p
              v-else-if="!farmingStore.plots?.features?.length"
              class="text-sm text-stone-600 text-center p-6"
            >
              {{ t('farmer.plots.empty') }}
            </p>
            <ul v-else class="space-y-2">
              <li
                v-for="feature in farmingStore.plots.features"
                :key="feature.properties.id"
                class="p-3 bg-white border border-stone-100 rounded-xl shadow-soft"
              >
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <AppButton
                    variant="ghost"
                    class="min-w-0 text-left !px-0"
                    :aria-label="t('farmer.plots.zoom_to', { name: feature.properties.name })"
                    @click="plotDrawerRef?.zoomToPlot(feature.properties.id)"
                    ><div class="min-w-0">
                      <div
                        class="font-semibold text-stone-900 text-sm truncate flex items-center gap-2"
                      >
                        {{ feature.properties.name }}
                        <VerifiedBadge v-if="isFeatureVerified(feature)" size="sm" />
                      </div>
                      <div class="flex items-center gap-2 mt-1 text-xs text-stone-500">
                        <span class="capitalize">{{
                          feature.properties.soil_type || t('farmer.plots.unknown_soil')
                        }}</span>
                        <span>•</span>
                        <span class="font-bold text-moss-600">
                          {{
                            feature.properties.calculated_area
                              ? Number(feature.properties.calculated_area).toFixed(2)
                              : 0
                          }}
                          ha
                        </span>
                      </div>
                    </div></AppButton
                  >
                  <router-link
                    :to="{ name: 'crop-recommendations', params: { id: feature.properties.id } }"
                    class="inline-flex min-h-11 items-center gap-1 px-2.5 py-1.5 rounded-xl bg-moss-50 hover:bg-moss-100 text-moss-700 text-xs font-semibold transition-colors duration-150 flex-shrink-0"
                  >
                    <span>{{ t('farmer.plots.recommendations') }}</span>
                  </router-link>
                </div>
              </li>
            </ul>
          </div>
        </AppCard>
      </div>
    </div>
    <ConfirmModal
      :is-open="pendingConfirm !== null"
      :loading="isExecuting"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="savePlot"
      @cancel="cancel"
    />
  </div>
</template>
