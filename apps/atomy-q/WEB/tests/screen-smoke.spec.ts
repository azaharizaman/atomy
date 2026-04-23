import { expect, test } from '@playwright/test';
import { stubAlphaSession } from './alpha-playwright-bootstrap';
import { fulfillJsonRoute } from './playwright-cors-helpers';

test.describe.configure({ mode: 'serial' });

async function stubUsersRolesApi(page: import('@playwright/test').Page) {
  await page.route(/\/api\/v1\/users(?:\/|\?.*)?$/, async (route) => {
    await fulfillJsonRoute(route, {
      data: [
        {
          id: 'user-1',
          name: 'Smoke User',
          email: 'smoke.user@atomy.test',
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
    });
  });

  await page.route(/\/api\/v1\/roles(?:\/|\?.*)?$/, async (route) => {
    await fulfillJsonRoute(route, {
      data: [
        {
          id: 'buyer',
          name: 'Buyer',
          description: 'Buyer access',
          tenant_id: 'tenant-smoke',
          is_system_role: false,
        },
      ],
    });
  });

  await page.route(/\/api\/v1\/rfqs(?:\/|\?.*)?$/, async (route) => {
    await fulfillJsonRoute(route, {
      data: [
        {
          id: 'RFQ-SMOKE-001',
          rfq_number: 'RFQ-SMOKE-001',
          title: 'Screen Smoke RFQ',
          status: 'active',
          owner: { name: 'QA User', email: 'qa.user@atomy.test' },
          deadline: '2026-04-15',
          category: 'IT Hardware',
          estValue: 50000,
          vendorsCount: 2,
          quotesCount: 2,
          savings: '12%',
        },
      ],
      meta: { total: 1, total_pages: 1, current_page: 1, per_page: 25 },
    });
  });
}

test.beforeEach(async ({ page }) => {
  await stubUsersRolesApi(page);
  await stubAlphaSession(page);
  await expect(page).toHaveURL('/', { timeout: 15000 });
});

test('screen smoke: alpha core routes render headings', async ({ page }) => {
  const sidebar = page.getByRole('navigation').first();

  await expect(page.getByRole('heading', { name: /^Dashboard$/ })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
  await expect(sidebar.getByRole('link', { name: 'Dashboard' })).toBeVisible();
  await expect(sidebar.getByRole('button', { name: 'Requisitions', exact: true })).toBeVisible();
  await expect(sidebar.getByRole('link', { name: 'Vendors' })).toBeVisible();

  await page.goto('/rfqs');
  await expect(page.getByRole('heading', { name: /^Requisitions$/ })).toBeVisible({ timeout: 20000 });
});

test('screen smoke: users and roles page renders heading', async ({ page }) => {
  await page.goto('/settings/users');

  await expect(page.getByRole('heading', { name: /^Users & Roles$/ })).toBeVisible({ timeout: 20000 });
});

// Note: RFQ workspace routes require auth. The "Use mock account" shortcut is not persisted across full reloads,
// so we keep the smoke suite to client-side navigations that don't reload.
