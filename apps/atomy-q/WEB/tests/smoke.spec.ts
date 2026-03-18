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

function getRequestOrigin(route: { request: () => { url: () => string; headers: () => Record<string, string> } }): string {
  const req = route.request();
  const h = req.headers();
  const origin = h['origin'] ?? h['Origin'];
  if (origin) return origin;
  try {
    return new URL(req.url()).origin;
  } catch {
    return 'http://localhost:3000';
  }
}

async function fulfillJsonRoute(
  route: {
    request: () => { method: () => string; url: () => string; headers: () => Record<string, string> };
    fulfill: (opts: { status: number; headers?: Record<string, string>; contentType?: string; body?: string }) => Promise<void>;
  },
  body: unknown,
  status = 200
): Promise<void> {
  const reqOrigin = getRequestOrigin(route);
  const corsHeaders = buildCorsHeaders(reqOrigin);
  if (route.request().method() === 'OPTIONS') {
    await route.fulfill({ status: 204, headers: corsHeaders });
    return;
  }
  await route.fulfill({
    status,
    headers: corsHeaders,
    contentType: 'application/json',
    body: JSON.stringify(body),
  });
}

async function stubAuth(page: import('@playwright/test').Page) {
  await page.route('**/auth/login**', async (route) => {
    await fulfillJsonRoute(route, {
      access_token: 'test-token',
      refresh_token: 'test-refresh',
      token_type: 'Bearer',
      expires_in: 3600,
      user: mockUser,
    });
  });

  await page.route('**/me**', async (route) => {
    await fulfillJsonRoute(route, { data: mockUser });
  });

  await page.goto('/login');

  await page.getByLabel('Tenant ID').fill('tenant-qa');
  await page.getByLabel('Email').fill(mockUser.email);
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: /log in/i }).click();
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
  await page.goto('/rfqs');
  await expect(page).toHaveURL('/rfqs');
  await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible();

  // Seed mode list should have rows; clicking any row should navigate to an RFQ workspace route.
  await page.locator('table').locator('tbody tr').first().click();
  await expect(page).toHaveURL(/\/rfqs\/.+\/overview$/);
  await expect(page.getByRole('heading', { name: 'Overview' })).toBeVisible();
});

test('smoke: Projects list and detail load (stubbed API)', async ({ page }) => {
  const project = {
    id: '01JNE4ZHT9S0VQ7E2GQW1QYJ7B',
    name: 'Smoke Project',
    status: 'planning',
    client_id: 'client-1',
    start_date: '2026-01-01',
    end_date: '2026-02-01',
  };

  await page.route('**/api/v1/projects*', async (route) => {
    await fulfillJsonRoute(route, { data: [project] });
  });

  await page.route(`**/api/v1/projects/${project.id}`, async (route) => {
    await fulfillJsonRoute(route, { data: project });
  });

  await page.route(`**/api/v1/projects/${project.id}/health`, async (route) => {
    await fulfillJsonRoute(route, { data: { project_id: project.id, overall_score: 80 } });
  });

  await page.route(`**/api/v1/projects/${project.id}/rfqs`, async (route) => {
    await fulfillJsonRoute(route, { data: [] });
  });

  await page.route(`**/api/v1/projects/${project.id}/tasks`, async (route) => {
    await fulfillJsonRoute(route, { data: [] });
  });

  await page.goto('/projects');

  await expect(page.getByRole('heading', { name: 'Projects' })).toBeVisible();
  await expect(page.getByText('Smoke Project')).toBeVisible();

  await page.getByText('Smoke Project').click();
  await expect(page).toHaveURL(new RegExp(`/projects/${project.id}$`));
  await expect(page.getByRole('heading', { name: 'Smoke Project' })).toBeVisible();
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
  await expect(page.getByText('Smoke Task')).toBeVisible();

  await page.getByText('Smoke Task').click();
  await expect(page.getByText('Task detail')).toBeVisible();
  await expect(page.getByText('Smoke Task')).toBeVisible();
});

