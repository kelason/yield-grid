import { test, expect, mockSession } from '../fixtures/session'
import { E2E } from '../constants'
import {
  mockAdminSession,
  trackMemberApiCalls,
  overviewPayload,
  paginated,
  adminUserRow,
  expectNoOverflow,
} from '../fixtures/admin'

const ADMIN_PATHS = [
  '/admin/overview',
  '/admin/users',
  '/admin/content',
  '/admin/inquiries',
  '/admin/reports',
  '/admin/issues',
]

test('verified admin session opens the overview with admin navigation', async ({
  page,
  isMobile,
}) => {
  await mockAdminSession(page)
  const memberCalls = await trackMemberApiCalls(page)
  await page.route('**/api/v1/admin/overview', (route) =>
    route.fulfill({ json: overviewPayload() }),
  )
  await page.goto('/admin/overview')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Overview')
  await expect(page.getByTestId('overview-updated')).toContainText('Updated at 2026-10-08 08:00')
  let scope = page
  if (isMobile) {
    await page.getByRole('button', { name: 'Open navigation' }).click()
    scope = page.getByRole('dialog', { name: 'Navigation', exact: true })
  }
  for (const name of ['Overview', 'Users', 'Content', 'Inquiries', 'Reports', 'Issues']) {
    await expect(scope.getByRole('link', { name, exact: true }).first()).toBeVisible()
  }
  await expect(page.getByTestId('overview-users-total')).toContainText('120')
  await expect(page.getByTestId('overview-users-suspended')).toContainText('4')
  await expect(page.getByTestId('overview-content-thread')).toContainText('27 visible · 3 hidden')
  expect(memberCalls).toEqual([])
  await expectNoOverflow(page)
})

test('unverified admin routes to the standalone verification page', async ({ page }) => {
  await mockAdminSession(page, { verified: false })
  await page.goto('/admin/overview')
  await page.waitForURL('**/auth/verification-required')
  await expect(
    page.getByRole('heading', { name: 'Verify your email', exact: true }).first(),
  ).toBeVisible()
})

for (const role of ['farmer', 'buyer']) {
  test(`${role} direct admin navigation is rejected`, async ({ page }) => {
    await mockSession(page, { role })
    for (const path of ADMIN_PATHS) {
      await page.goto(path)
      await page.waitForURL('**/403')
      await expect(page.getByRole('heading', { level: 1 })).toHaveText('Access forbidden')
    }
  })
}

test('guest admin navigation redirects to sign-in', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/admin/overview')
  await page.waitForURL('**/auth/login')
  await expect(page.getByRole('heading', { name: 'Welcome Back' })).toBeVisible()
})

test('admin cannot open member-only issue reporting', async ({ page }) => {
  await mockAdminSession(page)
  await page.goto('/dashboard/issues')
  await page.waitForURL('**/403')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Access forbidden')
})

test('admin mobile navigation exposes admin routes and closes after navigating', async ({
  page,
}) => {
  await page.setViewportSize(E2E.MOBILE)
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/overview', (route) =>
    route.fulfill({ json: overviewPayload() }),
  )
  await page.route('**/api/v1/admin/users?**', (route) =>
    route.fulfill({ json: paginated([adminUserRow()]) }),
  )
  await page.goto('/admin/overview')
  const menu = page.getByRole('button', { name: 'Open navigation' })
  await menu.focus()
  await menu.press('Enter')
  const drawer = page.getByRole('dialog', { name: 'Navigation', exact: true })
  await drawer.getByRole('link', { name: 'Users', exact: true }).click()
  await expect(page).toHaveURL(/\/admin\/users$/)
  await expect(drawer).not.toBeVisible()
  await expect(menu).toBeFocused()
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Users')
})

test('admin own profile stays reachable from the sidebar', async ({ page, isMobile }) => {
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/overview', (route) =>
    route.fulfill({ json: overviewPayload() }),
  )
  await page.route('**/api/v1/user/addresses', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/users/3', (route) =>
    route.fulfill({
      json: {
        data: {
          id: 3,
          name: 'Ramon Cruz',
          email: 'admin@example.test',
          role: 'admin',
          stats: {},
          posts: [],
          addresses: [],
        },
      },
    }),
  )
  await page.goto('/admin/overview')
  let scope = page
  if (isMobile) {
    await page.getByRole('button', { name: 'Open navigation' }).click()
    scope = page.getByRole('dialog', { name: 'Navigation', exact: true })
  }
  await scope.getByRole('link', { name: 'Ramon Cruz profile', exact: true }).first().click()
  await page.waitForURL('**/dashboard/users/3')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Profile')
  await expect(page.getByRole('heading', { name: 'Ramon Cruz', exact: true })).toBeVisible()
})

test('admin surfaces stay usable across viewports, enlarged text and reduced motion', async ({
  page,
}) => {
  await page.emulateMedia({ reducedMotion: 'reduce' })
  await mockAdminSession(page)
  const longUser = adminUserRow({
    name: 'A member with a very long display name that must wrap safely',
    email: 'a.member.with.a.very.long.address@example.test',
    suspended_at: '2026-10-01T00:00:00Z',
    suspended_reason:
      'Repeated policy violations across several reports with a long explanation attached.',
  })
  await page.route('**/api/v1/admin/overview', (route) =>
    route.fulfill({ json: overviewPayload() }),
  )
  await page.route('**/api/v1/admin/users?**', (route) =>
    route.fulfill({ json: paginated([longUser]) }),
  )
  await page.goto('/admin/overview')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Overview')
  for (const viewport of [E2E.NARROW, E2E.MOBILE, E2E.TABLET, E2E.DESKTOP]) {
    await page.setViewportSize(viewport)
    await expectNoOverflow(page)
  }
  await page.evaluate(() => {
    document.documentElement.style.fontSize = '200%'
  })
  await expectNoOverflow(page)
  await page.evaluate(() => {
    document.documentElement.style.fontSize = ''
  })
  await page.goto('/admin/users')
  await expect(page.getByText(longUser.name, { exact: true })).toBeVisible()
  await expect(page.locator('li span', { hasText: 'Suspended' }).first()).toBeVisible()
  await page.setViewportSize({ width: 360, height: 640 })
  await expectNoOverflow(page)
  const duration = await page
    .getByRole('button', { name: 'Search', exact: true })
    .evaluate((button) => Number.parseFloat(getComputedStyle(button).transitionDuration))
  expect(duration).toBeLessThanOrEqual(E2E.MAX_REDUCED_MOTION_SECONDS)
})
