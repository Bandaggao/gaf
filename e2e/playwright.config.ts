import { defineConfig, devices } from '@playwright/test';
import path from 'path';

const baseURL = process.env.E2E_BASE_URL ?? 'http://localhost:5173';
const apiURL = process.env.E2E_API_URL ?? 'http://localhost:8000';

export default defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never', outputFolder: '../docs/test-results/html' }]],
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
    actionTimeout: 15_000,
  },
  projects: [
    {
      name: 'chromium',
      testIgnore: /walkthrough\.spec\.ts/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'walkthrough',
      testMatch: /walkthrough\.spec\.ts/,
      timeout: 600_000,
      use: {
        ...devices['Desktop Chrome'],
        video: 'on',
        launchOptions: { slowMo: 600 },
        actionTimeout: 30_000,
        navigationTimeout: 30_000,
        contextOptions: {
          recordVideo: {
            dir: path.join(__dirname, '../docs/user-manual/videos'),
            size: { width: 1280, height: 720 },
          },
        },
      },
    },
  ],
  outputDir: '../docs/test-results/artifacts',
  globalSetup: path.join(__dirname, 'global-setup.ts'),
});

export { apiURL };
