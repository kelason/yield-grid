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

for (const role of ['farmer', 'buyer']) {
  test(`${role} tutorial start links respect the signed-in role`, async ({ page }) => {
    await mockSession(page, { role })
    await page.goto('/how-it-works')
    const destination = role === 'farmer' ? '/dashboard/farms' : '/dashboard/buyer/marketplace'
    await expect(page.locator(`main a[href="${destination}"]`)).toBeVisible()
    await expect(
      page.locator(
        `main a[href="${role === 'farmer' ? '/dashboard/buyer/marketplace' : '/dashboard/farms'}"]`,
      ),
    ).toHaveCount(0)
  })
}
