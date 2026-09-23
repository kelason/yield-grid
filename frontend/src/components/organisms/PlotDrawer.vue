<script setup>
import { onMounted, ref, watch, onBeforeUnmount } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-draw'
import 'leaflet-draw/dist/leaflet.draw.css'
import { GeoSearchControl, OpenStreetMapProvider } from 'leaflet-geosearch'
import 'leaflet-geosearch/dist/geosearch.css'
import { useApi } from '../../composables/useApi'
import booleanIntersects from '@turf/boolean-intersects'
import { polygon as turfPolygon } from '@turf/helpers'

const MAP_CONSTANTS = {
  DEFAULT_CENTER: [12.8797, 121.774], // Philippines
  DEFAULT_ZOOM: 6,
  PADDING_SMALL: [30, 30],
  PADDING_LARGE: [60, 60],
  MAX_ZOOM_FIT_PLOTS: 17,
  MAX_ZOOM_ZOOM_TO_PLOT: 18,
  BOUNDS_PAD_RATIO: 0.4,
}

const OVERPASS_CONSTANTS = {
  TIMEOUT_SEC: 8,
  MAX_SIZE_BYTES: 262144, // 256KB
  FETCH_TIMEOUT_MS: 10000,
}

const GEOMETRY_CONSTANTS = {
  COORD_PRECISION: 6,
  MIN_LINE_POINTS: 2,
  MIN_RING_POINTS: 3,
  MIN_POLYGON_POINTS: 4,
}

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
const api = useApi()
let restrictedZonesLayer = null
const isCheckingZone = ref(false)

// Type labels for friendly error messages
const RESTRICTED_TYPE_LABELS = {
  building: 'building or house',
  highway: 'road',
  waterway: 'river or waterway',
}

/**
 * Queries Overpass for OSM features ONLY within the drawn polygon's bounding box.
 * Roads/waterways in OSM are LineString ways — we use booleanIntersects with a line geometry.
 * Buildings are closed rings — we use polygon intersection for those.
 * Returns the first conflicting feature found, or null if clear.
 */
async function queryOsmForPolygon(drawnGeoJson) {
  const coords = drawnGeoJson.geometry.coordinates[0]
  const lats = coords.map((c) => c[1])
  const lngs = coords.map((c) => c[0])
  // No bbox expansion — tight bounds prevent picking up adjacent-but-not-overlapping roads
  const south = Math.min(...lats).toFixed(GEOMETRY_CONSTANTS.COORD_PRECISION)
  const west = Math.min(...lngs).toFixed(GEOMETRY_CONSTANTS.COORD_PRECISION)
  const north = Math.max(...lats).toFixed(GEOMETRY_CONSTANTS.COORD_PRECISION)
  const east = Math.max(...lngs).toFixed(GEOMETRY_CONSTANTS.COORD_PRECISION)

  const bbox = `${south},${west},${north},${east}`

  // Only check significant roads (not footpaths, tracks, or pedestrian paths)
  // "track" and "path" are typically dirt farm/forest paths, not real roads
  const query = `[out:json][timeout:${OVERPASS_CONSTANTS.TIMEOUT_SEC}][maxsize:${OVERPASS_CONSTANTS.MAX_SIZE_BYTES}];
(
  way["building"](${bbox});
  way["highway"~"^(residential|primary|secondary|tertiary|trunk|motorway|service|unclassified|living_street|road)$"](${bbox});
  way["waterway"~"^(river|stream|canal|drain)$"](${bbox});
);
out geom;`

  const mirrors = [
    'https://overpass-api.de/api/interpreter',
    'https://overpass.kumi.systems/api/interpreter',
    'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
  ]

  for (const url of mirrors) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'data=' + encodeURIComponent(query),
        signal: AbortSignal.timeout(OVERPASS_CONSTANTS.FETCH_TIMEOUT_MS),
      })
      if (!res.ok) continue

      const contentType = res.headers.get('content-type') || ''
      if (!contentType.includes('json')) continue

      const data = await res.json()
      if (!data?.elements?.length) return null

      const drawnPoly = turfPolygon(drawnGeoJson.geometry.coordinates)

      for (const el of data.elements) {
        if (!el.geometry || el.geometry.length < GEOMETRY_CONSTANTS.MIN_LINE_POINTS) continue

        const osmType = el.tags?.building ? 'building' : el.tags?.highway ? 'highway' : 'waterway'

        try {
          let osmFeature

          if (osmType === 'building') {
            // Buildings are closed polygons in OSM
            const ring = el.geometry.map((pt) => [pt.lon, pt.lat])
            if (ring.length < GEOMETRY_CONSTANTS.MIN_RING_POINTS) continue
            // Close the ring if needed
            if (
              ring[0][0] !== ring[ring.length - 1][0] ||
              ring[0][1] !== ring[ring.length - 1][1]
            ) {
              ring.push(ring[0])
            }
            if (ring.length < GEOMETRY_CONSTANTS.MIN_POLYGON_POINTS) continue // need at least 4 points for a valid polygon
            osmFeature = turfPolygon([ring])
          } else {
            // Roads and waterways are LineStrings in OSM — treat them as lines, NOT polygons
            // This prevents false positives from roads adjacent to (but not intersecting) the plot
            const line = el.geometry.map((pt) => [pt.lon, pt.lat])
            if (line.length < GEOMETRY_CONSTANTS.MIN_LINE_POINTS) continue
            osmFeature = { type: 'Feature', geometry: { type: 'LineString', coordinates: line } }
          }

          if (booleanIntersects(drawnPoly, osmFeature)) {
            return {
              type: RESTRICTED_TYPE_LABELS[osmType] || 'restricted area',
              name:
                el.tags?.name ||
                el.tags?.['addr:street'] ||
                el.tags?.ref ||
                RESTRICTED_TYPE_LABELS[osmType],
            }
          }
        } catch {
          /* skip malformed geometries */
        }
      }

      return null // reached Overpass, no intersection found
    } catch (err) {
      console.warn(`Overpass mirror ${url} failed:`, err.message)
    }
  }

  // All mirrors failed — fail open with a console warning
  console.warn('All Overpass mirrors unavailable. Zone validation skipped for this draw.')
  return null
}

const ZONE_STYLES = {
  house: { color: '#ef4444', fillColor: '#fca5a5', label: '🏠' },
  road: { color: '#f97316', fillColor: '#fdba74', label: '🛣️' },
  river: { color: '#3b82f6', fillColor: '#93c5fd', label: '💧' },
  default: { color: '#ef4444', fillColor: '#fca5a5', label: '⛔' },
}

async function fetchAndRenderRestrictedZones() {
  if (!map) return
  try {
    const response = await api.get('/restricted-zones')
    const geojson = response.data
    if (!geojson || !geojson.features?.length) return

    if (restrictedZonesLayer && map.hasLayer(restrictedZonesLayer)) {
      map.removeLayer(restrictedZonesLayer)
    }

    restrictedZonesLayer = L.geoJSON(geojson, {
      style: (feature) => {
        const style = ZONE_STYLES[feature.properties?.type] || ZONE_STYLES.default
        return {
          color: style.color,
          weight: 1.5,
          fillColor: style.fillColor,
          fillOpacity: 0.45,
          dashArray: '4, 4',
        }
      },
      onEachFeature: (feature, layer) => {
        const style = ZONE_STYLES[feature.properties?.type] || ZONE_STYLES.default
        const name = feature.properties?.name || 'Restricted Area'
        const type = feature.properties?.type || 'restricted'
        layer.bindTooltip(
          `<span class="font-semibold">${style.label} ${name}</span><br><span class="text-xs text-gray-400">Type: ${type} — No plotting allowed</span>`,
          { sticky: true, className: 'restricted-zone-tooltip' },
        )
      },
    }).addTo(map)
  } catch (err) {
    console.warn('Could not load restricted zones:', err)
  }
}

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
        map.fitBounds(bounds, { padding: MAP_CONSTANTS.PADDING_SMALL })
      }

      // Restrict panning so user stays in the farm's region
      map.setMaxBounds(bounds.pad(MAP_CONSTANTS.BOUNDS_PAD_RATIO))
    }
  } catch (err) {
    console.warn('Failed to geocode farm city boundary:', err)
  } finally {
    isGeocodingCity.value = false
  }
}

onMounted(() => {
  // Initialize map centered on the Philippines
  map = L.map(mapContainer.value).setView(MAP_CONSTANTS.DEFAULT_CENTER, MAP_CONSTANTS.DEFAULT_ZOOM)

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

  // Handle draw created event — async to run Overpass validation
  map.on(L.Draw.Event.CREATED, async function (e) {
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

      // OSM zone validation: query tiny bbox around drawn polygon
      isCheckingZone.value = true
      try {
        const conflict = await queryOsmForPolygon(geojson)
        if (conflict) {
          map.removeLayer(layer)
          emit(
            'plot-error',
            `Cannot plot here — your area overlaps a ${conflict.type} ("${conflict.name}"). Please draw only on vacant, agricultural land.`,
          )
          return
        }
      } finally {
        isCheckingZone.value = false
      }

      emit('plot-drawn', { layer, coordinates })
    }
  })

  // Focus farm city if available
  focusFarmCity()

  // Load restricted zones as red overlays
  fetchAndRenderRestrictedZones()

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

  // Remove any previously rendered plots layer
  if (window._plotsLayer && map.hasLayer(window._plotsLayer)) {
    map.removeLayer(window._plotsLayer)
  }

  if (
    props.existingPlots &&
    props.existingPlots.features &&
    props.existingPlots.features.length > 0
  ) {
    window._plotsLayer = L.geoJSON(props.existingPlots, {
      style: {
        color: '#059669',
        weight: 2,
        fillColor: '#10b981',
        fillOpacity: 0.25,
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
        link.textContent = '✨ View Recommendations'
        container.appendChild(link)

        layer.bindPopup(container)
      },
    }).addTo(map)

    // Fit map bounds to existing plots
    try {
      const bounds = window._plotsLayer.getBounds()
      if (bounds.isValid()) {
        map.fitBounds(bounds, {
          padding: MAP_CONSTANTS.PADDING_LARGE,
          maxZoom: MAP_CONSTANTS.MAX_ZOOM_FIT_PLOTS,
        })
      }
    } catch {
      if (allowedCityBounds.value) {
        map.fitBounds(allowedCityBounds.value, { padding: MAP_CONSTANTS.PADDING_SMALL })
      }
    }
  } else if (allowedCityBounds.value) {
    map.fitBounds(allowedCityBounds.value, { padding: MAP_CONSTANTS.PADDING_SMALL })
  }
}

onBeforeUnmount(() => {
  if (map) {
    map.remove()
  }
})

defineExpose({
  zoomToPlot: (plotId) => {
    if (window._plotsLayer && map) {
      let targetLayer = null
      window._plotsLayer.eachLayer((layer) => {
        if (layer.feature && layer.feature.properties.id === plotId) {
          targetLayer = layer
        }
      })
      if (targetLayer) {
        map.fitBounds(targetLayer.getBounds(), {
          padding: MAP_CONSTANTS.PADDING_LARGE,
          maxZoom: MAP_CONSTANTS.MAX_ZOOM_ZOOM_TO_PLOT,
        })
        targetLayer.openPopup()
      }
    }
  },
})
</script>

<template>
  <div class="relative w-full h-full">
    <div
      ref="mapContainer"
      class="w-full h-full z-0 rounded-md shadow-sm border border-gray-300"
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
        v-if="isCheckingZone"
        class="absolute inset-0 z-[500] bg-black/20 backdrop-blur-[1px] rounded-md flex items-center justify-center"
      >
        <div
          class="bg-white rounded-xl shadow-xl px-5 py-4 flex items-center gap-3 border border-gray-200"
        >
          <svg class="w-5 h-5 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
          <div>
            <p class="text-sm font-bold text-gray-900">Checking zone...</p>
            <p class="text-xs text-gray-500">Verifying no buildings or roads overlap</p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Active City Zone Indicator Overlay -->
    <div
      v-if="farm?.city"
      class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow-md border border-emerald-300 text-xs font-semibold text-emerald-800 flex items-center gap-2 max-w-[calc(100%-1.5rem)]"
    >
      <span
        class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse flex-shrink-0"
      ></span>
      <div class="truncate">
        <span class="text-gray-500 font-normal">Zone: </span>
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
/* Restricted zone tooltip styling */
.restricted-zone-tooltip {
  background: rgba(17, 17, 17, 0.9) !important;
  border: 1px solid #ef4444 !important;
  color: #fff !important;
  border-radius: 8px !important;
  padding: 6px 10px !important;
  font-size: 12px !important;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
}
.restricted-zone-tooltip::before {
  border-top-color: #ef4444 !important;
}
</style>
