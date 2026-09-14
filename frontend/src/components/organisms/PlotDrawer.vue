<script setup>
import { onMounted, ref, watch, onBeforeUnmount } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-draw'
import 'leaflet-draw/dist/leaflet.draw.css'
import { GeoSearchControl, OpenStreetMapProvider } from 'leaflet-geosearch'
import 'leaflet-geosearch/dist/geosearch.css'

const props = defineProps({
  existingPlots: { type: Object, default: () => ({ type: 'FeatureCollection', features: [] }) },
})

const emit = defineEmits(['plot-drawn'])

const mapContainer = ref(null)
let map = null
let drawnItems = null
let drawControl = null

onMounted(() => {
  // Initialize map centered on the Philippines
  map = L.map(mapContainer.value).setView([12.8797, 121.774], 6)

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map)

  // Initialize feature group for drawn items
  drawnItems = new L.FeatureGroup()
  map.addLayer(drawnItems)

  // Setup Leaflet Draw control
  drawControl = new L.Control.Draw({
    edit: {
      featureGroup: drawnItems,
      edit: false, // Disable edit for now to simplify saving
      remove: false, // Disable remove for now
    },
    draw: {
      polygon: {
        allowIntersection: true,
        showArea: true,
        drawError: {
          color: '#e1e100',
          message: '<strong>Error:</strong> shape edges cannot cross!',
        },
        shapeOptions: {
          color: '#059669', // farm-600
          fillOpacity: 0.4,
        },
      },
      polyline: false,
      circle: false,
      rectangle: false,
      marker: false,
      circlemarker: false,
    },
  })
  map.addControl(drawControl)

  // Setup GeoSearch control
  const provider = new OpenStreetMapProvider()
  const searchControl = new GeoSearchControl({
    provider: provider,
    style: 'bar',
    showMarker: false,
    showPopup: false,
    autoClose: true,
    retainZoomLevel: false,
    animateZoom: true,
    keepResult: true,
    searchLabel: 'Search for a city or address...',
  })
  map.addControl(searchControl)

  // Handle draw created event
  map.on(L.Draw.Event.CREATED, function (e) {
    const type = e.layerType
    const layer = e.layer

    if (type === 'polygon') {
      const geojson = layer.toGeoJSON()
      // Extract coordinates (GeoJSON polygon coordinates are in format [[[lng, lat], [lng, lat], ...]])
      const coordinates = geojson.geometry.coordinates[0]
      emit('plot-drawn', { layer, coordinates })
    }
  })

  // Load existing plots
  loadExistingPlots()
})

// Re-load plots when they change
watch(
  () => props.existingPlots,
  () => {
    loadExistingPlots()
  },
  { deep: true },
)

function loadExistingPlots() {
  if (!map || !drawnItems) return

  drawnItems.clearLayers()

  if (
    props.existingPlots &&
    props.existingPlots.features &&
    props.existingPlots.features.length > 0
  ) {
    L.geoJSON(props.existingPlots, {
      style: {
        color: '#059669', // farm-600
        weight: 2,
        fillOpacity: 0.2,
      },
      onEachFeature: (feature, layer) => {
        const area = feature.properties.calculated_area
          ? `${Number(feature.properties.calculated_area).toFixed(2)} ha`
          : 'Unknown area'
        layer.bindPopup(`<b>${feature.properties.name}</b><br/>Area: ${area}`)
        drawnItems.addLayer(layer)
      },
    })

    // Fit map bounds to existing plots
    if (drawnItems.getLayers().length > 0) {
      map.fitBounds(drawnItems.getBounds(), { padding: [50, 50] })
    }
  }
}

onBeforeUnmount(() => {
  if (map) {
    map.remove()
  }
})
</script>

<template>
  <div
    ref="mapContainer"
    class="w-full h-full z-0 rounded-md shadow-sm border border-gray-300"
  ></div>
</template>

<style>
/* Ensure leaflet draw tooltips stay above map but below modals/navs */
.leaflet-container {
  z-index: 10;
}
.leaflet-draw-tooltip {
  z-index: 20;
}
</style>
