import { test, expect, mockSession } from '../fixtures/session'
import { mockAdminSession, paginated, memberIssue, adminIssue } from '../fixtures/admin'

const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i

async function openMemberIssues(page, tickets) {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/issues?**', (route) => route.fulfill({ json: paginated(tickets) }))
  await page.goto('/dashboard/issues')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Report an issue')
}

async function fillDraft(page) {
  await page.getByRole('combobox', { name: 'Category' }).selectOption('marketplace')
  await page.getByLabel('Subject').fill('Checkout total changed after refresh')
  await page
    .getByLabel('Description')
    .fill('The total changed from PHP 500 to PHP 550 after I refreshed the page.')
  await page.getByLabel('Related page (optional)').fill('/dashboard/buyer/marketplace')
}

test('member issue submit confirms once and reuses the request key on retry', async ({ page }) => {
  await openMemberIssues(page, [])
  const payloads = []
  let calls = 0
  await page.route('**/api/v1/issues', (route) => {
    if (route.request().method() !== 'POST') {
      return route.fulfill({ json: paginated([]) })
    }
    calls += 1
    const payload = route.request().postDataJSON()
    expect(payload.client_request_id).toMatch(UUID_PATTERN)
    payloads.push(payload)
    if (calls === 1) {
      return route.fulfill({ status: 500, json: { message: 'Ticket service failed.' } })
    }
    return route.fulfill({ status: 201, json: { data: memberIssue() } })
  })
  await fillDraft(page)
  await page.getByRole('button', { name: 'Submit issue', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Submit this issue?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(calls).toBe(0)
  await page.getByRole('button', { name: 'Submit issue', exact: true }).click()
  await dialog.getByRole('button', { name: 'Submit issue', exact: true }).click()
  await expect(page.getByTestId('issue-submit-error')).toContainText('Ticket service failed.')
  await expect(dialog).toBeVisible()
  await expect(page.getByLabel('Subject')).toHaveValue('Checkout total changed after refresh')
  await dialog.getByRole('button', { name: 'Submit issue', exact: true }).click()
  await expect(page.getByTestId('issue-receipt')).toContainText('Issue #12 received.')
  expect(calls).toBe(2)
  expect(payloads[0]).toEqual({
    category: 'marketplace',
    subject: 'Checkout total changed after refresh',
    description: 'The total changed from PHP 500 to PHP 550 after I refreshed the page.',
    page_path: '/dashboard/buyer/marketplace',
    client_request_id: payloads[0].client_request_id,
  })
  expect(payloads[1].client_request_id).toBe(payloads[0].client_request_id)
})

test('member issue history opens detail with the public resolution', async ({ page }) => {
  const ticket = memberIssue({
    status: 'resolved',
    resolution: 'Totals now refresh from the server before payment.',
    resolved_at: '2026-10-07T03:00:00Z',
  })
  await openMemberIssues(page, [ticket])
  await page.route('**/api/v1/issues/12', (route) => route.fulfill({ json: { data: ticket } }))
  await page.getByRole('button', { name: 'Open issue 12', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Issue #12', exact: true })).toBeVisible()
  await expect(page.getByText('Totals now refresh from the server before payment.')).toBeVisible()
  await expect(page.getByText('Related page: /dashboard/buyer/marketplace')).toBeVisible()
})

test('member issue creation reports rate limits inline', async ({ page }) => {
  await openMemberIssues(page, [])
  await page.route('**/api/v1/issues', (route) => {
    if (route.request().method() !== 'POST') {
      return route.fulfill({ json: paginated([]) })
    }
    return route.fulfill({
      status: 429,
      json: { message: 'Too many tickets. Wait a minute and retry.' },
    })
  })
  await fillDraft(page)
  await page.getByRole('button', { name: 'Submit issue', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Submit this issue?', exact: true })
  await dialog.getByRole('button', { name: 'Submit issue', exact: true }).click()
  await expect(page.getByTestId('issue-submit-error')).toContainText(
    'Too many tickets. Wait a minute and retry.',
  )
  await expect(page.getByLabel('Subject')).toHaveValue('Checkout total changed after refresh')
})

async function openAdminIssues(page, tickets, details = {}) {
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/issues?**', (route) =>
    route.fulfill({ json: paginated(tickets) }),
  )
  for (const ticket of tickets) {
    await page.route(`**/api/v1/admin/issues/${ticket.id}`, (route) =>
      route.fulfill({ json: { data: details[ticket.id] ?? ticket } }),
    )
  }
  await page.goto('/admin/issues')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Issues')
}

test('admin resolves an issue with a public resolution', async ({ page }) => {
  const ticket = adminIssue()
  await openAdminIssues(page, [ticket])
  const requests = []
  await page.route('**/api/v1/admin/issues/12/transition', (route) => {
    const payload = route.request().postDataJSON()
    requests.push(payload)
    return route.fulfill({
      json: {
        data: {
          ...ticket,
          status: payload.status,
          resolution: payload.resolution ?? ticket.resolution,
          version: ticket.version + requests.length,
        },
      },
    })
  })
  await page.getByRole('button', { name: 'Review issue 12', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Issue #12', exact: true })).toBeVisible()
  await expect(page.getByText('Reported by: Cara Santos (buyer@example.test)')).toBeVisible()
  await page.getByTestId('issue-transition-in_progress').click()
  const progressDialog = page.getByRole('dialog', { name: 'Start progress?', exact: true })
  await progressDialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(requests).toEqual([])
  await page.getByTestId('issue-transition-in_progress').click()
  await progressDialog.getByTestId('issue-transition-confirm').click()
  await expect(progressDialog).not.toBeVisible()
  await page.getByTestId('issue-transition-resolved').click()
  const resolveDialog = page.getByRole('dialog', { name: 'Resolve this issue?', exact: true })
  await resolveDialog.getByLabel('Resolution').fill('Totals now refresh from the server.')
  await resolveDialog.getByTestId('issue-transition-confirm').click()
  await expect(resolveDialog).not.toBeVisible()
  expect(requests).toEqual([
    { status: 'in_progress', resolution: null, expected_version: 1 },
    {
      status: 'resolved',
      resolution: 'Totals now refresh from the server.',
      expected_version: 2,
    },
  ])
  await expect(page.getByText('Totals now refresh from the server.')).toBeVisible()
})

test('admin reopen clears the resolution after confirmation', async ({ page }) => {
  const ticket = adminIssue({
    status: 'resolved',
    version: 2,
    resolution: 'Totals now refresh from the server.',
  })
  await openAdminIssues(page, [ticket])
  let payload = null
  await page.route('**/api/v1/admin/issues/12/transition', (route) => {
    payload = route.request().postDataJSON()
    return route.fulfill({
      json: {
        data: { ...ticket, status: 'in_progress', resolution: null, version: 3 },
      },
    })
  })
  await page.getByRole('button', { name: 'Review issue 12', exact: true }).click()
  await page.getByTestId('issue-transition-in_progress').click()
  const dialog = page.getByRole('dialog', { name: 'Reopen this issue?', exact: true })
  await dialog.getByTestId('issue-transition-confirm').click()
  await expect(dialog).not.toBeVisible()
  expect(payload).toEqual({ status: 'in_progress', resolution: null, expected_version: 2 })
  await expect(page.getByText('Totals now refresh from the server.')).toHaveCount(0)
})

test('stale admin transition reloads the ticket and keeps the draft', async ({ page }) => {
  const ticket = adminIssue()
  const refreshed = adminIssue({ status: 'in_progress', version: 2 })
  await openAdminIssues(page, [ticket])
  let detailCalls = 0
  await page.route('**/api/v1/admin/issues/12', (route) => {
    detailCalls += 1
    return route.fulfill({ json: { data: detailCalls === 1 ? ticket : refreshed } })
  })
  let transitionCalls = 0
  await page.route('**/api/v1/admin/issues/12/transition', (route) => {
    transitionCalls += 1
    if (transitionCalls === 1) {
      return route.fulfill({
        status: 409,
        json: { message: 'Ticket changed.', current_version: 2 },
      })
    }
    return route.fulfill({
      json: { data: { ...refreshed, status: 'resolved', version: 3 } },
    })
  })
  await page.getByRole('button', { name: 'Review issue 12', exact: true }).click()
  await page.getByTestId('issue-transition-resolved').click()
  const dialog = page.getByRole('dialog', { name: 'Resolve this issue?', exact: true })
  await dialog.getByLabel('Resolution').fill('Totals now refresh from the server.')
  await dialog.getByTestId('issue-transition-confirm').click()
  await expect(
    dialog.getByText(
      'This issue changed since it was loaded. Reload for the latest version, then retry. Your draft is kept.',
    ),
  ).toBeVisible()
  await dialog.getByTestId('issue-conflict-reload').click()
  await expect(dialog.getByLabel('Resolution')).toHaveValue('Totals now refresh from the server.')
  await dialog.getByTestId('issue-transition-confirm').click()
  await expect(dialog).not.toBeVisible()
  expect(transitionCalls).toBe(2)
})
