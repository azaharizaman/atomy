import { expect, test } from '@playwright/test';

import { seedAuthSession } from './playwright-auth-bootstrap';

const rfqId = 'rfq-provider-degraded';
const quoteId = 'quote-degraded-1';

test.describe('provider normalization degraded manual continuity', () => {
  test('keeps manual normalization usable and allows freeze without fabricated provider success', async ({ page }) => {
    let hasBlockingIssues = true;
    let blockingIssueCount = 1;
    let comparisonFreezeRequested = false;
    const sourceLines: Array<Record<string, unknown>> = [];

    await seedAuthSession(page, {
      id: 'user-provider-degraded',
      name: 'Buyer Degraded',
      email: 'provider-degraded@example.com',
      role: 'admin',
      tenantId: 'default-alpha-buyer',
    });

    await page.route('**/api/v1/**', async (route) => {
      const url = new URL(route.request().url());
      const pathname = url.pathname;
      const method = route.request().method();

      if (pathname.endsWith('/ai/status')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              mode: 'provider',
              global_health: 'degraded',
              provider_name: 'openrouter-fake',
              reason_codes: ['provider_timeout'],
              capability_statuses: {
                quote_document_extraction: {
                  available: false,
                  status: 'degraded',
                  fallback_ui_mode: 'show_manual_continuity_banner',
                  operator_critical: true,
                  reason_codes: ['provider_timeout'],
                },
                normalization_suggestions: {
                  available: false,
                  status: 'degraded',
                  fallback_ui_mode: 'show_manual_continuity_banner',
                  operator_critical: true,
                  reason_codes: ['provider_timeout'],
                },
              },
            },
          }),
        });
        return;
      }

      if (pathname.endsWith(`/rfqs/${rfqId}`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: rfqId,
              title: 'Degraded provider normalization RFQ',
              status: 'draft',
              quotes_count: 1,
              vendors_count: 1,
              submission_deadline: new Date(Date.now() - 86400000).toISOString(),
            },
          }),
        });
        return;
      }

      if (pathname.endsWith(`/rfqs/${rfqId}/overview`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              rfq: {
                id: rfqId,
                rfq_number: rfqId,
                title: 'Degraded provider normalization RFQ',
                status: 'draft',
                submission_deadline: new Date(Date.now() - 86400000).toISOString(),
                vendors_count: 1,
                quotes_count: 1,
              },
              expected_quotes: 1,
              normalization: {
                total_quotes: 1,
                ready_count: hasBlockingIssues ? 0 : 1,
                accepted_count: hasBlockingIssues ? 0 : 1,
                progress_pct: hasBlockingIssues ? 0 : 100,
                uploaded_count: 0,
                needs_review_count: hasBlockingIssues ? 1 : 0,
              },
              comparison: null,
              approvals: {
                pending_count: 0,
                approved_count: 0,
                rejected_count: 0,
                overall: 'none',
              },
              activity: [],
            },
          }),
        });
        return;
      }

      if (pathname.endsWith(`/rfqs/${rfqId}/activity`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: [] }),
        });
        return;
      }

      if (pathname.endsWith(`/rfqs/${rfqId}/line-items`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: [{
              id: 'rfq-line-1',
              rfq_id: rfqId,
              description: 'Manual continuity line',
              quantity: 1,
              uom: 'JOB',
              unit_price: 0,
              currency: 'MYR',
              specifications: null,
              sort_order: 0,
            }],
          }),
        });
        return;
      }

      if (pathname.endsWith(`/normalization/${rfqId}/source-lines`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: sourceLines,
            meta: {
              rfq_id: rfqId,
              source_line_count: sourceLines.length,
              mapped_count: sourceLines.filter((line) => line.rfq_line_item_id).length,
              blocking_issue_count: blockingIssueCount,
              ai_status: {
                normalization: {
                  feature_key: 'normalization_suggestions',
                  status: 'degraded',
                  available: false,
                  manual_action_required: true,
                  reason_codes: ['provider_timeout'],
                  provider_provenance: null,
                },
              },
            },
          }),
        });
        return;
      }

      if (pathname.endsWith(`/normalization/${rfqId}/conflicts`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: [],
            meta: {
              rfq_id: rfqId,
              has_blocking_issues: hasBlockingIssues,
              blocking_issue_count: blockingIssueCount,
            },
          }),
        });
        return;
      }

      if (pathname.endsWith(`/quote-submissions/${quoteId}/source-lines`) && method === 'POST') {
        const body = route.request().postDataJSON() as Record<string, unknown>;
        sourceLines.push({
          id: 'manual-line-1',
          quote_submission_id: quoteId,
          vendor_id: 'vendor-1',
          vendor_name: 'Manual Vendor',
          source_description: String(body.source_description ?? 'Manual continuity line'),
          source_quantity: String(body.source_quantity ?? '1'),
          source_uom: String(body.source_uom ?? 'JOB'),
          source_unit_price: String(body.source_unit_price ?? '0'),
          rfq_line_item_id: String(body.rfq_line_item_id ?? 'rfq-line-1'),
          rfq_line_description: 'Manual continuity line',
          rfq_line_quantity: '1.0000',
          rfq_line_uom: 'JOB',
          rfq_line_unit_price: '0.0000',
          sort_order: 0,
          confidence: 'low',
          conflict_count: 0,
          blocking_issue_count: 0,
          has_blocking_issue: false,
          quote_submission_status: 'ready',
          ai_confidence: null,
          provider_suggested: null,
          effective_values: {
            rfq_line_item_id: 'rfq-line-1',
            quantity: String(body.source_quantity ?? '1'),
            uom: String(body.source_uom ?? 'JOB'),
            unit_price: String(body.source_unit_price ?? '0'),
          },
          is_buyer_overridden: false,
          latest_override: null,
          origin: 'manual',
        });
        hasBlockingIssues = false;
        blockingIssueCount = 0;

        await route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            data: { id: 'manual-line-1' },
            meta: {
              quote_submission_status: 'ready',
              manual_action_required: false,
            },
          }),
        });
        return;
      }

      if (pathname.endsWith('/comparison-runs/final') && method === 'POST') {
        comparisonFreezeRequested = true;
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: 'comparison-run-degraded-1',
              rfq_id: rfqId,
              status: 'final',
            },
          }),
        });
        return;
      }

      await route.fulfill({
        status: 404,
        contentType: 'application/json',
        body: JSON.stringify({ message: `Unhandled mocked API path: ${pathname}` }),
      });
    });

    await page.goto(`/rfqs/${rfqId}/quote-intake/${quoteId}/normalize`);

    await expect(page.getByText(/ai normalization suggestions are unavailable/i)).toBeVisible();
    await expect(page.getByText(/manual source-line mapping and comparison preparation remain available/i)).toBeVisible();
    await expect(page.getByRole('textbox', { name: /description/i })).toBeVisible();
    await expect(page.getByText(/normalize workspace unavailable/i)).toHaveCount(0);

    await page.getByRole('textbox', { name: /description/i }).fill('Manual continuity line');
    await page.getByLabel(/quantity/i).fill('1');
    await page.getByLabel(/uom/i).fill('JOB');
    await page.getByLabel(/unit price/i).fill('0');
    await page.getByLabel(/rfq line/i).selectOption('rfq-line-1');
    await page.getByLabel(/reason code/i).selectOption('manual_entry_required');
    await page.getByRole('button', { name: /add source line/i }).click();

    await expect(page.locator('span', { hasText: 'Manual continuity line' }).first()).toBeVisible();
    await expect(page.getByRole('button', { name: /freeze comparison/i })).toBeEnabled();

    await page.getByRole('button', { name: /freeze comparison/i }).click();
    await expect.poll(() => comparisonFreezeRequested).toBe(true);
  });
});
