import { test, expect, mockSession } from '../fixtures/session'
import { FARMS } from '../fixtures/data'

test('mobile navigation exposes role routes and closes after navigating', async ({
  page,
  isMobile,
}) => {
  test.skip(!isMobile, 'Mobile drawer behavior')
  await mockSession(page)
  await page.route('**/api/v1/farms', (route) => route.fulfill({ json: { data: FARMS } }))
  await page.goto('/dashboard/farmer')
  const menu = page.getByRole('button', { name: 'Open navigation' })
  await menu.click()
  const drawer = page.getByRole('dialog', { name: 'Navigation', exact: true })
  await drawer.getByRole('button', { name: 'Farm', exact: true }).click()
  await drawer.getByRole('link', { name: 'My Farms', exact: true }).click()
  await expect(page).toHaveURL(/\/dashboard\/farms$/)
  await expect(drawer).not.toBeVisible()
  await expect(menu).toBeFocused()
})
