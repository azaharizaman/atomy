import { expect, test, type Page } from '@playwright/test';
import { alphaMockUser, stubAlphaSession } from './alpha-playwright-bootstrap';
import { buildCorsHeaders, fulfillJsonRoute, getRequestOrigin } from './playwright-cors-helpers';

const RFQ_ID = 'RFQ-E2E-ALPHA-001';
const CREATED_RFQ_ID = 'RFQ-E2E-CREATED';
const RFQ_TITLE = 'Alpha Journey RFQ';

interface LineItemFixture {
  id: string;
  rfq_id: string;
  description: string;
  quantity: number;
  uom: string;
  unit_price: number;
  currency: string;
  specifications: string | null;
  sort_order: number;
  rowType: string;
}

function rfqFixture(id = RFQ_ID, title = RFQ_TITLE, status = 'active') {
  return {
    id,
    rfq_number: id,
    title,
    description: 'Browser journey RFQ fixture',
    status,
    owner: { id: alphaMockUser.id, name: alphaMockUser.name, email: alphaMockUser.email },
    deadline: '2026-05-01',
    category: 'Facilities',
    estimated_value: 50000,
    estValue: 50000,
    savings_percentage: 12,
    savings: '12%',
    vendors_count: 2,
    quotes_count: 2,
    vendorsCount: 2,
    quotesCount: 2,
    submission_deadline: '2026-05-01T00:00:00.000Z',
    closing_date: '2026-05-05T00:00:00.000Z',
    expected_award_at: '2026-05-10T00:00:00.000Z',
    technical_review_due_at: '2026-05-06T00:00:00.000Z',
    financial_review_due_at: '2026-05-07T00:00:00.000Z',
  };
}

async function routeAlphaRfqApi(page: Page) {
  let rfqs = [rfqFixture(RFQ_ID, RFQ_TITLE, 'draft')];
  let lineItems: LineItemFixture[] = [
    {
      id: 'line-1',
      rfq_id: RFQ_ID,
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
          vendors: Array<{ vendor_id: string; vendor_name: string; quote_submission_id: string | null }>;
        };
      } = null;
  const sentDebriefs: Array<{ awardId: string; vendorId: string; message: string }> = [];

  await page.route('**/api/v1/projects*', async (route) => {
    await fulfillJsonRoute(route, { data: [] });
  });

  await page.route('**/api/v1/rfqs**', async (route) => {
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: buildCorsHeaders(getRequestOrigin(route)) });
      return;
    }

    const requestUrl = new URL(route.request().url());
    const pathname = requestUrl.pathname;

    if (pathname.endsWith('/counts')) {
      await fulfillJsonRoute(route, {
        data: { draft: 1, published: 0, closed: 0, awarded: 0, cancelled: 0, active: 1, pending: 0, archived: 0 },
      });
      return;
    }

    if (route.request().method() === 'POST' && pathname.endsWith('/rfqs')) {
      const payload = route.request().postDataJSON() as { title?: string };
      const created = rfqFixture(CREATED_RFQ_ID, payload.title ?? 'Created RFQ', 'draft');
      rfqs = [created, ...rfqs];
      await fulfillJsonRoute(route, { data: created }, 201);
      return;
    }

    if (pathname.endsWith('/overview')) {
      const rfqId = pathname.split('/').at(-2) ?? RFQ_ID;
      const rfq = rfqs.find((item) => item.id === rfqId) ?? rfqFixture(rfqId, RFQ_TITLE);
      await fulfillJsonRoute(route, {
        data: {
          rfq,
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
            pending_count: 1,
            approved_count: 0,
            rejected_count: 0,
            overall: 'pending',
          },
          activity: [
            {
              id: 'activity-1',
              type: 'comparison',
              actor: alphaMockUser.name,
              action: 'Comparison snapshot frozen',
              timestamp: '2026-04-16T10:00:00Z',
            },
          ],
        },
      });
      return;
    }

    if (pathname.endsWith('/activity')) {
      await fulfillJsonRoute(route, {
        data: [
          {
            id: 'activity-1',
            type: 'comparison',
            actor: alphaMockUser.name,
            action: 'Comparison snapshot frozen',
            timestamp: '2026-04-16T10:00:00Z',
          },
        ],
      });
      return;
    }

    if (pathname.endsWith('/invitations')) {
      await fulfillJsonRoute(route, {
        data: [
          {
            id: 'inv-1',
            vendor_id: 'vendor-alpha-1',
            vendor_name: 'Alpha Vendor',
            vendor_email: 'alpha.vendor@atomy.test',
            status: 'accepted',
          },
          {
            id: 'inv-2',
            vendor_id: 'vendor-alpha-2',
            vendor_name: 'Backup Vendor',
            vendor_email: 'backup.vendor@atomy.test',
            status: 'invited',
          },
        ],
      });
      return;
    }

    if (pathname.endsWith('/line-items')) {
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
          rfq_id: RFQ_ID,
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
      return;
    }

    const rfqId = pathname.split('/').pop() ?? RFQ_ID;
    if (pathname.includes('/rfqs/') && !pathname.endsWith('/rfqs')) {
      const status = rfqId === RFQ_ID ? 'draft' : 'active';
      await fulfillJsonRoute(route, { data: rfqs.find((item) => item.id === rfqId) ?? rfqFixture(rfqId, RFQ_TITLE, status) });
      return;
    }

    await fulfillJsonRoute(route, {
      data: rfqs,
      meta: { total: rfqs.length, total_pages: 1, current_page: 1, per_page: 25 },
    });
  });

  await page.route('**/api/v1/normalization/**', async (route) => {
    await fulfillJsonRoute(route, {
      data: [],
      meta: { rfq_id: RFQ_ID, has_blocking_issues: false, blocking_issue_count: 0 },
    });
  });

  await page.route('**/api/v1/quote-submissions**', async (route) => {
    await fulfillJsonRoute(route, {
      data: [
        {
          id: 'quote-alpha-1',
          rfq_id: RFQ_ID,
          vendor_id: 'vendor-alpha-1',
          vendor_name: 'Alpha Vendor',
          original_filename: 'alpha-vendor.pdf',
          status: 'ready',
          confidence: 95,
          submitted_at: '2026-04-16T09:00:00Z',
          blocking_issue_count: 0,
        },
        {
          id: 'quote-alpha-2',
          rfq_id: RFQ_ID,
          vendor_id: 'vendor-alpha-2',
          vendor_name: 'Backup Vendor',
          original_filename: 'backup-vendor.pdf',
          status: 'needs_review',
          confidence: 72,
          submitted_at: '2026-04-16T09:05:00Z',
          blocking_issue_count: 0,
        },
      ],
    });
  });

  await page.route('**/api/v1/comparison-runs**', async (route) => {
    const requestUrl = new URL(route.request().url());
    const pathname = requestUrl.pathname;

    if (pathname.endsWith('/comparison-runs')) {
      await fulfillJsonRoute(route, {
        data: [
          {
            id: 'run-final-alpha',
            rfq_id: RFQ_ID,
            name: 'Final comparison',
            type: 'final',
            status: 'frozen',
            created_at: '2026-04-16T10:00:00Z',
          },
        ],
      });
      return;
    }

    if (pathname.endsWith('/comparison-runs/run-final-alpha/matrix')) {
      await fulfillJsonRoute(route, {
        data: {
          id: 'run-final-alpha',
          clusters: [
            {
              clusterKey: 'line-1',
              basis: 'price',
              offers: [
                {
                  vendorId: 'vendor-alpha-1',
                  rfqLineId: 'line-1',
                  taxonomyCode: 'HW-1',
                  normalizedUnitPrice: 100,
                  normalizedQuantity: 5,
                  aiConfidence: 0.98,
                },
                {
                  vendorId: 'vendor-alpha-2',
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
                recommendedVendorId: 'vendor-alpha-1',
                reason: 'Lowest evaluated total',
              },
            },
          ],
        },
      });
      return;
    }

    await fulfillJsonRoute(route, {
      data: {
        id: 'run-final-alpha',
        rfq_id: RFQ_ID,
        name: 'Final comparison',
        status: 'frozen',
        is_preview: false,
        created_at: '2026-04-16T10:00:00Z',
        snapshot: {
          rfqVersion: 1,
          normalizedLines: [
            {
              rfqLineItemId: 'line-1',
              sourceDescription: 'Existing line item',
              sourceLineId: 'source-line-1',
              quoteSubmissionId: 'quote-alpha-1',
              vendorId: 'vendor-alpha-1',
              sourceUnitPrice: '100',
              sourceUom: 'ea',
              sourceQuantity: '5',
            },
          ],
          resolutions: [],
          currencyMeta: { 'line-1': 'USD' },
          vendors: [
            { vendorId: 'vendor-alpha-1', vendorName: 'Alpha Vendor', quoteSubmissionId: 'quote-alpha-1' },
            { vendorId: 'vendor-alpha-2', vendorName: 'Backup Vendor', quoteSubmissionId: 'quote-alpha-2' },
          ],
        },
      },
    });
  });

  await page.route('**/api/v1/approvals**', async (route) => {
    await fulfillJsonRoute(route, {
      data: [
        {
          id: 'approval-alpha-1',
          rfq_id: RFQ_ID,
          rfq_title: RFQ_TITLE,
          type: 'award_signoff',
          type_label: 'Award signoff',
          status: 'pending',
          priority: 'high',
          summary: 'Final award decision awaiting sign-off',
          assignee: alphaMockUser.name,
          requested_at: '2026-04-16T10:05:00Z',
        },
      ],
      meta: { total: 1, total_pages: 1, current_page: 1, per_page: 20 },
    });
  });

  await page.route('**/api/v1/awards**', async (route) => {
    if (route.request().method() === 'GET') {
      await fulfillJsonRoute(route, { data: currentAward ? [currentAward] : [] });
      return;
    }

    const payload = route.request().postDataJSON() as { comparison_run_id: string; vendor_id: string };
    currentAward = {
      id: 'award-alpha-1',
      rfq_id: RFQ_ID,
      rfq_title: RFQ_TITLE,
      rfq_number: RFQ_ID,
      comparison_run_id: payload.comparison_run_id,
      vendor_id: payload.vendor_id,
      vendor_name: 'Alpha Vendor',
      status: 'pending',
      amount: 500,
      currency: 'USD',
      split_details: [],
      protest_id: null,
      signoff_at: null,
      signed_off_by: null,
      comparison: {
        vendors: [
          { vendor_id: 'vendor-alpha-1', vendor_name: 'Alpha Vendor', quote_submission_id: 'quote-alpha-1' },
          { vendor_id: 'vendor-alpha-2', vendor_name: 'Backup Vendor', quote_submission_id: 'quote-alpha-2' },
        ],
      },
    };
    await fulfillJsonRoute(route, { data: currentAward }, 201);
  });

  await page.route('**/api/v1/awards/award-alpha-1/signoff', async (route) => {
    currentAward = currentAward
      ? { ...currentAward, status: 'signed_off', signoff_at: '2026-04-16T10:10:00Z', signed_off_by: alphaMockUser.name }
      : null;
    await fulfillJsonRoute(route, { data: currentAward });
  });

  await page.route('**/api/v1/awards/award-alpha-1/debrief/**', async (route) => {
    const payload = route.request().postDataJSON() as { message?: string };
    sentDebriefs.push({
      awardId: 'award-alpha-1',
      vendorId: route.request().url().split('/').pop() ?? '',
      message: payload.message ?? '',
    });
    await fulfillJsonRoute(route, { data: currentAward });
  });

  return {
    getCurrentAward: () => currentAward,
    getSentDebriefs: () => sentDebriefs,
  };
}

test.describe('RFQ alpha journeys', () => {
  test.beforeEach(async ({ page }) => {
    await stubAlphaSession(page);
    await routeAlphaRfqApi(page);
  });

  test('renders the RFQ list', async ({ page }) => {
    await page.goto('/rfqs');

    await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible();
    await expect(page.getByText(RFQ_TITLE)).toBeVisible();
  });

  test('creates an RFQ and navigates to its overview', async ({ page }) => {
    await page.goto('/rfqs/new');

    await expect(page.getByRole('heading', { name: 'Create New RFQ' })).toBeVisible();
    await page.getByLabel('Title').fill('Created Alpha RFQ');
    await page.getByLabel('Submission deadline').fill('2030-05-05T09:30');

    const createRequest = page.waitForRequest((request) => {
      const url = new URL(request.url());
      return request.method() === 'POST' && url.pathname.endsWith('/api/v1/rfqs');
    });

    await page.getByRole('button', { name: 'Create RFQ' }).click();
    const request = await createRequest;
    const payload = request.postDataJSON() as { title?: string; submission_deadline?: string };

    expect(payload.title).toBe('Created Alpha RFQ');
    expect(payload.submission_deadline).toContain('2030-05-05');
    await expect(page).toHaveURL(new RegExp(`/rfqs/${CREATED_RFQ_ID}/overview$`), { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Schedule' })).toBeVisible({ timeout: 15000 });
  });

  test('renders overview and details', async ({ page }) => {
    await page.goto(`/rfqs/${RFQ_ID}/overview`);
    await expect(page.getByRole('heading', { name: 'Schedule' })).toBeVisible();
    await expect(page.getByText(/activity timeline/i)).toBeVisible();

    await page.goto(`/rfqs/${RFQ_ID}/details`);
    await expect(page.getByRole('heading', { name: /rfq details/i })).toBeVisible();
    await expect(page.getByText('Commercial metadata')).toBeVisible();
  });

  test('adds a line item and refreshes visible requested items', async ({ page }) => {
    await page.goto(`/rfqs/${RFQ_ID}/line-items`);

    await expect(page.getByRole('heading', { name: 'Line items' })).toBeVisible();
    await expect(page.getByText('Existing line item')).toBeVisible();

    await page.getByRole('button', { name: /add line item/i }).first().click();
    await page.getByLabel('Description').fill('Nitrogen compressor');
    await page.getByLabel('Quantity').fill('2');
    await page.getByLabel('UOM').fill('ea');
    await page.getByLabel('Unit price').fill('1200');
    await page.getByLabel('Currency').fill('USD');

    const saveRequest = page.waitForRequest((request) => {
      const url = new URL(request.url());
      return request.method() === 'POST' && url.pathname.endsWith(`/api/v1/rfqs/${RFQ_ID}/line-items`);
    });

    await page.getByRole('button', { name: /save line item/i }).click();
    const request = await saveRequest;
    const payload = request.postDataJSON() as {
      description?: string;
      quantity?: number;
      uom?: string;
      unit_price?: number;
      currency?: string;
    };

    expect(payload).toMatchObject({
      description: 'Nitrogen compressor',
      quantity: 2,
      uom: 'ea',
      unit_price: 1200,
      currency: 'USD',
    });
    await expect(page.getByRole('table').getByText('Nitrogen compressor')).toBeVisible({ timeout: 15000 });
    await expect(page.getByText('Existing line item')).toBeVisible();
  });

  test('renders vendors, quote intake, comparison runs, approvals, award, and decision trail', async ({ page }) => {
    test.setTimeout(90_000);
    const lifecycleApi = await routeAlphaRfqApi(page);

    await page.goto(`/rfqs/${RFQ_ID}/vendors`);
    await expect(page.getByRole('heading', { name: 'Invited vendors' })).toBeVisible();
    await expect(page.getByText('Alpha Vendor')).toBeVisible();

    await page.goto(`/rfqs/${RFQ_ID}/quote-intake`);
    await expect(page.getByRole('heading', { name: 'Quote Intake' })).toBeVisible();
    await expect(page.getByText('alpha-vendor.pdf')).toBeVisible();

    await page.goto(`/rfqs/${RFQ_ID}/comparison-runs`);
    await expect(page.locator('h1').filter({ hasText: 'Comparison Runs' })).toBeVisible();
    await expect(page.getByText(/snapshot frozen/i)).toBeVisible();

    await page.goto(`/rfqs/${RFQ_ID}/approvals`);
    await expect(page.getByRole('heading', { level: 1, name: 'Approvals' })).toBeVisible();
    await expect(page.getByText('Final award decision awaiting sign-off')).toBeVisible();

    await page.goto(`/rfqs/${RFQ_ID}/award`);
    await expect(page.getByText(/select a vendor to award the contract based on the final comparison run/i)).toBeVisible();
    await page.getByRole('button', { name: /create award/i }).click();
    await expect.poll(() => lifecycleApi.getCurrentAward()?.status ?? null).toBe('pending');
    await expect(page.getByText(/recommended for alpha vendor/i)).toBeVisible();

    await page.getByLabel(/debrief message/i).fill('Thanks for your proposal.');
    await page.getByRole('button', { name: /send debrief/i }).click();
    await expect.poll(() => lifecycleApi.getSentDebriefs().length).toBe(1);

    await page.getByRole('button', { name: /finalize award/i }).click();
    await expect.poll(() => lifecycleApi.getCurrentAward()?.status ?? null).toBe('signed_off');
    await expect(page.getByRole('status', { name: 'Signed off' }).first()).toBeVisible();

    await page.goto(`/rfqs/${RFQ_ID}/decision-trail`);
    await expect(page.getByRole('heading', { name: 'Decision Trail' })).toBeVisible();
    await expect(page.getByText('Chronological trail')).toBeVisible();
  });
});
