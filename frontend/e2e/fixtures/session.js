import { test as base, expect } from '@playwright/test'
import { E2E } from '../constants'
import { FARMER, BUYER, ADMIN } from './data'

const SESSION_USERS = { farmer: FARMER, buyer: BUYER, admin: ADMIN }

const unexpectedRequests = new WeakMap()

export async function mockSession(
  page,
  { role = 'farmer', verified = true, authenticated = true } = {},
) {
  const unexpected = []
  unexpectedRequests.set(page, unexpected)
  await page.route('**/api/v1/**', (route) => {
    unexpected.push(`${route.request().method()} ${route.request().url()}`)
    return route.fulfill({
      status: E2E.UNEXPECTED_STATUS,
      json: { message: 'Unexpected test API request' },
    })
  })
  await page.route('https://fonts.googleapis.com/**', (route) => route.abort())
  await page.route('https://fonts.gstatic.com/**', (route) => route.abort())
  await page.routeWebSocket(/\/app\//, (socket) => socket.close())
  await page.clock.setFixedTime(new Date(E2E.FIXED_NOW))
  if (!authenticated) return
  const template = SESSION_USERS[role]
  if (!template) throw new Error(`Unknown e2e role: ${String(role)}`)
  const user = {
    ...template,
    email_verified_at: verified ? template.email_verified_at : null,
  }
  await page.addInitScript(() => localStorage.setItem('auth_token', 'synthetic-e2e-token'))
  await page.route('**/api/v1/user', (route) => route.fulfill({ json: user }))
  await page.route('**/api/v1/user/locale', (route) =>
    route.fulfill({ json: { locale: route.request().postDataJSON()?.locale ?? 'en' } }),
  )
  if (role !== 'admin') {
    await page.route('**/api/v1/chat/conversations', (route) =>
      route.fulfill({ json: { data: [] } }),
    )
  }
}

export function assertNoUnexpectedApiRequests(page) {
  expect(unexpectedRequests.get(page) ?? []).toEqual([])
}

export const test = base.extend({
  page: async ({ page }, use) => {
    const pageErrors = []
    page.on('pageerror', (error) => pageErrors.push(error.message))
    await use(page)
    assertNoUnexpectedApiRequests(page)
    expect(pageErrors).toEqual([])
  },
})
export { expect }
