<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { GEO_CONSTANTS } from '@/constants/geo'
import { DESIGN_COLORS } from '@/constants/designTokens'

const props = defineProps({
  geojson: {
    type: Object,
    default: null,
  },
})

const mapContainer = ref(null)
let map = null
let layer = null

function renderPolygon() {
  if (!map) return
  if (layer) {
    map.removeLayer(layer)
    layer = null
  }
  const geometry = props.geojson?.geometry
  if (!geometry) {
    map.setView([...GEO_CONSTANTS.FALLBACK_CENTER], GEO_CONSTANTS.FALLBACK_ZOOM)
    return
  }
  layer = L.geoJSON(geometry, {
    style: { color: DESIGN_COLORS.moss[600], weight: 2, fillOpacity: 0.2 },
  }).addTo(map)
  map.fitBounds(layer.getBounds(), { padding: [20, 20] })
}

onMounted(() => {
  map = L.map(mapContainer.value, { scrollWheelZoom: false }).setView(
    [...GEO_CONSTANTS.FALLBACK_CENTER],
    GEO_CONSTANTS.FALLBACK_ZOOM,
  )
  L.tileLayer(GEO_CONSTANTS.TILE_URL, {
    attribution: GEO_CONSTANTS.TILE_ATTRIBUTION,
    maxZoom: GEO_CONSTANTS.TILE_MAX_ZOOM,
  }).addTo(map)
  renderPolygon()
})

watch(() => props.geojson, renderPolygon)

onUnmounted(() => {
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<template>
  <div
    ref="mapContainer"
    data-testid="verification-map"
    class="h-64 w-full overflow-hidden rounded-2xl border border-stone-200"
  />
</template>
