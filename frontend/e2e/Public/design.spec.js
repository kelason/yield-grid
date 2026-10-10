import { test, expect, mockSession } from '../fixtures/session'
import { E2E } from '../constants'
import en from '../../src/locales/en.js'
import tl from '../../src/locales/tl.js'
import ceb from '../../src/locales/ceb.js'

const LOCALES = { en, tl, ceb }
const FONT_SIZES = ['100%', '200%']
const DESKTOP_BREAKPOINT = { width: 1024, height: 900 }
const NAVBAR_DESKTOP_BREAKPOINT = { width: 1280, height: 900 }
const CONTACT_CONTROL_MIN_WIDTH = 160
const INVALID = 422
const FEATURE_KEYS = [
  'advisor',
  'planning',
  'market',
  'weather',
  'demands',
  'insurance',
  'score',
  'community',
]
const CONTACT_DRAFT = {
  name: 'Jane Doe',
  email: 'jane@example.test',
  subject: 'Help with YieldGrid',
  message: 'Please help me plan my next growing season.',
}
const CONTACT_ERROR = 'Please retry your message.'
const CONTACT_SUCCESS = 'Your message has been sent.'

async function readyPublicPage(page) {
  await page.evaluate(async () => {
    await document.fonts.ready
    await Promise.all(Array.from(document.images, (image) => image.decode()))
  })
}

async function assertNoOverflow(page) {
  const layout = await page.evaluate(() => ({
    path: location.pathname,
    width: innerWidth,
    height: innerHeight,
    fontSize: getComputedStyle(document.documentElement).fontSize,
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  }))
  expect(
    layout.scrollWidth,
    `${layout.path} at ${layout.width}×${layout.height} with ${layout.fontSize} root text`,
  ).toBeLessThanOrEqual(layout.clientWidth)
}

async function assertContactControls(page) {
  for (const selector of ['#contact-name', '#contact-email']) {
    const bounds = await page.locator(selector).boundingBox()
    expect(bounds?.width).toBeGreaterThanOrEqual(CONTACT_CONTROL_MIN_WIDTH)
  }
}

async function assertResponsiveLayout(page, path) {
  for (const viewport of [
    E2E.NARROW,
    E2E.TABLET,
    DESKTOP_BREAKPOINT,
    NAVBAR_DESKTOP_BREAKPOINT,
    E2E.DESKTOP,
  ]) {
    await page.setViewportSize(viewport)
    for (const size of FONT_SIZES) {
      await page.evaluate((fontSize) => {
        document.documentElement.style.fontSize = fontSize
      }, size)
      await assertNoOverflow(page)
      if (path === '/contact') await assertContactControls(page)
    }
  }
}

async function assertFeatureContent(page, messages) {
  for (const feature of FEATURE_KEYS) {
    await expect(
      page.getByRole('heading', { name: messages[`feature_${feature}_title`], exact: true }),
    ).toBeVisible()
    await expect(page.getByText(messages[`feature_${feature}_desc`], { exact: true })).toBeVisible()
  }
}

for (const [locale, messages] of Object.entries(LOCALES)) {
  test(`${locale} public pages preserve content without overflow at narrow tablet and enlarged text`, async ({
    page,
  }) => {
    await mockSession(page, { authenticated: false })
    await page.addInitScript((code) => localStorage.setItem('yieldgrid-locale', code), locale)
    const pages = [
      ['/', messages.public.home.heading_a + messages.public.home.heading_b],
      ['/about', messages.public.about.title],
      ['/contact', messages.public.contact.title],
    ]
    for (const [path, heading] of pages) {
      await page.goto(path)
      await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1)
      await expect(page.getByRole('heading', { level: 1 })).toHaveText(heading)
      await readyPublicPage(page)
      if (path !== '/contact') await assertFeatureContent(page, messages.public)
      await assertResponsiveLayout(page, path)
    }
  })
}

async function openPublicMenu(page) {
  const trigger = page.getByRole('button', { name: 'Open menu', exact: true })
  await trigger.focus()
  await trigger.press('Enter')
  const dialog = page.getByRole('dialog', { name: 'Menu', exact: true })
  await expect(dialog).toBeVisible()
  await expect(dialog).toHaveJSProperty('open', true)
  return { trigger, dialog }
}

test('public menu closes by keyboard and restores focus after navigation', async ({
  page,
  browserName,
}) => {
  await mockSession(page, { authenticated: false })
  await page.setViewportSize(E2E.NARROW)
  await page.goto('/')
  const { trigger, dialog } = await openPublicMenu(page)
  if (browserName !== 'webkit') {
    await page.keyboard.press('Tab')
    await expect(dialog.locator(':focus')).toHaveCount(1)
  }
  await page.keyboard.press('Escape')
  await expect(dialog).not.toBeVisible()
  await expect(trigger).toBeFocused()
  await openPublicMenu(page)
  await dialog.getByRole('button', { name: 'Close dialog', exact: true }).press('Enter')
  await expect(dialog).not.toBeVisible()
  await expect(trigger).toBeFocused()
  await openPublicMenu(page)
  await dialog.getByRole('link', { name: 'About', exact: true }).press('Enter')
  await expect(page).toHaveURL(/\/about$/)
  await expect(dialog).not.toBeVisible()
  await expect(trigger).toBeFocused()
  await assertNoOverflow(page)
})

test('about audience section links to registration without confirmation', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  await page.goto('/about')
  const section = page.getByRole('region', { name: en.public.about.people_title, exact: true })
  const link = section.getByRole('link', { name: en.common.auth.get_started, exact: true })
  await expect(link).toHaveAttribute('href', '/auth/register')
  await link.press('Enter')
  await expect(page).toHaveURL(/\/auth\/register$/)
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Create your account')
  await expect(page.getByRole('dialog')).toHaveCount(0)
})

async function fillContactDraft(page) {
  await page.getByLabel(/^Name/).fill(CONTACT_DRAFT.name)
  await page.getByLabel(/^Email/).fill(CONTACT_DRAFT.email)
  await page.getByLabel('Subject', { exact: true }).fill(CONTACT_DRAFT.subject)
  await page.getByLabel(/^Message/).fill(CONTACT_DRAFT.message)
}

async function assertContactDraft(page, draft) {
  await expect(page.getByLabel(/^Name/)).toHaveValue(draft.name)
  await expect(page.getByLabel(/^Email/)).toHaveValue(draft.email)
  await expect(page.getByLabel('Subject', { exact: true })).toHaveValue(draft.subject)
  await expect(page.getByLabel(/^Message/)).toHaveValue(draft.message)
}

async function requestContactConfirmation(page) {
  const trigger = page.getByRole('button', { name: 'Send Message', exact: true })
  await trigger.focus()
  await trigger.press('Enter')
  const dialog = page.getByRole('dialog', { name: 'Send this message?', exact: true })
  await expect(dialog).toBeVisible()
  return { trigger, dialog }
}

test('contact cancellation retains the draft and sends no request', async ({ page }) => {
  await mockSession(page, { authenticated: false })
  let requests = 0
  await page.route('**/api/v1/contact', (route) => {
    requests += 1
    return route.fulfill({ json: { message: CONTACT_SUCCESS } })
  })
  await page.goto('/contact')
  await expect(
    page.getByRole('heading', { level: 2, name: en.public.contact.form_title, exact: true }),
  ).toBeVisible()
  await fillContactDraft(page)
  const { trigger, dialog } = await requestContactConfirmation(page)
  expect(requests).toBe(0)
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).press('Enter')
  await expect(dialog).not.toBeVisible()
  await expect(trigger).toBeFocused()
  await assertContactDraft(page, CONTACT_DRAFT)
  expect(requests).toBe(0)
})

async function mockContactRetry(page) {
  const requests = []
  await page.route('**/api/v1/contact', (route) => {
    requests.push(route.request().postDataJSON())
    expect(route.request().method()).toBe('POST')
    expect(route.request().postDataJSON()).toEqual(CONTACT_DRAFT)
    return route.fulfill(
      requests.length === 1
        ? { status: INVALID, json: { message: CONTACT_ERROR } }
        : { json: { message: CONTACT_SUCCESS } },
    )
  })
  return requests
}

test('contact failure retains an accessible error and draft for a confirmed retry', async ({
  page,
}) => {
  await mockSession(page, { authenticated: false })
  const requests = await mockContactRetry(page)
  await page.goto('/contact')
  await fillContactDraft(page)
  const { trigger, dialog } = await requestContactConfirmation(page)
  expect(requests).toHaveLength(0)
  await dialog.getByRole('button', { name: 'Send message', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  await expect(page.getByRole('alert').filter({ hasText: CONTACT_ERROR })).toBeVisible()
  await expect(trigger).toBeFocused()
  await assertContactDraft(page, CONTACT_DRAFT)
  await requestContactConfirmation(page)
  expect(requests).toHaveLength(1)
  await dialog.getByRole('button', { name: 'Send message', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  await expect(page.getByRole('alert').filter({ hasText: CONTACT_SUCCESS })).toBeVisible()
  await assertContactDraft(page, { name: '', email: '', subject: '', message: '' })
  expect(requests).toHaveLength(2)
})
