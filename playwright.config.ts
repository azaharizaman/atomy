import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for Nexus monorepo E2E testing.
 * 
 * This configuration supports testing both:
 * - apps/laravel-nexus-saas (Laravel SaaS application)
 * - apps/atomy-api (Symfony API application)
 * 
 * @see https://playwright.dev/docs/test-configuration
 */

// Environment variables for app URLs
const LARAVEL_BASE_URL = process.env.LARAVEL_BASE_URL || 'http://localhost:8000';
const API_BASE_URL = process.env.API_BASE_URL || 'http://localhost:8001';

export default defineConfig({
  testDir: './e2e',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : undefined,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html', { open: 'never' }],
    ['list'],
  ],
  /* Global timeout for each test */
  timeout: 30000,
  /* Shared settings for all the projects below. */
  use: {
    /* Collect trace when retrying the failed test. */
    trace: 'on-first-retry',
    /* Screenshot on failure */
    screenshot: 'only-on-failure',
    /* Video on failure */
    video: 'retain-on-failure',
  },

  /* Configure projects for each application */
  projects: [
    // =========================================
    // Laravel Nexus SaaS Tests
    // =========================================
    {
      name: 'laravel-nexus-saas',
      testDir: './e2e/laravel-nexus-saas',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: LARAVEL_BASE_URL,
      },
    },
    {
      name: 'laravel-nexus-saas-mobile',
      testDir: './e2e/laravel-nexus-saas',
      use: {
        ...devices['iPhone 13'],
        baseURL: LARAVEL_BASE_URL,
      },
    },

    // =========================================
    // Atomy API Tests
    // =========================================
    {
      name: 'atomy-api',
      testDir: './e2e/atomy-api',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: API_BASE_URL,
        // API tests typically don't need a browser UI
        extraHTTPHeaders: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      },
    },

    // =========================================
    // Cross-browser testing (optional, for full test runs)
    // =========================================
    // {
    //   name: 'firefox',
    //   testDir: './e2e/laravel-nexus-saas',
    //   use: {
    //     ...devices['Desktop Firefox'],
    //     baseURL: LARAVEL_BASE_URL,
    //   },
    // },
    // {
    //   name: 'webkit',
    //   testDir: './e2e/laravel-nexus-saas',
    //   use: {
    //     ...devices['Desktop Safari'],
    //     baseURL: LARAVEL_BASE_URL,
    //   },
    // },
  ],

  /* Run local dev servers before starting the tests */
  // Uncomment for local development - CI handles this separately
  // webServer: [
  //   {
  //     command: 'cd apps/laravel-nexus-saas && php artisan serve --port=8000',
  //     url: LARAVEL_BASE_URL,
  //     reuseExistingServer: !process.env.CI,
  //     timeout: 120000,
  //   },
  //   {
  //     command: 'cd apps/atomy-api && php -S localhost:8001 -t public',
  //     url: API_BASE_URL,
  //     reuseExistingServer: !process.env.CI,
  //     timeout: 120000,
  //   },
  // ],
});
