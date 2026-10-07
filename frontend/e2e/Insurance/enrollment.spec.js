import { test, expect, mockSession } from '../fixtures/session'

async function mockInsuranceApi(page) {
  await mockSession(page)
  await page.route('**/api/v1/farmer/insurance/profile', (route) => {
    if (route.request().method() === 'PUT') {
      return route.fulfill({
        json: { data: { rsbsa_number: 'RSBSA-1', rsbsa_status: 'registered' } },
      })
    }
    return route.fulfill({ json: { data: { rsbsa_number: null, rsbsa_status: 'not_registered' } } })
  })
  await page.route('**/api/v1/farmer/insurance/enrollments', (route) =>
    route.fulfill({ json: { data: [] } }),
  )
  await page.route('**/api/v1/farmer/insurance/reminders', (route) =>
    route.fulfill({ json: { data: [] } }),
  )
  await page.route('**/api/v1/farmer/insurance/offices', (route) =>
    route.fulfill({
      json: {
        data: [
          {
            name: 'PCIC Head Office',
            region_code: null,
            city: 'Quezon City',
            address: 'NIA Complex',
            phone: '(02) 8441-1323',
            source_note: '',
            is_head_office: true,
            is_serving_region: false,
          },
        ],
      },
    }),
  )
  await page.route('**/api/v1/plots', (route) =>
    route.fulfill({ json: { data: [{ id: 3, name: 'North Plot', calculated_area: 1.5 }] } }),
  )
}

test('redirects unauthenticated visitors to login', async ({ page }) => {
  await page.goto('/dashboard/insurance')
  await expect(page).toHaveURL(/\/auth\/login/)
})

test('guides a farmer through RSBSA save in both languages', async ({
  page,
  isMobile,
}, testInfo) => {
  await mockInsuranceApi(page)
  await page.addInitScript(() => localStorage.setItem('auth_token', 'e2e-fake-token'))
  await page.goto('/dashboard/insurance')

  await expect(page.getByRole('heading', { name: 'Crop Insurance' })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Enrollment Guide' })).toBeVisible()
  if (!isMobile) {
    await expect(page.getByRole('link', { name: 'Mang Juan' }).first()).toBeVisible()
    await expect(page.getByRole('button', { name: 'Farm', exact: true })).toBeVisible()
  }

  await page.screenshot({ path: testInfo.outputPath('insurance.png'), fullPage: true })
  await page.getByTestId('locale-tl').click()
  await expect(page.getByRole('heading', { name: 'Seguro sa Pananim' })).toBeVisible()
  await page.getByTestId('locale-en').click()
  await expect(page.getByRole('heading', { name: 'Crop Insurance' })).toBeVisible()

  await page.getByTestId('rsbsa-number').locator('input').fill('RSBSA-1')
  await page.getByTestId('rsbsa-status').locator('select').selectOption('registered')

  const putRequest = page.waitForRequest(
    (request) => request.method() === 'PUT' && request.url().includes('/farmer/insurance/profile'),
  )
  await page.getByTestId('rsbsa-save').click()
  await page.getByRole('button', { name: 'Save RSBSA details' }).last().click()
  const request = await putRequest
  expect(request.postDataJSON()).toEqual({ rsbsa_number: 'RSBSA-1', rsbsa_status: 'registered' })
  await expect(page.getByTestId('rsbsa-number').locator('input')).toHaveValue('RSBSA-1')
})
