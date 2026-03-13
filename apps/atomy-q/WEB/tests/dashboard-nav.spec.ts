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
  await page.getByLabel('Tenant ID').fill('tenant-qa');
  await page.getByLabel('Email').fill(mockUser.email);
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: /log in/i }).click();
  await expect(page).toHaveURL('/');
});

test('dashboard shows sidebar and header after login', async ({ page }) => {
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
  await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
  await expect(page.getByText('Requisition', { exact: true })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Documents' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Reporting' })).toBeVisible();
  await expect(page.getByText('Settings', { exact: true })).toBeVisible();
  await expect(page.getByText('Atomy-Q').first()).toBeVisible();
});

test('Documents page shows with layout', async ({ page }) => {
  await page.goto('/documents');
  await expect(page.getByRole('heading', { name: 'Documents', exact: true })).toBeVisible();
  await expect(page.getByText('Documents library')).toBeVisible();
  await expect(page.getByRole('link', { name: 'Documents' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
});

test('Reporting page shows with layout', async ({ page }) => {
  await page.goto('/reporting');
  await expect(page.getByRole('heading', { name: 'Reporting' })).toBeVisible();
  await expect(page.getByText('Reports & analytics')).toBeVisible();
  await expect(page.getByRole('link', { name: 'Reporting' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
});

test('Settings and Users & Roles pages show with layout', async ({ page }) => {
  await page.goto('/settings');
  await expect(page.getByRole('heading', { name: 'Settings' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Users & Roles', exact: true }).first()).toBeVisible();
  await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();

  await page.getByRole('link', { name: 'Users & Roles', exact: true }).first().click();
  await expect(page).toHaveURL(/\/settings\/users/);
  await expect(page.getByRole('heading', { name: 'Users & Roles', exact: true })).toBeVisible();
  await expect(page.getByText(/Invite users and assign roles/)).toBeVisible();
});
