// Dashboard language switching: the toggle lives in the header on desktop
// and inside the navigation drawer on mobile.
export async function switchDashboardLocale(page, isMobile, locale) {
  if (isMobile) {
    await page.getByTestId('mobile-nav-toggle').click()
  }
  await page.getByTestId('language-menu').filter({ visible: true }).click()
  await page.getByTestId(`locale-${locale}`).click()
  if (isMobile) {
    await page.keyboard.press('Escape')
  }
}
