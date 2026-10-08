import { test, expect, mockSession } from '../fixtures/session'

const INVALID = 422
const EMAIL = 'farmer@example.com'
const PASSWORD = 'known-password'

async function guest(page) {
  await mockSession(page, { authenticated: false })
}
test('sign-in confirms once and retains credentials on failure', async ({ page }, testInfo) => {
  await guest(page)
  let requests = 0
  let release
  const gate = new Promise((resolve) => {
    release = resolve
  })
  await page.route('**/api/v1/login', async (route) => {
    requests += 1
    expect(route.request().postDataJSON()).toEqual({
      email: EMAIL,
      password: PASSWORD,
      remember: false,
    })
    await gate
    await route.fulfill({ status: INVALID, json: { message: 'Please check your credentials.' } })
  })
  await page.goto('/auth/login')
  await page.getByRole('textbox', { name: 'Email address' }).fill(EMAIL)
  const password = page.getByLabel(/^Password/)
  await expect(password).toHaveAttribute('autocomplete', 'current-password')
  await expect(password).toHaveAttribute('maxlength', '255')
  await password.fill(PASSWORD)
  const trigger = page.getByRole('button', { name: 'Sign in', exact: true })
  await trigger.focus()
  await trigger.press('Enter')
  const dialog = page.getByRole('dialog', { name: 'Sign in?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(requests).toBe(0)
  await expect(trigger).toBeFocused()
  await trigger.focus()
  await trigger.press('Enter')
  const requested = page.waitForRequest('**/api/v1/login')
  await dialog.getByRole('button', { name: 'Sign in', exact: true }).click()
  await requested
  await expect(dialog).toHaveAttribute('aria-busy', 'true')
  await expect(dialog.getByRole('button', { name: 'Sign in', exact: true })).toBeDisabled()
  expect(requests).toBe(1)
  release()
  await expect(page.getByRole('alert')).toContainText('Please check your credentials.')
  await expect(password).toHaveValue(PASSWORD)
  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({ path: testInfo.outputPath('login-error.png'), animations: 'disabled' })
})

test('registration confirms the selected role and retains validation feedback', async ({
  page,
}) => {
  await guest(page)
  let requests = 0
  await page.route('**/api/v1/register', (route) => {
    requests += 1
    expect(route.request().postDataJSON()).toEqual({
      name: 'Juan Santos',
      email: EMAIL,
      password: PASSWORD,
      password_confirmation: PASSWORD,
      role: 'farmer',
    })
    return route.fulfill({
      status: INVALID,
      json: { message: 'This email is already registered.' },
    })
  })
  await page.goto('/auth/register')
  await page.getByRole('textbox', { name: 'Full Name' }).fill('Juan Santos')
  await page.getByRole('textbox', { name: 'Email address' }).fill(EMAIL)
  await page.getByLabel(/^Password/).fill(PASSWORD)
  await page.getByLabel(/^Confirm Password/).fill(PASSWORD)
  await page.getByRole('radio', { name: 'Farmer', exact: true }).check()
  await page.getByRole('button', { name: 'Create account', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Create account?', exact: true })
  expect(requests).toBe(0)
  await dialog.getByRole('button', { name: 'Create account', exact: true }).click()
  await expect(page.getByRole('alert')).toContainText('This email is already registered.')
  await expect(page.getByRole('textbox', { name: 'Full Name' })).toHaveValue('Juan Santos')
  expect(requests).toBe(1)
})

test('registration offers only trading roles and never admin', async ({ page }) => {
  await guest(page)
  await page.goto('/auth/register')
  const roles = page.getByRole('radio')
  await expect(roles).toHaveCount(2)
  await expect(page.getByRole('radio', { name: 'Farmer', exact: true })).toBeVisible()
  await expect(page.getByRole('radio', { name: 'Buyer', exact: true })).toBeVisible()
  await expect(page.getByRole('radio', { name: 'Admin', exact: true })).toHaveCount(0)
})

test('reset-email remains disabled and new password autocomplete reaches the control', async ({
  page,
}) => {
  await guest(page)
  await page.goto(`/auth/reset-password?email=${encodeURIComponent(EMAIL)}&token=synthetic-token`)
  await expect(page.getByRole('textbox', { name: 'Email address' })).toBeDisabled()
  await expect(page.getByRole('textbox', { name: 'Email address' })).toHaveValue(EMAIL)
  await expect(page.getByLabel(/^New Password/)).toHaveAttribute('autocomplete', 'new-password')
  await expect(page.getByLabel(/^New Password/)).toHaveAttribute('maxlength', '255')
})
