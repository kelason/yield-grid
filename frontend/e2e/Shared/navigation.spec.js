import { test, expect, mockSession } from '../fixtures/session'
import { E2E } from '../constants'
import { FARMS, FARMER, BUYER } from '../fixtures/data'

test('mobile navigation exposes role routes and closes after navigating', async ({ page }) => {
  await page.setViewportSize(E2E.MOBILE)
  await mockSession(page)
  await page.route('**/api/v1/farms', (route) => route.fulfill({ json: { data: FARMS } }))
  await page.goto('/dashboard/farmer')
  const menu = page.getByRole('button', { name: 'Open navigation' })
  await menu.focus()
  await menu.press('Enter')
  const drawer = page.getByRole('dialog', { name: 'Navigation', exact: true })
  await drawer.getByRole('button', { name: 'Farm', exact: true }).click()
  await drawer.getByRole('link', { name: 'My Farms', exact: true }).click()
  await expect(page).toHaveURL(/\/dashboard\/farms$/)
  await expect(drawer).not.toBeVisible()
  await expect(menu).toBeFocused()
})

for (const role of ['farmer', 'buyer']) {
  test(`${role} mobile navigation links to issue reporting`, async ({ page }) => {
    await page.setViewportSize(E2E.MOBILE)
    await mockSession(page, { role })
    await page.route('**/api/v1/issues?**', (route) =>
      route.fulfill({
        json: { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 15 } },
      }),
    )
    await page.goto('/dashboard/chat')
    const menu = page.getByRole('button', { name: 'Open navigation' })
    await menu.focus()
    await menu.press('Enter')
    const drawer = page.getByRole('dialog', { name: 'Navigation', exact: true })
    await drawer.getByRole('link', { name: 'Report an issue', exact: true }).click()
    await expect(page).toHaveURL(/\/dashboard\/issues$/)
    await expect(drawer).not.toBeVisible()
    await expect(menu).toBeFocused()
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Report an issue')
  })
}

for (const role of ['farmer', 'buyer']) {
  test(`${role} mobile navigation exposes its own profile and closes the drawer`, async ({
    page,
  }) => {
    await page.setViewportSize(E2E.MOBILE)
    await mockSession(page, { role })
    const user = role === 'farmer' ? FARMER : BUYER
    await page.route('**/api/v1/user/addresses', (route) => route.fulfill({ json: { data: [] } }))
    await page.route(`**/api/v1/users/${user.id}`, (route) =>
      route.fulfill({ json: { data: user } }),
    )
    if (role === 'farmer') {
      await page.route('**/api/v1/farms', (route) => route.fulfill({ json: { data: FARMS } }))
    }
    await page.goto('/dashboard/chat')
    const menu = page.getByRole('button', { name: 'Open navigation' })
    await menu.focus()
    await menu.press('Enter')
    const drawer = page.getByRole('dialog', { name: 'Navigation', exact: true })
    const profile = drawer.getByRole('link', { name: `${user.name} profile`, exact: true })
    await expect(profile).toHaveAttribute('href', `/dashboard/users/${user.id}`)
    await profile.click()
    await page.waitForURL(`**/dashboard/users/${user.id}`)
    await expect(drawer).not.toBeVisible()
    await expect(menu).toBeFocused()
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Profile')
    await expect(page.getByRole('heading', { name: user.name, exact: true })).toBeVisible()
  })
}
