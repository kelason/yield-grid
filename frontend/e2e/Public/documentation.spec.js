import { test, expect, mockSession } from '../fixtures/session'

test('public documentation has readable topics and unknown-link recovery', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/documentation')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('YieldGrid documentation')
  await page
    .getByRole('main')
    .getByRole('link', { name: 'Getting started', exact: true })
    .last()
    .click()
  await expect(page.locator('#verify-email')).toBeVisible()
  await page.goto('/documentation/not-a-guide')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Guide not found')
  await page.getByRole('link', { name: 'Back to documentation', exact: true }).click()
  await expect(page).toHaveURL(/\/documentation$/)
})

test('buyer can read farmer guidance without being offered a farmer mutation', async ({ page }) => {
  await mockSession(page, { role: 'buyer' })
  await page.goto('/documentation/farms-and-plots')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Farms & plots')
  await expect(page.locator('main a[href="/dashboard/farms"]')).toHaveCount(0)
  await expect(page.locator('#draw-a-plot')).toBeVisible()
})
