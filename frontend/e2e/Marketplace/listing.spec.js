import { test, expect, mockSession } from '../fixtures/session'

test('new-listing quantity and price inputs align on desktop', async ({ page, isMobile }) => {
  test.skip(isMobile, 'The inputs stack vertically on mobile.')
  await mockSession(page)
  await page.route('**/api/v1/farms', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/plots', (route) => route.fulfill({ json: { data: [] } }))
  await page.goto('/dashboard/contracts/new-listing')
  await expect(page.locator('#quantity_kg')).toBeVisible()
  const tops = await page.evaluate(() => {
    const quantity = document.querySelector('#quantity_kg').getBoundingClientRect()
    const price = document.querySelector('#price_per_kg').getBoundingClientRect()
    return { quantityTop: quantity.y, priceTop: price.y }
  })
  expect(Math.abs(tops.quantityTop - tops.priceTop)).toBeLessThanOrEqual(1)
})
