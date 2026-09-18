import { test, expect } from '@playwright/test';

test('has title and can navigate to login', async ({ page }) => {
  await page.goto('/');

  // Expect a title "to contain" a substring.
  await expect(page).toHaveTitle(/YieldGrid/i);

  // create a locator
  const getStarted = page.getByRole('link', { name: 'Log in' });

  // Expect an attribute "to be strictly equal" to the value.
  await expect(getStarted).toHaveAttribute('href', '/auth/login');

  // Click the get started link.
  await getStarted.click();

  // Expects page to have a heading with the name of Login.
  await expect(page.getByRole('heading', { name: 'Welcome Back' })).toBeVisible();
});
