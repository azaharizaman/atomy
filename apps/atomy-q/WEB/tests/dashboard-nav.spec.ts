import { expect, test } from '@playwright/test';

const mockUser = {
  id: 'user-1',
  name: 'QA User',
  email: 'qa.user@atomy.test',
  role: 'buyer',
  tenantId: 'tenant-qa',
};

const mockUsersResponse = {
  data: [
    {
      id: 'user-1',
      name: 'QA User',
      email: 'qa.user@atomy.test',
      status: 'active',
      role: 'buyer',
      created_at: '2026-04-17T00:00:00Z',
      last_login_at: null,
    },
  ],
  meta: {
    current_page: 1,
    per_page: 10,
    total: 1,
  },
};

const mockRolesResponse = {
  data: [
    {
      id: 'buyer',
      name: 'Buyer',
      description: 'Buyer access',
      tenant_id: 'tenant-qa',
      is_system_role: false,
    },
  ],
};

const buildCorsHeaders = (origin: string) => ({
  'access-control-allow-origin': origin,
  'access-control-allow-credentials': 'true',
  'access-control-allow-headers': 'Content-Type, Authorization',
  'access-control-allow-methods': 'GET,POST,OPTIONS',
});

test.beforeEach(async ({ page }) => {
  const originRef = { current: 'http://localhost:3000' };

  page.on('framenavigated', () => {
    try {
      originRef.current = new URL(page.url()).origin;
    } catch {
      // ignore
    }
  });

  await page.route('**/api/v1/auth/login', async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
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
    const corsHeaders = buildCorsHeaders(originRef.current);
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
  await page.route('**/api/v1/auth/refresh', async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
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
      }),
    });
  });
  await page.route(/\/api\/v1\/users(?:\/|\?.*)?$/, async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify(mockUsersResponse),
    });
  });
  await page.route(/\/api\/v1\/roles(?:\/|\?.*)?$/, async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify(mockRolesResponse),
    });
  });
  await page.goto('/login');
  originRef.current = new URL(page.url()).origin;
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
  await expect(page.getByText('Atomy-Q').first()).toBeVisible();
});

test('dashboard users and roles page renders live tenant data', async ({ page }) => {
  await page.goto('/settings');

  const usersAndRolesLink = page.getByRole('link', { name: 'Users & Roles', exact: true });
  await expect(usersAndRolesLink).toBeVisible();
  await usersAndRolesLink.click();

  await expect(page).toHaveURL(/\/settings\/users/);
  await expect(page.getByRole('heading', { name: 'Users & Roles' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Invite user' })).toBeVisible();
});
