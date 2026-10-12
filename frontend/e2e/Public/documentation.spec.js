import { test, expect, mockSession } from '../fixtures/session'

test('public documentation has readable topics and unknown-link recovery', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/documentation')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('YieldGrid documentation')
  await page
    .getByRole('heading', { name: 'Getting started', level: 3, exact: true })
    .getByRole('link')
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

test('all current guide topics load directly for guests', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  for (const topic of [
    'getting-started',
    'profile-addresses-language',
    'troubleshooting-and-support',
    'farms-and-plots',
    'crop-advisor',
    'selling-harvests',
    'verification-and-trust-score',
    'insurance-and-rsbsa',
    'buying-harvests',
    'payments-and-purchases',
    'demands-and-offers',
    'price-guide',
    'community-and-messaging',
  ]) {
    await page.goto(`/documentation/${topic}`)
    await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1)
    await expect(page.getByRole('heading', { level: 1 })).not.toHaveText('Guide not found')
    await expect(page.locator('article ol li').first()).toBeVisible()
  }
})

for (const locale of ['tl', 'ceb']) {
  test(`${locale} detailed trade and insurance guidance is translated`, async ({ page }) => {
    await mockSession(page, { authenticated: false })
    await page.addInitScript((code) => localStorage.setItem('yieldgrid-locale', code), locale)
    for (const topic of ['payments-and-purchases', 'insurance-and-rsbsa']) {
      await page.goto(`/documentation/${topic}`)
      await expect(page.locator('html')).toHaveAttribute('lang', locale)
      await expect(page.locator('article')).not.toContainText('guides.')
      await expect(page.locator('article ol li').first()).toBeVisible()
    }
  })
}
