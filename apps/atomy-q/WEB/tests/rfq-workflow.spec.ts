import { expect, test } from '@playwright/test';
import { fulfillJsonRoute } from './playwright-cors-helpers';
import { seedAuthSession } from './playwright-auth-bootstrap';

const mockUser = {
  id: 'user-1',
  name: 'QA User',
  email: 'qa.user@atomy.test',
  role: 'buyer',
  tenantId: 'tenant-qa',
};

async function signInWithMockedAuth(page: import('@playwright/test').Page) {
  await page.route('**/api/v1/feature-flags', async (route) => {
    await fulfillJsonRoute(route, { data: { projects: true, tasks: true } });
  });
  await page.route('**/api/v1/rfqs/counts', async (route) => {
    await fulfillJsonRoute(route, {
      data: { draft: 0, published: 0, closed: 0, awarded: 0, cancelled: 0, active: 1, pending: 0, archived: 0 },
    });
  });
  await page.route('**/api/v1/rfqs**', async (route) => {
    const pathname = new URL(route.request().url()).pathname;
    if (pathname.endsWith('/counts')) {
      await fulfillJsonRoute(route, {
        data: { draft: 0, published: 0, closed: 0, awarded: 0, cancelled: 0, active: 1, pending: 0, archived: 0 },
      });
      return;
    }
    if (pathname.endsWith('/overview')) {
      await fulfillJsonRoute(route, {
        data: {
          rfq: {
            id: 'RFQ-E2E-001',
            rfq_number: 'RFQ-E2E-001',
            title: 'Workflow RFQ',
            status: 'active',
            owner: { id: 'u1', name: 'QA User', email: mockUser.email },
            deadline: '2026-04-15',
            category: 'IT Hardware',
            estimated_value: 50000,
            estValue: 50000,
            savings_percentage: 12,
            savings: '12%',
            vendors_count: 2,
            quotes_count: 2,
            vendorsCount: 2,
            quotesCount: 2,
          },
          expected_quotes: 2,
          normalization: {
            accepted_count: 2,
            total_quotes: 2,
            progress_pct: 100,
            uploaded_count: 0,
            needs_review_count: 0,
            ready_count: 2,
          },
          comparison: null,
          approvals: {
            pending_count: 0,
            approved_count: 0,
            rejected_count: 0,
            overall: 'none',
          },
          activity: [
            {
              id: 'activity-1',
              type: 'comparison',
              actor: 'QA User',
              action: 'Comparison snapshot frozen',
              timestamp: '2026-04-16T10:00:00Z',
            },
          ],
        },
      });
      return;
    }
    if (pathname.endsWith('/activity')) {
      await fulfillJsonRoute(route, {
        data: [
          {
            id: 'activity-1',
            type: 'comparison',
            actor: 'QA User',
            action: 'Comparison snapshot frozen',
            timestamp: '2026-04-16T10:00:00Z',
          },
        ],
      });
      return;
    }
    await fulfillJsonRoute(route, {
      data: [
        {
          id: 'RFQ-E2E-001',
          rfq_number: 'RFQ-E2E-001',
          title: 'Workflow RFQ',
          status: 'active',
          owner: { name: 'QA User', email: mockUser.email },
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
  await seedAuthSession(page, mockUser);
  await page.goto('/rfqs');
  await expect(page).toHaveURL('/rfqs');
}

test('rfq list navigates to rfq workspace overview', async ({ page }) => {
  await signInWithMockedAuth(page);

  await page.goto('/rfqs');
  await expect(page).toHaveURL('/rfqs');
  await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible();
  await page.goto('/rfqs/RFQ-E2E-001/overview');
  await expect(page).toHaveURL(/\/rfqs\/.+\/overview/, { timeout: 15000 });
  await expect(page.getByRole('heading', { name: 'Schedule' })).toBeVisible({ timeout: 15000 });
  await expect(page.getByText(/activity timeline/i)).toBeVisible({ timeout: 15000 });
});
