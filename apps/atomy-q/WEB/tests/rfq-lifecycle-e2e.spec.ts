/**
 * E2E: Full RFQ lifecycle from creation entry → list → overview → details →
 * line items → vendors → quote intake → comparison runs → approvals → award.
 *
 * - Mocked: stubs auth + RFQ list/detail; runs without API.
 * - Real API: set E2E_USE_REAL_API=1 and run with API + WEB on (creates RFQ via API, then navigates UI to award).
 *   Example: E2E_USE_REAL_API=1 E2E_API_URL=http://localhost:8000/api/v1 npm run test:e2e -- tests/rfq-lifecycle-e2e.spec.ts -g "real API"
 *   Requires a working /auth/login (Identity bindings e.g. UserPersistInterface) and seeded user1@example.com / secret.
 */
import { expect, test } from '@playwright/test';

const mockUser = {
  id: 'user-1',
  name: 'QA User',
  email: 'qa.user@atomy.test',
  role: 'buyer',
  tenantId: 'tenant-qa',
};

const E2E_RFQ_ID = 'RFQ-E2E-001';
const E2E_RFQ_TITLE = 'E2E Test RFQ Lifecycle';

const buildCorsHeaders = (origin: string) => ({
  'access-control-allow-origin': origin,
  'access-control-allow-credentials': 'true',
  'access-control-allow-headers': 'Content-Type, Authorization',
  'access-control-allow-methods': 'GET,POST,PATCH,PUT,DELETE,OPTIONS',
});

async function stubAuth(page: import('@playwright/test').Page) {
  let origin = 'http://localhost:3000';

  await page.route('**/api/v1/rfqs**', async (route) => {
    const cors = buildCorsHeaders(origin);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: cors });
      return;
    }
    const url = route.request().url();
    if (url.includes(E2E_RFQ_ID) || url.endsWith('/rfqs/' + E2E_RFQ_ID)) {
      await route.fulfill({
        status: 200,
        headers: cors,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: E2E_RFQ_ID,
            rfq_number: E2E_RFQ_ID,
            title: E2E_RFQ_TITLE,
            status: 'active',
            owner: { id: 'u1', name: 'QA User', email: mockUser.email },
            deadline: '2026-04-15',
            category: 'IT Hardware',
            estimated_value: 50000,
            estValue: 50000,
            savings_percentage: 12,
            savings: '12%',
            vendors_count: 3,
            quotes_count: 2,
            vendorsCount: 3,
            quotesCount: 2,
          },
        }),
      });
      return;
    }
    await route.fulfill({
      status: 200,
      headers: cors,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [
          {
            id: E2E_RFQ_ID,
            rfq_number: E2E_RFQ_ID,
            title: E2E_RFQ_TITLE,
            status: 'active',
            owner: { name: 'QA User', email: mockUser.email },
            deadline: '2026-04-15',
            category: 'IT Hardware',
            estValue: 50000,
            vendorsCount: 3,
            quotesCount: 2,
            savings: '12%',
          },
        ],
        meta: { total: 1, total_pages: 1, current_page: 1, per_page: 25 },
      }),
    });
  });

  await page.goto('/login');
  origin = new URL(page.url()).origin;
  await page.getByRole('button', { name: /use mock account/i }).click();
  await expect(page).toHaveURL('/');
}

function firstRfqTableDataRow(page: import('@playwright/test').Page) {
  return page.locator('table tbody tr.cursor-pointer').first();
}

/** Workspace rail uses Next Link; href suffix avoids ambiguous accessible names (e.g. badges). */
function workspaceNavLink(page: import('@playwright/test').Page, pathSuffix: string) {
  return page.getByTestId('active-record-menu').locator(`a[href$="/${pathSuffix}"]`).first();
}

test.describe('RFQ lifecycle E2E (creation to award)', () => {
  test('navigates full RFQ lifecycle with mocked API: create entry → list → overview → details → line items → vendors → quote intake → comparison → approvals → award', async ({
    page,
  }) => {
    test.setTimeout(120_000);
    await stubAuth(page);

    // 1) Creation entry point: New RFQ page
    await page.goto('/rfqs/new');
    await expect(page).toHaveURL(/\/rfqs\/new/);
    await expect(page.getByRole('heading', { name: /create new rfq/i })).toBeVisible();

    // 2) List and open first RFQ (stubbed API or seed data when USE_MOCKS=true)
    await page.goto('/rfqs');
    await expect(page).toHaveURL('/rfqs');
    await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible();

    const row = firstRfqTableDataRow(page);
    await expect(row).toBeVisible({ timeout: 15000 });
    await row.locator('td').nth(2).click();

    await expect(page).toHaveURL(/\/rfqs\/.+\/overview/, { timeout: 15000 });

    // 3) Overview
    await expect(page.getByText('Activity timeline', { exact: false })).toBeVisible();

    // 4) Details
    await workspaceNavLink(page, 'details').click();
    await expect(page).toHaveURL(/\/rfqs\/.+\/details/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: /rfq details/i })).toBeVisible();

    // 5) Line Items
    await workspaceNavLink(page, 'line-items').click();
    await expect(page).toHaveURL(/\/rfqs\/.+\/line-items/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Line items' })).toBeVisible();

    // 6) Vendors
    await workspaceNavLink(page, 'vendors').click();
    await expect(page).toHaveURL(/\/rfqs\/.+\/vendors/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Invited vendors' })).toBeVisible();

    // 7) Quote Intake
    await workspaceNavLink(page, 'quote-intake').click();
    await expect(page).toHaveURL(/\/rfqs\/.+\/quote-intake/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Quote Intake' })).toBeVisible();

    // 8) Comparison Runs
    await workspaceNavLink(page, 'comparison-runs').click();
    await expect(page).toHaveURL(/\/rfqs\/.+\/comparison-runs/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Comparison Runs' })).toBeVisible();
    if ((await page.getByText(/snapshot frozen/i).count()) > 0) {
      await expect(page.getByText(/snapshot frozen/i).first()).toBeVisible();
      await expect(workspaceNavLink(page, 'decision-trail')).toBeVisible();
    }

    // 9) Approvals
    await workspaceNavLink(page, 'approvals').click();
    await expect(page).toHaveURL(/\/rfqs\/.+\/approvals/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Approvals' })).toBeVisible();

    // 10) Award
    await workspaceNavLink(page, 'award').click();
    await expect(page).toHaveURL(/\/rfqs\/.+\/award/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Sign-off' })).toBeVisible();
  });

  const useRealApi = process.env.E2E_USE_REAL_API === '1';

  test('full RFQ lifecycle with real API: create via API then navigate to award', async ({ page, request }, testInfo) => {
    if (!useRealApi) {
      testInfo.skip();
      return;
    }

    const apiBase = process.env.NEXT_PUBLIC_API_URL ?? process.env.E2E_API_URL ?? 'http://localhost:8001/api/v1';
    const email = process.env.E2E_EMAIL ?? 'user1@example.com';
    const password = process.env.E2E_PASSWORD ?? 'secret';

    const title = `E2E RFQ ${Date.now()}`;

    const loginRes = await request.post(`${apiBase}/auth/login`, {
      data: { email, password },
    });
    if (!loginRes.ok()) {
      throw new Error(
        `API login failed: ${loginRes.status()}. Ensure API is running and DB seeded (user1@example.com / secret).`
      );
    }
    const loginData = await loginRes.json();
    const token = loginData.access_token ?? loginData.token;
    if (!token) throw new Error('No access_token in login response.');

    const headers = { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' };

    const submissionDeadline = new Date(Date.now() + 14 * 86400000).toISOString();
    const createRes = await request.post(`${apiBase}/rfqs`, {
      headers,
      data: {
        title,
        description: 'E2E test RFQ',
        category: 'IT Hardware',
        department: 'Procurement',
        estimated_value: 25000,
        submission_deadline: submissionDeadline,
      },
    });
    if (!createRes.ok()) {
      throw new Error(`Create RFQ failed: ${createRes.status()} ${await createRes.text()}`);
    }
    const createData = await createRes.json();
    const rfqId = createData.data?.id ?? createData.id;
    if (!rfqId) throw new Error('No RFQ id in create response.');

    await request.post(`${apiBase}/rfqs/${rfqId}/line-items`, {
      headers,
      data: { description: 'Line 1', quantity: 10, uom: 'ea', unit_price: 100, currency: 'USD' },
    });
    await request.patch(`${apiBase}/rfqs/${rfqId}/status`, { headers, data: { status: 'published' } });
    await request.post(`${apiBase}/rfqs/${rfqId}/invitations`, {
      headers,
      data: { vendor_email: 'vendor@test.com', vendor_name: 'E2E Vendor' },
    });
    const uploadRes = await request.post(`${apiBase}/quote-submissions/upload`, {
      headers: { Authorization: headers.Authorization },
      multipart: {
        rfq_id: rfqId,
        vendor_id: '01' + '0'.repeat(24),
        vendor_name: 'E2E Vendor',
        file: {
          name: 'e2e-quote.txt',
          mimeType: 'text/plain',
          buffer: Buffer.from('E2E quote payload'),
        },
      },
    });
    if (uploadRes.ok()) {
      const qData = await uploadRes.json();
      const quoteId = qData.data?.id;
      if (quoteId) {
        await request.patch(`${apiBase}/quote-submissions/${quoteId}/status`, {
          headers,
          data: { status: 'accepted' },
        });
      }
    }

    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: /log in/i }).click();
    await expect(page).toHaveURL('/', { timeout: 15000 });

    await page.goto('/rfqs');
    await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible({ timeout: 10000 });
    await page.getByText(title, { exact: true }).click();
    await expect(page).toHaveURL(new RegExp(`/rfqs/${rfqId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}/overview`));

    await expect(page.getByRole('heading', { name: 'Overview' })).toBeVisible();
    await page.getByRole('link', { name: 'Details' }).click();
    await expect(page.getByRole('heading', { name: /rfq details/i })).toBeVisible();
    await page.getByRole('link', { name: 'Line Items' }).click();
    await expect(page).toHaveURL(/\/line-items/);
    await page.getByRole('link', { name: 'Vendors' }).click();
    await expect(page).toHaveURL(/\/vendors/);
    await page.getByRole('link', { name: 'Quote Intake' }).click();
    await expect(page).toHaveURL(/\/quote-intake/);
    await page.getByRole('link', { name: 'Comparison Runs' }).click();
    await expect(page).toHaveURL(/\/comparison-runs/);
    await page.getByRole('link', { name: 'Approvals' }).click();
    await expect(page).toHaveURL(/\/approvals/);
    await page.getByRole('link', { name: 'Award' }).click();
    await expect(page).toHaveURL(/\/award/);
    await expect(page.getByRole('heading', { name: 'Sign-off' })).toBeVisible();
  });
});
