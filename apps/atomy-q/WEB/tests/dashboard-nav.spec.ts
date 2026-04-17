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

test.beforeEach(async ({ page }) => {
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
  await page.getByLabel('Email').fill(mockUser.email);
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: /log in/i }).click();
  await expect(page).toHaveURL('/');
});

test('dashboard shows the alpha sidebar after login', async ({ page }) => {
  const sidebar = page.locator('aside').first();

  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
  await expect(sidebar.getByRole('link', { name: 'Dashboard' })).toBeVisible();
  await expect(sidebar.getByRole('button', { name: 'Requisition', exact: true })).toBeVisible();
  await expect(sidebar.getByRole('link', { name: 'Projects' })).toHaveCount(0);
  await expect(sidebar.getByRole('link', { name: 'Task Inbox' })).toHaveCount(0);
  await expect(sidebar.getByRole('link', { name: 'Documents' })).toHaveCount(0);
  await expect(sidebar.getByRole('link', { name: 'Reporting' })).toHaveCount(0);
  await expect(sidebar.getByRole('link', { name: 'Approval Queue' })).toHaveCount(0);
  await expect(sidebar.getByRole('button', { name: 'Settings' })).toHaveCount(0);
  await expect(page.getByText('Atomy-Q').first()).toBeVisible();
});
