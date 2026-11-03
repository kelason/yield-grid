import { test, expect, mockSession } from '../fixtures/session'

test('tutorials offer independent farmer and buyer paths with detailed guide links', async ({
  page,
}) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/how-it-works')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(
    'A clear path from soil to sale.',
  )
  for (const id of ['plan-a-crop', 'sell-a-harvest', 'buy-a-harvest', 'request-a-crop']) {
    await expect(page.locator(`#${id}`)).toBeVisible()
  }
  await page
    .locator('#sell-a-harvest')
    .locator('..')
    .getByRole('link', { name: 'Read the full guide', exact: true })
    .first()
    .click()
  await expect(page).toHaveURL(/\/documentation\/selling-harvests#manual-listing$/)
})

test('missing tutorial images keep all text steps and accessible recovery', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.route('**/images/guides/**', (route) => route.abort())
  await page.goto('/how-it-works#farmers')
  await expect(page.locator('#farmers')).toBeFocused()
  await expect(page.locator('#plan-a-crop').locator('..')).toContainText('Open My Farms')
  await expect(
    page.getByText('Example image unavailable. Follow the written steps.', { exact: true }).first(),
  ).toBeVisible()
})

test('platform walkthrough plays above the written guides', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/how-it-works')
  const video = page
    .locator('video')
    .and(page.getByLabel('YieldGrid Platform Walkthrough', { exact: true }))
  await expect(video).toBeVisible()
  await expect.poll(() => video.evaluate((element) => element.readyState)).toBeGreaterThan(0)
  expect(await video.evaluate((element) => element.paused)).toBe(true)
  await video.focus()
  await expect(video).toBeFocused()
  await video.evaluate(async (element) => {
    element.muted = true
    await element.play()
  })
  await expect.poll(() => video.evaluate((element) => element.currentTime)).toBeGreaterThan(0)
  await video.evaluate((element) => element.pause())
  const videoBox = await video.boundingBox()
  const guideBox = await page.getByRole('heading', { name: 'Start with an account' }).boundingBox()
  expect(videoBox.y + videoBox.height).toBeLessThan(guideBox.y)
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(
    true,
  )
})

for (const role of ['farmer', 'buyer']) {
  test(`${role} tutorial start links respect the signed-in role`, async ({ page }) => {
    await mockSession(page, { role })
    await page.goto('/how-it-works')
    const destination = role === 'farmer' ? '/dashboard/farms' : '/dashboard/buyer/marketplace'
    await expect(page.locator(`main a[href="${destination}"]`)).toBeVisible()
    if (role === 'buyer')
      await expect(
        page.locator('#request-a-crop').locator('..').locator('a[href="/dashboard/buyer/demands"]'),
      ).toBeVisible()
    await expect(
      page.locator(
        `main a[href="${role === 'farmer' ? '/dashboard/buyer/marketplace' : '/dashboard/farms'}"]`,
      ),
    ).toHaveCount(0)
  })
}
