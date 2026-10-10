/* global process */
import { test, expect, mockSession } from '../fixtures/session'
import { FARMS, PURCHASES } from '../fixtures/data'
import { mockInsuranceApi } from '../fixtures/insurance'
import {
  mockAdminSession,
  overviewPayload,
  paginated,
  adminInquiry,
  inquiryReply,
  adminReport,
} from '../fixtures/admin'
import { E2E } from '../constants'
import { switchDashboardLocale } from '../fixtures/language'
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
  if (name.startsWith('public-')) await preparePublicCapture(page)
  expect(
    await page.evaluate(
      () => document.fonts.check('400 16px Inter') && document.fonts.check('700 32px Lora'),
    ),
  ).toBe(true)
  const options = { animations: 'disabled', fullPage: name !== 'public-mobile-menu' }
  if (
    process.platform === 'linux' &&
    ['chromium', 'mobile-chromium'].includes(testInfo.project.name)
  )
    await expect(page).toHaveScreenshot(`${name}.png`, options)
  else await page.screenshot({ path: testInfo.outputPath(`${name}-review-only.png`), ...options })
}
async function preparePublicCapture(page) {
  await page.evaluate(async () => {
    await Promise.all(Array.from(document.images, (image) => image.decode()))
    document.getElementById('main-content')?.scrollTo(0, 0)
    window.scrollTo(0, 0)
  })
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
for (const [path, heading] of [
  ['/about', 'Growing together, season after season.'],
  ['/contact', 'Contact Us'],
]) {
  test(`public ${path.slice(1)} visual`, async ({ page }, testInfo) => {
    await mockSession(page, { authenticated: false })
    await page.goto(path)
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(heading)
    await noOverflow(page)
    await capture(page, testInfo, `public-${path.slice(1)}`)
  })
}
test('public mobile menu visual', async ({ page, isMobile }, testInfo) => {
  test.skip(!isMobile, 'The public menu is a mobile control.')
  await mockSession(page, { authenticated: false })
  await page.goto('/contact')
  await page.getByRole('button', { name: 'Open menu', exact: true }).click()
  await expect(page.getByRole('dialog', { name: 'Menu', exact: true })).toBeVisible()
  await capture(page, testInfo, 'public-mobile-menu')
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
    .getByRole('button', { name: 'Log out', exact: true })
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

test('Tagalog insurance controls stay readable', async ({ page, isMobile }, testInfo) => {
  await mockInsuranceApi(page)
  await page.goto('/dashboard/insurance')
  await switchDashboardLocale(page, isMobile, 'tl')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Seguro sa Pananim')
  await noOverflow(page)
  await capture(page, testInfo, 'insurance-tagalog')
})
test('admin operational overview visual', async ({ page }, testInfo) => {
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/overview', (route) =>
    route.fulfill({ json: overviewPayload() }),
  )
  await page.goto('/admin/overview')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Overview')
  await expect(page.getByTestId('overview-users-total')).toContainText('120')
  await noOverflow(page)
  await capture(page, testInfo, 'admin-overview')
})
test('admin inquiry failure visual', async ({ page }, testInfo) => {
  const failed = inquiryReply({
    delivery_status: 'failed',
    attempts: 3,
    error_code: 'smtp_timeout',
    created_at: '2026-10-07T00:00:00Z',
  })
  const detail = adminInquiry({ status: 'read', replies: [failed] })
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/contact-messages?**', (route) =>
    route.fulfill({ json: paginated([adminInquiry({ replies: [failed] })]) }),
  )
  await page.route('**/api/v1/admin/contact-messages/7', (route) =>
    route.fulfill({ json: { data: detail } }),
  )
  await page.goto('/admin/inquiries')
  await page.getByRole('button', { name: 'View inquiry from Guest Visitor', exact: true }).click()
  await expect(page.getByText('Failed', { exact: true }).first()).toBeVisible()
  await noOverflow(page)
  // The click above scrolls #main-content by a font-timing-dependent 0-2px;
  // reset to a deterministic origin before comparing pixels.
  await page.evaluate(() => document.getElementById('main-content')?.scrollTo(0, 0))
  await capture(page, testInfo, 'admin-inquiry-failed')
})
test('admin report review visual', async ({ page }, testInfo) => {
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/reports?**', (route) =>
    route.fulfill({ json: paginated([adminReport()]) }),
  )
  await page.route('**/api/v1/admin/reports/5', (route) =>
    route.fulfill({ json: { data: adminReport() } }),
  )
  await page.goto('/admin/reports')
  await page.getByRole('button', { name: 'Review report 5', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Report #5', exact: true })).toBeVisible()
  await noOverflow(page)
  await capture(page, testInfo, 'admin-report-review')
})
test('member issue form visual', async ({ page }, testInfo) => {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/issues?**', (route) => route.fulfill({ json: paginated([]) }))
  await page.goto('/dashboard/issues')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Report an issue')
  await noOverflow(page)
  await capture(page, testInfo, 'member-issue-form')
})
