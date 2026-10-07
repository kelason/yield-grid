/* global process */
import { test, expect, mockSession } from '../fixtures/session'
import { FARMS, PURCHASES } from '../fixtures/data'
import { mockInsuranceApi } from '../fixtures/insurance'
import { E2E } from '../constants'
const INVALID = 422
const OK = 200
const LONG_PURCHASES = [
  {
    ...PURCHASES[0],
    amount_paid: '999999999.99',
    total_contract_amount: '999999999.99',
    payment_status: 'completed',
    contract: {
      ...PURCHASES[0].contract,
      crop_name: 'A long local harvest name for a mixed seasonal crop',
      farmer: { id: 1, name: 'A farmer with a long business and community name' },
    },
  },
]
const MARKET_CONTRACT = {
  id: 3,
  type: 'listing',
  title: 'Rice harvest',
  crop_name: 'Rice',
  quantity_kg: 100,
  price_per_kg: 50,
  total_price: 5000,
  currency: 'PHP',
  estimated_harvest_date: '2026-11-01',
  is_harvest_available: true,
  status: 'available',
  farmer: { id: 1, name: 'Juan' },
}
async function capture(page, testInfo, name) {
  await page.evaluate(() => document.fonts.ready)
  expect(
    await page.evaluate(
      () => document.fonts.check('400 16px Inter') && document.fonts.check('700 32px Lora'),
    ),
  ).toBe(true)
  const options = { animations: 'disabled', fullPage: true }
  if (
    process.platform === 'linux' &&
    ['chromium', 'mobile-chromium'].includes(testInfo.project.name)
  )
    await expect(page).toHaveScreenshot(`${name}.png`, options)
  else await page.screenshot({ path: testInfo.outputPath(`${name}-review-only.png`), ...options })
}
async function mockOverview(page, role, data) {
  await mockSession(page, { role })
  const endpoint = role === 'farmer' ? '**/api/v1/farms' : '**/api/v1/buyer/purchases?**'
  await page.route(endpoint, (route) =>
    route.fulfill({
      json: { data, meta: { current_page: 1, last_page: 1, total: data.length, per_page: 10 } },
    }),
  )
}
async function noOverflow(page) {
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
    ),
  ).toBe(true)
}
for (const role of ['farmer', 'buyer']) {
  test(`${role} populated dashboard visual`, async ({ page }, testInfo) => {
    await mockOverview(page, role, role === 'farmer' ? FARMS : PURCHASES)
    await page.goto(`/dashboard/${role}`)
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(
      role === 'farmer' ? 'Farm overview' : 'Buyer overview',
    )
    await expect(
      page.getByText(role === 'farmer' ? 'North Field' : 'Rice', { exact: true }).first(),
    ).toBeVisible()
    await capture(page, testInfo, `${role}-dashboard-populated`)
  })
}
test('farmer empty dashboard visual', async ({ page }, testInfo) => {
  await mockOverview(page, 'farmer', [])
  await page.goto('/dashboard/farmer')
  const empty = page.getByRole('heading', { name: 'No farms yet', exact: true })
  await expect(empty).toBeVisible()
  await page.evaluate(() => document.fonts.ready)
  await empty.scrollIntoViewIfNeeded()
  await capture(page, testInfo, 'farmer-dashboard-empty')
})
test('farmer loading dashboard visual', async ({ page }, testInfo) => {
  await mockSession(page)
  let release
  const gate = new Promise((resolve) => {
    release = resolve
  })
  await page.route('**/api/v1/farms', async (route) => {
    await gate
    await route.fulfill({ json: { data: FARMS } })
  })
  try {
    await page.goto('/dashboard/farmer')
    await expect(
      page.getByRole('region', { name: 'Your farms', exact: true }).getByRole('status'),
    ).toBeVisible()
    await capture(page, testInfo, 'farmer-dashboard-loading')
  } finally {
    release()
  }
})
test('ordinary and destructive confirmation visuals', async ({ page }, testInfo) => {
  await mockOverview(page, 'buyer', PURCHASES)
  await page.goto('/dashboard/buyer')
  await expect(page.getByText('Rice', { exact: true }).first()).toBeVisible()
  await page.evaluate(() => document.fonts.ready)
  await page.getByRole('button', { name: 'Message farmer', exact: true }).click()
  const ordinary = page.getByRole('dialog', { name: 'Open conversation?', exact: true })
  await expect(ordinary).toBeVisible()
  await capture(page, testInfo, 'confirmation-ordinary')
  await ordinary.getByRole('button', { name: 'Cancel', exact: true }).click()
  await page.getByRole('button', { name: 'Cancel purchase', exact: true }).click()
  const destructive = page.getByRole('dialog', { name: 'Cancel Purchase', exact: true })
  await expect(destructive).toBeVisible()
  await capture(page, testInfo, 'confirmation-destructive')
  await destructive.getByRole('button', { name: 'Cancel', exact: true }).click()
})
test('sign-in validation feedback visual', async ({ page }, testInfo) => {
  await mockSession(page, { authenticated: false })
  await page.route('**/api/v1/login', (route) =>
    route.fulfill({ status: INVALID, json: { message: 'Please check your email and password.' } }),
  )
  await page.goto('/auth/login')
  await page.getByLabel(/^Email address/i).fill('farmer@example.test')
  await page.getByLabel(/^Password/i).fill('test-password')
  await page.getByRole('button', { name: 'Sign in', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Sign in?', exact: true })
  await dialog.getByRole('button', { name: 'Sign in', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  await expect(
    page.getByRole('alert').filter({ hasText: 'Please check your email and password.' }).first(),
  ).toBeVisible()
  // Dismiss redundant transient notices; retain the actual form validation feedback.
  for (const button of await page.getByRole('button', { name: 'Close', exact: true }).all())
    await button.click()
  await capture(page, testInfo, 'login-validation-error')
})
test('marketplace visual', async ({ page }, testInfo) => {
  await mockSession(page, { authenticated: false })
  await page.route('**/api/v1/market/contracts?**', (route) =>
    route.fulfill({
      status: OK,
      json: {
        data: [MARKET_CONTRACT],
        meta: { current_page: 1, last_page: 1, total: 1, per_page: 12 },
      },
    }),
  )
  await page.route('**/api/v1/market/prices/guide**', (route) =>
    route.fulfill({ json: { available: false, data: {} } }),
  )
  await page.goto('/marketplace')
  await expect(page.getByRole('heading', { name: 'Rice harvest', exact: true })).toBeVisible()
  await capture(page, testInfo, 'marketplace-populated')
})
test('public home visual', async ({ page }, testInfo) => {
  await page.goto('/')
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Smarter farming')
  await capture(page, testInfo, 'public-home')
})
test('dashboard supports narrow tablet enlarged text and reduced motion', async ({
  page,
}, testInfo) => {
  await page.emulateMedia({ reducedMotion: 'reduce' })
  await mockOverview(page, 'buyer', LONG_PURCHASES)
  await page.goto('/dashboard/buyer')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Buyer overview')
  await page.evaluate(() => document.fonts.ready)
  for (const viewport of [E2E.NARROW, E2E.TABLET, E2E.DESKTOP]) {
    await page.setViewportSize(viewport)
    await noOverflow(page)
  }
  await page.evaluate(() => {
    document.documentElement.style.fontSize = '200%'
  })
  await noOverflow(page)
  await expect(page.getByText('₱999,999,999.99', { exact: true }).first()).toBeVisible()
  await page.screenshot({
    path: testInfo.outputPath('buyer-enlarged-text.png'),
    animations: 'disabled',
  })
  const duration = await page
    .getByRole('button', { name: 'Logout', exact: true })
    .evaluate((button) => Number.parseFloat(getComputedStyle(button).transitionDuration))
  expect(duration).toBeLessThanOrEqual(E2E.MAX_REDUCED_MOTION_SECONDS)
})
test('short viewport confirmation traps keyboard focus and restores its trigger', async ({
  page,
}) => {
  await page.setViewportSize({ width: 360, height: 640 })
  await mockOverview(page, 'buyer', PURCHASES)
  await page.goto('/dashboard/buyer')
  const trigger = page.getByRole('button', { name: 'Cancel purchase', exact: true })
  await trigger.click()
  const dialog = page.getByRole('dialog', { name: 'Cancel Purchase', exact: true })
  for (let index = 0; index < 6; index += 1) {
    await page.keyboard.press('Tab')
    await expect(dialog.locator(':focus')).toHaveCount(1)
  }
  await page.keyboard.press('Escape')
  await expect(dialog).not.toBeVisible()
  await expect(trigger).toBeFocused()
  await noOverflow(page)
})

test('Tagalog insurance controls stay readable', async ({ page }, testInfo) => {
  await mockInsuranceApi(page)
  await page.goto('/dashboard/insurance')
  await page.getByRole('button', { name: 'Tagalog', exact: true }).click()
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Seguro sa Pananim')
  await noOverflow(page)
  await capture(page, testInfo, 'insurance-tagalog')
})
