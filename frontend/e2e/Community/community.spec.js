import { test, expect, mockSession } from '../fixtures/session'
import { BUYER } from '../fixtures/data'
const UNAVAILABLE = 503
const AUTHOR = { id: 9, name: 'Ana Farmer', role: 'farmer', avatar_url: null }
const THREAD = {
  id: 8,
  user_id: 9,
  author: AUTHOR,
  title: 'Watering tips for the dry season',
  body: 'Share practical ways to conserve water on a small farm.',
  category: { id: 1, name: 'Growing', slug: 'growing' },
  tags: [],
  vote_score: 2,
  user_vote: 0,
  reply_count: 0,
  replies: [],
  last_activity_at: '2026-10-07T00:00:00Z',
}
const CONVERSATION = {
  id: 5,
  unread_count: 3,
  last_message_at: '2026-10-07T00:00:00Z',
  other_participant: AUTHOR,
  latest_message: { body: 'Hello from the farm' },
}
async function forumFixtures(page) {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/forum/categories', (route) =>
    route.fulfill({
      json: {
        data: [
          {
            id: 1,
            slug: 'growing',
            name: 'Growing',
            description: 'Practical growing advice',
            threads_count: 1,
          },
        ],
      },
    }),
  )
  await page.route('**/api/v1/forum/tags', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/forum/threads?**', (route) =>
    route.fulfill({ json: { data: [THREAD], meta: { current_page: 1, last_page: 1, total: 1 } } }),
  )
  await page.route('**/api/v1/forum/threads/8', (route) =>
    route.fulfill({ json: { data: THREAD } }),
  )
}
async function noOverflow(page) {
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
}
test('discussion replies confirm, retain a failed draft and escape user content', async ({
  page,
}, testInfo) => {
  await forumFixtures(page)
  let posts = 0
  let fail = true
  const draft = '<img src=x onerror=alert(1)> Practical reply.'
  await page.route('**/api/v1/forum/threads/8/replies', (route) => {
    posts += 1
    expect(route.request().postDataJSON()).toEqual({ body: draft, is_anonymous: false })
    return route.fulfill({
      status: fail ? UNAVAILABLE : 201,
      json: fail
        ? { message: 'Reply service unavailable.' }
        : {
            data: {
              id: 10,
              author: { ...BUYER, avatar_url: null },
              body: draft,
              vote_score: 0,
              user_vote: 0,
              is_accepted: false,
              children: [],
              created_at: '2026-10-07T01:00:00Z',
            },
          },
    })
  })
  await page.goto('/dashboard/community/thread/8')
  await page.getByLabel('Your Reply').fill(draft)
  await page.getByRole('button', { name: 'Post Reply', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Post this reply?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(posts).toBe(0)
  await page.getByRole('button', { name: 'Post Reply', exact: true }).click()
  await dialog.getByRole('button', { name: 'Post reply', exact: true }).click()
  await expect(
    page.getByRole('alert').filter({ hasText: 'Reply service unavailable.' }).last(),
  ).toBeVisible()
  await expect(page.getByLabel('Your Reply')).toHaveValue(draft)
  fail = false
  await page.getByRole('button', { name: 'Post Reply', exact: true }).click()
  await dialog.getByRole('button', { name: 'Post reply', exact: true }).click()
  await expect(page.getByLabel('Your Reply')).toHaveValue('')
  await expect(page.getByText(draft, { exact: true })).toBeVisible()
  await expect(page.locator('img[src="x"]')).toHaveCount(0)
  expect(posts).toBe(2)
  await expect(page.getByRole('heading', { name: '1 Replies', exact: true })).toBeVisible()
  await noOverflow(page)
  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({ path: testInfo.outputPath('discussion.png'), animations: 'disabled' })
})
test('chat keyboard send confirms and preserves failed drafts while clearing unread', async ({
  page,
}, testInfo) => {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/chat/conversations', (route) =>
    route.fulfill({ json: { data: [CONVERSATION] } }),
  )
  await page.route('**/api/v1/chat/conversations/5?**', (route) =>
    route.fulfill({ json: { data: [], meta: {} } }),
  )
  let posts = 0
  let fail = true
  const draft = 'Harvest details '.repeat(15)
  await page.route('**/api/v1/chat/conversations/5/messages', (route) => {
    posts += 1
    expect(route.request().postDataJSON()).toEqual({ body: draft })
    return route.fulfill({
      status: fail ? UNAVAILABLE : 201,
      json: fail
        ? { message: 'Please retry sending.' }
        : {
            data: {
              id: 11,
              conversation_id: 5,
              body: draft,
              is_own: true,
              created_at: '2026-10-07T01:00:00Z',
            },
          },
    })
  })
  await page.goto('/dashboard/chat?conversation=5')
  const message = page.getByLabel('Message', { exact: true })
  await message.fill(draft)
  await message.press('Enter')
  const dialog = page.getByRole('dialog', { name: 'Send this message?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(posts).toBe(0)
  await message.press('Enter')
  await dialog.getByRole('button', { name: 'Send message', exact: true }).click()
  await expect(page.getByRole('alert').filter({ hasText: 'Please retry sending.' })).toBeVisible()
  await expect(message).toHaveValue(draft)
  fail = false
  await message.press('Enter')
  await dialog.getByRole('button', { name: 'Send message', exact: true }).click()
  await expect(message).toHaveValue('')
  await expect(
    page.getByRole('log', { name: 'Conversation messages' }).getByText(draft, { exact: true }),
  ).toBeVisible()
  expect(posts).toBe(2)
  if (testInfo.project.name === 'mobile-chromium')
    await page.getByRole('button', { name: 'Back to conversations', exact: true }).click()
  await expect(page.getByLabel('3 unread messages', { exact: true })).toHaveCount(0)
  await noOverflow(page)
  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({ path: testInfo.outputPath('chat.png'), animations: 'disabled' })
})
test('discussion report confirms once and retains the draft on failure', async ({ page }) => {
  await forumFixtures(page)
  const requests = []
  let fail = true
  await page.route('**/api/v1/reports', (route) => {
    const payload = route.request().postDataJSON()
    expect(route.request().method()).toBe('POST')
    expect(payload).toEqual({
      reportable_type: 'thread',
      reportable_id: '8',
      reason: 'spam',
      description: 'This thread keeps advertising unrelated products.',
    })
    requests.push(payload)
    return route.fulfill({
      status: fail ? UNAVAILABLE : 201,
      json: fail
        ? { message: 'Report service unavailable.' }
        : { data: { id: '5', status: 'open' } },
    })
  })
  await page.goto('/dashboard/community/thread/8')
  await page.getByRole('button', { name: 'Report this discussion' }).click()
  const form = page.getByRole('dialog', { name: 'Report content', exact: true })
  await form.getByRole('combobox', { name: 'Reason' }).selectOption('spam')
  await form.getByLabel('Description').fill('This thread keeps advertising unrelated products.')
  await form.getByRole('button', { name: 'Submit report', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Submit this report?', exact: true })
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
  expect(requests).toEqual([])
  await form.getByRole('button', { name: 'Submit report', exact: true }).click()
  await dialog.getByRole('button', { name: 'Send report', exact: true }).click()
  await expect(form.getByRole('alert')).toContainText('Report service unavailable.')
  await expect(form.getByLabel('Description')).toHaveValue(
    'This thread keeps advertising unrelated products.',
  )
  fail = false
  await form.getByRole('button', { name: 'Submit report', exact: true }).click()
  await dialog.getByRole('button', { name: 'Send report', exact: true }).click()
  await expect(form).not.toBeVisible()
  expect(requests).toHaveLength(2)
  await noOverflow(page)
})
test('owners do not see report controls on their own replies', async ({ page }) => {
  await mockSession(page, { role: 'buyer' })
  await page.route('**/api/v1/forum/categories', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/forum/tags', (route) => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/v1/forum/threads?**', (route) =>
    route.fulfill({ json: { data: [THREAD], meta: { current_page: 1, last_page: 1, total: 1 } } }),
  )
  const ownReply = {
    id: 21,
    thread_id: 8,
    author: { id: 2, name: 'Cara Santos', role: 'buyer', avatar_url: null },
    body: 'My own reply about watering schedules.',
    vote_score: 0,
    user_vote: 0,
    is_accepted: false,
    children: [],
    created_at: '2026-10-07T01:00:00Z',
  }
  const otherReply = {
    ...ownReply,
    id: 22,
    author: AUTHOR,
    body: 'A neighbor reply about watering schedules.',
  }
  await page.route('**/api/v1/forum/threads/8', (route) =>
    route.fulfill({
      json: { data: { ...THREAD, reply_count: 2, replies: [ownReply, otherReply] } },
    }),
  )
  await page.goto('/dashboard/community/thread/8')
  await expect(page.getByText('My own reply about watering schedules.')).toBeVisible()
  await expect(page.getByText('A neighbor reply about watering schedules.')).toBeVisible()
  const replyCards = page.locator('[aria-label="Report this reply"]')
  await expect(replyCards).toHaveCount(1)
  await noOverflow(page)
})
test('community discussion creation uses nested confirmation and retains failure details', async ({
  page,
}) => {
  await forumFixtures(page)
  let posts = 0
  await page.route('**/api/v1/forum/threads', (route) => {
    posts += 1
    expect(route.request().postDataJSON()).toEqual({
      title: 'Water saving practices',
      body: 'Which watering practices work best during dry weather?',
      category_id: '1',
      tag_ids: [],
      is_anonymous: false,
    })
    return route.fulfill({
      status: UNAVAILABLE,
      json: { message: 'Please retry this discussion.' },
    })
  })
  await page.goto('/dashboard/community')
  await page.getByRole('button', { name: 'New Discussion', exact: true }).click()
  const editor = page.getByRole('dialog', { name: 'New community post', exact: true })
  await editor.getByLabel(/^Title/).fill('Water saving practices')
  await editor.getByRole('combobox', { name: 'Category' }).selectOption('1')
  await editor
    .getByLabel('Details', { exact: true })
    .fill('Which watering practices work best during dry weather?')
  await editor.getByRole('button', { name: 'Post Discussion', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: 'Post this discussion?', exact: true })
  await dialog.getByRole('button', { name: 'Post discussion', exact: true }).click()
  await expect(editor.getByRole('alert')).toContainText('Please retry this discussion.')
  await expect(editor.getByLabel(/^Title/)).toHaveValue('Water saving practices')
  expect(posts).toBe(1)
  await noOverflow(page)
})
