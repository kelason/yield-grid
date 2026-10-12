import { test, expect, mockSession } from '../fixtures/session'
import { farmingFixtures, PLOT, RESULT } from '../fixtures/farming'

const CAPTURE_VIEWPORT = { width: 390, height: 1100 }
const DRAW_POINTS = [
  { x: 0.3, y: 0.3 },
  { x: 0.65, y: 0.3 },
  { x: 0.5, y: 0.6 },
]
const LISTING = {
  id: 3,
  type: 'listing',
  title: 'Rice harvest',
  crop_name: 'Rice',
  quantity_kg: 100,
  price_per_kg: 50,
  total_price: 5000,
  currency: 'PHP',
  estimated_harvest_date: null,
  is_harvest_available: true,
  status: 'available',
  farmer: { id: 1, name: 'Juan' },
}

test.beforeEach(async ({ page, isMobile }) => {
  test.skip(isMobile, 'Tutorial source images use one fixed, readable viewport.')
  await page.setViewportSize(CAPTURE_VIEWPORT)
})

async function captureExample(page, target, testInfo, name) {
  await page.evaluate(async () => {
    await document.fonts.ready
    await Promise.all(Array.from(document.images, (image) => image.decode()))
    window.scrollTo(0, 0)
    document.getElementById('main-content')?.scrollTo(0, 0)
  })
  expect(
    await page.evaluate(
      () => document.fonts.check('400 16px Inter') && document.fonts.check('700 32px Lora'),
    ),
  ).toBe(true)
  await target.scrollIntoViewIfNeeded()
  await target.screenshot({ path: testInfo.outputPath(name), animations: 'disabled' })
}

test('plot setup example draws a real unsaved polygon', async ({ page }, testInfo) => {
  await farmingFixtures(page)
  await page.route(/overpass|maps\.mail\.ru/, (route) => route.fulfill({ json: { elements: [] } }))
  await page.goto('/dashboard/farms/3/plots')
  await expect(page.getByTestId('city-locating-overlay')).not.toBeVisible()
  await expect(page.locator('.leaflet-tile-loaded').first()).toBeVisible()
  await page.locator('.leaflet-draw-draw-polygon').click()
  const map = await page.locator('.leaflet-container').boundingBox()
  for (const [index, point] of DRAW_POINTS.entries()) {
    await expect
      .poll(async () => {
        await page.mouse.click(map.x + map.width * point.x, map.y + map.height * point.y)
        return page.locator('.leaflet-marker-icon.leaflet-div-icon').count()
      })
      .toBe(index + 1)
  }
  await page.getByRole('link', { name: 'Finish', exact: true }).click()
  const heading = page.getByRole('heading', { name: 'Save New Plot', exact: true })
  await expect(heading).toBeVisible()
  await page.locator('#plot-name').fill('West vegetable bed')
  await page.locator('#plot-soil').selectOption('clay')
  await captureExample(page, heading.locator('..').locator('..'), testInfo, 'plot-setup-en.png')
})

test('recommendation review example shows advisory output without publishing', async ({
  page,
}, testInfo) => {
  await farmingFixtures(page)
  await page.route('**/api/v1/plots/4/recommendations', (route) =>
    route.fulfill({
      json: {
        data: [RESULT],
        meta: {
          plot_id: PLOT.id,
          plot_name: PLOT.name,
          calculated_area: PLOT.calculated_area,
          soil_type: PLOT.soil_type,
          city: 'Quezon City',
          state: 'Metro Manila',
          country: 'Philippines',
        },
      },
    }),
  )
  await page.goto('/dashboard/plots/4/recommendations')
  await expect(page.getByRole('heading', { name: 'Pechay', exact: true })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Accept & Contract', exact: true })).toBeVisible()
  await captureExample(
    page,
    page.locator('.recommendation-card'),
    testInfo,
    'recommendations-en.png',
  )
})

test('manual listing example fills a draft with no farm source', async ({ page }, testInfo) => {
  await mockSession(page)
  await page.route('**/api/v1/farms', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/plots', (route) => route.fulfill({ json: { data: [] } }))
  await page.goto('/dashboard/contracts/new-listing')
  await page.locator('#title').fill('Rice harvest')
  await page
    .locator('#listing-description')
    .fill('Available rice harvest. Coordinate details before confirming a purchase.')
  await page.locator('#quantity_kg').fill('100')
  await page.locator('#price_per_kg').fill('50')
  await page.locator('#harvest_available').check()
  await expect(page.locator('#listing-farm')).toHaveValue('')
  await captureExample(
    page,
    page.locator('main form > div').nth(1),
    testInfo,
    'manual-listing-en.png',
  )
})

async function checkoutFixtures(page) {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/user/addresses', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/market/contracts?**', (route) =>
    route.fulfill({
      json: {
        data: [LISTING],
        meta: { current_page: 1, last_page: 1, total: 1, per_page: 12 },
      },
    }),
  )
  await page.route('**/api/v1/market/items/listing/3', (route) =>
    route.fulfill({ json: { data: LISTING } }),
  )
  await page.route('**/api/v1/market/prices/guide**', (route) =>
    route.fulfill({ json: { available: false, data: {} } }),
  )
}

test('buyer checkout example reviews cash without initiating payment', async ({
  page,
}, testInfo) => {
  await checkoutFixtures(page)
  await page.goto('/dashboard/buyer/marketplace')
  await page.getByRole('button', { name: 'View Details', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Contract Details', exact: true })
  await dialog.getByRole('spinbutton', { name: 'Purchase Quantity (kg)' }).fill('25')
  await dialog.getByRole('radio', { name: 'Cash (Off-Site)' }).check()
  await expect(dialog.getByText('₱1,250', { exact: false }).first()).toBeVisible()
  await captureExample(page, dialog, testInfo, 'buyer-checkout-en.png')
})
