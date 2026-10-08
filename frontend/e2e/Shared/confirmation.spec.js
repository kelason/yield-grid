import { test, expect, mockSession } from '../fixtures/session'
import { E2E } from '../constants'
import { PURCHASES, BUYER } from '../fixtures/data'

async function openBuyer(page) {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/buyer/purchases?**', (route) =>
    route.fulfill({
      json: { data: PURCHASES, meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 } },
    }),
  )
  await page.goto('/dashboard/buyer')
}

test('opening a conversation asks before creating chat state', async ({ page }) => {
  await openBuyer(page)
  let created = 0
  await page.route('**/api/v1/chat/conversations', (route) => {
    if (route.request().method() === 'POST') created += 1
    return route.fulfill({ json: { data: [] } })
  })
  await page.getByRole('button', { name: 'Message farmer' }).click()
  const dialog = page.getByRole('dialog', { name: 'Open conversation?', exact: true })
  await expect(dialog).toBeVisible()
  expect(created).toBe(0)
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(created).toBe(0)
})

test('cancel confirmation preserves state and returns focus to its trigger', async ({ page }) => {
  await openBuyer(page)
  let mutations = 0
  await page.route('**/api/v1/checkout/4/cancel', (route) => {
    mutations += 1
    return route.fulfill({ json: {} })
  })
  const trigger = page.getByRole('button', { name: 'Cancel purchase', exact: true })
  await trigger.focus()
  await trigger.press('Enter')
  const dialog = page.getByRole('dialog', { name: 'Cancel Purchase', exact: true })
  await expect(dialog.getByRole('button', { name: 'Cancel', exact: true })).toBeFocused()
  await page.keyboard.press('Escape')
  await expect(dialog).not.toBeVisible()
  await expect(trigger).toBeFocused()
  expect(mutations).toBe(0)
})

test('pending confirmation sends one request and blocks repeat or dismissal', async ({ page }) => {
  await openBuyer(page)
  let mutations = 0
  let release
  const gate = new Promise((resolve) => {
    release = resolve
  })
  await page.route('**/api/v1/checkout/4/cancel', async (route) => {
    mutations += 1
    await gate
    await route.fulfill({ json: {} })
  })
  await page.getByRole('button', { name: 'Cancel purchase', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Cancel Purchase', exact: true })
  const requested = page.waitForRequest('**/api/v1/checkout/4/cancel')
  await dialog.getByRole('button', { name: 'Confirm', exact: true }).click()
  const request = await requested
  expect(request.method()).toBe('POST')
  await expect(dialog).toHaveAttribute('aria-busy', 'true')
  await expect(dialog.getByRole('button', { name: 'Cancel', exact: true })).toBeDisabled()
  await page.keyboard.press('Escape')
  await expect(dialog).toBeVisible()
  expect(mutations).toBe(1)
  release()
  await expect(dialog).not.toBeVisible()
})

test('nested address confirmation restores the form and preserves its draft', async ({
  page,
  browserName,
}) => {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/users/2', (route) =>
    route.fulfill({
      json: {
        data: { ...BUYER, stats: { total_purchases: 0, total_spent: 0 }, posts: [], addresses: [] },
      },
    }),
  )
  await page.route('**/api/v1/geo/regions', (route) =>
    route.fulfill({ json: { data: [{ code: 'region', name: 'Metro Manila' }] } }),
  )
  await page.route('**/api/v1/geo/provinces?**', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/geo/cities-municipalities?**', (route) =>
    route.fulfill({ json: { data: [{ code: 'city', name: 'Quezon City' }] } }),
  )
  await page.route('**/api/v1/geo/barangays?**', (route) =>
    route.fulfill({ json: { data: [{ code: 'barangay', name: 'Bagumbayan' }] } }),
  )
  await page.route('**/api/v1/geo/center?**', (route) =>
    route.fulfill({ json: { data: { lat: 14.6, lng: 121.07, max_radius_km: 3 } } }),
  )
  await page.route(/tile\.openstreetmap\.org/, (route) => route.abort())
  await page.goto('/dashboard/users/2')
  const trigger = page.getByRole('button', { name: 'Add address', exact: true })
  await trigger.focus()
  await trigger.press('Enter')
  const form = page.getByRole('dialog', { name: 'Add address', exact: true })
  await form.getByLabel('Label', { exact: true }).fill('Farm gate')
  await form.getByRole('combobox', { name: 'Region', exact: true }).selectOption('region')
  await form
    .getByRole('combobox', { name: 'City / Municipality', exact: true })
    .selectOption('city')
  await form.getByRole('combobox', { name: 'Barangay', exact: true }).selectOption('barangay')
  const submit = form.getByRole('button', { name: 'Add address', exact: true })
  await submit.focus()
  await submit.press('Enter')
  const confirmation = page.getByRole('dialog', { name: 'Add this address?', exact: true })
  await expect(confirmation).toBeVisible()
  // Safari tabs past buttons without Full Keyboard Access, so native Tab order
  // can only be asserted where the platform moves focus through every control.
  if (browserName !== 'webkit') {
    await page.keyboard.press('Tab')
    await expect(confirmation.locator(':focus')).toHaveCount(1)
  }
  await page.keyboard.press('Escape')
  await expect(confirmation).not.toBeVisible()
  await expect(submit).toBeFocused()
  await expect(form.getByLabel('Label', { exact: true })).toHaveValue('Farm gate')
  await form.getByRole('button', { name: 'Close dialog' }).click()
  await expect(trigger).toBeFocused()
})

test('public dialogs lock document scrolling and restore it when closed', async ({ page }) => {
  await page.setViewportSize(E2E.MOBILE)
  await page.goto('/')
  await page.getByRole('button', { name: 'Open menu', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Menu', exact: true })
  await expect(dialog).toBeVisible()
  expect(await page.evaluate(() => getComputedStyle(document.body).overflowY)).toBe('hidden')
  await dialog.getByRole('button', { name: 'Close dialog', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(await page.evaluate(() => getComputedStyle(document.body).overflowY)).not.toBe('hidden')
})
