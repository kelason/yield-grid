import { test, expect, mockSession } from '../fixtures/session'

test('marketplace price range filters live after typing', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  const catalogRequests = []
  page.on('request', (request) => {
    if (request.url().includes('/api/v1/market/contracts')) {
      catalogRequests.push(request.url())
    }
  })
  await page.route('**/api/v1/market/contracts?**', (route) =>
    route.fulfill({ json: { data: [], meta: { current_page: 1, last_page: 1 } } }),
  )
  await page.route('**/api/v1/market/prices/guide**', (route) =>
    route.fulfill({ json: { available: false, data: {} } }),
  )
  await page.goto('/marketplace')
  await expect(page.locator('#max_price')).toBeVisible()
  expect(catalogRequests).toHaveLength(1)

  const filteredResponse = page.waitForResponse(
    (response) =>
      response.url().includes('/api/v1/market/contracts') &&
      response.url().includes('min_price=100') &&
      response.url().includes('max_price=500'),
  )
  await page.locator('#min_price').fill('100')
  await page.locator('#max_price').fill('500')
  await filteredResponse
  expect(catalogRequests).toHaveLength(2)
})
