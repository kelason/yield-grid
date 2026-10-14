import { test, expect } from '../fixtures/session'

test('branding provides a keyboard home link and working browser icons', async ({ page }) => {
  await page.goto('/auth/login')
  await expect(page).toHaveTitle(/YieldGrid/)
  const home = page.getByRole('link', { name: 'YieldGrid home', exact: true })
  await home.focus()
  await home.press('Enter')
  await expect(page).toHaveURL('/')

  const icons = await page
    .locator('link[rel="icon"], link[rel="apple-touch-icon"]')
    .evaluateAll(async (links) =>
      Promise.all(
        links.map(async (link) => {
          const image = new Image()
          image.src = link.href
          await image.decode()
          return { path: new URL(link.href).pathname, width: image.naturalWidth }
        }),
      ),
    )
  expect(icons.map((icon) => icon.path)).toEqual([
    '/favicon.ico',
    '/favicon.svg',
    '/apple-touch-icon.png',
  ])
  for (const icon of icons) expect(icon.width).toBeGreaterThan(0)
})
