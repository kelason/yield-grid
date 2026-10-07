import { test, expect, mockSession } from '../fixtures/session'
import { FARMS, PURCHASES } from '../fixtures/data'
import { E2E } from '../constants'

const ROLES = [
  {
    role: 'farmer',
    path: '/dashboard/farmer',
    title: 'Farm overview',
    empty: 'No farms yet',
    endpoint: '**/api/v1/farms',
  },
  {
    role: 'buyer',
    path: '/dashboard/buyer',
    title: 'Buyer overview',
    empty: 'No purchases yet',
    endpoint: '**/api/v1/buyer/purchases?**',
  },
]

async function mockOverview(page, config, data) {
  await mockSession(page, { role: config.role })
  await page.route(config.endpoint, (route) =>
    route.fulfill({
      json: { data, meta: { current_page: 1, last_page: 3, total: 30, per_page: 10 } },
    }),
  )
}

async function captureOverview(page, testInfo, role) {
  await page.evaluate(() => document.fonts.ready)
  await page.emulateMedia({ reducedMotion: 'reduce' })
  await page.screenshot({ path: testInfo.outputPath(`${role}-overview.png`), fullPage: true })
  if (testInfo.project.name !== 'chromium') return
  for (const [name, viewport] of [
    ['tablet', E2E.TABLET],
    ['narrow', E2E.NARROW],
  ]) {
    await page.setViewportSize(viewport)
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
      ),
    ).toBe(true)
    await page.screenshot({ path: testInfo.outputPath(`${role}-${name}.png`), fullPage: true })
  }
}

for (const config of ROLES) {
  test(`${config.role} overview presents actual data without page overflow`, async ({
    page,
  }, testInfo) => {
    const data = config.role === 'farmer' ? FARMS : PURCHASES
    await mockOverview(page, config, data)
    await page.goto(config.path)
    await expect(page.getByRole('heading', { name: config.title, level: 1 })).toBeVisible()
    await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1)
    if (config.role === 'farmer')
      await expect(page.getByRole('heading', { name: 'North Field' })).toBeVisible()
    else
      await expect(
        page.locator('dl').filter({ hasText: 'Purchases on this page' }).locator('dd'),
      ).toHaveText('1')
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
      ),
    ).toBe(true)
    await captureOverview(page, testInfo, config.role)
  })
  test(`${config.role} overview distinguishes empty data`, async ({ page }) => {
    await mockOverview(page, config, [])
    await page.goto(config.path)
    await expect(page.getByRole('heading', { name: config.empty })).toBeVisible()
  })
  test(`${config.role} overview recovers from a failed response`, async ({ page }) => {
    await mockSession(page, { role: config.role })
    let attempts = 0
    await page.route(config.endpoint, (route) => {
      attempts += 1
      return route.fulfill({
        status: attempts === 1 ? 503 : 200,
        json:
          attempts === 1
            ? { message: 'Unavailable' }
            : { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 10 } },
      })
    })
    await page.goto(config.path)
    await expect(page.getByRole('alert')).toContainText(
      config.role === 'farmer' ? 'Unable to load your farms' : 'Unable to load your purchases',
    )
    await page.getByRole('button', { name: 'Retry', exact: true }).click()
    await expect(page.getByRole('heading', { name: config.empty })).toBeVisible()
  })
}

test('long buyer amounts remain readable on a narrow screen', async ({ page }) => {
  const config = ROLES.find((entry) => entry.role === 'buyer')
  await mockOverview(page, config, [
    { ...PURCHASES[0], amount_paid: '9999999999.99', payment_status: 'completed' },
  ])
  await page.setViewportSize(E2E.NARROW)
  await page.goto(config.path)
  await expect(page.getByRole('heading', { name: config.title })).toBeVisible()
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
    ),
  ).toBe(true)
})
