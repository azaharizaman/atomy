import { expect, test } from '@playwright/test';

test.describe.configure({ mode: 'serial' });

const buildCorsHeaders = (origin: string) => ({
  'access-control-allow-origin': origin,
  'access-control-allow-credentials': 'true',
  'access-control-allow-headers': 'Content-Type, Authorization',
  'access-control-allow-methods': 'GET,POST,OPTIONS',
});

/** Shared origin tracking: updates ref.current on framenavigated so CORS headers stay correct. */
function trackOriginOnNavigation(
  page: import('@playwright/test').Page,
  originRef: { current: string }
): void {
  page.on('framenavigated', () => {
    try {
      originRef.current = new URL(page.url()).origin;
    } catch {
      // ignore
    }
  });
}

async function stubAuth(page: import('@playwright/test').Page) {
  await page.goto('/login');
  await page.getByRole('button', { name: /use mock account/i }).click();
  await expect(page).toHaveURL('/', { timeout: 15000 });
}

async function stubProjectsApi(
  page: import('@playwright/test').Page,
  baseUrl?: string
) {
  const defaultOrigin = baseUrl ?? process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:3000';
  const originRef = { current: defaultOrigin };
  trackOriginOnNavigation(page, originRef);

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

  await page.route('**/api/v1/projects', async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }

    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify({ data: [project] }),
    });
  });

  await page.route(`**/api/v1/projects/${project.id}`, async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify({ data: project }),
    });
  });

  await page.route(`**/api/v1/projects/${project.id}/health`, async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify({ data: { project_id: project.id, overall_score: 80 } }),
    });
  });

  await page.route(`**/api/v1/projects/${project.id}/rfqs`, async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify({ data: [] }),
    });
  });

  await page.route(`**/api/v1/projects/${project.id}/tasks`, async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify({ data: [] }),
    });
  });
}

async function stubTasksApi(
  page: import('@playwright/test').Page,
  baseUrl?: string
) {
  const defaultOrigin = baseUrl ?? process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:3000';
  const originRef = { current: defaultOrigin };
  trackOriginOnNavigation(page, originRef);

  const task = { id: 'task-1', title: 'Smoke Task', status: 'pending', due_date: null, project_id: null };

  await page.route('**/api/v1/tasks', async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify({ data: [task] }),
    });
  });

  await page.route('**/api/v1/tasks/task-1', async (route) => {
    const corsHeaders = buildCorsHeaders(originRef.current);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: corsHeaders,
      contentType: 'application/json',
      body: JSON.stringify({ data: task }),
    });
  });
}

const baseUrl = process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:3000';

test.beforeEach(async ({ page }) => {
  await stubProjectsApi(page, baseUrl);
  await stubTasksApi(page, baseUrl);
  await stubAuth(page);
});

test('screen smoke: alpha core routes render headings', async ({ page }) => {
  if (process.env.NEXT_PUBLIC_ALPHA_MODE !== 'true') {
    test.skip();
  }
  const sidebar = page.getByRole('navigation').first();

  await expect(page.getByRole('heading', { name: /^Dashboard$/ })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
  await expect(sidebar.getByRole('link', { name: 'Dashboard' })).toBeVisible();
  await expect(sidebar.getByRole('button', { name: 'Requisition', exact: true })).toBeVisible();
  await expect(sidebar.getByRole('link', { name: 'Documents' })).toHaveCount(0);
  await expect(sidebar.getByRole('link', { name: 'Reporting' })).toHaveCount(0);
  await expect(sidebar.getByRole('link', { name: 'Approval Queue' })).toHaveCount(0);
  await expect(sidebar.getByRole('button', { name: 'Settings' })).toHaveCount(0);

  await sidebar.getByRole('button', { name: 'Requisition', exact: true }).click();
  await sidebar.getByRole('link', { name: 'Active' }).click();
  await expect(page.getByRole('heading', { name: /^Requisitions$/ })).toBeVisible({ timeout: 20000 });
});

// Note: RFQ workspace routes require auth. The "Use mock account" shortcut is not persisted across full reloads,
// so we keep the smoke suite to client-side navigations that don't reload.
