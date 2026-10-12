import { mockSession } from './session'
import { FARMS } from './data'

const POLYGON = [
  [
    [121.065, 14.603],
    [121.069, 14.603],
    [121.069, 14.607],
    [121.065, 14.603],
  ],
]
export const PLOT = {
  id: 4,
  name: 'East bed',
  farm_id: 3,
  farm_name: 'North Field',
  soil_type: 'clay',
  calculated_area: 0.25,
}
export const GEOJSON = {
  type: 'FeatureCollection',
  features: [
    { type: 'Feature', geometry: { type: 'Polygon', coordinates: POLYGON }, properties: PLOT },
  ],
}
export const TAXONOMY = {
  types: {
    vegetable: {
      label: 'Vegetables',
      subtypes: { leafy: { label: 'Leafy greens', crop_count: 5 } },
    },
  },
  crops: [],
  irrigation_levels: [{ value: 'limited', label: 'Limited' }],
  goals: [{ value: 'quick_cash', label: 'Quick cash' }],
}
export const RESULT = {
  id: 10,
  crop_name: 'Pechay',
  confidence_score: 85,
  projected_yield: '500 kg',
  reasoning: 'Suitable for the recorded soil and irrigation conditions.',
  produce_type: 'vegetable',
  subtype: 'leafy',
  status: 'pending',
}
export const MOCK_TILE =
  '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"><rect width="256" height="256" fill="#FAFAF7"/><path d="M0 128H256M128 0V256" stroke="#CDD3C5"/></svg>'
export async function farmingFixtures(page) {
  await mockSession(page)
  await page.route('**/api/v1/farms', (route) => route.fulfill({ json: { data: FARMS } }))
  await page.route('**/api/v1/farms/3/plots', (route) => route.fulfill({ json: GEOJSON }))
  await page.route('**/api/v1/plots', (route) => route.fulfill({ json: { data: [PLOT] } }))
  await page.route('**/api/v1/restricted-zones', (route) =>
    route.fulfill({ json: { type: 'FeatureCollection', features: [] } }),
  )
  await page.route('**/api/v1/crop-taxonomy', (route) =>
    route.fulfill({ json: { data: TAXONOMY } }),
  )
  await page.route('**/api/v1/market/prices/guide**', (route) =>
    route.fulfill({ json: { available: false, data: {} } }),
  )
  await geographyFixtures(page)
}

async function geographyFixtures(page) {
  await page.route('https://photon.komoot.io/**', (route) =>
    route.fulfill({
      json: {
        features: [{ properties: { extent: [121.04, 14.65, 121.11, 14.58], osm_value: 'city' } }],
      },
    }),
  )
  await page.route(/tile\.openstreetmap\.org/, (route) =>
    route.fulfill({ contentType: 'image/svg+xml', body: MOCK_TILE }),
  )
}
