import { expect, test } from '@playwright/test';
import { fulfillJsonRoute } from './playwright-cors-helpers';
import { seedAuthSession } from './playwright-auth-bootstrap';
import { stubAlphaSession } from './alpha-playwright-bootstrap';

test('authenticated dashboard smoke with mocked session', async ({ page }) => {
  await stubAlphaSession(page);

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

/**
 * Login against the real test API (requires the backend at localhost:8000 and seeded DB).
 */
const realApiTest = process.env.E2E_USE_REAL_API === 'true' ? test : test.skip;

realApiTest('login with real API redirects to dashboard', async ({ page, request }) => {
  const email = 'user1@example.com';
  const password = 'secret';

  const loginRes = await request.post('http://localhost:8000/api/v1/auth/login', {
    data: { email, password },
  });
  expect(loginRes.ok()).toBe(true);
  const loginData = await loginRes.json();
  const user = loginData.user;
  expect(user).toBeTruthy();
  expect(typeof user).toBe('object');

  await seedAuthSession(page, user as Parameters<typeof seedAuthSession>[1], {
    token: String(loginData.access_token ?? ''),
    refreshToken: loginData.refresh_token ?? null,
  });

  await page.goto('/');
  await expect(page).toHaveURL('/');
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 5000 });
});
