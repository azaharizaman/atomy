import { expect, test } from '@playwright/test';

const mockUser = {
  id: 'user-1',
  name: 'QA User',
  email: 'qa.user@atomy.test',
  role: 'buyer',
  tenantId: 'tenant-qa',
};

const buildCorsHeaders = (origin: string) => ({
  'access-control-allow-origin': origin,
  'access-control-allow-credentials': 'true',
  'access-control-allow-headers': 'Content-Type, Authorization',
  'access-control-allow-methods': 'GET,POST,OPTIONS',
});

test('login with mocked API redirects to dashboard', async ({ page }) => {
  let origin = 'http://localhost:3000';

  await page.route('**/api/v1/auth/login', async (route) => {
    const corsHeaders = buildCorsHeaders(origin);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify({
        access_token: 'test-token',
        refresh_token: 'test-refresh',
        token_type: 'Bearer',
        expires_in: 3600,
        user: mockUser,
      }),
    });
  });

  await page.route('**/api/v1/me', async (route) => {
    const corsHeaders = buildCorsHeaders(origin);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify({ data: mockUser }),
    });
  });

  await page.goto('/login');
  origin = new URL(page.url()).origin;

  await page.getByLabel('Tenant ID').fill('tenant-qa');
  await page.getByLabel('Email').fill(mockUser.email);
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: /log in/i }).click();

  await expect(page).toHaveURL('/');
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
  await expect(page.getByText('Recent Activity')).toBeVisible();
});

const useRealApi = process.env.E2E_USE_REAL_API === '1';

/**
 * Login against the real API (requires API running, e.g. localhost:8001, and seeded DB).
 * Run with: E2E_USE_REAL_API=1 npm run test:e2e -- tests/auth.spec.ts -g "real API"
 */
test('login with real API redirects to dashboard', async ({ page }, testInfo) => {
  if (!useRealApi) {
    testInfo.skip();
    return;
  }
  const tenantId = '01KKGX0YT42CRG3XFB1E24SH1A';
  const email = 'user1@example.com';
  const password = 'secret';

  await page.goto('/login');

  await page.getByLabel('Tenant ID').fill(tenantId);
  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Password').fill(password);
  await page.getByRole('button', { name: /log in/i }).click();

  // Wait for either redirect to dashboard or an error to appear
  const errorBox = page.locator('[class*="border-red-200"]').filter({ hasText: /.+/ });
  const gotRedirect = await page.waitForURL(/\/(?!login)/, { timeout: 10000 }).catch(() => false);
  if (!gotRedirect) {
    const errorText = await errorBox.textContent().catch(() => '');
    throw new Error(
      `Login did not redirect. Ensure API is running with JWT_SECRET set and returns \`user\` in login response. ${errorText ? `Page error: ${errorText}` : ''}`
    );
  }
  await expect(page).toHaveURL('/');
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 5000 });
});
