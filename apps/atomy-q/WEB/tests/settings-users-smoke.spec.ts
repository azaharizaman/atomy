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

test.beforeEach(async ({ page }) => {
  await page.route(/\/api\/v1\/users(?:\/|\?.*)?$/, async (route) => {
    await fulfillJsonRoute(route, mockUsersResponse);
  });
  await page.route(/\/api\/v1\/roles(?:\/|\?.*)?$/, async (route) => {
    await fulfillJsonRoute(route, mockRolesResponse);
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
});

test('settings users and roles page renders tenant data', async ({ page }) => {
  await page.goto('/settings');

  await expect(page.getByRole('heading', { name: 'Settings' })).toBeVisible();
  const usersAndRolesLink = page.getByRole('link', { name: 'Users & Roles', exact: true });
  await expect(usersAndRolesLink).toBeVisible();
  await usersAndRolesLink.click();

  await expect(page).toHaveURL(/\/settings\/users/);
  await expect(page.getByRole('heading', { name: 'Users & Roles' })).toBeVisible();
  const usersTable = page.getByRole('table');
  await expect(usersTable.getByText('QA User')).toBeVisible();
  await expect(usersTable.getByText('qa.user@atomy.test')).toBeVisible();
  await expect(usersTable.getByText('Buyer')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Invite user' })).toBeVisible();
});
