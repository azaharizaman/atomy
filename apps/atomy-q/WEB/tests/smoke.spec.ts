import { expect, test } from '@playwright/test';
import { fulfillJsonRoute } from './playwright-cors-helpers';
import { stubAlphaSession } from './alpha-playwright-bootstrap';

test.beforeEach(async ({ page }) => {
  await stubAlphaSession(page);
  await expect(page).toHaveURL('/');
});

test('smoke: dashboard renders after login', async ({ page }) => {
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
});

test('smoke: RFQs list loads', async ({ page }) => {
  const rfq = {
    id: 'RFQ-E2E-001',
    rfq_number: 'RFQ-E2E-001',
    title: 'Smoke RFQ',
    status: 'active',
    owner: { name: 'QA User', email: 'qa.user@atomy.test' },
    deadline: '2026-04-15',
    category: 'IT Hardware',
    estValue: 50000,
    vendorsCount: 2,
    quotesCount: 2,
    savings: '12%',
  };

  await page.route('**/api/v1/rfqs**', async (route) => {
    await fulfillJsonRoute(route, {
      data: [rfq],
      meta: { total: 1, total_pages: 1, current_page: 1, per_page: 25 },
    });
  });

  await page.goto('/rfqs');
  await expect(page).toHaveURL('/rfqs');
  await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible();
  await expect(page.getByText('Smoke RFQ')).toBeVisible();
});
