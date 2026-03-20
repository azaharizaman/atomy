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

function getRequestOrigin(route: {
  request: () => { url: () => string; headers: () => Record<string, string> };
}): string {
  const req = route.request();
  const h = req.headers();
  const origin = h['origin'] ?? h['Origin'];
  if (origin) return origin;
  try {
    return new URL(req.url()).origin;
  } catch {
    const base = process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:3100';
    try {
      return new URL(base).origin;
    } catch {
      return 'http://localhost:3100';
    }
  }
}

async function fulfillJsonRoute(
  route: {
    request: () => { method: () => string; url: () => string; headers: () => Record<string, string> };
    fulfill: (opts: {
      status: number;
      headers?: Record<string, string>;
      contentType?: string;
      body?: string;
    }) => Promise<void>;
  },
  body: unknown,
  status = 200,
): Promise<void> {
  const reqOrigin = getRequestOrigin(route);
  const corsHeaders = buildCorsHeaders(reqOrigin);
  if (route.request().method() === 'OPTIONS') {
    await route.fulfill({ status: 204, headers: corsHeaders });
    return;
  }
  await route.fulfill({
    status,
    headers: corsHeaders,
    contentType: 'application/json',
    body: JSON.stringify(body),
  });
}

test('login with mocked API redirects to dashboard', async ({ page }) => {
  await page.route('**/auth/login', async (route) => {
    await fulfillJsonRoute(route, {
      access_token: 'test-token',
      refresh_token: 'test-refresh',
      token_type: 'Bearer',
      expires_in: 3600,
      user: mockUser,
    });
  });

  await page.route('**/me', async (route) => {
    if (route.request().method() !== 'GET') {
      await route.continue();
      return;
    }
    await fulfillJsonRoute(route, { data: mockUser });
  });

  await page.goto('/login');

  await page.getByLabel('Email').fill(mockUser.email);
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: /log in/i }).click();

  await expect(page).toHaveURL('/');
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
  await expect(page.getByText('Recent Activity')).toBeVisible();
});

test('forgot-password with mocked API shows anti-enumeration success state', async ({ page }) => {
  await page.route('**/auth/forgot-password', async (route) => {
    await fulfillJsonRoute(route, {
      message: 'If an account exists for this email, password reset instructions have been sent.',
    });
  });

  await page.goto('/forgot-password');

  await page.getByLabel('Email').fill('nobody@example.com');
  await page.getByRole('button', { name: /send reset link/i }).click();

  await expect(
    page.locator('.border-emerald-200').getByText(/reset link has been sent/i),
  ).toBeVisible();
  await expect(page.getByRole('button', { name: /send another link/i })).toBeVisible();
});

test('reset-password with mocked API completes and offers return to sign in', async ({ page }) => {
  await page.route('**/auth/reset-password', async (route) => {
    await fulfillJsonRoute(route, { message: 'Password has been reset.' });
  });

  await page.goto('/reset-password?token=e2e-stub-token');

  await page.getByLabel('Email').fill('qa.user@atomy.test');
  await page.getByLabel('Reset token').fill('e2e-stub-token');
  await page.getByLabel('New password', { exact: true }).fill('newpassword123');
  await page.getByLabel('Confirm password').fill('newpassword123');
  await page.getByRole('button', { name: /update password/i }).click();

  await expect(page.getByText(/password has been updated/i)).toBeVisible();
  await expect(page.getByRole('button', { name: /return to sign in/i })).toBeVisible();
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
  const email = 'user1@example.com';
  const password = 'secret';

  await page.goto('/login');

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
