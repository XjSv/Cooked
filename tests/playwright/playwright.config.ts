import path from 'path';
import dotenv from 'dotenv';
import { defineConfig, devices } from '@playwright/test';

dotenv.config({ path: path.resolve(__dirname, '.env'), quiet: true });

function getBaseURL(): string {
  if (process.env.WP_BASE_URL) {
    return process.env.WP_BASE_URL;
  }
  if (process.env.DDEV_HOSTNAME || process.env.IS_DDEV_PROJECT) {
    return 'https://dev.mimisrecipes.ddev.site';
  }
  return 'http://localhost:8888';
}

export default defineConfig({
  testDir: './tests',
  outputDir: path.join(__dirname, 'test-results'),
  globalSetup: require.resolve('./global-setup.ts'),
  fullyParallel: false,
  forbidOnly: true,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [
    ['list'],
    ['html', { open: 'never', outputFolder: path.join(__dirname, 'playwright-report') }],
    ['./reporters/a11y-reporter.ts'],
  ],
  use: {
    baseURL: getBaseURL(),
    ignoreHTTPSErrors: true,
    trace: 'on-first-retry',
    actionTimeout: 30000,
    navigationTimeout: 30000,
  },
  timeout: 60000,
  webServer: {
    command: 'echo "Using existing WordPress server"',
    reuseExistingServer: true,
    ignoreHTTPSErrors: true,
    timeout: 120000,
  },
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        launchOptions: {
          args: ['--disable-dev-shm-usage'],
        },
      },
    },
  ],
});
