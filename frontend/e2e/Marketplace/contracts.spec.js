import { test, expect, mockSession } from '../fixtures/session'

const CONTRACT = {
  id: 5,
  type: 'listing',
  title: 'Rice harvest',
  crop_name: 'Rice',
  quantity_kg: 100,
  price_per_kg: 50,
  total_price: 5000,
  status: 'available',
}

test('farmer contracts page renders per-kg prices without page errors', async ({ page }) => {
  await mockSession(page)
  await page.route('**/api/v1/farmer/contracts?**', (route) =>
    route.fulfill({
      json: {
        data: [CONTRACT],
        meta: { current_page: 1, last_page: 1, total: 1, per_page: 10 },
      },
    }),
  )
  await page.route('**/api/v1/farmer/contracts/stats', (route) =>
    route.fulfill({ json: { data: { total: 1, active: 1, revenue: 5000, pending: 0 } } }),
  )
  await page.goto('/dashboard/contracts')
  await expect(page.getByText('Rice harvest').filter({ visible: true }).first()).toBeVisible()
  await expect(page.getByText('@ ₱50/kg').filter({ visible: true }).first()).toBeVisible()
})
