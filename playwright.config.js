import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/compatibility',
  timeout: 60000,
  reporter: [
    ['list'],
    ['html', { open: 'never' }],
  ],
  use: {
    baseURL: 'http://127.0.0.1:8000',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
  projects: [
    {
      name: 'Desktop Chrome',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'Desktop Firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'Desktop Safari',
      use: { ...devices['Desktop Safari'] },
    },
    {
      name: 'iPhone Safari',
      use: { ...devices['iPhone 12'] },
    },
    {
      name: 'Android Chrome',
      use: { ...devices['Pixel 5'] },
    },
  ],
});