import type { Page } from '@playwright/test';
import { seedAuthSession } from './playwright-auth-bootstrap';
import { fulfillJsonRoute } from './playwright-cors-helpers';

export const alphaMockUser = {
  id: 'user-1',
  name: 'QA User',
  email: 'qa.user@atomy.test',
  role: 'buyer',
  tenantId: 'tenant-qa',
};

export async function stubAlphaSession(page: Page): Promise<void> {
  await page.route('**/api/v1/feature-flags', async (route) => {
    await fulfillJsonRoute(route, { data: { projects: true, tasks: true } });
  });
  await page.route('**/api/v1/rfqs/counts', async (route) => {
    await fulfillJsonRoute(route, {
      data: { draft: 0, published: 0, closed: 0, awarded: 0, cancelled: 0, active: 0, pending: 0, archived: 0 },
    });
  });

  await seedAuthSession(page, alphaMockUser);
  await page.goto('/');
}
