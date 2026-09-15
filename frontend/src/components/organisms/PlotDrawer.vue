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
  farm: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['plot-drawn', 'plot-error'])

const mapContainer = ref(null)
let map = null
let drawnItems = null
let drawControl = null
let boundaryLayer = null
const allowedCityBounds = ref(null)
const isGeocodingCity = ref(false)

async function focusFarmCity() {
  if (!map || !props.farm?.city) return

  const city = props.farm.city
  const state = props.farm.state || ''
  const country = props.farm.country || 'Philippines'

  // Clean city name for search (remove redundant "City" suffix if needed)
  const cleanCity = city.replace(/\bcity\b/gi, '').trim()
  const searchQuery = [cleanCity, state, country].filter(Boolean).join(', ')

  isGeocodingCity.value = true
  try {
    const res = await fetch(
      `https://photon.komoot.io/api/?q=${encodeURIComponent(searchQuery)}&limit=5`,
    )
    if (!res.ok) return
    const data = await res.json()
    const features = data.features || []

    // Find the feature with an extent that best matches
    const match =
      features.find(
        (f) =>
          f.properties?.extent &&
          (f.properties?.osm_value === 'city' ||
            f.properties?.osm_value === 'town' ||
            f.properties?.osm_value === 'municipality' ||
            f.properties?.type === 'city'),
      ) || features.find((f) => f.properties?.extent)

    if (match && match.properties?.extent) {
      const ext = match.properties.extent // [minLng, maxLat, maxLng, minLat]
      const bounds = L.latLngBounds([ext[3], ext[0]], [ext[1], ext[2]])
      allowedCityBounds.value = bounds

      if (boundaryLayer && map.hasLayer(boundaryLayer)) {
        map.removeLayer(boundaryLayer)
      }

      // Draw dashed emerald boundary overlay for the allowed city
      boundaryLayer = L.rectangle(bounds, {
        color: '#059669', // emerald
        weight: 2,
        dashArray: '8, 8',
        fillColor: '#10b981',
        fillOpacity: 0.04,
        interactive: false,
      }).addTo(map)

      // If no existing plots, fit directly to city bounds
      if (!props.existingPlots?.features?.length) {
        map.fitBounds(bounds, { padding: [30, 30] })
      }

      // Restrict panning so user stays in the farm's region
      map.setMaxBounds(bounds.pad(0.4))
    }
  } catch (err) {
    console.warn('Failed to geocode farm city boundary:', err)
  } finally {
    isGeocodingCity.value = false
  }
}

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
      const coordinates = geojson.geometry.coordinates[0]

      // Validate boundary if farm city bounds are active
      if (allowedCityBounds.value) {
        const latlngs = layer.getLatLngs()[0] || []
        const isWithin = latlngs.every((pt) => allowedCityBounds.value.contains(pt))

        if (!isWithin) {
          const farmCity = props.farm?.city || 'the registered farm city'
          map.removeLayer(layer)
          emit(
            'plot-error',
            `Plots for this farm must be drawn within ${farmCity}. The drawn shape is outside the allowed city area.`,
          )
          return
        }
      }

      emit('plot-drawn', { layer, coordinates })
    }
  })

  // Focus farm city if available
  focusFarmCity()

  // Load existing plots
  loadExistingPlots()
})

// Watch for farm changes to re-center/bound
watch(
  () => props.farm,
  () => {
    focusFarmCity()
  },
  { deep: true },
)

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
        const area = feature.properties?.calculated_area
          ? `${Number(feature.properties.calculated_area).toFixed(2)} ha`
          : 'Unknown area'

        // Safely construct popup content using DOM elements to prevent XSS (CWE-79)
        const container = document.createElement('div')
        container.className = 'text-center'

        const nameEl = document.createElement('b')
        nameEl.textContent = String(feature.properties?.name || 'Plot')
        container.appendChild(nameEl)
        container.appendChild(document.createElement('br'))

        const areaEl = document.createElement('span')
        areaEl.className = 'text-gray-600'
        areaEl.textContent = `Area: ${area}`
        container.appendChild(areaEl)
        container.appendChild(document.createElement('br'))

        const plotId = encodeURIComponent(String(feature.properties?.id ?? ''))
        const link = document.createElement('a')
        link.href = `/dashboard/plots/${plotId}/recommendations`
        link.className =
          'inline-block mt-3 px-4 py-1.5 bg-farm-600 !text-white rounded-md text-sm font-semibold hover:bg-farm-700 transition-colors'
        link.style.cssText = 'text-decoration: none; color: white !important;'
        link.textContent = '🌾 View Recommendations'
        container.appendChild(link)

        layer.bindPopup(container)
        drawnItems.addLayer(layer)
      },
    })

    // Fit map bounds to existing plots
    if (drawnItems.getLayers().length > 0) {
      map.fitBounds(drawnItems.getBounds(), { padding: [50, 50] })
    }
  } else if (allowedCityBounds.value) {
    map.fitBounds(allowedCityBounds.value, { padding: [30, 30] })
  }
}

onBeforeUnmount(() => {
  if (map) {
    map.remove()
  }
})
</script>

<template>
  <div class="relative w-full h-full">
    <div
      ref="mapContainer"
      class="w-full h-full z-0 rounded-md shadow-sm border border-gray-300"
    ></div>

    <!-- Active City Zone Indicator Overlay -->
    <div
      v-if="farm?.city"
      class="absolute top-3 right-3 z-[400] bg-white/95 backdrop-blur-sm px-3.5 py-2 rounded-lg shadow-md border border-emerald-300 text-xs font-semibold text-emerald-800 flex items-center gap-2"
    >
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
      <div>
        <span class="text-gray-500 font-normal">Plotting Zone: </span>
        <strong class="text-emerald-900">{{ farm.city }}</strong>
        <span v-if="farm.country" class="text-gray-500 font-normal">, {{ farm.country }}</span>
      </div>
    </div>
  </div>
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
