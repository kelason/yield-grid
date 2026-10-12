import { test, expect, mockSession } from '../fixtures/session'
import { PLATFORM_FEATURES } from '../../src/constants/platformFeatures'
import en from '../../src/locales/en'
test('features group all eight areas with functional detailed guide links', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/features')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(
    'Useful tools for every part of the season.',
  )
  for (const feature of PLATFORM_FEATURES)
    await expect(
      page.getByRole('heading', {
        name: en.public[feature.titleKey.split('.').pop()],
        exact: true,
      }),
    ).toBeVisible()
  await expect(page.locator('main a[href^="/documentation/"]')).toHaveCount(8)
  await page.locator('main a[href^="/documentation/"]').first().click()
  await expect(page).toHaveURL(/\/documentation\/crop-advisor#request-analysis$/)
})
