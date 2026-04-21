import { expect, test, type Page } from '@playwright/test';
import { alphaMockUser } from './alpha-playwright-bootstrap';
import { seedAuthSession } from './playwright-auth-bootstrap';
import { fulfillJsonRoute } from './playwright-cors-helpers';

const PROJECT_ID = '01JNE4ZHT9S0VQ7E2GQW1QYJ7B';
const TASK_ID = 'task-smoke-1';

const project = {
  id: PROJECT_ID,
  name: 'Project Smoke Alpha Adjacent',
  status: 'planning',
  client_id: 'client-1',
  client_name: 'Acme Operations',
  project_manager_id: alphaMockUser.id,
  start_date: '2026-01-01',
  end_date: '2026-02-01',
  budget_type: 'capex',
  completion_percentage: 25,
};

const linkedRfq = {
  id: 'RFQ-PROJECT-SMOKE-1',
  rfq_number: 'RFQ-PROJECT-SMOKE-1',
  title: 'Project-linked RFQ',
  status: 'active',
};

const linkedTask = {
  id: TASK_ID,
  title: 'Project-linked task',
  description: 'Review project procurement readiness.',
  status: 'pending',
  due_date: '2026-02-02',
  project_id: PROJECT_ID,
};

async function stubNonAlphaShell(page: Page): Promise<void> {
  await page.route('**/api/v1/feature-flags', async (route) => {
    await fulfillJsonRoute(route, { data: { projects: true, tasks: true } });
  });
  await page.route('**/api/v1/rfqs/counts', async (route) => {
    await fulfillJsonRoute(route, {
      data: { draft: 0, published: 0, closed: 0, awarded: 0, cancelled: 0, active: 0, pending: 0, archived: 0 },
    });
  });
  await page.route(/\/api\/v1\/users(?:\/|\?.*)?$/, async (route) => {
    await fulfillJsonRoute(route, {
      data: [
        {
          id: alphaMockUser.id,
          name: alphaMockUser.name,
          email: alphaMockUser.email,
          status: 'active',
          role: alphaMockUser.role,
          created_at: '2026-04-17T00:00:00Z',
          last_login_at: null,
        },
      ],
      meta: { current_page: 1, per_page: 10, total: 1 },
    });
  });

  await seedAuthSession(page, alphaMockUser);
}

async function stubProjectsApi(page: Page): Promise<void> {
  await page.route('**/api/v1/projects**', async (route) => {
    const pathname = new URL(route.request().url()).pathname;

    if (pathname.endsWith(`/${PROJECT_ID}/health`)) {
      await fulfillJsonRoute(route, {
        data: {
          project_id: PROJECT_ID,
          overall_score: 80,
          labor: { actual_hours: 42, health_percentage: 75 },
          timeline: { completion_percentage: 50, completed_milestones: 2, total_milestones: 4 },
        },
      });
      return;
    }

    if (pathname.endsWith(`/${PROJECT_ID}/rfqs`)) {
      await fulfillJsonRoute(route, { data: [linkedRfq] });
      return;
    }

    if (pathname.endsWith(`/${PROJECT_ID}/tasks`)) {
      await fulfillJsonRoute(route, { data: [linkedTask] });
      return;
    }

    if (pathname.endsWith(`/${PROJECT_ID}/acl`)) {
      await fulfillJsonRoute(route, {
        data: { roles: [{ user_id: alphaMockUser.id, role: 'owner' }] },
      });
      return;
    }

    if (pathname.endsWith(`/${PROJECT_ID}`)) {
      await fulfillJsonRoute(route, { data: project });
      return;
    }

    await fulfillJsonRoute(route, { data: [project] });
  });
}

async function stubTasksApi(page: Page): Promise<void> {
  await page.route('**/api/v1/tasks**', async (route) => {
    const pathname = new URL(route.request().url()).pathname;
    if (pathname.endsWith(`/${TASK_ID}`)) {
      await fulfillJsonRoute(route, { data: linkedTask });
      return;
    }

    await fulfillJsonRoute(route, { data: [linkedTask] });
  });
}

test.beforeEach(async ({ page }) => {
  await stubNonAlphaShell(page);
  await stubProjectsApi(page);
  await stubTasksApi(page);
});

test('projects list opens project detail with health, linked RFQs, and linked tasks', async ({ page }) => {
  await page.goto('/projects');

  await expect(page.getByRole('heading', { name: 'Projects' })).toBeVisible();
  await expect(page.getByText(project.name)).toBeVisible();

  await page.getByText(project.name).click();
  await expect(page).toHaveURL(new RegExp(`/projects/${PROJECT_ID}$`));
  await expect(page.getByRole('heading', { name: project.name })).toBeVisible();
  await expect(page.getByText('Overall score')).toBeVisible();
  await expect(page.getByText('80%')).toBeVisible();
  await expect(page.getByText('Project-linked RFQ')).toBeVisible();
  await expect(page.getByText('Project-linked task')).toBeVisible();
});

test('tasks inbox opens and closes the selected task detail drawer', async ({ page }) => {
  await page.goto('/tasks');

  await expect(page.getByRole('heading', { name: 'Task Inbox' })).toBeVisible();
  await expect(page.getByRole('table').getByText(linkedTask.title)).toBeVisible();

  await page.getByRole('table').getByText(linkedTask.title).click();
  await expect(page.getByText('Task detail')).toBeVisible();
  await expect(page.locator('div.fixed.inset-y-0.right-0').getByText(linkedTask.title)).toBeVisible();
  await expect(page.getByText(linkedTask.description)).toBeVisible();

  await page.getByRole('button', { name: 'Close', exact: true }).click();
  await expect(page.locator('div.fixed.inset-y-0.right-0')).toBeHidden();
});
