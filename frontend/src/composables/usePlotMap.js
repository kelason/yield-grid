import { ref, toValue, onMounted, onBeforeUnmount, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-draw'
import 'leaflet-draw/dist/leaflet.draw.css'
import { GeoSearchControl, OpenStreetMapProvider } from 'leaflet-geosearch'
import 'leaflet-geosearch/dist/geosearch.css'
import booleanIntersects from '@turf/boolean-intersects'
import { polygon as turfPolygon } from '@turf/helpers'
import { useApi } from '@/composables/useApi'
import { floodZoneStyle } from '@/composables/useFloodRisk'
import { GEO_CONSTANTS } from '@/constants/geo'
import { DESIGN_COLORS, DESIGN_STATUS_COLORS } from '@/constants/designTokens'

const MAP_CONSTANTS = {
  PADDING_SMALL: [30, 30],
  PADDING_LARGE: [60, 60],
  MAX_ZOOM_FIT_PLOTS: 17,
  MAX_ZOOM_ZOOM_TO_PLOT: 18,
  BOUNDS_PAD_RATIO: 0.4,
}
const OVERPASS_CONSTANTS = { TIMEOUT_SEC: 8, MAX_SIZE_BYTES: 262144, FETCH_TIMEOUT_MS: 10000 }
const GEOMETRY_CONSTANTS = {
  COORD_PRECISION: 6,
  MIN_LINE_POINTS: 2,
  MIN_RING_POINTS: 3,
  MIN_POLYGON_POINTS: 4,
}
const FLOOD_BOUNDS_DEBOUNCE_MS = 400
const FLOOD_PATH_CLASS = 'flood-zone-path'
const OVERPASS_MIRRORS = [
  'https://overpass-api.de/api/interpreter',
  'https://overpass.kumi.systems/api/interpreter',
  'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
]
const RESTRICTED_TYPE_LABELS = {
  building: 'building or house',
  highway: 'road',
  waterway: 'river or waterway',
}
const ZONE_STYLES = {
  house: { color: DESIGN_STATUS_COLORS.error, fillColor: DESIGN_STATUS_COLORS.errorFill },
  road: { color: DESIGN_COLORS.harvest[700], fillColor: DESIGN_COLORS.harvest[200] },
  river: { color: DESIGN_COLORS.dew[700], fillColor: DESIGN_COLORS.dew[200] },
  default: { color: DESIGN_STATUS_COLORS.error, fillColor: DESIGN_STATUS_COLORS.errorFill },
}
const PLOT_STYLE = {
  color: DESIGN_COLORS.moss[600],
  weight: 2,
  fillColor: DESIGN_COLORS.moss[300],
  fillOpacity: 0.25,
}
const DRAW_OPTIONS = {
  polygon: {
    allowIntersection: true,
    showArea: true,
    drawError: {
      color: DESIGN_STATUS_COLORS.error,
      message: '<strong>Error:</strong> shape edges cannot cross!',
    },
    shapeOptions: { color: DESIGN_COLORS.moss[600], fillOpacity: 0.4 },
  },
  polyline: false,
  circle: false,
  rectangle: false,
  marker: false,
  circlemarker: false,
}
const SEARCH_OPTIONS = {
  style: 'bar',
  showMarker: false,
  showPopup: false,
  autoClose: true,
  retainZoomLevel: false,
  animateZoom: true,
  keepResult: true,
  searchLabel: 'Search for a city or address...',
}

function osmQuery(geojson) {
  const coords = geojson.geometry.coordinates[0]
  const lats = coords.map((c) => c[1])
  const lngs = coords.map((c) => c[0])
  const bbox = [Math.min(...lats), Math.min(...lngs), Math.max(...lats), Math.max(...lngs)]
    .map((n) => n.toFixed(GEOMETRY_CONSTANTS.COORD_PRECISION))
    .join(',')
  return `[out:json][timeout:${OVERPASS_CONSTANTS.TIMEOUT_SEC}][maxsize:${OVERPASS_CONSTANTS.MAX_SIZE_BYTES}];
(way["building"](${bbox});
way["highway"~"^(residential|primary|secondary|tertiary|trunk|motorway|service|unclassified|living_street|road)$"](${bbox});
way["waterway"~"^(river|stream|canal|drain)$"](${bbox}););
out geom;`
}
function osmGeometry(element, type) {
  const coordinates = element.geometry.map((point) => [point.lon, point.lat])
  if (type !== 'building') return { type: 'Feature', geometry: { type: 'LineString', coordinates } }
  if (coordinates.length < GEOMETRY_CONSTANTS.MIN_RING_POINTS) return null
  const first = coordinates[0]
  const last = coordinates[coordinates.length - 1]
  if (first[0] !== last[0] || first[1] !== last[1]) coordinates.push(first)
  return coordinates.length < GEOMETRY_CONSTANTS.MIN_POLYGON_POINTS
    ? null
    : turfPolygon([coordinates])
}
function intersectingFeature(elements, geojson) {
  const drawnPoly = turfPolygon(geojson.geometry.coordinates)
  for (const element of elements) {
    if (!element.geometry || element.geometry.length < GEOMETRY_CONSTANTS.MIN_LINE_POINTS) continue
    const type = element.tags?.building
      ? 'building'
      : element.tags?.highway
        ? 'highway'
        : 'waterway'
    try {
      const feature = osmGeometry(element, type)
      if (feature && booleanIntersects(drawnPoly, feature))
        return {
          type: RESTRICTED_TYPE_LABELS[type] || 'restricted area',
          name:
            element.tags?.name ||
            element.tags?.['addr:street'] ||
            element.tags?.ref ||
            RESTRICTED_TYPE_LABELS[type],
        }
    } catch {
      /* Preserve skipping malformed OSM geometries. */
    }
  }
  return null
}
async function queryOsmForPolygon(geojson) {
  const query = osmQuery(geojson)
  for (const url of OVERPASS_MIRRORS) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'data=' + encodeURIComponent(query),
        signal: AbortSignal.timeout(OVERPASS_CONSTANTS.FETCH_TIMEOUT_MS),
      })
      if (!res.ok || !(res.headers.get('content-type') || '').includes('json')) continue
      const data = await res.json()
      return data?.elements?.length ? intersectingFeature(data.elements, geojson) : null
    } catch (err) {
      console.warn(`Overpass mirror ${url} failed:`, err.message)
    }
  }
  console.warn('All Overpass mirrors unavailable. Zone validation skipped for this draw.')
  return null
}
function zoneStyle(feature) {
  const style = ZONE_STYLES[feature?.properties?.type] || ZONE_STYLES.default
  return {
    color: style.color,
    weight: 1.5,
    fillColor: style.fillColor,
    fillOpacity: 0.45,
    dashArray: '4, 4',
  }
}
function zoneTooltip(feature, layer) {
  const container = document.createElement('div')
  const title = document.createElement('strong')
  title.textContent = String(feature.properties?.name || 'Restricted Area')
  const description = document.createElement('p')
  description.textContent = `Type: ${feature.properties?.type || 'restricted'} — No plotting allowed`
  container.append(title, description)
  layer.bindTooltip(container, { sticky: true, className: 'restricted-zone-tooltip' })
}
async function fetchRestrictedZones(s) {
  try {
    const response = await s.api.get('/restricted-zones')
    if (!s.map || !response.data?.features?.length) return
    if (s.restrictedZonesLayer && s.map.hasLayer(s.restrictedZonesLayer))
      s.map.removeLayer(s.restrictedZonesLayer)
    s.restrictedZonesLayer = L.geoJSON(response.data, {
      style: zoneStyle,
      onEachFeature: zoneTooltip,
    }).addTo(s.map)
  } catch (err) {
    console.warn('Could not load restricted zones:', err)
  }
}
function cityFeature(features) {
  return (
    features.find(
      (feature) =>
        feature.properties?.extent &&
        (['city', 'town', 'municipality'].includes(feature.properties?.osm_value) ||
          feature.properties?.type === 'city'),
    ) || features.find((feature) => feature.properties?.extent)
  )
}
function clearCityBoundary(s) {
  s.allowedCityBounds.value = null
  if (s.boundaryLayer && s.map?.hasLayer(s.boundaryLayer)) s.map.removeLayer(s.boundaryLayer)
  s.boundaryLayer = null
  s.map?.setMaxBounds(null)
}
function applyCityBoundary(s, feature) {
  const extent = feature?.properties?.extent
  if (!s.map || !extent) return
  const bounds = L.latLngBounds([extent[3], extent[0]], [extent[1], extent[2]])
  s.allowedCityBounds.value = bounds
  s.boundaryLayer = L.rectangle(bounds, {
    color: DESIGN_COLORS.moss[600],
    weight: 2,
    dashArray: '8, 8',
    fillColor: DESIGN_COLORS.moss[300],
    fillOpacity: 0.04,
    interactive: false,
  }).addTo(s.map)
  if (!toValue(s.existingPlots)?.features?.length)
    s.map.fitBounds(bounds, { padding: MAP_CONSTANTS.PADDING_SMALL })
  s.map.setMaxBounds(bounds.pad(MAP_CONSTANTS.BOUNDS_PAD_RATIO))
}
async function focusFarmCity(s) {
  const requestId = ++s.cityRequestId
  clearCityBoundary(s)
  const farm = toValue(s.farm)
  if (!s.map || !farm?.city) return
  const city = farm.city.replace(/\bcity\b/gi, '').trim()
  const query = [city, farm.state || '', farm.country || 'Philippines'].filter(Boolean).join(', ')
  s.pendingGeocodes += 1
  s.isGeocodingCity.value = true
  try {
    const res = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=5`)
    if (!res.ok) return
    const data = await res.json()
    if (requestId === s.cityRequestId) applyCityBoundary(s, cityFeature(data.features || []))
  } catch (err) {
    console.warn('Failed to geocode farm city boundary:', err)
  } finally {
    s.pendingGeocodes -= 1
    s.isGeocodingCity.value = s.pendingGeocodes > 0
  }
}
function boundaryError(s, layer) {
  if (!s.allowedCityBounds.value) return ''
  const isWithin = (layer.getLatLngs()[0] || []).every((point) =>
    s.allowedCityBounds.value.contains(point),
  )
  if (isWithin) return ''
  const city = toValue(s.farm)?.city || 'the registered farm city'
  return `Plots for this farm must be drawn within ${city}. The drawn shape is outside the allowed city area.`
}
async function handleDraw(s, event) {
  if (event.layerType !== 'polygon') return
  const layer = event.layer
  const error = boundaryError(s, layer)
  if (error) {
    s.map?.removeLayer(layer)
    s.onPlotError(error)
    return
  }
  const geojson = layer.toGeoJSON()
  s.isCheckingZone.value = true
  try {
    const conflict = await queryOsmForPolygon(geojson)
    if (!s.map) return
    if (conflict) {
      s.map.removeLayer(layer)
      s.onPlotError(
        `Cannot plot here — your area overlaps a ${conflict.type} ("${conflict.name}"). Please draw only on vacant, agricultural land.`,
      )
      return
    }
    s.onPlotDrawn({ layer, coordinates: geojson.geometry.coordinates[0] })
  } finally {
    s.isCheckingZone.value = false
  }
}
function plotPopup(feature, layer) {
  const container = document.createElement('div')
  container.className = 'text-center'
  const name = document.createElement('b')
  name.textContent = String(feature.properties?.name || 'Plot')
  const area = document.createElement('p')
  area.className = 'text-stone-600'
  const value = feature.properties?.calculated_area
    ? `${Number(feature.properties.calculated_area).toFixed(2)} ha`
    : 'Unknown area'
  area.textContent = `Area: ${value}`
  const link = document.createElement('a')
  link.href = `/dashboard/plots/${encodeURIComponent(String(feature.properties?.id ?? ''))}/recommendations`
  link.className =
    'inline-flex min-h-11 items-center mt-3 px-4 bg-moss-600 !text-white rounded-xl text-sm font-semibold hover:bg-moss-700 transition-colors no-underline'
  link.textContent = 'View Recommendations'
  container.append(name, area, link)
  layer.bindPopup(container)
}
function fitPlotBounds(s) {
  try {
    const bounds = s.plotsLayer.getBounds()
    if (bounds.isValid())
      s.map.fitBounds(bounds, {
        padding: MAP_CONSTANTS.PADDING_LARGE,
        maxZoom: MAP_CONSTANTS.MAX_ZOOM_FIT_PLOTS,
      })
  } catch {
    if (s.allowedCityBounds.value)
      s.map.fitBounds(s.allowedCityBounds.value, { padding: MAP_CONSTANTS.PADDING_SMALL })
  }
}
function loadExistingPlots(s) {
  if (!s.map || !s.drawnItems) return
  s.drawnItems.clearLayers()
  if (s.plotsLayer && s.map.hasLayer(s.plotsLayer)) s.map.removeLayer(s.plotsLayer)
  s.plotsLayer = null
  const plots = toValue(s.existingPlots)
  if (plots?.features?.length) {
    s.plotsLayer = L.geoJSON(plots, { style: PLOT_STYLE, onEachFeature: plotPopup }).addTo(s.map)
    fitPlotBounds(s)
  } else if (s.allowedCityBounds.value)
    s.map.fitBounds(s.allowedCityBounds.value, { padding: MAP_CONSTANTS.PADDING_SMALL })
}
function zoomToPlot(s, plotId) {
  if (!s.plotsLayer || !s.map) return
  let target = null
  s.plotsLayer.eachLayer((layer) => {
    if (layer.feature?.properties?.id === plotId) target = layer
  })
  if (!target) return
  s.map.fitBounds(target.getBounds(), {
    padding: MAP_CONSTANTS.PADDING_LARGE,
    maxZoom: MAP_CONSTANTS.MAX_ZOOM_ZOOM_TO_PLOT,
  })
  target.openPopup()
}
function observeMapSize(s) {
  if (typeof ResizeObserver === 'undefined') return
  s.resizeObserver = new ResizeObserver(() => s.map?.invalidateSize())
  s.resizeObserver.observe(toValue(s.mapContainer))
}
function floodOverlayStyle(feature) {
  return { ...floodZoneStyle(feature?.properties?.hazard_class), className: FLOOD_PATH_CLASS }
}
function setFloodZones(s, collection) {
  if (!s.map) return
  if (s.floodZonesLayer && s.map.hasLayer(s.floodZonesLayer)) s.map.removeLayer(s.floodZonesLayer)
  s.floodZonesLayer = null
  if (!collection?.features?.length) return
  // interactive:false must be a layer option: Path.setStyle ignores it, and an
  // interactive overlay swallows the clicks leaflet-draw needs to place vertices.
  s.floodZonesLayer = L.geoJSON(collection, {
    style: floodOverlayStyle,
    interactive: false,
  }).addTo(s.map)
}
function getMapBounds(s) {
  if (!s.map) return null
  const bounds = s.map.getBounds()
  return [bounds.getWest(), bounds.getSouth(), bounds.getEast(), bounds.getNorth()]
}
function scheduleBoundsChange(s) {
  if (s.boundsTimer) clearTimeout(s.boundsTimer)
  s.boundsTimer = setTimeout(() => {
    s.boundsTimer = null
    const bbox = getMapBounds(s)
    if (bbox) s.onBoundsChange?.(bbox)
  }, FLOOD_BOUNDS_DEBOUNCE_MS)
}
function initializeMap(s) {
  s.map = L.map(toValue(s.mapContainer)).setView(
    GEO_CONSTANTS.FALLBACK_CENTER,
    GEO_CONSTANTS.FALLBACK_ZOOM,
  )
  L.tileLayer(GEO_CONSTANTS.TILE_URL, {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(s.map)
  s.drawnItems = new L.FeatureGroup()
  s.map.addLayer(s.drawnItems)
  s.drawControl = new L.Control.Draw({
    edit: { featureGroup: s.drawnItems, edit: false, remove: false },
    draw: DRAW_OPTIONS,
  })
  s.map.addControl(s.drawControl)
  s.map.addControl(
    new GeoSearchControl({ provider: new OpenStreetMapProvider(), ...SEARCH_OPTIONS }),
  )
  s.map.on(L.Draw.Event.CREATED, (event) => handleDraw(s, event))
  s.map.on('moveend', () => scheduleBoundsChange(s))
  observeMapSize(s)
  focusFarmCity(s)
  fetchRestrictedZones(s)
  loadExistingPlots(s)
}
function disposeMap(s) {
  s.cityRequestId += 1
  s.resizeObserver?.disconnect()
  if (s.boundsTimer) clearTimeout(s.boundsTimer)
  s.boundsTimer = null
  s.map?.remove()
  s.map = null
  s.drawnItems = null
  s.plotsLayer = null
  s.floodZonesLayer = null
}
function createMapState(options) {
  return {
    ...options,
    api: useApi(),
    map: null,
    drawnItems: null,
    drawControl: null,
    boundaryLayer: null,
    plotsLayer: null,
    restrictedZonesLayer: null,
    floodZonesLayer: null,
    boundsTimer: null,
    resizeObserver: null,
    cityRequestId: 0,
    pendingGeocodes: 0,
    allowedCityBounds: ref(null),
    isGeocodingCity: ref(false),
    isCheckingZone: ref(false),
  }
}
export function usePlotMap(options) {
  const s = createMapState(options)
  onMounted(() => initializeMap(s))
  watch(
    () => toValue(s.farm),
    () => focusFarmCity(s),
    { deep: true },
  )
  watch(
    () => toValue(s.existingPlots),
    () => loadExistingPlots(s),
    { deep: true },
  )
  onBeforeUnmount(() => disposeMap(s))
  return {
    isGeocodingCity: s.isGeocodingCity,
    isCheckingZone: s.isCheckingZone,
    allowedCityBounds: s.allowedCityBounds,
    zoomToPlot: (id) => zoomToPlot(s, id),
    setFloodZones: (collection) => setFloodZones(s, collection),
    getMapBounds: () => getMapBounds(s),
  }
}
