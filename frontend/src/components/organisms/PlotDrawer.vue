<script setup>
import { ref, watch } from 'vue'
import { usePlotMap } from '@/composables/usePlotMap'
import FloodRiskLegend from '../molecules/FloodRiskLegend.vue'
import AppSpinner from '../atoms/AppSpinner.vue'
const props = defineProps({
  existingPlots: { type: Object, default: () => ({ type: 'FeatureCollection', features: [] }) },
  farm: { type: Object, default: () => ({}) },
  floodZones: { type: Object, default: null },
  floodLevel: { type: String, default: 'unknown' },
  zonesAvailable: { type: Boolean, default: true },
})
const emit = defineEmits(['plot-drawn', 'plot-error', 'bounds-change'])
const mapContainer = ref(null)
const showZones = ref(true)
const { isGeocodingCity, isCheckingZone, zoomToPlot, setFloodZones } = usePlotMap({
  mapContainer,
  farm: () => props.farm,
  existingPlots: () => props.existingPlots,
  onPlotDrawn: (plot) => emit('plot-drawn', plot),
  onPlotError: (message) => emit('plot-error', message),
  onBoundsChange: (bbox) => emit('bounds-change', bbox),
})
watch(
  () => props.floodZones,
  (zones) => {
    if (showZones.value) setFloodZones(zones)
  },
  { deep: true },
)
function onToggleZones(visible) {
  showZones.value = visible
  setFloodZones(visible ? props.floodZones : null)
}
defineExpose({ zoomToPlot })
</script>

<template>
  <div class="relative w-full h-full">
    <div
      ref="mapContainer"
      class="w-full h-full z-0 rounded-2xl shadow-soft border border-stone-300"
    ></div>

    <!-- Overpass validation loading indicator -->
    <Transition
      enter-active-class="transition-all duration-200"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition-all duration-150"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        role="status"
        aria-live="polite"
        v-if="isCheckingZone"
        class="absolute inset-0 z-[500] bg-soil-900/20 rounded-2xl flex items-center justify-center"
      >
        <div
          class="bg-white rounded-2xl shadow-organic px-5 py-4 flex items-center gap-3 border border-stone-200"
        >
          <AppSpinner class="loading-spinner w-5 h-5 text-moss-700" />
          <div>
            <p class="text-sm font-bold text-stone-900">Checking zone...</p>
            <p class="text-xs text-stone-500">Verifying no buildings or roads overlap</p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- City geocoding loading indicator -->
    <Transition
      enter-active-class="transition-all duration-200"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition-all duration-150"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="isGeocodingCity"
        data-testid="city-locating-overlay"
        role="status"
        aria-live="polite"
        class="absolute inset-0 z-[500] bg-soil-900/20 rounded-2xl flex items-center justify-center"
      >
        <div
          class="bg-white rounded-2xl shadow-organic px-5 py-4 flex items-center gap-3 border border-stone-200"
        >
          <AppSpinner class="loading-spinner w-5 h-5 text-moss-700" />
          <div>
            <p class="text-sm font-bold text-stone-900">Locating city...</p>
            <p class="text-xs text-stone-500">Zooming the map to {{ farm?.city }}</p>
          </div>
        </div>
      </div>
    </Transition>

    <FloodRiskLegend
      :level="floodLevel"
      :available="zonesAvailable"
      class="absolute bottom-16 left-3 z-[400]"
      @toggle="onToggleZones"
    />

    <!-- Active City Zone Indicator Overlay -->
    <div
      v-if="farm?.city"
      class="absolute bottom-3 left-3 z-[400] bg-white px-3 py-1.5 rounded-full shadow-soft border border-moss-300 text-xs font-semibold text-moss-800 flex items-center gap-2 max-w-[calc(100%-1.5rem)]"
    >
      <span class="inline-block w-2 h-2 rounded-full bg-moss-500 flex-shrink-0"></span>
      <div class="truncate">
        <span class="text-stone-500 font-normal">Zone: </span>
        <strong class="text-moss-900">{{ farm.city }}</strong>
        <span v-if="farm.country" class="text-stone-500 font-normal">, {{ farm.country }}</span>
      </div>
    </div>
  </div>
</template>

<style>
.leaflet-container {
  z-index: 10;
}
.leaflet-draw-tooltip {
  z-index: 20;
}
.restricted-zone-tooltip {
  @apply !bg-soil-900 !border-red-600 !text-white !rounded-xl !px-3 !py-2 !text-sm !shadow-soft;
}
.restricted-zone-tooltip::before {
  @apply !border-t-red-600;
}
</style>
