import { test, expect, mockSession } from '../fixtures/session'
import { farmingFixtures, RESULT } from '../fixtures/farming'
import { E2E } from '../constants'
const OK = 200
const UNAVAILABLE = 503
const QUEUED = 202
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
  const drawButton = page.locator('.leaflet-draw-draw-polygon')
  await expect(drawButton).toBeVisible()
  const spritePosition = await drawButton.evaluate((element) =>
    getComputedStyle(element).backgroundPosition.split(',')[0].trim(),
  )
  expect(spritePosition).toBe('-22px 50%')
  const searchForm = page.locator('.leaflet-geosearch-bar form')
  await expect(page.locator('.leaflet-control-geosearch button.reset')).toBeVisible()
  const searchGeometry = await searchForm.evaluate((form) => {
    const input = form.querySelector('input').getBoundingClientRect()
    const reset = form.parentElement.querySelector('button.reset').getBoundingClientRect()
    return {
      radius: getComputedStyle(form).borderRadius,
      overflow: getComputedStyle(form).overflow,
      inputCenterY: input.y + input.height / 2,
      resetCenterY: reset.y + reset.height / 2,
    }
  })
  expect(searchGeometry.radius).toBe('12px')
  expect(searchGeometry.overflow).toBe('hidden')
  expect(Math.abs(searchGeometry.inputCenterY - searchGeometry.resetCenterY)).toBeLessThanOrEqual(1)
  await page.route('https://nominatim.openstreetmap.org/**', (route) =>
    route.fulfill({
      json: [
        {
          place_id: 1,
          display_name: 'Quezon City, Metro Manila, Philippines',
          lat: '14.65',
          lon: '121.07',
          boundingbox: ['14.58', '14.72', '121.0', '121.11'],
        },
      ],
    }),
  )
  await searchForm.locator('input').pressSequentially('Quezon', { delay: 60 })
  await expect(searchForm.locator('.results.active')).toBeVisible()
  const resultsBorderHack = await searchForm.evaluate(
    (form) => getComputedStyle(form.querySelector('.results'), '::after').content,
  )
  expect(resultsBorderHack).toBe('none')
  const focusStyles = await searchForm.evaluate((form) => {
    const input = form.querySelector('input')
    return {
      inputShadow: getComputedStyle(input).boxShadow,
      formShadow: getComputedStyle(form).boxShadow,
      formFocusWithin: form.matches(':focus-within'),
    }
  })
  expect(focusStyles.inputShadow).toBe('none')
  expect(focusStyles.formFocusWithin).toBe(true)
  expect(focusStyles.formShadow).not.toBe('none')
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
