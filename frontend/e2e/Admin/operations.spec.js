import { test, expect } from '../fixtures/session'
import {
  mockAdminSession,
  overviewPayload,
  paginated,
  adminUserRow,
  adminInquiry,
  inquiryReply,
} from '../fixtures/admin'

const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i

async function mockOverview(page, payload = overviewPayload()) {
  await page.route('**/api/v1/admin/overview', (route) => route.fulfill({ json: payload }))
}

test('overview cards link to their matching filtered lists', async ({ page }) => {
  await mockAdminSession(page)
  await mockOverview(page)
  await page.route('**/api/v1/admin/users?**', (route) =>
    route.fulfill({ json: paginated([adminUserRow()]) }),
  )
  await page.goto('/admin/overview')
  await expect(page.getByTestId('overview-users-total')).toHaveAttribute(
    'href',
    '/admin/users?role=members',
  )
  await expect(page.getByTestId('overview-users-suspended')).toHaveAttribute(
    'href',
    '/admin/users?suspended=suspended',
  )
  await expect(page.getByTestId('overview-content-thread')).toHaveAttribute(
    'href',
    '/admin/content?type=thread',
  )
  await expect(page.getByTestId('overview-inquiries-failed')).toHaveAttribute(
    'href',
    '/admin/inquiries?delivery=failed',
  )
  await expect(page.getByTestId('overview-reports-open')).toHaveAttribute(
    'href',
    '/admin/reports?status=open',
  )
  await expect(page.getByTestId('overview-issues-open')).toHaveAttribute(
    'href',
    '/admin/issues?status=open',
  )
  await page.getByTestId('overview-users-suspended').click()
  await page.waitForURL('**/admin/users?suspended=suspended')
  await expect(page.getByRole('combobox', { name: 'Status' })).toHaveValue('suspended')
})

test('overview failure retries without losing loaded cards', async ({ page }) => {
  await mockAdminSession(page)
  let calls = 0
  await page.route('**/api/v1/admin/overview', (route) => {
    calls += 1
    if (calls === 1) {
      return route.fulfill({ status: 500, json: { message: 'Overview unavailable. Try again.' } })
    }
    return route.fulfill({ json: overviewPayload() })
  })
  await page.goto('/admin/overview')
  await expect(page.getByRole('alert')).toContainText('Overview unavailable. Try again.')
  await page.getByTestId('overview-retry').click()
  await expect(page.getByTestId('overview-users-total')).toContainText('120')
  expect(calls).toBe(2)
})

async function openUsers(page, users) {
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/users?**', (route) => route.fulfill({ json: paginated(users) }))
  await page.goto('/admin/users')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Users')
}

test('suspend confirms once and updates the member badge', async ({ page }) => {
  await openUsers(page, [adminUserRow()])
  const requests = []
  await page.route('**/api/v1/admin/users/11/suspend', (route) => {
    requests.push(route.request().postDataJSON())
    return route.fulfill({
      json: {
        data: adminUserRow({
          suspended_at: '2026-10-07T02:00:00Z',
          suspended_reason: 'Repeated spam reports.',
        }),
      },
    })
  })
  const trigger = page.getByRole('button', { name: 'Suspend Jose Ramos', exact: true })
  await trigger.click()
  const dialog = page.getByRole('dialog', { name: 'Suspend user?', exact: true })
  await dialog.getByLabel('Reason').fill('Repeated spam reports.')
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(requests).toEqual([])
  await trigger.click()
  await dialog.getByLabel('Reason').fill('Repeated spam reports.')
  await dialog.getByRole('button', { name: 'Suspend user', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(requests).toEqual([{ reason: 'Repeated spam reports.' }])
  await expect(page.locator('li span', { hasText: 'Suspended' }).first()).toBeVisible()
  await expect(page.getByText('Repeated spam reports.')).toBeVisible()
})

test('restore confirms once and clears the suspension', async ({ page }) => {
  await openUsers(page, [
    adminUserRow({
      suspended_at: '2026-10-01T00:00:00Z',
      suspended_reason: 'Repeated spam reports.',
    }),
  ])
  const requests = []
  await page.route('**/api/v1/admin/users/11/unsuspend', (route) => {
    requests.push(route.request().postDataJSON())
    return route.fulfill({ json: { data: adminUserRow() } })
  })
  await page.getByRole('button', { name: 'Restore Jose Ramos', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Restore user?', exact: true })
  await dialog.getByLabel('Reason').fill('Appeal accepted after review.')
  await dialog.getByRole('button', { name: 'Restore user', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(requests).toEqual([{ reason: 'Appeal accepted after review.' }])
  await expect(page.locator('li span', { hasText: 'Active' }).first()).toBeVisible()
})

test('suspend conflict keeps the dialog open with the draft intact', async ({ page }) => {
  await openUsers(page, [adminUserRow()])
  let calls = 0
  await page.route('**/api/v1/admin/users/11/suspend', (route) => {
    calls += 1
    if (calls === 1) {
      return route.fulfill({
        status: 409,
        json: { message: 'This user was already suspended by another session.' },
      })
    }
    return route.fulfill({
      json: {
        data: adminUserRow({
          suspended_at: '2026-10-07T02:00:00Z',
          suspended_reason: 'Repeated spam reports.',
        }),
      },
    })
  })
  await page.getByRole('button', { name: 'Suspend Jose Ramos', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Suspend user?', exact: true })
  await dialog.getByLabel('Reason').fill('Repeated spam reports.')
  await dialog.getByRole('button', { name: 'Suspend user', exact: true }).click()
  await expect(
    dialog.getByText('This user was already suspended by another session.'),
  ).toBeVisible()
  await expect(dialog.getByLabel('Reason')).toHaveValue('Repeated spam reports.')
  await dialog.getByRole('button', { name: 'Suspend user', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(calls).toBe(2)
})

test('pending suspension blocks repeat confirmation and dismissal', async ({ page }) => {
  await openUsers(page, [adminUserRow()])
  let calls = 0
  let release
  const gate = new Promise((resolve) => {
    release = resolve
  })
  await page.route('**/api/v1/admin/users/11/suspend', async (route) => {
    calls += 1
    await gate
    await route.fulfill({ json: { data: adminUserRow({ suspended_at: '2026-10-07T02:00:00Z' }) } })
  })
  await page.getByRole('button', { name: 'Suspend Jose Ramos', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Suspend user?', exact: true })
  await dialog.getByLabel('Reason').fill('Repeated spam reports.')
  const requested = page.waitForRequest('**/api/v1/admin/users/11/suspend')
  await dialog.getByRole('button', { name: 'Suspend user', exact: true }).click()
  const request = await requested
  expect(request.method()).toBe('POST')
  expect(request.postDataJSON()).toEqual({ reason: 'Repeated spam reports.' })
  await expect(dialog).toHaveAttribute('aria-busy', 'true')
  await expect(dialog.getByRole('button', { name: 'Cancel', exact: true })).toBeDisabled()
  await page.keyboard.press('Escape')
  await expect(dialog).toBeVisible()
  expect(calls).toBe(1)
  release()
  await expect(dialog).not.toBeVisible()
})

test('suspend dialog traps keyboard focus and restores its trigger', async ({ page }) => {
  await openUsers(page, [adminUserRow()])
  const trigger = page.getByRole('button', { name: 'Suspend Jose Ramos', exact: true })
  await trigger.click()
  const dialog = page.getByRole('dialog', { name: 'Suspend user?', exact: true })
  for (let index = 0; index < 4; index += 1) {
    await page.keyboard.press('Tab')
    await expect(dialog.locator(':focus')).toHaveCount(1)
  }
  await page.keyboard.press('Shift+Tab')
  await expect(dialog.locator(':focus')).toHaveCount(1)
  await page.keyboard.press('Escape')
  await expect(dialog).not.toBeVisible()
  await expect(trigger).toBeFocused()
})

async function openInquiry(page, detail) {
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/contact-messages?**', (route) =>
    route.fulfill({ json: paginated([adminInquiry({ replies: detail.replies ?? [] })]) }),
  )
  await page.route('**/api/v1/admin/contact-messages/7', (route) =>
    route.fulfill({ json: { data: detail } }),
  )
  await page.goto('/admin/inquiries')
  await page.getByRole('button', { name: 'View inquiry from Guest Visitor', exact: true }).click()
  await expect(page.getByTestId('admin-inquiry-detail')).toBeVisible()
}

test('inquiry reply queues once and survives a failed attempt', async ({ page }) => {
  await openInquiry(page, adminInquiry())
  await page.route('**/api/v1/admin/contact-messages/7/read', (route) =>
    route.fulfill({ json: { data: adminInquiry({ status: 'read' }) } }),
  )
  const bodies = []
  let calls = 0
  await page.route('**/api/v1/admin/contact-messages/7/replies', (route) => {
    calls += 1
    const payload = route.request().postDataJSON()
    expect(payload.body).toBe('Thanks for reaching out. Pickup is Friday.')
    expect(payload.client_request_id).toMatch(UUID_PATTERN)
    bodies.push(payload.client_request_id)
    if (calls === 1) {
      return route.fulfill({ status: 500, json: { message: 'Delivery service failed.' } })
    }
    return route.fulfill({
      status: 202,
      json: {
        data: inquiryReply({ body: 'Thanks for reaching out. Pickup is Friday.' }),
      },
    })
  })
  await page.getByRole('button', { name: 'Mark read', exact: true }).click()
  const readDialog = page.getByRole('dialog', { name: 'Mark as read?', exact: true })
  await readDialog.getByRole('button', { name: 'Mark read', exact: true }).click()
  await expect(readDialog).not.toBeVisible()
  const draft = 'Thanks for reaching out. Pickup is Friday.'
  await page.getByLabel('Reply to sender').fill(draft)
  await page.getByRole('button', { name: 'Send reply', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Queue reply?', exact: true })
  await expect(page.getByTestId('admin-inquiry-reply-preview')).toContainText(draft)
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(calls).toBe(0)
  await page.getByRole('button', { name: 'Send reply', exact: true }).click()
  await dialog.getByRole('button', { name: 'Queue reply', exact: true }).click()
  await expect(dialog.getByText('Delivery service failed.')).toBeVisible()
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  await expect(page.getByLabel('Reply to sender')).toHaveValue(draft)
  await page.getByRole('button', { name: 'Send reply', exact: true }).click()
  await dialog.getByRole('button', { name: 'Queue reply', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(calls).toBe(2)
  await expect(page.getByText('Queued', { exact: true }).first()).toBeVisible()
  await expect(page.getByText('A reply is already being delivered.')).toBeVisible()
  await expect(page.getByRole('button', { name: 'Send reply', exact: true })).toBeDisabled()
})

test('failed reply retries to sent before the inquiry closes', async ({ page }) => {
  const failed = inquiryReply({
    delivery_status: 'failed',
    attempts: 3,
    error_code: 'smtp_timeout',
    created_at: '2026-10-07T00:00:00Z',
  })
  await openInquiry(page, adminInquiry({ status: 'read', replies: [failed] }))
  await page.route('**/api/v1/admin/contact-messages/7/replies/21/retry', (route) =>
    route.fulfill({
      status: 202,
      json: { data: inquiryReply({ delivery_status: 'sent', sent_at: '2026-10-07T03:00:00Z' }) },
    }),
  )
  await page.route('**/api/v1/admin/contact-messages/7/close', (route) =>
    route.fulfill({
      json: {
        data: adminInquiry({
          status: 'closed',
          replies: [inquiryReply({ delivery_status: 'sent', sent_at: '2026-10-07T03:00:00Z' })],
        }),
      },
    }),
  )
  await expect(page.getByText('Failed', { exact: true }).first()).toBeVisible()
  await page.getByRole('button', { name: 'Retry delivery of reply 21', exact: true }).click()
  const retryDialog = page.getByRole('dialog', { name: 'Retry delivery?', exact: true })
  await retryDialog.getByRole('button', { name: 'Retry delivery', exact: true }).click()
  await expect(retryDialog).not.toBeVisible()
  await expect(page.getByText('Sent', { exact: true }).first()).toBeVisible()
  await page.getByRole('button', { name: 'Close inquiry', exact: true }).click()
  const closeDialog = page.getByRole('dialog', { name: 'Close inquiry?', exact: true })
  await closeDialog.getByRole('button', { name: 'Close inquiry', exact: true }).click()
  await expect(closeDialog).not.toBeVisible()
  await expect(
    page.getByText('This inquiry is closed. Reopen it to send another reply.'),
  ).toBeVisible()
})
