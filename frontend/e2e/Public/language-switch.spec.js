import { test, expect } from '../fixtures/session'
import { mockInsuranceApi } from '../fixtures/insurance'

test('guest language switch persists across reload', async ({ page, isMobile }) => {
  await page.goto('/')
  const nav = page.getByRole('navigation', {
    name: isMobile ? 'Mobile navigation' : 'Main navigation',
    exact: true,
  })
  const menu = page.getByTestId('language-menu').filter({ visible: true })
  if (isMobile) {
    await page.getByRole('button', { name: 'Open menu', exact: true }).click()
  }
  await expect(nav.getByRole('link', { name: 'About', exact: true })).toBeVisible()

  await menu.click()
  await page.getByTestId('locale-ceb').click()

  await expect(nav.getByRole('link', { name: 'Mahitungod', exact: true })).toBeVisible()
  await expect(menu).toContainText('CEB')
  await expect(await page.evaluate(() => localStorage.getItem('yieldgrid-locale'))).toBe('ceb')

  await page.reload()
  if (isMobile) {
    await page.getByRole('button', { name: 'Open menu', exact: true }).click()
  }
  await expect(nav.getByRole('link', { name: 'Mahitungod', exact: true })).toBeVisible()
  await expect(await page.evaluate(() => localStorage.getItem('yieldgrid-locale'))).toBe('ceb')
})

test('farmer dashboard switch translates header chrome', async ({ page }) => {
  await mockInsuranceApi(page)
  await page.goto('/dashboard/insurance')
  await expect(page.getByRole('heading', { name: 'Crop Insurance' })).toBeVisible()

  await page.getByTestId('language-menu').click()
  await page.getByTestId('locale-ceb').click()

  await expect(page.getByRole('button', { name: 'Gawas', exact: true })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Seguro sa Pananom' })).toBeVisible()
})

test('insurance page has no inline language pill', async ({ page }) => {
  await mockInsuranceApi(page)
  await page.goto('/dashboard/insurance')
  await expect(page.getByRole('heading', { name: 'Crop Insurance' })).toBeVisible()

  await expect(page.getByTestId('locale-en')).toHaveCount(0)
  await expect(page.getByTestId('locale-tl')).toHaveCount(0)
  await expect(page.getByTestId('locale-ceb')).toHaveCount(0)
})
