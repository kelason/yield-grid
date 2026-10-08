import { test, expect } from '../fixtures/session'
import { mockAdminSession, paginated, adminReport, adminContentTarget } from '../fixtures/admin'

async function openReports(page, reports, details = {}) {
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/reports?**', (route) =>
    route.fulfill({ json: paginated(reports) }),
  )
  for (const report of reports) {
    await page.route(`**/api/v1/admin/reports/${report.id}`, (route) =>
      route.fulfill({ json: { data: details[report.id] ?? report } }),
    )
  }
  await page.goto('/admin/reports')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Reports')
}

async function reviewReport(page, id) {
  await page.getByRole('button', { name: `Review report ${id}`, exact: true }).click()
  await expect(page.getByRole('heading', { name: `Report #${id}`, exact: true })).toBeVisible()
}

test('report review moves open to reviewing to resolved-hidden', async ({ page }) => {
  const report = adminReport()
  await openReports(page, [report])
  const requests = []
  await page.route('**/api/v1/admin/reports/5/decision', (route) => {
    requests.push(route.request().postDataJSON())
    const payload = route.request().postDataJSON()
    return route.fulfill({
      json: {
        data: {
          ...report,
          status: payload.status,
          outcome: payload.outcome,
          resolution_note: payload.note,
          version: report.version + requests.length,
          target: { ...report.target, is_hidden: payload.outcome === 'hidden' },
        },
      },
    })
  })
  await reviewReport(page, '5')
  await expect(page.getByText('Cara Santos')).toBeVisible()
  await expect(page.getByText('Watering tips for the dry season').first()).toBeVisible()
  await page.getByTestId('report-decision-reviewing').click()
  const reviewingDialog = page.getByRole('dialog', { name: 'Mark as reviewing?', exact: true })
  await reviewingDialog.getByLabel('Decision note').fill('Checking the thread history.')
  await reviewingDialog.getByRole('button', { name: 'Mark reviewing', exact: true }).click()
  await expect(reviewingDialog).not.toBeVisible()
  await page.getByTestId('report-decision-resolve-hidden').click()
  const resolveDialog = page.getByRole('dialog', {
    name: 'Resolve and hide content?',
    exact: true,
  })
  await resolveDialog.getByLabel('Decision note').fill('Confirmed spam. Hiding thread.')
  await resolveDialog.getByRole('button', { name: 'Resolve and hide', exact: true }).click()
  await expect(resolveDialog).not.toBeVisible()
  expect(requests).toEqual([
    {
      status: 'reviewing',
      outcome: null,
      note: 'Checking the thread history.',
      expected_version: 1,
    },
    {
      status: 'resolved',
      outcome: 'hidden',
      note: 'Confirmed spam. Hiding thread.',
      expected_version: 2,
    },
  ])
  await expect(page.getByText('Outcome: Hidden')).toBeVisible()
  await expect(page.getByTestId('report-decision-resolve-hidden')).toHaveCount(0)
})

test('cancelled decision sends no mutation and empty note is rejected', async ({ page }) => {
  await openReports(page, [adminReport()])
  let calls = 0
  await page.route('**/api/v1/admin/reports/5/decision', (route) => {
    calls += 1
    return route.fulfill({ json: { data: adminReport() } })
  })
  await reviewReport(page, '5')
  await page.getByTestId('report-decision-dismiss').click()
  const dialog = page.getByRole('dialog', { name: 'Dismiss this report?', exact: true })
  await dialog.getByRole('button', { name: 'Dismiss report', exact: true }).click()
  await expect(dialog.getByText('Enter a note for this decision.')).toBeVisible()
  expect(calls).toBe(0)
  await dialog.getByLabel('Decision note').fill('Duplicate of an older report.')
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(calls).toBe(0)
})

test('stale decision reloads the report and keeps the note', async ({ page }) => {
  const report = adminReport()
  const refreshed = adminReport({ status: 'reviewing', version: 2 })
  await openReports(page, [report])
  let detailCalls = 0
  await page.route('**/api/v1/admin/reports/5', (route) => {
    detailCalls += 1
    return route.fulfill({ json: { data: detailCalls === 1 ? report : refreshed } })
  })
  let decisionCalls = 0
  await page.route('**/api/v1/admin/reports/5/decision', (route) => {
    decisionCalls += 1
    if (decisionCalls === 1) {
      return route.fulfill({
        status: 409,
        json: { message: 'Report changed.', current_version: 2 },
      })
    }
    return route.fulfill({
      json: { data: { ...refreshed, status: 'dismissed', version: 3 } },
    })
  })
  await reviewReport(page, '5')
  await page.getByTestId('report-decision-dismiss').click()
  const dialog = page.getByRole('dialog', { name: 'Dismiss this report?', exact: true })
  await dialog.getByLabel('Decision note').fill('Duplicate of an older report.')
  await dialog.getByRole('button', { name: 'Dismiss report', exact: true }).click()
  await expect(
    dialog.getByText(
      'This report changed since it was loaded. Reload for the latest version, then retry. Your note is kept.',
    ),
  ).toBeVisible()
  await expect(dialog.getByLabel('Decision note')).toHaveValue('Duplicate of an older report.')
  await dialog.getByTestId('report-conflict-reload').click()
  await expect(page.getByText('Version 2')).toBeVisible()
  await expect(dialog.getByLabel('Decision note')).toHaveValue('Duplicate of an older report.')
  await dialog.getByRole('button', { name: 'Dismiss report', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(decisionCalls).toBe(2)
})

test('missing target only allows dismissal or no-action resolution', async ({ page }) => {
  const report = adminReport({ target_available: false, target: null })
  await openReports(page, [report])
  let payload = null
  await page.route('**/api/v1/admin/reports/5/decision', (route) => {
    payload = route.request().postDataJSON()
    return route.fulfill({
      json: { data: { ...report, status: 'resolved', outcome: 'no_action', version: 2 } },
    })
  })
  await reviewReport(page, '5')
  await expect(page.getByText('Target unavailable')).toBeVisible()
  await expect(page.getByTestId('report-decision-resolve-hidden')).toHaveCount(0)
  await page.getByTestId('report-decision-resolve-no-action').click()
  const dialog = page.getByRole('dialog', { name: 'Resolve with no action?', exact: true })
  await dialog.getByLabel('Decision note').fill('Target already removed.')
  await dialog.getByRole('button', { name: 'Resolve report', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(payload).toEqual({
    status: 'resolved',
    outcome: 'no_action',
    note: 'Target already removed.',
    expected_version: 1,
  })
  await expect(page.getByText('Outcome: No action')).toBeVisible()
})

test('rate-limited decision shows inline feedback and preserves the note', async ({ page }) => {
  await openReports(page, [adminReport()])
  let calls = 0
  await page.route('**/api/v1/admin/reports/5/decision', (route) => {
    calls += 1
    if (calls === 1) {
      return route.fulfill({
        status: 429,
        json: { message: 'Too many decisions. Wait a minute and retry.' },
      })
    }
    return route.fulfill({
      json: { data: adminReport({ status: 'reviewing', version: 2 }) },
    })
  })
  await reviewReport(page, '5')
  await page.getByTestId('report-decision-reviewing').click()
  const dialog = page.getByRole('dialog', { name: 'Mark as reviewing?', exact: true })
  await dialog.getByLabel('Decision note').fill('Checking the thread history.')
  await dialog.getByRole('button', { name: 'Mark reviewing', exact: true }).click()
  await expect(dialog.getByText('Too many decisions. Wait a minute and retry.')).toBeVisible()
  await expect(dialog.getByLabel('Decision note')).toHaveValue('Checking the thread history.')
  await dialog.getByRole('button', { name: 'Mark reviewing', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(calls).toBe(2)
})

test('slow report detail cannot overwrite a newer selection', async ({ page }) => {
  const first = adminReport()
  const second = adminReport({
    id: '6',
    status: 'reviewing',
    version: 2,
    target_snapshot: { ...first.target_snapshot, title: 'Second reported thread' },
  })
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/reports?**', (route) =>
    route.fulfill({ json: paginated([first, second]) }),
  )
  let releaseFirst
  const gate = new Promise((resolve) => {
    releaseFirst = resolve
  })
  await page.route('**/api/v1/admin/reports/5', async (route) => {
    await gate
    await route.fulfill({ json: { data: first } })
  })
  await page.route('**/api/v1/admin/reports/6', (route) =>
    route.fulfill({ json: { data: second } }),
  )
  await page.goto('/admin/reports')
  await page.getByRole('button', { name: 'Review report 5', exact: true }).click()
  await expect(page.getByText('Loading report')).toBeVisible()
  await page.getByRole('button', { name: 'Review report 6', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Report #6', exact: true })).toBeVisible()
  releaseFirst()
  await page.waitForTimeout(100)
  await expect(page.getByRole('heading', { name: 'Report #6', exact: true })).toBeVisible()
  await expect(page.getByText('Version 2')).toBeVisible()
})

async function openContent(page) {
  const item = adminContentTarget()
  await mockAdminSession(page)
  await page.route('**/api/v1/admin/content/thread?**', (route) =>
    route.fulfill({ json: paginated([item]) }),
  )
  await page.route('**/api/v1/admin/content/thread/8', (route) =>
    route.fulfill({ json: { data: adminContentTarget() } }),
  )
  await page.goto('/admin/content')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Content')
  await page.getByRole('button', { name: 'Inspect Watering tips for the dry season' }).click()
  await expect(page.getByRole('heading', { name: 'Content detail', exact: true })).toBeVisible()
}

test('content hide and restore confirm with an exact reason payload', async ({ page }) => {
  await openContent(page)
  const requests = []
  await page.route('**/api/v1/admin/content/thread/8/hide', (route) => {
    requests.push(['hide', route.request().postDataJSON()])
    return route.fulfill({
      json: {
        data: adminContentTarget({ is_hidden: true, hidden_reason: 'Confirmed spam.' }),
      },
    })
  })
  await page.route('**/api/v1/admin/content/thread/8/restore', (route) => {
    requests.push(['restore', route.request().postDataJSON()])
    return route.fulfill({ json: { data: adminContentTarget() } })
  })
  await page.getByTestId('content-hide').click()
  const hideDialog = page.getByRole('dialog', { name: 'Hide this content?', exact: true })
  await hideDialog.getByLabel('Reason').fill('Confirmed spam.')
  await hideDialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(requests).toEqual([])
  await page.getByTestId('content-hide').click()
  await hideDialog.getByLabel('Reason').fill('Confirmed spam.')
  await hideDialog.getByRole('button', { name: 'Hide content', exact: true }).click()
  await expect(hideDialog).not.toBeVisible()
  await expect(page.getByText('Hidden reason: Confirmed spam.')).toBeVisible()
  await page.getByTestId('content-restore').click()
  const restoreDialog = page.getByRole('dialog', { name: 'Restore this content?', exact: true })
  await restoreDialog.getByLabel('Reason').fill('False positive after review.')
  await restoreDialog.getByRole('button', { name: 'Restore content', exact: true }).click()
  await expect(restoreDialog).not.toBeVisible()
  expect(requests).toEqual([
    ['hide', { reason: 'Confirmed spam.' }],
    ['restore', { reason: 'False positive after review.' }],
  ])
  await expect(page.getByTestId('content-hide')).toBeVisible()
})

test('repeated hide reports the conflict inline and keeps the reason', async ({ page }) => {
  await openContent(page)
  await page.route('**/api/v1/admin/content/thread/8/hide', (route) =>
    route.fulfill({ status: 409, json: { message: 'This content is already hidden.' } }),
  )
  await page.getByTestId('content-hide').click()
  const dialog = page.getByRole('dialog', { name: 'Hide this content?', exact: true })
  await dialog.getByLabel('Reason').fill('Confirmed spam.')
  await dialog.getByRole('button', { name: 'Hide content', exact: true }).click()
  await expect(dialog.getByText('This content is already hidden.')).toBeVisible()
  await expect(dialog.getByLabel('Reason')).toHaveValue('Confirmed spam.')
})
