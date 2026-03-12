import { defineConfig, devices } from '@playwright/test';

const isCI = Boolean(process.env.CI);
const port = process.env.PLAYWRIGHT_PORT ?? '3000';
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? `http://localhost:${port}`;
const webServerCommand =
  process.env.PLAYWRIGHT_WEB_SERVER_COMMAND ?? `npm run dev -- --port ${port}`;
// When USE_EXISTING_SERVER=1, assume app is already running (no webServer).
const useExistingServer = process.env.PLAYWRIGHT_USE_EXISTING_SERVER === '1';

export default defineConfig({
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: isCI,
  retries: isCI ? 2 : 0,
  workers: isCI ? 2 : undefined,
  reporter: isCI ? 'line' : 'list',
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  ...(useExistingServer
    ? {}
    : {
        webServer: {
          command: webServerCommand,
          url: baseURL,
          reuseExistingServer: !isCI,
          timeout: 120000,
          env: {
            NEXT_PUBLIC_USE_MOCKS: 'true',
          },
        },
      }),
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
