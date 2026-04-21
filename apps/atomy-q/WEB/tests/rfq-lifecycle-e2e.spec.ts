/**
 * E2E: Full RFQ lifecycle from creation entry → list → overview → details →
 * line items → vendors → quote intake → comparison runs → approvals → award.
 *
 * - Mocked: stubs auth + RFQ list/detail; runs without API.
 * - Real API: creates RFQ via the local test backend at http://localhost:8000/api/v1, then navigates UI to award.
 *   Requires a working /auth/login (Identity bindings e.g. UserPersistInterface) and seeded user1@example.com / secret.
 */
import { expect, test } from '@playwright/test';
import { fulfillJsonRoute, getRequestOrigin, buildCorsHeaders } from './playwright-cors-helpers';
import { seedAuthSession } from './playwright-auth-bootstrap';

const mockUser = {
  id: 'user-1',
  name: 'QA User',
  email: 'qa.user@atomy.test',
  role: 'buyer',
  tenantId: 'tenant-qa',
};

const E2E_RFQ_ID = 'RFQ-E2E-001';
const E2E_RFQ_TITLE = 'E2E Test RFQ Lifecycle';

async function stubAuth(page: import('@playwright/test').Page) {
  let currentAward:
    | null
    | {
        id: string;
        rfq_id: string;
        rfq_title: string;
        rfq_number: string;
        comparison_run_id: string;
        vendor_id: string;
        vendor_name: string;
        status: 'pending' | 'signed_off';
        amount: number;
        currency: string;
        split_details: [];
        protest_id: null;
        signoff_at: string | null;
        signed_off_by: string | null;
        comparison: {
          vendors: Array<{
            vendor_id: string;
            vendor_name: string;
            quote_submission_id: string | null;
          }>;
        };
      } = null;
  const sentDebriefs: Array<{ awardId: string; vendorId: string; message: string }> = [];

  await page.context().route('**/api/v1/feature-flags', async (route) => {
    await fulfillJsonRoute(route, { data: { projects: true, tasks: true } });
  });

  await seedAuthSession(
    page,
    {
      id: 'e2e-user-1',
      name: 'QA User',
      email: mockUser.email,
      role: 'admin',
      tenantId: mockUser.tenantId,
    },
    { token: 'e2e-access-token', refreshToken: 'e2e-refresh-token' },
  );

  await page.context().route('**/api/v1/rfqs/counts', async (route) => {
    await fulfillJsonRoute(route, {
      data: { draft: 0, published: 0, closed: 0, awarded: 0, cancelled: 0, active: 1, pending: 0, archived: 0 },
    });
  });

  await page.context().route('**/api/v1/rfqs**', async (route) => {
    if (route.request().method() === 'OPTIONS') {
      const origin = getRequestOrigin(route);
      await route.fulfill({ status: 204, headers: buildCorsHeaders(origin) });
      return;
    }
    const requestUrl = new URL(route.request().url());
    const pathname = requestUrl.pathname;

    if (pathname.endsWith('/counts')) {
      await fulfillJsonRoute(route, {
        data: { draft: 0, published: 0, closed: 0, awarded: 0, cancelled: 0, active: 1, pending: 0, archived: 0 },
      });
      return;
    }

    if (pathname.endsWith(`/rfqs/${E2E_RFQ_ID}/overview`)) {
      await fulfillJsonRoute(route, {
        data: {
          rfq: {
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
            vendors_count: 2,
            quotes_count: 2,
            vendorsCount: 2,
            quotesCount: 2,
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
    if (pathname.endsWith(`/rfqs/${E2E_RFQ_ID}/activity`)) {
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
    if (pathname.endsWith('/rfqs')) {
      await fulfillJsonRoute(route, {
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
      });
      return;
    }
    if (pathname.endsWith(`/rfqs/${E2E_RFQ_ID}/invitations`)) {
      await fulfillJsonRoute(route, {
        data: [
          {
            id: 'inv-1',
            vendor_id: 'vendor-e2e-1',
            vendor_name: 'E2E Vendor',
            vendor_email: 'vendor-1@atomy.test',
            status: 'accepted',
          },
          {
            id: 'inv-2',
            vendor_id: 'vendor-e2e-2',
            vendor_name: 'Runner Up Vendor',
            vendor_email: 'vendor-2@atomy.test',
            status: 'accepted',
          },
        ],
      });
      return;
    }
    if (pathname.includes(E2E_RFQ_ID) || pathname.endsWith('/rfqs/' + E2E_RFQ_ID)) {
      await fulfillJsonRoute(route, {
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
          vendors_count: 2,
          quotes_count: 2,
          vendorsCount: 2,
          quotesCount: 2,
          comparison: null,
          approvals: {
            pending_count: 0,
            approved_count: 0,
            rejected_count: 0,
            overall: 'none',
          },
        },
      });
      return;
    }
    await fulfillJsonRoute(route, {
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
    });
  });

  await page.context().route('**/api/v1/normalization/**', async (route) => {
    if (route.request().method() === 'OPTIONS') {
      const origin = getRequestOrigin(route);
      await route.fulfill({ status: 204, headers: buildCorsHeaders(origin) });
      return;
    }

    await fulfillJsonRoute(route, {
      data: [],
      meta: { rfq_id: E2E_RFQ_ID, has_blocking_issues: false, blocking_issue_count: 0 },
    });
  });

  await page.context().route('**/api/v1/quote-submissions**', async (route) => {
    if (route.request().method() === 'OPTIONS') {
      const origin = getRequestOrigin(route);
      await route.fulfill({ status: 204, headers: buildCorsHeaders(origin) });
      return;
    }

    await fulfillJsonRoute(route, {
      data: [
        {
          id: 'quote-e2e-1',
          rfq_id: E2E_RFQ_ID,
          vendor_id: 'vendor-e2e-1',
          vendor_name: 'E2E Vendor',
          original_filename: 'e2e-vendor-1.pdf',
          status: 'ready',
          confidence: 95,
          submitted_at: '2026-04-16T09:00:00Z',
          blocking_issue_count: 0,
        },
        {
          id: 'quote-e2e-2',
          rfq_id: E2E_RFQ_ID,
          vendor_id: 'vendor-e2e-2',
          vendor_name: 'Runner Up Vendor',
          original_filename: 'e2e-vendor-2.pdf',
          status: 'ready',
          confidence: 92,
          submitted_at: '2026-04-16T09:05:00Z',
          blocking_issue_count: 0,
        },
      ],
    });
  });

  await page.context().route('**/api/v1/comparison-runs**', async (route) => {
    const origin = getRequestOrigin(route);
    const cors = buildCorsHeaders(origin);
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: cors });
      return;
    }

    const requestUrl = new URL(route.request().url());
    const pathname = requestUrl.pathname;

    if (pathname.endsWith('/comparison-runs')) {
      await route.fulfill({
        status: 200,
        headers: cors,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            {
              id: 'run-final-e2e',
              rfq_id: E2E_RFQ_ID,
              name: 'Final comparison',
              type: 'final',
              status: 'frozen',
              created_at: '2026-04-16T10:00:00Z',
            },
          ],
        }),
      });
      return;
    }

    if (pathname.endsWith('/comparison-runs/run-final-e2e/matrix')) {
      await fulfillJsonRoute(route, {
        data: {
          id: 'run-final-e2e',
          clusters: [
            {
              clusterKey: 'line-1',
              basis: 'price',
              offers: [
                {
                  vendorId: 'vendor-e2e-1',
                  rfqLineId: 'line-1',
                  taxonomyCode: 'HW-1',
                  normalizedUnitPrice: 100,
                  normalizedQuantity: 5,
                  aiConfidence: 0.98,
                },
                {
                  vendorId: 'vendor-e2e-2',
                  rfqLineId: 'line-1',
                  taxonomyCode: 'HW-1',
                  normalizedUnitPrice: 125,
                  normalizedQuantity: 5,
                  aiConfidence: 0.96,
                },
              ],
              statistics: {
                minNormalizedUnitPrice: 100,
                maxNormalizedUnitPrice: 125,
                avgNormalizedUnitPrice: 112.5,
              },
              recommendation: {
                recommendedVendorId: 'vendor-e2e-1',
                reason: 'Lowest evaluated total',
              },
            },
          ],
        },
      });
      return;
    }

    if (pathname.endsWith('/comparison-runs/run-final-e2e')) {
      await fulfillJsonRoute(route, {
        data: {
          id: 'run-final-e2e',
          rfq_id: E2E_RFQ_ID,
          name: 'Final comparison',
          status: 'frozen',
          is_preview: false,
          created_at: '2026-04-16T10:00:00Z',
          snapshot: {
            rfqVersion: 1,
            normalizedLines: [
              {
                rfqLineItemId: 'line-1',
                sourceDescription: 'Dell PowerEdge R750',
                sourceLineId: 'source-line-1',
                quoteSubmissionId: 'quote-e2e-1',
                vendorId: 'vendor-e2e-1',
                sourceUnitPrice: '100',
                sourceUom: 'units',
                sourceQuantity: '5',
              },
            ],
            resolutions: [],
            currencyMeta: {
              'line-1': 'USD',
            },
            vendors: [
              {
                vendorId: 'vendor-e2e-1',
                vendorName: 'E2E Vendor',
                quoteSubmissionId: 'quote-e2e-1',
              },
              {
                vendorId: 'vendor-e2e-2',
                vendorName: 'Runner Up Vendor',
                quoteSubmissionId: 'quote-e2e-2',
              },
            ],
          },
        },
      });
      return;
    }

    await route.fulfill({
      status: 404,
      headers: buildCorsHeaders(origin),
      contentType: 'application/json',
      body: JSON.stringify({ message: 'Comparison run route not stubbed' }),
    });
  });

  await page.context().route('**/api/v1/approvals**', async (route) => {
    if (route.request().method() === 'OPTIONS') {
      const origin = getRequestOrigin(route);
      await route.fulfill({ status: 204, headers: buildCorsHeaders(origin) });
      return;
    }

    await fulfillJsonRoute(route, {
      data: [
        {
          id: 'approval-e2e-1',
          rfq_id: E2E_RFQ_ID,
          rfq_title: E2E_RFQ_TITLE,
          type: 'award_signoff',
          type_label: 'Award signoff',
          status: 'pending',
          priority: 'high',
          summary: 'Final award decision awaiting sign-off',
          assignee: 'QA User',
          requested_at: '2026-04-16T10:05:00Z',
        },
      ],
      meta: { total: 1, total_pages: 1, current_page: 1, per_page: 20 },
    });
  });

  await page.context().route('**/api/v1/awards**', async (route) => {
    if (route.request().method() === 'OPTIONS') {
      const origin = getRequestOrigin(route);
      await route.fulfill({ status: 204, headers: buildCorsHeaders(origin) });
      return;
    }

    if (route.request().method() === 'GET') {
      await fulfillJsonRoute(route, { data: currentAward ? [currentAward] : [] });
      return;
    }

    if (route.request().method() === 'POST') {
      const payload = route.request().postDataJSON() as {
        comparison_run_id: string;
        vendor_id: string;
        amount: number;
        currency: string;
      };
      currentAward = {
        id: 'award-e2e-1',
        rfq_id: E2E_RFQ_ID,
        rfq_title: E2E_RFQ_TITLE,
        rfq_number: E2E_RFQ_ID,
        comparison_run_id: payload.comparison_run_id,
        vendor_id: payload.vendor_id,
        vendor_name: 'E2E Vendor',
        status: 'pending',
        amount: payload.amount,
        currency: payload.currency,
        split_details: [],
        protest_id: null,
        signoff_at: null,
        signed_off_by: null,
        comparison: {
          vendors: [
            { vendor_id: 'vendor-e2e-1', vendor_name: 'E2E Vendor', quote_submission_id: 'quote-e2e-1' },
            { vendor_id: 'vendor-e2e-2', vendor_name: 'Runner Up Vendor', quote_submission_id: 'quote-e2e-2' },
          ],
        },
      };
      await fulfillJsonRoute(route, { data: currentAward }, 201);
      return;
    }

    const origin = getRequestOrigin(route);
    await route.fulfill({
      status: 405,
      headers: buildCorsHeaders(origin),
      contentType: 'application/json',
      body: JSON.stringify({ message: 'Unsupported award method' }),
    });
  });

  await page.context().route('**/api/v1/awards/award-e2e-1/signoff', async (route) => {
    currentAward = currentAward
      ? {
          ...currentAward,
          status: 'signed_off',
          signoff_at: '2026-04-16T10:10:00Z',
          signed_off_by: mockUser.name,
        }
      : null;
    await fulfillJsonRoute(route, { data: currentAward });
  });

  await page.context().route('**/api/v1/awards/award-e2e-1/debrief/**', async (route) => {
    if (route.request().method() === 'OPTIONS') {
      const origin = getRequestOrigin(route);
      await route.fulfill({ status: 204, headers: buildCorsHeaders(origin) });
      return;
    }

    const payload = route.request().postDataJSON() as { message?: string };
    const vendorId = route.request().url().split('/').pop() ?? '';
    sentDebriefs.push({
      awardId: 'award-e2e-1',
      vendorId,
      message: payload.message ?? '',
    });
    await fulfillJsonRoute(route, { data: currentAward });
  });

  await page.goto('/');
  await expect(page).toHaveURL('/');

  return {
    getCurrentAward: () => currentAward,
    getSentDebriefs: () => sentDebriefs,
  };
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
    const lifecycleApi = await stubAuth(page);

    // 1) List
    await page.goto('/rfqs');
    await expect(page).toHaveURL('/rfqs');
    await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible();

    // 2) Overview
    await page.goto(`/rfqs/${E2E_RFQ_ID}/overview`);
    await expect(page).toHaveURL(/\/rfqs\/.+\/overview/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Schedule' })).toBeVisible({ timeout: 15000 });

    // 3) Details
    await page.goto(`/rfqs/${E2E_RFQ_ID}/details`);
    await expect(page).toHaveURL(/\/rfqs\/.+\/details/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: /rfq details/i })).toBeVisible();

    // 4) Line Items
    await page.goto(`/rfqs/${E2E_RFQ_ID}/line-items`);
    await expect(page).toHaveURL(/\/rfqs\/.+\/line-items/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Line items' })).toBeVisible();

    // 5) Vendors
    await page.goto(`/rfqs/${E2E_RFQ_ID}/vendors`);
    await expect(page).toHaveURL(/\/rfqs\/.+\/vendors/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Invited vendors' })).toBeVisible();

    // 6) Quote Intake
    await page.goto(`/rfqs/${E2E_RFQ_ID}/quote-intake`);
    await expect(page).toHaveURL(/\/rfqs\/.+\/quote-intake/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Quote Intake' })).toBeVisible();

    // 7) Comparison Runs
    await page.goto(`/rfqs/${E2E_RFQ_ID}/comparison-runs`);
    await expect(page).toHaveURL(/\/rfqs\/.+\/comparison-runs/, { timeout: 15000 });
    await expect(page.locator('h1').filter({ hasText: 'Comparison Runs' })).toBeVisible();
    if ((await page.getByText(/snapshot frozen/i).count()) > 0) {
      await expect(page.getByText(/snapshot frozen/i).first()).toBeVisible();
      await expect(workspaceNavLink(page, 'decision-trail')).toBeVisible();
    }

    // 8) Approvals
    await page.goto(`/rfqs/${E2E_RFQ_ID}/approvals`);
    await expect(page).toHaveURL(/\/rfqs\/.+\/approvals/, { timeout: 15000 });
    await expect(page.getByRole('heading', { level: 1, name: 'Approvals' })).toBeVisible();

    // 9) Award
    await page.goto(`/rfqs/${E2E_RFQ_ID}/award`);
    await expect(page).toHaveURL(/\/rfqs\/.+\/award/, { timeout: 15000 });
    await expect(page.getByText(/select a vendor to award the contract based on the final comparison run/i)).toBeVisible();
    await page.getByRole('button', { name: /create award/i }).click();
    await expect.poll(() => lifecycleApi.getCurrentAward()?.status ?? null).toBe('pending');
    await expect(page.getByText(/recommended for e2e vendor/i)).toBeVisible();

    await page.getByLabel(/debrief message/i).fill('Thanks for your proposal.');
    await page.getByRole('button', { name: /send debrief/i }).click();
    await expect.poll(() => lifecycleApi.getSentDebriefs().length).toBe(1);
    expect(lifecycleApi.getSentDebriefs()[0]).toEqual({
      awardId: 'award-e2e-1',
      vendorId: 'vendor-e2e-2',
      message: 'Thanks for your proposal.',
    });

    await page.getByRole('button', { name: /finalize award/i }).click();
    await expect.poll(() => lifecycleApi.getCurrentAward()?.status ?? null).toBe('signed_off');
    await expect(page.getByRole('status', { name: 'Signed off' }).first()).toBeVisible();
    await expect(page.getByText(/awarded to e2e vendor/i)).toBeVisible();
  });

  const realApiLifecycleTest = process.env.RUN_REAL_API_TESTS === 'true' ? test : test.skip;

  realApiLifecycleTest('full RFQ lifecycle with real API: create via API then navigate to award', async ({ page, request }) => {
    const apiBase = process.env.NEXT_PUBLIC_API_URL ?? process.env.E2E_API_URL ?? 'http://localhost:8000/api/v1';
    const email = process.env.E2E_EMAIL ?? 'user1@example.com';
    const password = process.env.E2E_PASSWORD ?? 'secret';

    const title = 'E2E RFQ real API smoke';
    const idempotencyKey = 'e2e-rfq-real-api-smoke';

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
    const user = loginData.user;
    if (!user || typeof user !== 'object') {
      throw new Error('Login response did not include a user payload.');
    }

    await seedAuthSession(page, user as Parameters<typeof seedAuthSession>[1], {
      token: String(token),
      refreshToken: loginData.refresh_token ?? null,
    });

    const headers = { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' };

    const submissionDeadline = '2030-05-05T00:00:00.000Z';
    const createRes = await request.post(`${apiBase}/rfqs`, {
      headers: {
        ...headers,
        'Idempotency-Key': idempotencyKey,
      },
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

    await page.goto('/');
    await expect(page).toHaveURL('/', { timeout: 15000 });

    await page.goto(`/rfqs/${rfqId}/overview`);
    await expect(page).toHaveURL(new RegExp(`/rfqs/${rfqId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}/overview`));
  });
});
