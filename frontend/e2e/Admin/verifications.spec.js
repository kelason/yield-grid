import { test, expect, mockSession } from '../fixtures/session'
import { mockAdminSession, paginated, expectNoOverflow } from '../fixtures/admin'
import { FARMS } from '../fixtures/data'

const FARMER = { id: 1, name: 'Mang Juan', email: 'farmer@example.test' }
const POLYGON = [
  [
    [121.065, 14.603],
    [121.069, 14.603],
    [121.069, 14.607],
    [121.065, 14.603],
  ],
]
const MOCK_TILE =
  '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"><rect width="256" height="256" fill="#FAFAF7"/><path d="M0 128H256M128 0V256" stroke="#CDD3C5"/></svg>'

const FARM_DETAIL = {
  id: 3,
  type: 'farm',
  name: 'North Field',
  verification_status: 'pending',
  verification_method: null,
  verification_note: null,
  verified_by: null,
  verified_at: null,
  farmer: FARMER,
  address: null,
  city: 'Quezon City',
  state: 'Metro Manila',
  country: 'Philippines',
  zip: null,
  total_area: '1.50',
}

const PLOT_DETAIL = {
  id: 4,
  type: 'plot',
  name: 'East bed',
  verification_status: 'pending',
  verification_method: null,
  verification_note: null,
  verified_by: null,
  verified_at: null,
  farmer: FARMER,
  farm: { id: 3, name: 'North Field' },
  soil_type: 'clay',
  calculated_area: 0.25,
  geojson: { geometry: { type: 'Polygon', coordinates: POLYGON } },
}

const LISTING = {
  id: 3,
  type: 'listing',
  title: 'Rice harvest',
  crop_name: 'Rice',
  quantity_kg: 100,
  price_per_kg: 50,
  total_price: 5000,
  currency: 'PHP',
  estimated_harvest_date: '2026-11-01',
  is_harvest_available: true,
  status: 'available',
  farmer: { name: 'Juan' },
}

async function mockTiles(page) {
  await page.route(/tile\.openstreetmap\.org/, (route) =>
    route.fulfill({ contentType: 'image/svg+xml', body: MOCK_TILE }),
  )
}

async function mockQueue(page, farms, plots) {
  await page.route('**/api/v1/admin/verifications?**', (route) =>
    route.fulfill({
      json: paginated(route.request().url().includes('scope=plots') ? plots : farms),
    }),
  )
}

test('admin verifies a pending farm and the decision posts once', async ({ page }) => {
  await mockAdminSession(page)
  await mockQueue(page, [FARM_DETAIL], [])
  await page.route('**/api/v1/admin/verifications/farms/3', (route) =>
    route.fulfill({ json: { data: FARM_DETAIL } }),
  )
  const requests = []
  await page.route('**/api/v1/admin/verifications/farms/3/verify', (route) => {
    requests.push(route.request().postDataJSON())
    return route.fulfill({
      json: {
        data: {
          ...FARM_DETAIL,
          verification_status: 'verified',
          verification_method: 'field_visit',
          verification_note: 'Boundary matches title records.',
        },
      },
    })
  })

  await page.goto('/admin/verifications')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Farm verifications')
  await page.getByTestId('verification-row').click()
  await expect(page.getByTestId('verification-detail')).toBeVisible()
  await page.getByLabel('Verification method (required when verifying)').selectOption('field_visit')
  await page.getByLabel('Decision note').fill('Boundary matches title records.')
  await page.getByTestId('decision-verify').click()

  const dialog = page.getByRole('dialog', { name: 'Verify record', exact: true })
  await expect(dialog).toContainText('Field visit')
  await expect(dialog.getByRole('button', { name: 'Cancel', exact: true })).toBeFocused()
  await dialog.getByRole('button', { name: 'Verify', exact: true }).focus()
  await page.keyboard.press('Enter')

  await expect(dialog).not.toBeVisible()
  expect(requests).toEqual([{ method: 'field_visit', note: 'Boundary matches title records.' }])
  const detail = page.getByTestId('verification-detail')
  await expect(detail.getByTestId('verified-badge')).toBeVisible()
  await expect(detail).toContainText('Verified')
  await expect(page.getByTestId('verification-row')).toContainText('Verified')
  await expectNoOverflow(page)
})

test('admin rejects a plot with a farmer-visible reason', async ({ page }) => {
  await mockAdminSession(page)
  await mockTiles(page)
  await mockQueue(page, [], [PLOT_DETAIL])
  await page.route('**/api/v1/admin/verifications/plots/4', (route) =>
    route.fulfill({ json: { data: PLOT_DETAIL } }),
  )
  const requests = []
  await page.route('**/api/v1/admin/verifications/plots/4/reject', (route) => {
    requests.push(route.request().postDataJSON())
    return route.fulfill({
      json: {
        data: {
          ...PLOT_DETAIL,
          verification_status: 'rejected',
          verification_note: 'No crops planted; lot is vacant.',
        },
      },
    })
  })

  await page.goto('/admin/verifications')
  await page.getByTestId('scope-plots').click()
  await page.getByTestId('verification-row').click()
  const detail = page.getByTestId('verification-detail')
  await expect(detail).toBeVisible()
  await expect(detail).toContainText('North Field')
  await expect(page.getByTestId('verification-map')).toBeVisible()

  await page.getByTestId('decision-reject').click()
  await expect(page.getByText('A reason is required')).toBeVisible()
  expect(requests).toHaveLength(0)

  await page.getByLabel('Decision note').fill('No crops planted; lot is vacant.')
  await page.getByTestId('decision-reject').click()
  const dialog = page.getByRole('dialog', { name: 'Reject record', exact: true })
  await expect(dialog).toContainText('The farmer will see this reason.')
  await dialog.getByRole('button', { name: 'Reject', exact: true }).click()

  await expect(dialog).not.toBeVisible()
  expect(requests).toEqual([{ reason: 'No crops planted; lot is vacant.' }])
  await expect(detail).toContainText('Rejected')
  await expect(detail).toContainText('No crops planted; lot is vacant.')
  await expectNoOverflow(page)
})

test('farmer sees badges on a verified farm and listing, none on an unverified plot', async ({
  page,
}) => {
  await mockSession(page)
  await mockTiles(page)
  await page.route('**/api/v1/farms', (route) =>
    route.fulfill({ json: { data: [{ ...FARMS[0], verification_status: 'verified' }] } }),
  )
  await page.route('**/api/v1/user/addresses', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/market/prices/guide**', (route) =>
    route.fulfill({ json: { available: false, data: {} } }),
  )
  await page.route('**/api/v1/market/contracts?**', (route) =>
    route.fulfill({
      json: {
        data: [
          { ...LISTING, is_from_verified_farm: true },
          { ...LISTING, id: 6, title: 'Corn harvest', is_from_verified_farm: false },
        ],
        meta: { current_page: 1, last_page: 1, total: 2, per_page: 12 },
      },
    }),
  )
  await page.route('**/api/v1/farms/3/plots', (route) =>
    route.fulfill({
      json: {
        type: 'FeatureCollection',
        features: [
          {
            type: 'Feature',
            geometry: { type: 'Polygon', coordinates: POLYGON },
            properties: { id: 4, name: 'East bed', soil_type: 'clay', calculated_area: 0.25 },
          },
        ],
      },
    }),
  )
  await page.route('**/api/v1/plots', (route) =>
    route.fulfill({
      json: {
        data: [
          {
            id: 4,
            verification_status: 'pending',
            farm: { id: 3, verification_status: 'verified' },
          },
        ],
      },
    }),
  )
  await page.route('**/api/v1/restricted-zones', (route) =>
    route.fulfill({ json: { type: 'FeatureCollection', features: [] } }),
  )
  await page.route('**/api/v1/crop-taxonomy', (route) =>
    route.fulfill({ json: { data: { types: {}, crops: [] } } }),
  )
  await page.route('https://photon.komoot.io/**', (route) =>
    route.fulfill({ json: { features: [] } }),
  )

  await page.goto('/dashboard/farms')
  await expect(page.getByText('North Field').first()).toBeVisible()
  await expect(page.getByTestId('verified-badge')).toHaveCount(1)

  await page.goto('/marketplace')
  await expect(page.getByRole('heading', { name: 'Rice harvest' })).toBeVisible()
  await expect(page.getByTestId('verified-badge')).toHaveCount(1)

  await page.goto('/dashboard/farms/3/plots')
  await expect(page.getByText('East bed').first()).toBeVisible()
  await expect(page.getByTestId('verified-badge')).toHaveCount(0)
  await expectNoOverflow(page)
})
