import { test, expect, mockSession } from '../fixtures/session'
import { FARMS } from '../fixtures/data'
import { E2E } from '../constants'
async function noOverflow(page) {
  // Allow the measured navbar fallback to render after viewport/text resizing.
  await page.evaluate(
    () => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))),
  )
  const layout = await page.evaluate(() => ({
    width: innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    fontSize: getComputedStyle(document.documentElement).fontSize,
    overflow: Array.from(document.querySelectorAll('body *'))
      .filter((element) => element.getBoundingClientRect().right > innerWidth)
      .map((element) => ({ tag: element.tagName, class: element.className })),
  }))
  await expect
    .poll(
      () =>
        page.evaluate(
          () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
        ),
      { message: JSON.stringify(layout) },
    )
    .toBeLessThanOrEqual(0)
}
test('guest home has usable register and login navigation with one heading', async ({
  page,
}, testInfo) => {
  await page.goto('/')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(
    'Practical tools. Stronger farming futures.',
  )
  await expect(page.locator('a[href="/how-it-works#farmers"]')).toBeVisible()
  await expect(page.locator('a[href="/how-it-works#buyers"]')).toBeVisible()
  await expect(page.getByRole('heading', { name: 'AI Crop Advisor', exact: true })).toHaveCount(0)
  await page.evaluate(() => document.fonts.ready)
  await noOverflow(page)
  await page.screenshot({
    path: testInfo.outputPath('public-home.png'),
    fullPage: true,
    animations: 'disabled',
  })
  await page.getByRole('link', { name: 'Get started', exact: true }).click()
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Create your account')
  await expect(page.getByRole('dialog')).toHaveCount(0)
  await page.getByRole('link', { name: 'Sign in here', exact: true }).click()
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Welcome Back')
})
test('public navigation remains usable on mobile and enlarged text', async ({
  page,
  isMobile,
}, testInfo) => {
  await page.goto('/')
  if (isMobile) await page.getByRole('button', { name: 'Open menu', exact: true }).click()
  const nav = page.getByRole('navigation', {
    name: isMobile ? 'Mobile navigation' : 'Main navigation',
    exact: true,
  })
  await nav.getByRole('link', { name: 'About', exact: true }).click()
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Growing together')
  await expect(page.getByRole('dialog')).toHaveCount(0)
  await noOverflow(page)
  if (testInfo.project.name === 'chromium') {
    for (const viewport of [E2E.NARROW, E2E.TABLET, E2E.DESKTOP]) {
      await page.setViewportSize(viewport)
      await noOverflow(page)
    }
    await page.evaluate(() => {
      document.documentElement.style.fontSize = '200%'
    })
    await noOverflow(page)
  }
})
test('protected guest navigation redirects to the login form', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/dashboard/farms')
  await expect(page).toHaveURL(/\/auth\/login$/)
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Welcome Back')
})
test('cross-role access keeps the correct dashboard destination', async ({ page }) => {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/buyer/purchases?**', (route) =>
    route.fulfill({
      json: { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 10 } },
    }),
  )
  await page.goto('/dashboard/farms')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Access forbidden')
  await page.getByRole('button', { name: 'Go back home', exact: true }).click()
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Buyer overview')
})
test('unverified access keeps the banner and confirms verification resend', async ({ page }) => {
  await mockSession(page, { verified: false })
  await page.route('**/api/v1/farms', (route) => route.fulfill({ json: { data: FARMS } }))
  let sends = 0
  await page.route('**/api/v1/email/verification-notification', (route) => {
    sends += 1
    return route.fulfill({ json: { message: 'Verification link sent.' } })
  })
  await page.goto('/dashboard/community')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Farm overview')
  await expect(
    page.getByText('Contact the admin to approve your verification request.'),
  ).toBeVisible()
  const approvalBadge = page.getByRole('link', { name: 'Admin approval', exact: true })
  await expect(approvalBadge).toBeVisible()
  await expect(approvalBadge).toHaveAttribute('href', '/contact')
  await page.getByRole('button', { name: 'Resend Verification Email', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Resend verification email?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(sends).toBe(0)
  await page.getByRole('button', { name: 'Resend Verification Email', exact: true }).click()
  await dialog.getByRole('button', { name: 'Resend email', exact: true }).click()
  await expect(page.getByRole('button', { name: /Resend in/ })).toBeDisabled()
  expect(sends).toBe(1)
  await noOverflow(page)
})
test('checkout result pages present the verified status and retain cancellation contract', async ({
  page,
}) => {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/checkout/session-test/verify', (route) =>
    route.fulfill({ json: { status: 'completed' } }),
  )
  let cancels = 0
  await page.route('**/api/v1/checkout/session-test/cancel', (route) => {
    cancels += 1
    expect(route.request().method()).toBe('POST')
    return route.fulfill({ json: {} })
  })
  await page.goto('/checkout/success?session_id=session-test')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Payment Successful!')
  await page.goto('/checkout/success')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Payment Pending')
  await page.goto('/checkout/cancel?session_id=session-test')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Payment Cancelled')
  expect(cancels).toBe(1)
  await expect(page.getByRole('dialog')).toHaveCount(0)
  await noOverflow(page)
})
