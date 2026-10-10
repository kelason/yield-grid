<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { DESIGN_COLORS } from '@/constants/designTokens'
import { GEO_CONSTANTS } from '@/constants/geo'
import { haversineKm } from '@/composables/useGeo'

const props = defineProps({
  modelValue: { type: Object, default: null },
  center: { type: Object, default: null },
  maxRadiusKm: { type: Number, default: null },
})

const emit = defineEmits(['update:modelValue', 'validation'])

const { t } = useI18n()
const mapContainer = ref(null)
let map = null
let marker = null
let radiusCircle = null

const outOfRange = ref(false)

function resolveCenter() {
  if (props.modelValue?.lat != null && props.modelValue?.lng != null) {
    return [props.modelValue.lat, props.modelValue.lng]
  }
  if (props.center?.lat != null && props.center?.lng != null) {
    return [props.center.lat, props.center.lng]
  }
  return GEO_CONSTANTS.FALLBACK_CENTER
}

function checkRange(lat, lng) {
  if (props.center?.lat == null || props.center?.lng == null || props.maxRadiusKm == null) {
    outOfRange.value = false
    emit('validation', { valid: true, distanceKm: null })
    return true
  }
  const distanceKm = haversineKm(props.center.lat, props.center.lng, lat, lng)
  const valid = distanceKm <= props.maxRadiusKm
  outOfRange.value = !valid
  emit('validation', { valid, distanceKm })
  return valid
}

function placeMarker(lat, lng) {
  if (!map) return
  if (!marker) {
    marker = L.marker([lat, lng], { draggable: true }).addTo(map)
    marker.on('dragend', () => {
      const pos = marker.getLatLng()
      handlePick(pos.lat, pos.lng)
    })
  } else {
    marker.setLatLng([lat, lng])
  }
}

function drawRadius() {
  if (radiusCircle) {
    map.removeLayer(radiusCircle)
    radiusCircle = null
  }
  if (map && props.center?.lat != null && props.center?.lng != null && props.maxRadiusKm != null) {
    radiusCircle = L.circle([props.center.lat, props.center.lng], {
      radius: props.maxRadiusKm * GEO_CONSTANTS.METERS_PER_KM,
      color: DESIGN_COLORS.moss[600],
      weight: 1.5,
      dashArray: '6 4',
      fillOpacity: 0.06,
    }).addTo(map)
  }
}

function handlePick(lat, lng) {
  placeMarker(lat, lng)
  const rounded = {
    lat: Math.round(lat * 1000000) / 1000000,
    lng: Math.round(lng * 1000000) / 1000000,
  }
  checkRange(rounded.lat, rounded.lng)
  emit('update:modelValue', rounded)
}

onMounted(() => {
  map = L.map(mapContainer.value).setView(resolveCenter(), GEO_CONSTANTS.PIN_ZOOM)
  L.tileLayer(GEO_CONSTANTS.TILE_URL, {
    attribution: GEO_CONSTANTS.TILE_ATTRIBUTION,
    maxZoom: GEO_CONSTANTS.TILE_MAX_ZOOM,
  }).addTo(map)

  map.on('click', (e) => handlePick(e.latlng.lat, e.latlng.lng))

  if (props.modelValue?.lat != null && props.modelValue?.lng != null) {
    placeMarker(props.modelValue.lat, props.modelValue.lng)
    checkRange(props.modelValue.lat, props.modelValue.lng)
  }
  drawRadius()
})

watch(
  () => props.center,
  (next) => {
    if (!map || next?.lat == null || next?.lng == null) return
    map.setView([next.lat, next.lng], GEO_CONSTANTS.PIN_ZOOM)
    drawRadius()
  },
  { deep: true },
)

onBeforeUnmount(() => {
  if (map) {
    map.remove()
    map = null
    marker = null
    radiusCircle = null
  }
})
</script>

<template>
  <div>
    <div
      ref="mapContainer"
      class="w-full h-64 rounded-2xl border border-stone-200 shadow-soft z-0"
      role="application"
      :aria-label="t('shell.map.pin_aria')"
    ></div>
    <p v-if="outOfRange" class="mt-2 text-sm text-red-600" role="alert">
      {{ t('shell.map.pin_range_error') }}
    </p>
    <p v-else class="mt-2 text-xs text-stone-500">
      {{ t('shell.map.pin_hint') }}
    </p>
  </div>
</template>
