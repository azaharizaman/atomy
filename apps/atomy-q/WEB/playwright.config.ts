import fs from 'node:fs';
import path from 'node:path';

import { defineConfig, devices } from '@playwright/test';

function loadEnvValue(filePath: string, key: string): string | undefined {
  if (!fs.existsSync(filePath)) {
    return undefined;
  }

  const lines = fs.readFileSync(filePath, 'utf8').split(/\r?\n/);
  for (const line of lines) {
    const trimmed = line.trim();
    if (trimmed === '' || trimmed.startsWith('#')) {
      continue;
    }

    const equalsIndex = trimmed.indexOf('=');
    if (equalsIndex <= 0) {
      continue;
    }

    const parsedKey = trimmed.slice(0, equalsIndex).trim();
    if (parsedKey !== key) {
      continue;
    }

    const rawValue = trimmed.slice(equalsIndex + 1).trim();
    if (rawValue === '') {
      return '';
    }

    const quote = rawValue[0];
    if (quote === '"' || quote === "'") {
      const closingQuoteIndex = rawValue.lastIndexOf(quote);
      if (closingQuoteIndex > 0) {
        return rawValue.slice(1, closingQuoteIndex);
      }
    }

    const commentIndex = rawValue.indexOf('#');
    const valueWithoutComment = commentIndex >= 0 ? rawValue.slice(0, commentIndex) : rawValue;

    return valueWithoutComment.trim();
  }

  return undefined;
}

const apiEnvPath = path.resolve(__dirname, '../API/.env');
const openRouterCallGapSecondsFromApiEnv = loadEnvValue(apiEnvPath, 'OPENROUTER_E2E_CALL_GAP_SECONDS');

if (process.env.OPENROUTER_E2E_CALL_GAP_SECONDS === undefined && openRouterCallGapSecondsFromApiEnv !== undefined) {
  process.env.OPENROUTER_E2E_CALL_GAP_SECONDS = openRouterCallGapSecondsFromApiEnv;
}

process.env.NEXT_PUBLIC_USE_MOCKS ??= 'false';
process.env.NEXT_PUBLIC_API_URL ??= 'http://localhost:8000/api/v1';
process.env.E2E_API_URL ??= process.env.NEXT_PUBLIC_API_URL;

const isCI = Boolean(process.env.CI);
// Use 3100 by default so Playwright-started server doesn't conflict with dev on 3000.
const port = process.env.PLAYWRIGHT_PORT ?? '3100';
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? `http://localhost:${port}`;
const webServerCommand =
  process.env.PLAYWRIGHT_WEB_SERVER_COMMAND ?? `npm run dev -- --port ${port}`;
// When USE_EXISTING_SERVER=1, assume app is already running (no webServer). Set PLAYWRIGHT_BASE_URL if not on 3100.
const useExistingServer = process.env.PLAYWRIGHT_USE_EXISTING_SERVER === '1';
const webServerEnv: Record<string, string> = {
  NEXT_PUBLIC_USE_MOCKS: process.env.NEXT_PUBLIC_USE_MOCKS ?? 'false',
  NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1',
  E2E_API_URL: process.env.E2E_API_URL ?? process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1',
};

if (process.env.NEXT_PUBLIC_AI_MODE !== undefined) {
  webServerEnv.NEXT_PUBLIC_AI_MODE = process.env.NEXT_PUBLIC_AI_MODE;
}
if (process.env.OPENROUTER_E2E_CALL_GAP_SECONDS !== undefined) {
  webServerEnv.OPENROUTER_E2E_CALL_GAP_SECONDS = process.env.OPENROUTER_E2E_CALL_GAP_SECONDS;
}

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
          env: webServerEnv,
        },
      }),
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
