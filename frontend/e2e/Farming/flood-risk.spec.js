import { test, expect, mockSession } from '../fixtures/session'
import { FARMS } from '../fixtures/data'
import { E2E } from '../constants'

const DRAW_CLICK_GAP_MS = 600
const MOCK_TILE =
  '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"><rect width="256" height="256" fill="#FAFAF7"/><path d="M0 128H256M128 0V256" stroke="#CDD3C5"/></svg>'
const POLYGON = [
  [
    [121.065, 14.603],
    [121.069, 14.603],
    [121.069, 14.607],
    [121.065, 14.603],
  ],
]
const PLOT_PROPERTIES = {
  id: 4,
  name: 'East bed',
  farm_id: 3,
  farm_name: 'North Field',
  soil_type: 'clay',
  calculated_area: 0.25,
  flood_risk_level: 'high',
  flood_within_coverage: true,
  flood_risk_assessed_at: '2026-10-07T00:00:00Z',
}
const PLOTS = {
  type: 'FeatureCollection',
  features: [
    {
      type: 'Feature',
      geometry: { type: 'Polygon', coordinates: POLYGON },
      properties: PLOT_PROPERTIES,
    },
  ],
}
const ZONES = {
  type: 'FeatureCollection',
  features: [
    {
      type: 'Feature',
      geometry: {
        type: 'Polygon',
        coordinates: [
          [
            [121.06, 14.6],
            [121.06, 14.61],
            [121.07, 14.61],
            [121.07, 14.6],
            [121.06, 14.6],
          ],
        ],
      },
      properties: { id: 1, hazard_class: 'high' },
    },
    {
      type: 'Feature',
      geometry: {
        type: 'Polygon',
        coordinates: [
          [
            [121.07, 14.6],
            [121.07, 14.61],
            [121.08, 14.61],
            [121.08, 14.6],
            [121.07, 14.6],
          ],
        ],
      },
      properties: { id: 2, hazard_class: 'low' },
    },
  ],
}
const HIGH_RISK = {
  level: 'high',
  label: 'High',
  legend_token: 'high',
  advice: ['Build drainage canals before planting season.', 'Use elevated beds.'],
  within_coverage: true,
  assessed_at: '2026-10-07T00:00:00Z',
}

async function floodFixtures(page, hooks) {
  await mockSession(page)
  await page.route('**/api/v1/farms', (route) => route.fulfill({ json: { data: FARMS } }))
  await page.route('**/api/v1/farms/3/plots', (route) =>
    route.fulfill({ json: hooks.plots ?? PLOTS }),
  )
  await page.route('**/api/v1/plots', (route) =>
    route.fulfill({ json: { data: [PLOT_PROPERTIES] } }),
  )
  await page.route('**/api/v1/restricted-zones', (route) =>
    route.fulfill({ json: { type: 'FeatureCollection', features: [] } }),
  )
  await page.route('**/api/v1/market/prices/guide**', (route) =>
    route.fulfill({ json: { available: false, data: {} } }),
  )
  await page.route('**/api/v1/flood-hazard-zones**', (route) => {
    hooks.zonesRequested = true
    return route.fulfill({ json: ZONES })
  })
  await page.route('https://photon.komoot.io/**', (route) =>
    route.fulfill({
      json: {
        features: [{ properties: { extent: [121.04, 14.65, 121.11, 14.58], osm_value: 'city' } }],
      },
    }),
  )
  for (const host of ['overpass-api.de', 'overpass.kumi.systems', 'maps.mail.ru']) {
    await page.route(`https://${host}/**`, (route) => route.fulfill({ json: { elements: [] } }))
  }
  await page.route(/tile\.openstreetmap\.org/, (route) =>
    route.fulfill({ contentType: 'image/svg+xml', body: MOCK_TILE }),
  )
}

async function noOverflow(page) {
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
}

async function drawTriangle(page) {
  const map = page.locator('.leaflet-container').first()
  await map.waitFor()
  await page.locator('.leaflet-draw-draw-polygon').click()
  const box = await map.boundingBox()
  const points = [
    { x: box.width * 0.4, y: box.height * 0.4 },
    { x: box.width * 0.6, y: box.height * 0.4 },
    { x: box.width * 0.5, y: box.height * 0.6 },
  ]
  const markers = page.locator('.leaflet-marker-pane .leaflet-marker-icon')
  for (const [index, point] of points.entries()) {
    // Pace vertices: back-to-back taps/clicks are read as double-tap zoom.
    if (index > 0) await page.waitForTimeout(DRAW_CLICK_GAP_MS)
    const before = await markers.count()
    await map.click({ position: point })
    await expect.poll(() => markers.count(), { timeout: 3000 }).toBeGreaterThan(before)
  }
  await page.getByRole('link', { name: 'Finish', exact: true }).click()
}

test('flood overlay, legend, and draw-time warning guide the farmer', async ({
  page,
}, testInfo) => {
  // Empty plots list: an existing interactive plot polygon would swallow the
  // draw clicks that land on it; existing plots are covered by the next test.
  const hooks = {
    zonesRequested: false,
    previewBody: null,
    plots: { type: 'FeatureCollection', features: [] },
  }
  // The flood overlay renders with interactive:false (pointer-transparent), so the
  // triangle below is drawn over visible hazard polygons. Reduced-motion
  // styling is pinned by the legend unit spec instead of emulateMedia here.
  await floodFixtures(page, hooks)
  await page.route('**/api/v1/plots/flood-risk/preview', (route) => {
    hooks.previewBody = route.request().postDataJSON()
    return route.fulfill({ json: { data: HIGH_RISK } })
  })

  await page.goto('/dashboard/farms/3/plots')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Plot planner')
  await expect(page.getByTestId('legend-step')).toHaveCount(4)
  await expect.poll(() => hooks.zonesRequested).toBe(true)
  await expect(page.locator('.flood-zone-path')).toHaveCount(2)

  const toggle = page.getByRole('button', { name: 'Toggle flood overlay', exact: true })
  await toggle.focus()
  await page.keyboard.press('Enter')
  await expect(page.locator('.flood-zone-path')).toHaveCount(0)
  await page.keyboard.press('Enter')
  await expect(page.locator('.flood-zone-path')).toHaveCount(2)

  await drawTriangle(page)
  await expect(page.getByTestId('flood-warning')).toContainText('High flood risk')
  expect(hooks.previewBody.coordinates.length).toBeGreaterThanOrEqual(3)
  await expect(page.getByRole('button', { name: 'Save Plot', exact: true })).toBeEnabled()

  await page.getByRole('button', { name: 'Proceed anyway', exact: true }).click()
  await expect(page.getByTestId('flood-warning')).not.toBeVisible()
  await expect(page.getByRole('button', { name: 'Save Plot', exact: true })).toBeVisible()

  if (testInfo.project.name === 'chromium') await page.setViewportSize(E2E.NARROW)
  await noOverflow(page)
  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({ path: testInfo.outputPath('flood-planner.png'), animations: 'disabled' })
})

test('existing plot rows show risk badges with details and re-check', async ({ page }) => {
  const hooks = { zonesRequested: false, plots: PLOTS }
  await floodFixtures(page, hooks)
  await page.route('**/api/v1/plots/4/flood-risk', (route) =>
    route.fulfill({ json: { data: HIGH_RISK } }),
  )
  await page.route('**/api/v1/plots/4/flood-risk/refresh', (route) =>
    route.fulfill({
      json: {
        data: {
          ...HIGH_RISK,
          level: 'medium',
          label: 'Medium',
          legend_token: 'medium',
          advice: ['Clear field drains before the wet season.'],
        },
      },
    }),
  )

  await page.goto('/dashboard/farms/3/plots')
  const badge = page.getByTestId('flood-badge').first()
  await expect(badge).toHaveText('High')

  await page.getByRole('button', { name: 'Details', exact: true }).first().click()
  await expect(page.getByText('Build drainage canals before planting season.')).toBeVisible()

  hooks.plots = {
    type: 'FeatureCollection',
    features: [
      {
        type: 'Feature',
        geometry: { type: 'Polygon', coordinates: POLYGON },
        properties: { ...PLOT_PROPERTIES, flood_risk_level: 'medium' },
      },
    ],
  }
  await page.getByRole('button', { name: 'Re-check', exact: true }).click()
  await expect(page.getByTestId('flood-badge').first()).toHaveText('Medium')
  await noOverflow(page)
})
