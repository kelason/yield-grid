import { test, expect, mockSession } from '../fixtures/session'
import { FARMS } from '../fixtures/data'

const POSITION_TOLERANCE_PX = 2
const FIRST_POSITION_PX = 700
const SECOND_POSITION_PX = 1100

async function expectReadingTarget(page, id) {
  const heading = page.locator(`#${id}`)
  await expect(heading).toBeFocused()
  await expect
    .poll(async () => {
      const bounds = await heading.boundingBox()
      const header = await page.locator('body header').first().boundingBox()
      return bounds.y >= header.y + header.height
    })
    .toBe(true)
}

test('direct and same-article hash links focus headings below the sticky header', async ({
  page,
}) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/documentation/farms-and-plots#draw-a-plot')
  await expectReadingTarget(page, 'draw-a-plot')
  await page.locator('article a[href$="#save-a-plot"]').click()
  await expectReadingTarget(page, 'save-a-plot')
})

test('back and forward distinguish repeated entries for the same article', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/documentation/farms-and-plots')
  await expectReadingTarget(page, 'guide-heading')
  await page.evaluate((top) => window.scrollTo(0, top), FIRST_POSITION_PX)
  await expect.poll(() => page.evaluate(() => scrollY)).toBe(FIRST_POSITION_PX)
  await page
    .locator('article a[href="/documentation/crop-advisor"]')
    .evaluate((link) => link.click())
  await expectReadingTarget(page, 'guide-heading')
  await page
    .locator('article a[href="/documentation/farms-and-plots"]')
    .evaluate((link) => link.click())
  await expectReadingTarget(page, 'guide-heading')
  await page.evaluate((top) => window.scrollTo(0, top), SECOND_POSITION_PX)
  await expect.poll(() => page.evaluate(() => scrollY)).toBe(SECOND_POSITION_PX)
  await page.goBack()
  await expect(page).toHaveURL(/\/crop-advisor$/)
  await page.goBack()
  await expect
    .poll(async () => Math.abs((await page.evaluate(() => scrollY)) - FIRST_POSITION_PX))
    .toBeLessThanOrEqual(POSITION_TOLERANCE_PX)
  await page.goForward()
  await page.goForward()
  await expect
    .poll(async () => Math.abs((await page.evaluate(() => scrollY)) - SECOND_POSITION_PX))
    .toBeLessThanOrEqual(POSITION_TOLERANCE_PX)
})

test('invalid hashes and rapid route changes leave the latest guide usable', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.emulateMedia({ reducedMotion: 'reduce' })
  await page.goto('/documentation/farms-and-plots#not-a-section')
  await expectReadingTarget(page, 'guide-heading')
  await page.locator('article a[href="/documentation/crop-advisor"]').click()
  await page.locator('article a[href="/documentation/selling-harvests"]').click()
  await expect(page).toHaveURL(/\/selling-harvests$/)
  await expectReadingTarget(page, 'guide-heading')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Selling an existing harvest')
})

test('changing language in the mobile dialog keeps focus inside it', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.setViewportSize({ width: 390, height: 844 })
  await page.goto('/documentation/payments-and-purchases#cash-confirmation')
  await expectReadingTarget(page, 'cash-confirmation')
  await page.getByRole('button', { name: 'Open menu', exact: true }).click()
  const dialog = page.getByRole('dialog')
  await dialog.getByRole('button', { name: /Language|Wika|Pinulongan/i }).click()
  await dialog.getByRole('option', { name: 'Tagalog', exact: true }).click()
  await expect(dialog).toBeVisible()
  await expect(dialog.locator(':focus')).toHaveCount(1)
  await expect(page).toHaveURL(/#cash-confirmation$/)
  await expect(page.locator('html')).toHaveAttribute('lang', 'tl')
})

test('entering a hashed guide from another layout waits for layout readiness', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/auth/login')
  await page.evaluate(() =>
    document
      .getElementById('app')
      .__vue_app__.config.globalProperties.$router.push(
        '/documentation/farms-and-plots#save-a-plot',
      ),
  )
  await expectReadingTarget(page, 'save-a-plot')
})

test('leaving public reading preserves dashboard scrolling and browser restoration', async ({
  page,
}) => {
  await mockSession(page)
  await page.setViewportSize({ width: 390, height: 480 })
  await page.route('**/api/v1/farms', (route) => route.fulfill({ json: { data: FARMS } }))
  await page.goto('/documentation/farms-and-plots#draw-a-plot')
  await expectReadingTarget(page, 'draw-a-plot')
  const restoration = await page.evaluate(() => history.scrollRestoration)
  await page.evaluate(() =>
    document
      .getElementById('app')
      .__vue_app__.config.globalProperties.$router.push('/dashboard/farmer'),
  )
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Farm overview')
  await expect(page.locator('.page-enter-active')).toHaveCount(0)
  await page.locator('#main-content').evaluate((main) => main.scrollTo(0, 100))
  await expect
    .poll(() => page.locator('#main-content').evaluate((main) => main.scrollTop))
    .toBe(100)
  expect(await page.evaluate(() => history.scrollRestoration)).toBe(restoration)
})

async function navigateApp(page, path) {
  await page.evaluate(
    (to) => document.getElementById('app').__vue_app__.config.globalProperties.$router.push(to),
    path,
  )
}

test('fresh guide entries after cross-layout Back do not inherit discarded forward positions', async ({
  page,
}) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/auth/login')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Welcome Back')
  await navigateApp(page, '/documentation/farms-and-plots')
  await expectReadingTarget(page, 'guide-heading')
  await page.evaluate((top) => window.scrollTo(0, top), FIRST_POSITION_PX)
  await expect.poll(() => page.evaluate(() => scrollY)).toBe(FIRST_POSITION_PX)
  await page.goBack()
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Welcome Back')
  await expect(page.locator('.page-enter-active')).toHaveCount(0)
  await navigateApp(page, '/documentation/farms-and-plots')
  await expectReadingTarget(page, 'guide-heading')
  await expect.poll(() => page.evaluate(() => scrollY)).toBe(0)
  await page.evaluate((top) => window.scrollTo(0, top), SECOND_POSITION_PX)
  await expect.poll(() => page.evaluate(() => scrollY)).toBe(SECOND_POSITION_PX)
  await navigateApp(page, '/auth/login')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Welcome Back')
  await page.goBack()
  await expect.poll(() => page.evaluate(() => scrollY)).toBe(SECOND_POSITION_PX)
  expect(await page.evaluate(() => history.scrollRestoration)).toBe('auto')
})
