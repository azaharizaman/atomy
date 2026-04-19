/**
 * E2E coverage for the RFQ line-items screen.
 *
 * Run with NEXT_PUBLIC_USE_MOCKS=false so the Add Line Item action is enabled and
 * the screen exercises the live-query refresh path after a successful save.
 */
import { expect, test } from '@playwright/test';
import { fulfillJsonRoute } from './playwright-cors-helpers';

const rfqId = 'RFQ-E2E-LINE-1';

const mockUser = {
  id: 'line-items-user-1',
  name: 'Line Items QA',
  email: 'line.items.qa@atomy.test',
  role: 'buyer',
  tenantId: 'tenant-qa',
};

test.describe('RFQ line-items screen', () => {
  test.skip(process.env.NEXT_PUBLIC_USE_MOCKS === 'true', 'Run with NEXT_PUBLIC_USE_MOCKS=false to exercise line-item creation.');

  test.beforeEach(async ({ page }) => {
    let lineItems = [
      {
        id: 'line-1',
        rfq_id: rfqId,
        description: 'Existing line item',
        quantity: 1,
        uom: 'ea',
        unit_price: 100,
        currency: 'USD',
        specifications: 'Baseline item',
        sort_order: 1,
        rowType: 'line',
      },
    ];

    await page.addInitScript((user) => {
      window.localStorage.setItem(
        'auth-storage',
        JSON.stringify({
          state: {
            user,
            token: 'playwright-access-token',
            refreshToken: null,
            isAuthenticated: true,
            isLoading: false,
          },
          version: 0,
        }),
      );
    }, mockUser);

    await page.route('**/api/v1/feature-flags', async (route) => {
      await fulfillJsonRoute(route, { data: { projects: false, tasks: false } });
    });

    await page.route('**/api/v1/approvals**', async (route) => {
      await fulfillJsonRoute(route, {
        data: [],
        meta: { total: 0, total_pages: 1, current_page: 1, per_page: 20 },
      });
    });

    await page.route(`**/api/v1/rfqs/${rfqId}`, async (route) => {
      await fulfillJsonRoute(route, {
        data: {
          id: rfqId,
          rfq_number: rfqId,
          title: 'Line Items E2E RFQ',
          status: 'draft',
          owner: { id: 'u1', name: mockUser.name, email: mockUser.email },
          deadline: '2026-05-01',
          category: 'Facilities',
          estimated_value: 1000,
          estValue: 1000,
          savings: '0%',
          vendors_count: 0,
          quotes_count: 0,
          vendorsCount: 0,
          quotesCount: 0,
        },
      });
    });

    await page.route(`**/api/v1/rfqs/${rfqId}/line-items`, async (route) => {
      if (route.request().method() === 'POST') {
        const payload = route.request().postDataJSON() as {
          description?: string;
          quantity?: number;
          uom?: string;
          unit_price?: number;
          currency?: string;
          specifications?: string | null;
        };
        const nextItem = {
          id: `line-${lineItems.length + 1}`,
          rfq_id: rfqId,
          description: payload.description ?? 'New line item',
          quantity: payload.quantity ?? 1,
          uom: payload.uom ?? 'ea',
          unit_price: payload.unit_price ?? 0,
          currency: payload.currency ?? 'USD',
          specifications: payload.specifications ?? null,
          sort_order: lineItems.length + 1,
          rowType: 'line',
        };
        lineItems = [...lineItems, nextItem];
        await fulfillJsonRoute(route, { data: nextItem }, 201);
        return;
      }

      await fulfillJsonRoute(route, { data: lineItems });
    });
  });

  test('adds a line item and refreshes the requested items table', async ({ page }) => {
    await page.goto(`/rfqs/${rfqId}/line-items`);

    await expect(page.getByRole('heading', { name: 'Line items' })).toBeVisible();
    await expect(page.getByText('Existing line item')).toBeVisible();

    await page.getByRole('button', { name: /add line item/i }).first().click();

    await page.getByLabel('Description').fill('Nitrogen compressor');
    await page.getByLabel('Quantity').fill('2');
    await page.getByLabel('UOM').fill('ea');
    await page.getByLabel('Unit price').fill('1200');
    await page.getByLabel('Currency').fill('USD');
    await page.getByRole('button', { name: /save line item/i }).click();

    await expect(page.getByText('Nitrogen compressor')).toBeVisible({ timeout: 15000 });
    await expect(page.getByText('Existing line item')).toBeVisible();
  });
});
