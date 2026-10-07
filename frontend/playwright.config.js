/* global process */
import { defineConfig, devices } from '@playwright/test'
import { E2E } from './e2e/constants'

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : 2,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: E2E.BASE_URL,
    locale: 'en-PH',
    timezoneId: 'Asia/Manila',
    trace: 'on-first-retry',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'], viewport: E2E.DESKTOP } },
    { name: 'mobile-chromium', use: { ...devices['Pixel 7'], viewport: E2E.MOBILE } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'], viewport: E2E.DESKTOP } },
    { name: 'webkit', use: { ...devices['Desktop Safari'], viewport: E2E.DESKTOP } },
  ],
  webServer: {
    command: 'npm run dev -- --host 127.0.0.1 --port 4173 --strictPort',
    url: E2E.BASE_URL,
    reuseExistingServer: false,
    env: {
      E2E_RUN: '1',
      VITE_API_URL: E2E.API_URL,
      VITE_REVERB_HOST: '127.0.0.1',
      VITE_REVERB_APP_KEY: 'synthetic-e2e-key',
    },
  },
})
