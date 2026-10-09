import { test, expect } from '../fixtures/session'
import { mockInsuranceApi } from '../fixtures/insurance'
import { switchDashboardLocale } from '../fixtures/language'

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

  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({
    path: testInfo.outputPath('insurance.png'),
    fullPage: true,
    animations: 'disabled',
  })
  await switchDashboardLocale(page, isMobile, 'tl')
  await expect(page.getByRole('heading', { name: 'Seguro sa Pananim' })).toBeVisible()
  await switchDashboardLocale(page, isMobile, 'en')
  await expect(page.getByRole('heading', { name: 'Crop Insurance' })).toBeVisible()

  await page.getByLabel('RSBSA number').fill('RSBSA-1')
  await page.getByLabel('Registration status').selectOption('registered')

  const putRequest = page.waitForRequest(
    (request) => request.method() === 'PUT' && request.url().includes('/farmer/insurance/profile'),
  )
  await page.getByTestId('rsbsa-save').click()
  await page.getByRole('dialog').getByRole('button', { name: 'Save RSBSA details' }).click()
  const request = await putRequest
  expect(request.postDataJSON()).toEqual({ rsbsa_number: 'RSBSA-1', rsbsa_status: 'registered' })
  await expect(page.getByLabel('RSBSA number')).toHaveValue('RSBSA-1')
})
