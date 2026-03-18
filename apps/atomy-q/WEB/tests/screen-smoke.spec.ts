import { expect, test } from '@playwright/test';

test.describe.configure({ mode: 'serial' });

const buildCorsHeaders = (origin: string) => ({
  'access-control-allow-origin': origin,
  'access-control-allow-credentials': 'true',
  'access-control-allow-headers': 'Content-Type, Authorization',
  'access-control-allow-methods': 'GET,POST,OPTIONS',
});

async function stubAuth(page: import('@playwright/test').Page) {
  await page.goto('/login');
  await page.getByRole('button', { name: /use mock account/i }).click();
  await expect(page).toHaveURL('/', { timeout: 15000 });
}

async function stubProjectsApi(page: import('@playwright/test').Page) {
  let origin = 'http://localhost:3000';
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
    const corsHeaders = buildCorsHeaders(origin);
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
    const corsHeaders = buildCorsHeaders(origin);
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
    const corsHeaders = buildCorsHeaders(origin);
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
    const corsHeaders = buildCorsHeaders(origin);
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
    const corsHeaders = buildCorsHeaders(origin);
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

  // Once we’ve navigated anywhere, update origin to match baseURL.
  page.on('framenavigated', () => {
    try {
      origin = new URL(page.url()).origin;
    } catch {
      // ignore
    }
  });
}

async function stubTasksApi(page: import('@playwright/test').Page) {
  let origin = 'http://localhost:3000';
  const task = { id: 'task-1', title: 'Smoke Task', status: 'pending', due_date: null, project_id: null };

  await page.route('**/api/v1/tasks', async (route) => {
    const corsHeaders = buildCorsHeaders(origin);
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
    const corsHeaders = buildCorsHeaders(origin);
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

  page.on('framenavigated', () => {
    try {
      origin = new URL(page.url()).origin;
    } catch {
      // ignore
    }
  });
}

test.beforeEach(async ({ page }) => {
  await stubAuth(page);
  await stubProjectsApi(page);
  await stubTasksApi(page);
});

const screens: Array<{ path: string; heading: RegExp }> = [
  { path: '/', heading: /^Dashboard$/ },
  { path: '/projects', heading: /^Projects$/ },
  { path: '/tasks', heading: /^Task Inbox$/ },
  { path: '/rfqs', heading: /^Requisitions$/ },
  { path: '/documents', heading: /^Documents$/ },
  { path: '/reporting', heading: /^Reporting$/ },
  { path: '/approvals', heading: /^Approval Queue$/ },
  { path: '/settings', heading: /^Settings$/ },
  { path: '/settings/users', heading: /^Users & Roles$/ },
];

test('screen smoke: core routes render headings', async ({ page }) => {
  // Navigate via sidebar to avoid full reloads (token is in-memory; refresh flow isn't required).
  await expect(page.getByRole('heading', { name: /^Dashboard$/ })).toBeVisible();
  const nav = page.getByRole('navigation');

  await nav.getByRole('link', { name: 'Projects' }).click();
  await expect(page.getByRole('heading', { name: /^Projects$/ })).toBeVisible({ timeout: 20000 });

  await nav.getByRole('link', { name: 'Task Inbox' }).click();
  await expect(page.getByRole('heading', { name: /^Task Inbox$/ })).toBeVisible({ timeout: 20000 });

  // RFQs: click the "Active" link under the Requisition nav group
  await nav.getByRole('button', { name: 'Requisition' }).click();
  await nav.getByRole('link', { name: 'Active' }).click();
  await expect(page.getByRole('heading', { name: /^Requisitions$/ })).toBeVisible({ timeout: 20000 });

  await nav.getByRole('link', { name: 'Documents' }).click();
  await expect(page.getByRole('heading', { name: /^Documents$/ })).toBeVisible({ timeout: 20000 });
});

// Note: RFQ workspace routes require auth. The "Use mock account" shortcut is not persisted across full reloads,
// so we keep the smoke suite to client-side navigations that don't reload.