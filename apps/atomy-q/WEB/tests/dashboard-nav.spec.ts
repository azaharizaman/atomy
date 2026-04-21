import { expect, test } from '@playwright/test';
import { stubAlphaSession } from './alpha-playwright-bootstrap';

test.beforeEach(async ({ page }) => {
  await stubAlphaSession(page);
  await expect(page).toHaveURL('/');
});

test('dashboard shows the alpha sidebar after login', async ({ page }) => {
  const sidebar = page.locator('aside').first();

  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  await expect(page.getByText('Active RFQs')).toBeVisible();
  await expect(sidebar.getByRole('link', { name: 'Dashboard' })).toBeVisible();
  await expect(sidebar.getByRole('button', { name: 'Requisition', exact: true })).toBeVisible();
  await expect(page.getByText('Atomy-Q').first()).toBeVisible();
});
