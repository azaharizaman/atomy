import { expect, test } from '@playwright/test';

async function signInWithMockAccount(page: import('@playwright/test').Page) {
  await page.goto('/login');
  await page.getByRole('button', { name: /use mock account/i }).click();
  await expect(page).toHaveURL('/');
}

/** First body row with row-click navigation; avoid checkbox / expand cells (they stopPropagation). */
function firstRfqTableDataRow(page: import('@playwright/test').Page) {
  return page.locator('table tbody tr.cursor-pointer').first();
}

test('rfq list navigates to rfq workspace overview', async ({ page }) => {
  await signInWithMockAccount(page);

  await page.goto('/rfqs');
  await expect(page).toHaveURL('/rfqs');
  await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible();

  const row = firstRfqTableDataRow(page);
  await expect(row).toBeVisible({ timeout: 15000 });
  await row.locator('td').nth(2).click();

  await expect(page).toHaveURL(/\/rfqs\/.+\/overview/, { timeout: 15000 });
  await expect(page.getByTestId('active-record-menu')).toBeVisible();
  await expect(page.getByText(/activity timeline/i)).toBeVisible();
});

