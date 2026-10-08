import { test, expect, mockSession } from '../fixtures/session'
import { E2E } from '../constants'

const CONFLICT = 409
const INVALID = 422
const DEMAND = {
  id: 9,
  title: 'Rice needed',
  crop_name: 'Rice',
  quantity_kg: 100,
  remaining_quantity_kg: 100,
  target_price_per_kg: 50,
  status: 'open',
  pending_offers_count: 1,
}
const OFFER = {
  id: 7,
  quantity_kg: 100,
  price_per_kg: 50,
  total_price: 5000,
  status: 'pending',
  farmer: { id: 1, name: 'Juan' },
  demand: { ...DEMAND, buyer: { id: 2, name: 'Cara' } },
}
const PURCHASE = {
  id: 7,
  payment_status: 'pending',
  cash_payment_status: 'pending',
  payment_method: 'cash',
  total_contract_amount: 50000,
  cash_amount_confirmed: 0,
  currency: 'PHP',
  created_at: '2026-10-01',
  buyer: { id: 2, name: 'Cara' },
  contract: { title: 'Rice Harvest' },
}
const CONTRACT = {
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
  farmer: { name: 'Juan' },
}

async function priceGuide(page) {
  await page.route('**/api/v1/market/prices/guide**', (route) =>
    route.fulfill({ json: { available: false, data: {} } }),
  )
}
async function pendingRequest(page, path, dialog, confirmText, expectedPayload) {
  let count = 0
  let release
  const gate = new Promise((resolve) => {
    release = resolve
  })
  await page.route(`**/api/v1${path}`, async (route) => {
    count += 1
    await gate
    await route.fulfill({
      status: CONFLICT,
      json: { message: 'Quantity changed. Please review and retry.' },
    })
  })
  const requested = page.waitForRequest(`**/api/v1${path}`)
  await dialog.getByRole('button', { name: confirmText, exact: true }).click()
  const request = await requested
  expect(request.method()).toBe('POST')
  if (expectedPayload) expect(request.postDataJSON()).toEqual(expectedPayload)
  await expect(dialog).toHaveAttribute('aria-busy', 'true')
  await expect(dialog.getByRole('button', { name: confirmText, exact: true })).toBeDisabled()
  await page.keyboard.press('Escape')
  await expect(dialog).toBeVisible()
  expect(count).toBe(1)
  release()
  await expect(dialog).not.toBeVisible()
}

for (const [action, status, button, title] of [
  ['accept', 'pending', 'Accept offer', 'Accept this offer?'],
  ['reject', 'pending', 'Reject', 'Reject this offer?'],
  ['cancel', 'accepted', 'Cancel', 'Cancel this offer?'],
]) {
  test(`buyer ${action} requires confirmation and keeps request failures visible`, async ({
    page,
  }) => {
    await mockSession(page, { role: 'buyer' })
    await priceGuide(page)
    await page.route('**/api/v1/buyer/demands', (route) =>
      route.fulfill({ json: { data: [DEMAND] } }),
    )
    await page.route('**/api/v1/buyer/demands/9/offers', (route) =>
      route.fulfill({ json: { data: [{ ...OFFER, status }] } }),
    )
    await page.goto('/dashboard/buyer/demands')
    await page.getByRole('button', { name: 'Show offers' }).click()
    const trigger = page.getByRole('button', { name: button, exact: true })
    await trigger.click()
    const dialog = page.getByRole('dialog', { name: title, exact: true })
    await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
    await expect(trigger).toBeFocused()
    await trigger.click()
    await pendingRequest(
      page,
      `/buyer/offers/7/${action}`,
      dialog,
      action === 'reject' ? 'Reject offer' : action === 'accept' ? 'Accept offer' : 'Cancel offer',
    )
    await expect(page.getByText('Quantity changed. Please review and retry.').first()).toBeVisible()
  })
}

test('farmer withdrawal confirms once and shows failure', async ({ page }) => {
  await mockSession(page)
  await page.route('**/api/v1/farmer/offers', (route) => route.fulfill({ json: { data: [OFFER] } }))
  await page.goto('/dashboard/farmer/offers')
  await page.getByRole('button', { name: 'Withdraw', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Withdraw offer?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  await page.getByRole('button', { name: 'Withdraw', exact: true }).click()
  await pendingRequest(page, '/farmer/offers/7/withdraw', dialog, 'Withdraw')
  await expect(page.getByText('Quantity changed. Please review and retry.').first()).toBeVisible()
})

test('cash review retains the bounded draft after a rejected approval', async ({
  page,
}, testInfo) => {
  await mockSession(page)
  await page.route('**/api/v1/farmer/purchases?**', (route) =>
    route.fulfill({ json: { data: [PURCHASE] } }),
  )
  await page.route('**/api/v1/farmer/purchases/7/approve', (route) => {
    expect(route.request().method()).toBe('POST')
    expect(route.request().postDataJSON()).toEqual({ type: 'partial', amount: 1234.56 })
    return route.fulfill({
      status: INVALID,
      json: { message: 'Please verify the received amount.' },
    })
  })
  await page.goto('/dashboard/cash-approvals')
  await page.getByRole('button', { name: 'Approve 10%', exact: true }).click()
  const form = page.getByRole('dialog', { name: 'Approve Partial (10%) Payment', exact: true })
  await form.getByRole('spinbutton', { name: 'Amount Received (₱)' }).fill('1234.56')
  await form.getByRole('button', { name: 'Review payment', exact: true }).click()
  const confirmation = page.getByRole('dialog', { name: 'Confirm cash payment?', exact: true })
  await confirmation.getByRole('button', { name: 'Cancel', exact: true }).click()
  await expect(form.getByRole('spinbutton')).toHaveValue('1234.56')
  await form.getByRole('button', { name: 'Review payment', exact: true }).click()
  await confirmation.getByRole('button', { name: 'Confirm payment', exact: true }).click()
  await expect(form.getByText('Please verify the received amount.')).toBeVisible()
  await expect(form.getByRole('spinbutton')).toHaveValue('1234.56')
  await page.evaluate(() => document.fonts.ready)
  await page.emulateMedia({ reducedMotion: 'reduce' })
  await page.screenshot({ path: testInfo.outputPath('cash-review.png'), animations: 'disabled' })
})

test('marketplace listing report confirms without starting checkout', async ({ page }) => {
  await mockSession(page, { role: 'buyer' })
  await priceGuide(page)
  await page.route('**/api/v1/user/addresses', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/market/contracts?**', (route) =>
    route.fulfill({
      json: { data: [CONTRACT], meta: { current_page: 1, last_page: 1, total: 1, per_page: 12 } },
    }),
  )
  const reports = []
  await page.route('**/api/v1/reports', (route) => {
    const payload = route.request().postDataJSON()
    expect(route.request().method()).toBe('POST')
    expect(payload).toEqual({
      reportable_type: 'listing',
      reportable_id: '3',
      reason: 'suspected_fraud',
      description: 'The price and photos do not match this harvest.',
    })
    reports.push(payload)
    return route.fulfill({ status: 201, json: { data: { id: '5', status: 'open' } } })
  })
  let checkouts = 0
  await page.route('**/api/v1/market/listing/3/checkout', (route) => {
    checkouts += 1
    return route.fulfill({ json: {} })
  })
  await page.goto('/marketplace')
  await page.getByRole('button', { name: 'Report this listing' }).click()
  const form = page.getByRole('dialog', { name: 'Report content', exact: true })
  await form.getByRole('combobox', { name: 'Reason' }).selectOption('suspected_fraud')
  await form.getByLabel('Description').fill('The price and photos do not match this harvest.')
  await form.getByRole('button', { name: 'Submit report', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Submit this report?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(reports).toEqual([])
  await form.getByRole('button', { name: 'Submit report', exact: true }).click()
  await dialog.getByRole('button', { name: 'Send report', exact: true }).click()
  await expect(form).not.toBeVisible()
  expect(reports).toHaveLength(1)
  expect(checkouts).toBe(0)
  expect(page.url()).toBe(`${E2E.BASE_URL}/marketplace`)
})

test('farmer demand report confirms without placing an offer', async ({ page }) => {
  await mockSession(page)
  await priceGuide(page)
  await page.route('**/api/v1/user/addresses', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/market/demands?**', (route) =>
    route.fulfill({
      json: {
        data: [
          {
            ...DEMAND,
            description: 'Rice needed for a community kitchen.',
            needed_by_date: '2026-11-15',
            buyer: { id: 2, name: 'Cara' },
          },
        ],
        meta: { current_page: 1, last_page: 1, total: 1, per_page: 12 },
      },
    }),
  )
  await page.route('**/api/v1/market/prices/guide/batch**', (route) =>
    route.fulfill({ json: { guides: {} } }),
  )
  const reports = []
  await page.route('**/api/v1/reports', (route) => {
    const payload = route.request().postDataJSON()
    expect(route.request().method()).toBe('POST')
    expect(payload).toEqual({
      reportable_type: 'demand',
      reportable_id: '9',
      reason: 'prohibited_item',
      description: null,
    })
    reports.push(payload)
    return route.fulfill({ status: 201, json: { data: { id: '6', status: 'open' } } })
  })
  let offers = 0
  await page.route('**/api/v1/demands/9/offers', (route) => {
    offers += 1
    return route.fulfill({ json: {} })
  })
  await page.goto('/dashboard/demands/browse')
  await page.getByRole('button', { name: 'Report this demand' }).click()
  const form = page.getByRole('dialog', { name: 'Report content', exact: true })
  await form.getByRole('combobox', { name: 'Reason' }).selectOption('prohibited_item')
  await form.getByRole('button', { name: 'Submit report', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Submit this report?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(reports).toEqual([])
  await form.getByRole('button', { name: 'Submit report', exact: true }).click()
  await dialog.getByRole('button', { name: 'Send report', exact: true }).click()
  await expect(form).not.toBeVisible()
  expect(reports).toHaveLength(1)
  expect(offers).toBe(0)
})

test('marketplace checkout preserves quantity and cash payload without external navigation', async ({
  page,
}, testInfo) => {
  await mockSession(page, { role: 'buyer' })
  await priceGuide(page)
  await page.route('**/api/v1/user/addresses', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/market/contracts?**', (route) =>
    route.fulfill({
      json: { data: [CONTRACT], meta: { current_page: 1, last_page: 1, total: 1, per_page: 12 } },
    }),
  )
  await page.route('**/api/v1/market/items/listing/3', (route) =>
    route.fulfill({ json: { data: CONTRACT } }),
  )
  await page.goto('/dashboard/buyer/marketplace')
  await page.getByRole('button', { name: 'View Details', exact: true }).click()
  const form = page.getByRole('dialog', { name: 'Contract Details', exact: true })
  await form.getByRole('spinbutton', { name: 'Purchase Quantity (kg)' }).fill('25')
  await form.getByRole('radio', { name: 'Cash (Off-Site)' }).check()
  await form.getByRole('button', { name: 'Proceed to Payment', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Request cash payment?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  await expect(form.getByRole('spinbutton')).toHaveValue('25')
  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({ path: testInfo.outputPath('checkout.png'), animations: 'disabled' })
  await form.getByRole('button', { name: 'Proceed to Payment', exact: true }).click()
  await pendingRequest(page, '/market/listing/3/checkout', dialog, 'Request cash payment', {
    quantity_kg: 25,
    payment_option: 'cash',
  })
  await expect(form.getByRole('alert')).toContainText('Quantity changed. Please review and retry.')
  await expect(form.getByRole('spinbutton')).toHaveValue('25')
  expect(page.url()).toBe(`${E2E.BASE_URL}/dashboard/buyer/marketplace`)
})
