import { test, expect } from '../fixtures/session'

test('has title and can navigate to login', async ({ page, isMobile }) => {
  await page.goto('/')

  // Expect a title "to contain" a substring.
  await expect(page).toHaveTitle(/YieldGrid/i)

  // create a locator
  const getStarted = page.getByRole('link', { name: 'Log in' })

  // Expect an attribute "to be strictly equal" to the value.
  if (isMobile) await page.getByRole('button', { name: 'Open menu' }).click()
  await expect(getStarted).toHaveAttribute('href', '/auth/login')

  // Click the get started link.
  await getStarted.click()
  await page.waitForURL('**/auth/login')

  // Expects page to have a heading with the name of Login.
  await expect(page.getByRole('heading', { name: 'Welcome Back' })).toBeVisible()
})

test('password recovery routes expose their own page headings', async ({ page }) => {
  await page.goto('/auth/forgot-password')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Forgot your password?')
  await page.goto('/auth/reset-password?token=synthetic-token&email=farmer%40example.test')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Reset your password')
  await expect(page.getByLabel(/^Email address/i)).toBeDisabled()
})
