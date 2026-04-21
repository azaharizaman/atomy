import { expect, test } from '@playwright/test';
import { seedAuthSession } from './playwright-auth-bootstrap';
import { fulfillJsonRoute } from './playwright-cors-helpers';

const mockUser = {
  id: 'user-1',
  name: 'QA User',
  email: 'qa.user@atomy.test',
  role: 'buyer',
  tenantId: 'tenant-qa',
};

test.beforeEach(async ({ page }) => {
  await page.route('**/api/v1/auth/login', async (route) => {
    await fulfillJsonRoute(route, {
      access_token: 'test-token',
      refresh_token: 'test-refresh',
      token_type: 'Bearer',
      expires_in: 3600,
      user: mockUser,
    });
  });
  await page.route('**/api/v1/me', async (route) => {
    await fulfillJsonRoute(route, { data: mockUser });
  });
  await page.route('**/api/v1/auth/refresh', async (route) => {
    await fulfillJsonRoute(route, {
      access_token: 'test-token',
      refresh_token: 'test-refresh',
      token_type: 'Bearer',
      expires_in: 3600,
    });
  });
  await page.route('**/api/v1/feature-flags', async (route) => {
    await fulfillJsonRoute(route, { data: { projects: true, tasks: true } });
  });
  await page.route('**/api/v1/rfqs/counts', async (route) => {
    await fulfillJsonRoute(route, {
      data: { draft: 0, published: 0, closed: 0, awarded: 0, cancelled: 0, active: 0, pending: 0, archived: 0 },
    });
  });
  await seedAuthSession(page, mockUser);
  await page.goto('/');
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
