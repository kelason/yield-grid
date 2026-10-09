import { test, expect, mockSession } from '../fixtures/session'
import { FARMS } from '../fixtures/data'
import { switchDashboardLocale } from '../fixtures/language'
import { mockAdminSession, overviewPayload } from '../fixtures/admin'

test('shell chrome renders in Tagalog', async ({ page, isMobile }) => {
  await mockSession(page, { role: 'farmer' })
  await page.route('**/api/v1/farms', (route) =>
    route.fulfill({
      json: {
        data: FARMS,
        meta: { current_page: 1, last_page: 1, total: FARMS.length, per_page: 10 },
      },
    }),
  )
  await page.goto('/dashboard/farmer')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Farm overview')
  await switchDashboardLocale(page, isMobile, 'tl')
  if (isMobile) {
    await page.getByRole('button', { name: 'Buksan ang nabigasyon', exact: true }).click()
  } else {
    await expect(
      page.getByText('Ang iyong workspace sa pagbubukid', { exact: true }),
    ).toBeVisible()
  }
  const nav = page.getByRole('navigation', { name: 'Dashboard navigation', exact: true })
  await expect(nav.getByRole('link', { name: 'Pangkalahatan', exact: true })).toBeVisible()
})

test('farmer dashboard renders in Tagalog', async ({ page, isMobile }) => {
  await mockSession(page, { role: 'farmer' })
  await page.route('**/api/v1/farms', (route) =>
    route.fulfill({
      json: {
        data: FARMS,
        meta: { current_page: 1, last_page: 1, total: FARMS.length, per_page: 10 },
      },
    }),
  )
  await page.goto('/dashboard/farmer')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Farm overview')
  await switchDashboardLocale(page, isMobile, 'tl')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Pangkalahatan ng Bukid')
  await expect(
    page.getByRole('link', { name: 'Pamahalaan ang mga bukid', exact: true }),
  ).toBeVisible()
})

test('buyer dashboard renders in Tagalog', async ({ page, isMobile }) => {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/buyer/purchases*', (route) =>
    route.fulfill({
      json: {
        data: [],
        meta: { current_page: 1, last_page: 1, total: 0, per_page: 10 },
      },
    }),
  )
  await page.goto('/dashboard/buyer')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Buyer overview')
  await switchDashboardLocale(page, isMobile, 'tl')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Pangkalahatan ng Mamimili')
  await expect(
    page.getByRole('link', { name: 'Mag-browse sa Palengke', exact: true }),
  ).toBeVisible()
})

test('community forum renders in Tagalog', async ({ page, isMobile }) => {
  await mockSession(page, { role: 'farmer' })
  await page.route('**/api/v1/forum/categories', (route) =>
    route.fulfill({ json: { data: [] } }),
  )
  await page.route('**/api/v1/forum/tags', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/forum/threads?**', (route) =>
    route.fulfill({
      json: { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 10 } },
    }),
  )
  await page.goto('/dashboard/community')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Community Forum')
  await switchDashboardLocale(page, isMobile, 'tl')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Forum ng Komunidad')
})

test('admin overview renders in Tagalog', async ({ page, isMobile }) => {
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/overview', (route) =>
    route.fulfill({ json: overviewPayload() }),
  )
  await page.goto('/admin/overview')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Overview')
  await switchDashboardLocale(page, isMobile, 'tl')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Pangkalahatan')
})
