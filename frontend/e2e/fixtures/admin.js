import { mockSession, expect } from './session'

const CREATED_AT = '2026-10-07T01:00:00Z'

export function paginated(items, { page = 1, perPage = 15, total = null } = {}) {
  return {
    data: items,
    links: { first: '', last: '', prev: null, next: null },
    meta: {
      current_page: page,
      last_page: 1,
      per_page: perPage,
      total: total ?? items.length,
    },
  }
}

export function overviewPayload(overrides = {}) {
  return {
    data: {
      users: { members_total: 120, members_suspended: 4 },
      content: {
        thread: { total: 30, visible: 27, hidden: 3 },
        reply: { total: 90, visible: 85, hidden: 5 },
        contract: { total: 12, visible: 11, hidden: 1 },
        listing: { total: 20, visible: 19, hidden: 1 },
        demand: { total: 8, visible: 8, hidden: 0 },
      },
      inquiries: { unread: 3, read: 5, replied: 7, closed: 9, failed_replies: 1 },
      reports: { open: 2, reviewing: 1, resolved: 6, dismissed: 2 },
      issues: { open: 4, in_progress: 2, resolved: 10, closed: 12 },
      generated_at: '2026-10-08T08:00:00+08:00',
      ...overrides,
    },
  }
}

export function adminUserRow(overrides = {}) {
  return {
    id: '11',
    name: 'Jose Ramos',
    email: 'jose.ramos@example.test',
    role: 'farmer',
    email_verified_at: '2026-01-01T00:00:00Z',
    suspended_at: null,
    suspended_reason: null,
    created_at: CREATED_AT,
    ...overrides,
  }
}

export function inquiryReply(overrides = {}) {
  return {
    id: '21',
    message_id: '7',
    admin_id: '3',
    recipient: 'guest@example.test',
    body: 'Thanks for reaching out. We are looking into this.',
    delivery_status: 'queued',
    attempts: 0,
    delivery_generation: 1,
    sent_at: null,
    error_code: null,
    created_at: CREATED_AT,
    ...overrides,
  }
}

export function adminInquiry(overrides = {}) {
  return {
    id: '7',
    name: 'Guest Visitor',
    email: 'guest@example.test',
    subject: 'Harvest pickup question',
    message: 'When can I pick up my prepaid harvest share?',
    status: 'unread',
    replied_at: null,
    created_at: CREATED_AT,
    replies: [],
    ...overrides,
  }
}

export function adminContentTarget(overrides = {}) {
  return {
    type: 'thread',
    id: '8',
    title: 'Watering tips for the dry season',
    body: 'Share practical ways to conserve water on a small farm.',
    category_id: '1',
    reply_count: 0,
    author: { id: '9', name: 'Ana Farmer', role: 'farmer', avatar_url: null },
    is_hidden: false,
    hidden_at: null,
    hidden_by: null,
    hidden_reason: null,
    created_at: CREATED_AT,
    updated_at: CREATED_AT,
    ...overrides,
  }
}

export function adminReport(overrides = {}) {
  return {
    id: '5',
    reportable_type: 'thread',
    reportable_id: '8',
    reason: 'spam',
    description: 'This thread keeps advertising unrelated products.',
    status: 'open',
    version: 1,
    outcome: null,
    resolution_note: null,
    reviewed_by: null,
    reviewed_at: null,
    target_snapshot: {
      type: 'thread',
      id: '8',
      owner_id: '9',
      title: 'Watering tips for the dry season',
      excerpt: 'Share practical ways to conserve water on a small farm.',
      status: null,
      captured_at: CREATED_AT,
    },
    reporter: { id: '2', name: 'Cara Santos', email: 'buyer@example.test' },
    target_available: true,
    target: adminContentTarget(),
    created_at: CREATED_AT,
    updated_at: CREATED_AT,
    ...overrides,
  }
}

export function memberIssue(overrides = {}) {
  return {
    id: '12',
    category: 'marketplace',
    subject: 'Checkout total changed after refresh',
    description: 'The total changed from PHP 500 to PHP 550 after I refreshed the page.',
    page_path: '/dashboard/buyer/marketplace',
    status: 'open',
    resolution: null,
    resolved_at: null,
    created_at: CREATED_AT,
    updated_at: CREATED_AT,
    ...overrides,
  }
}

export function adminIssue(overrides = {}) {
  return {
    id: '12',
    user_id: '2',
    category: 'marketplace',
    subject: 'Checkout total changed after refresh',
    description: 'The total changed from PHP 500 to PHP 550 after I refreshed the page.',
    page_path: '/dashboard/buyer/marketplace',
    status: 'open',
    version: 1,
    resolution: null,
    resolved_by: null,
    resolved_at: null,
    reporter: { id: '2', name: 'Cara Santos', email: 'buyer@example.test' },
    created_at: CREATED_AT,
    updated_at: CREATED_AT,
    ...overrides,
  }
}

export async function mockAdminSession(page, options = {}) {
  await mockSession(page, { role: 'admin', ...options })
}

const ADMIN_FORBIDDEN_PATTERNS = [
  '**/api/v1/chat/**',
  '**/api/v1/forum/**',
  '**/api/v1/market/**',
  '**/api/v1/buyer/**',
  '**/api/v1/farmer/**',
  '**/api/v1/checkout/**',
  '**/api/v1/demands/**',
  '**/api/v1/reports',
]

export async function trackMemberApiCalls(page) {
  const calls = []
  for (const pattern of ADMIN_FORBIDDEN_PATTERNS) {
    await page.route(pattern, (route) => {
      calls.push(`${route.request().method()} ${route.request().url()}`)
      return route.fulfill({ status: 501, json: { message: 'Admin must not call member APIs' } })
    })
  }
  return calls
}

export async function expectNoOverflow(page) {
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
    ),
  ).toBe(true)
}
