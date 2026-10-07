import { test, expect, mockSession } from '../fixtures/session'
import { FARMS } from '../fixtures/data'
import { E2E } from '../constants'
const OK = 200
const UNAVAILABLE = 503
const QUEUED = 202
const POLYGON = [
  [
    [121.065, 14.603],
    [121.069, 14.603],
    [121.069, 14.607],
    [121.065, 14.603],
  ],
]
const PLOT = {
  id: 4,
  name: 'East bed',
  farm_id: 3,
  farm_name: 'North Field',
  soil_type: 'clay',
  calculated_area: 0.25,
}
const GEOJSON = {
  type: 'FeatureCollection',
  features: [
    { type: 'Feature', geometry: { type: 'Polygon', coordinates: POLYGON }, properties: PLOT },
  ],
}
const TAXONOMY = {
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
const RESULT = {
  id: 10,
  crop_name: 'Pechay',
  confidence_score: 85,
  projected_yield: '500 kg',
  reasoning: 'Suitable for the recorded soil and irrigation conditions.',
  produce_type: 'vegetable',
  subtype: 'leafy',
  status: 'pending',
}
const MOCK_TILE =
  '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"><rect width="256" height="256" fill="#FAFAF7"/><path d="M0 128H256M128 0V256" stroke="#CDD3C5"/></svg>'
async function farmingFixtures(page) {
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
async function noOverflow(page) {
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
}
async function mockPlotSocket(page) {
  let plotSocket
  await page.route('**/api/v1/broadcasting/auth', (route) =>
    route.fulfill({ json: { auth: 'synthetic-auth-signature' } }),
  )
  await page.routeWebSocket(/\/app\//, (socket) => {
    socket.send(
      JSON.stringify({
        event: 'pusher:connection_established',
        data: JSON.stringify({ socket_id: '1.2', activity_timeout: 120 }),
      }),
    )
    socket.onMessage((message) => {
      const packet = JSON.parse(String(message))
      if (packet.event !== 'pusher:subscribe') return
      const channel = packet.data.channel
      if (channel === 'private-plot.4') plotSocket = socket
      socket.send(
        JSON.stringify({ event: 'pusher_internal:subscription_succeeded', channel, data: '{}' }),
      )
    })
  })
  return {
    ready: () => Boolean(plotSocket),
    complete: () =>
      plotSocket.send(
        JSON.stringify({ event: 'AnalysisCompleted', channel: 'private-plot.4', data: '{}' }),
      ),
  }
}

test('farm and plot selection expose keyboard-operable map controls without overflow', async ({
  page,
}, testInfo) => {
  await farmingFixtures(page)
  await page.route('**/api/v1/plots/4/recommendations', (route) =>
    route.fulfill({ json: { data: [], meta: { plot_name: 'East bed' } } }),
  )
  await page.goto('/dashboard/farms')
  await page.getByRole('button', { name: 'Manage plots for North Field', exact: true }).click()
  await page.waitForURL(/\/dashboard\/farms\/3\/plots$/)
  await expect(page).toHaveURL(/\/dashboard\/farms\/3\/plots$/)
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Plot planner')
  const zoom = page.getByRole('button', { name: 'Zoom to plot East bed', exact: true })
  await zoom.focus()
  await page.keyboard.press('Enter')
  await expect(page.getByText('Area: 0.25 ha')).toBeVisible()
  await noOverflow(page)
  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({ path: testInfo.outputPath('plot-planner.png'), animations: 'disabled' })
  await page.getByRole('link', { name: 'Recommendations', exact: true }).last().click()
  await expect(page).toHaveURL(/\/dashboard\/plots\/4\/recommendations$/)
})

test('analysis failure can be retried and queued results arrive through the existing plot event', async ({
  page,
}, testInfo) => {
  await farmingFixtures(page)
  const socket = await mockPlotSocket(page)
  let results = []
  let runs = 0
  await page.route('**/api/v1/plots/4/recommendations', (route) =>
    route.fulfill({
      json: {
        data: results,
        meta: {
          plot_id: 4,
          plot_name: 'East bed',
          calculated_area: 0.25,
          soil_type: 'clay',
          city: 'Quezon City',
          state: 'Metro Manila',
          country: 'Philippines',
        },
      },
    }),
  )
  await page.route('**/api/v1/plots/4/analyze', (route) => {
    runs += 1
    expect(route.request().method()).toBe('POST')
    expect(route.request().postDataJSON()).toEqual({
      produce_types: ['vegetable'],
      subtypes: [],
      irrigation: null,
      goal: null,
    })
    return route.fulfill({
      status: runs === 1 ? UNAVAILABLE : QUEUED,
      json: runs === 1 ? { message: 'Analysis unavailable. Please retry.' } : { message: 'Queued' },
    })
  })
  await page.goto('/dashboard/plots/4/recommendations')
  await page.getByRole('button', { name: 'Vegetables', exact: true }).click()
  await page.getByRole('button', { name: 'Run AI Analysis', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Run AI analysis?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(runs).toBe(0)
  await page.getByRole('button', { name: 'Run AI Analysis', exact: true }).click()
  await dialog.getByRole('button', { name: 'Run analysis', exact: true }).click()
  await expect(page.getByRole('alert').first()).toContainText('Analysis unavailable. Please retry.')
  await page.getByRole('button', { name: 'Run AI Analysis', exact: true }).click()
  await dialog.getByRole('button', { name: 'Run analysis', exact: true }).click()
  await expect(page.getByRole('status').filter({ hasText: 'Analyzing crops' })).toBeVisible()
  await expect(page.locator('#main-content')).toBeFocused()
  results = [RESULT]
  await expect.poll(socket.ready).toBe(true)
  socket.complete()
  await expect(page.getByRole('heading', { name: 'Pechay', exact: true })).toBeVisible()
  expect(runs).toBe(2)
  await noOverflow(page)
  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({
    path: testInfo.outputPath('analysis-result.png'),
    animations: 'disabled',
  })
})

test('recommendation fetch failure shows Retry rather than an empty result', async ({ page }) => {
  await farmingFixtures(page)
  let failed = true
  await page.route('**/api/v1/plots/4/recommendations', (route) =>
    route.fulfill({
      status: failed ? UNAVAILABLE : OK,
      json: failed
        ? { message: 'Recommendations unavailable.' }
        : { data: [RESULT], meta: { plot_name: 'East bed' } },
    }),
  )
  await page.goto('/dashboard/plots/4/recommendations')
  await expect(page.getByRole('alert').first()).toContainText('Recommendations unavailable.')
  await expect(page.getByText('No recommendations yet', { exact: true })).not.toBeVisible()
  failed = false
  await page.getByRole('button', { name: 'Retry recommendations', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Pechay', exact: true })).toBeVisible()
})

test('credit charts preserve values and readable narrow-screen controls', async ({
  page,
}, testInfo) => {
  await mockSession(page)
  await page.emulateMedia({ reducedMotion: 'reduce' })
  await page.route('**/api/v1/farmer/credit-score', (route) =>
    route.fulfill({
      json: {
        data: {
          overall_score: 80,
          tier: 'good',
          tier_label: 'Good',
          dimension_scores: {
            plot_activity: 80,
            recommendation_adherence: 65,
            contract_fulfillment: 95,
            offer_reliability: 75,
            transaction_volume: 60,
            platform_tenure: 50,
          },
          report_status: 'none',
          improvement_tips: [],
        },
      },
    }),
  )
  await page.route('**/api/v1/farmer/credit-score/history', (route) =>
    route.fulfill({
      json: {
        data: [
          { snapshot_date: '2026-10-07', overall_score: 80 },
          { snapshot_date: '2026-10-06', overall_score: 75 },
        ],
      },
    }),
  )
  await page.goto('/dashboard/credit-score')
  await expect(page.getByTestId('gauge-score')).toHaveText('80')
  const about = page.getByRole('button', { name: 'About Plot Activity', exact: true })
  await about.focus()
  await page.keyboard.press('Enter')
  await expect(page.getByText('Grows with more active plots', { exact: false })).toBeVisible()
  await expect(page.getByRole('progressbar', { name: 'Plot Activity score' })).toHaveAttribute(
    'value',
    '80',
  )
  if (testInfo.project.name === 'chromium') await page.setViewportSize(E2E.NARROW)
  await noOverflow(page)
  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({
    path: testInfo.outputPath('trust-score.png'),
    fullPage: true,
    animations: 'disabled',
  })
})
