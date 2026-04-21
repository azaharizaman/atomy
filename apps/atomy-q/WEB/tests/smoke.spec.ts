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

async function stubAuth(page: import('@playwright/test').Page) {
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
}

test.beforeEach(async ({ page }) => {
  await stubAuth(page);
});

test('smoke: dashboard renders after login', async ({ page }) => {
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
});

test('smoke: RFQs list loads and can open an RFQ', async ({ page }) => {
  const rfq = {
    id: 'RFQ-E2E-001',
    rfq_number: 'RFQ-E2E-001',
    title: 'Smoke RFQ',
    status: 'active',
    owner: { name: 'QA User', email: mockUser.email },
    deadline: '2026-04-15',
    category: 'IT Hardware',
    estValue: 50000,
    vendorsCount: 2,
    quotesCount: 2,
    savings: '12%',
  };

  await page.route('**/api/v1/rfqs**', async (route) => {
    const url = new URL(route.request().url());
    const pathname = url.pathname;
    if (pathname.endsWith('/overview')) {
      await fulfillJsonRoute(route, {
        data: {
          rfq: {
            id: rfq.id,
            rfq_number: rfq.rfq_number,
            title: rfq.title,
            status: rfq.status,
            owner: { id: 'u1', name: 'QA User', email: mockUser.email },
            deadline: rfq.deadline,
            category: rfq.category,
            estimated_value: rfq.estValue,
            estValue: rfq.estValue,
            savings_percentage: 12,
            savings: rfq.savings,
            vendors_count: rfq.vendorsCount,
            quotes_count: rfq.quotesCount,
            vendorsCount: rfq.vendorsCount,
            quotesCount: rfq.quotesCount,
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
      data: [rfq],
      meta: { total: 1, total_pages: 1, current_page: 1, per_page: 25 },
    });
  });

  await page.goto('/rfqs');
  await expect(page).toHaveURL('/rfqs');
  await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible();

  // Clicking the RFQ workspace route should render the overview view.
  await page.goto('/rfqs/RFQ-E2E-001/overview');
  await expect(page).toHaveURL(/\/rfqs\/.+\/overview$/);
  await expect(page.getByRole('heading', { name: 'Schedule' })).toBeVisible({ timeout: 15000 });
});

test('smoke: Projects list loads', async ({ page }) => {
  const project = {
    id: '01JNE4ZHT9S0VQ7E2GQW1QYJ7B',
    name: 'Smoke Project',
    status: 'planning',
    client_id: 'client-1',
    start_date: '2026-01-01',
    end_date: '2026-02-01',
    budget_type: null,
    completion_percentage: 0,
  };

  await page.route('**/api/v1/projects*', async (route) => {
    await fulfillJsonRoute(route, { data: [project] });
  });

  await page.goto('/projects');

  await expect(page.getByRole('heading', { name: 'Projects' })).toBeVisible();
  await expect(page.getByText('Smoke Project')).toBeVisible();
});

test('smoke: Tasks inbox loads and drawer opens (stubbed API)', async ({ page }) => {
  const task = { id: 'task-1', title: 'Smoke Task', status: 'pending', due_date: null, project_id: null };

  await page.route('**/api/v1/tasks*', async (route) => {
    await fulfillJsonRoute(route, { data: [task] });
  });

  await page.route('**/api/v1/tasks/task-1', async (route) => {
    await fulfillJsonRoute(route, { data: task });
  });

  await page.goto('/tasks');

  await expect(page.getByRole('heading', { name: 'Task Inbox' })).toBeVisible();
  await expect(page.getByRole('table').getByText('Smoke Task')).toBeVisible();

  await page.getByRole('table').getByText('Smoke Task').click();
  await expect(page.getByText('Task detail')).toBeVisible();
  await expect(page.locator('div.fixed.inset-y-0.right-0').getByText('Smoke Task')).toBeVisible();
});
