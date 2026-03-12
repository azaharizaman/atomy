import { expect, test } from '@playwright/test';

const user = {
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

test('login redirects to dashboard', async ({ page }) => {
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
      body: JSON.stringify({ access_token: 'test-token', user }),
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
      body: JSON.stringify(user),
    });
  });

  await page.goto('/login');
  origin = new URL(page.url()).origin;
  await page.getByLabel('Email address').fill(user.email);
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: /sign in/i }).click();

  await expect(page).toHaveURL('/');
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
  await expect(page.getByText('Recent Activity')).toBeVisible();
});
