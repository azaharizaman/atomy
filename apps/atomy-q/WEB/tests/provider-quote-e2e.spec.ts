import path from 'node:path';

import { expect, test } from '@playwright/test';

import { seedAuthSession } from './playwright-auth-bootstrap';
import { discoverProviderQuoteFixtures } from './support/providerQuoteFixtures';

const fixtures = discoverProviderQuoteFixtures(path.resolve(__dirname, '../../../../sample'));
const fixture = fixtures[0];
const rfqId = 'rfq-provider-fake';
const quoteId = 'quote-1';

test.describe('provider quote e2e with mocked API responses', () => {
  test.skip(!fixture, 'No sample requisition metadata folders found under sample/.');

  test('drives provider-backed normalization through buyer override and comparison freeze', async ({ page }) => {
    if (!fixture) {
      test.fail(true, 'Fixture discovery returned no sample folders.');
      return;
    }

    const rfqLineId = 'rfq-line-1';
    let hasBlockingIssues = true;
    let blockingIssueCount = 1;
    let comparisonFreezeRequested = false;

    const sourceLine = {
      id: 'source-line-1',
      quote_submission_id: quoteId,
      vendor_id: 'vendor-1',
      vendor_name: fixture.quotes[0]?.vendorName ?? 'Vendor 1',
      source_description: fixture.rfqLineItems[0]?.description ?? 'Provider line item',
      source_quantity: '1.0000',
      source_uom: fixture.rfqLineItems[0]?.uom ?? 'JOB',
      source_unit_price: null as string | null,
      rfq_line_item_id: rfqLineId,
      rfq_line_description: fixture.rfqLineItems[0]?.description ?? 'RFQ line 1',
      rfq_line_quantity: '1.0000',
      rfq_line_uom: fixture.rfqLineItems[0]?.uom ?? 'JOB',
      rfq_line_unit_price: '0.0000',
      sort_order: 0,
      confidence: 'medium',
      conflict_count: 1,
      blocking_issue_count: 1,
      has_blocking_issue: true,
      quote_submission_status: 'needs_review',
      ai_confidence: '88.25',
      provider_suggested: {
        rfq_line_item_id: rfqLineId,
        quantity: '1.0000',
        uom: fixture.rfqLineItems[0]?.uom ?? 'JOB',
        unit_price: null,
      },
      effective_values: {
        rfq_line_item_id: rfqLineId,
        quantity: '1.0000',
        uom: fixture.rfqLineItems[0]?.uom ?? 'JOB',
        unit_price: null,
      },
      is_buyer_overridden: false,
      latest_override: null as null | {
        reason_code: string;
        note: string;
        actor_name: string;
        actor_user_id: string;
        overridden_at: string;
        provider_confidence: string;
      },
    };

    await seedAuthSession(page, {
      id: 'user-provider-fake',
      name: fixture.buyer.name,
      email: 'provider-fake@example.com',
      role: 'admin',
      tenantId: fixture.buyer.tenantReference,
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
              global_health: 'healthy',
              provider_name: 'openrouter-fake',
              reason_codes: ['provider_available'],
              capability_statuses: {
                quote_document_extraction: {
                  available: true,
                  status: 'available',
                  fallback_ui_mode: 'show_manual_continuity_banner',
                  operator_critical: true,
                  reason_codes: ['provider_available'],
                },
                normalization_suggestions: {
                  available: true,
                  status: 'available',
                  fallback_ui_mode: 'show_manual_continuity_banner',
                  operator_critical: true,
                  reason_codes: ['provider_available'],
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
              title: fixture.title,
              description: `${fixture.title} fixture`,
              status: 'draft',
              quotes_count: fixture.quotes.length,
              vendors_count: fixture.quotes.length,
              submission_deadline: new Date(Date.now() + fixture.submissionDeadlineDaysFromNow * 86400000).toISOString(),
            },
          }),
        });
        return;
      }

      if (pathname.endsWith(`/rfqs/${rfqId}/line-items`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: fixture.rfqLineItems.map((lineItem, index) => ({
              id: index === 0 ? rfqLineId : `rfq-line-${index + 1}`,
              rfq_id: rfqId,
              description: lineItem.description,
              quantity: lineItem.quantity,
              uom: lineItem.uom,
              unit_price: 0,
              currency: fixture.currency,
              specifications: null,
              sort_order: index,
            })),
          }),
        });
        return;
      }

      if (pathname.endsWith('/quote-submissions') && url.searchParams.get('rfq_id') === rfqId) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: [
              {
                id: quoteId,
                rfq_id: rfqId,
                vendor_id: 'vendor-1',
                vendor_name: fixture.quotes[0]?.vendorName ?? 'Vendor 1',
                original_filename: fixture.quotes[0]?.file ?? 'provider-quote.pdf',
                file_name: fixture.quotes[0]?.file ?? 'provider-quote.pdf',
                status: hasBlockingIssues ? 'needs_review' : 'ready',
                confidence: 88.25,
                submitted_at: new Date().toISOString(),
                blocking_issue_count: blockingIssueCount,
                extraction_origin: 'provider',
                provider_name: 'openrouter-fake',
              },
            ],
            meta: { total: 1 },
          }),
        });
        return;
      }

      if (pathname.endsWith(`/quote-submissions/${quoteId}`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: quoteId,
              rfq_id: rfqId,
              vendor_id: 'vendor-1',
              vendor_name: fixture.quotes[0]?.vendorName ?? 'Vendor 1',
              original_filename: fixture.quotes[0]?.file ?? 'provider-quote.pdf',
              status: hasBlockingIssues ? 'needs_review' : 'ready',
              blocking_issue_count: blockingIssueCount,
              submitted_at: new Date().toISOString(),
              confidence: 88.25,
            },
          }),
        });
        return;
      }

      if (pathname.endsWith(`/normalization/${rfqId}/source-lines`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: [sourceLine],
            meta: {
              rfq_id: rfqId,
              source_line_count: 1,
              mapped_count: sourceLine.rfq_line_item_id ? 1 : 0,
              blocking_issue_count: blockingIssueCount,
              ai_status: {
                normalization: {
                  feature_key: 'normalization_suggestions',
                  status: 'available',
                  available: true,
                  manual_action_required: false,
                  reason_codes: ['provider_available'],
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
            data: hasBlockingIssues
              ? [{
                  id: 'conflict-1',
                  conflict_type: 'price_missing',
                  resolution: null,
                  normalization_source_line_id: sourceLine.id,
                }]
              : [],
            meta: {
              rfq_id: rfqId,
              has_blocking_issues: hasBlockingIssues,
              blocking_issue_count: blockingIssueCount,
            },
          }),
        });
        return;
      }

      if (pathname.endsWith(`/quote-submissions/${quoteId}/source-lines/${sourceLine.id}`) && method === 'PUT') {
        const body = route.request().postDataJSON() as Record<string, unknown>;
        sourceLine.source_unit_price = String(body.source_unit_price ?? '12.34');
        sourceLine.effective_values = {
          ...sourceLine.effective_values,
          unit_price: sourceLine.source_unit_price,
        };
        sourceLine.is_buyer_overridden = true;
        sourceLine.latest_override = {
          reason_code: String(body.reason ?? 'price_correction'),
          note: String(body.note ?? 'Matched supplier quote'),
          actor_name: fixture.buyer.name,
          actor_user_id: 'user-provider-fake',
          overridden_at: new Date().toISOString(),
          provider_confidence: '88.25',
        };
        sourceLine.blocking_issue_count = 0;
        sourceLine.has_blocking_issue = false;
        sourceLine.conflict_count = 0;
        sourceLine.quote_submission_status = 'ready';
        hasBlockingIssues = false;
        blockingIssueCount = 0;

        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: { id: sourceLine.id },
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
              id: 'comparison-run-1',
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

    await page.goto(`/rfqs/${rfqId}/quote-intake`);

    await expect(page.getByRole('heading', { name: 'Quote Intake' })).toBeVisible();
    await expect(page.getByRole('cell', { name: fixture.quotes[0]?.vendorName ?? '' })).toBeVisible();
    await expect(page.getByText(/blocking issues/i)).toBeVisible();

    await page.goto(`/rfqs/${rfqId}/quote-intake/${quoteId}`);
    await expect(page.getByRole('button', { name: /accept & normalize/i })).toBeVisible();
    await page.getByRole('button', { name: /accept & normalize/i }).click();

    await expect(page.getByText(/source lines/i)).toBeVisible();
    await expect(page.getByText(/provider confidence 88\.25%/i)).toBeVisible();
    await expect(page.getByText(/provider suggested/i)).toBeVisible();
    await expect(page.getByRole('button', { name: /freeze comparison/i })).toBeDisabled();

    await page.getByRole('button', { name: /edit source line 1/i }).click({ force: true });
    await page.getByLabel(/unit price source line 1/i).fill('12.34');
    await page.getByLabel(/reason code source line 1/i).selectOption('price_correction');
    await page.getByLabel(/note source line 1/i).fill('Matched supplier quote');
    await page.getByRole('button', { name: /save source line 1/i }).click();

    await expect(page.getByText(/buyer override/i)).toBeVisible();
    await expect(page.getByText(/override reason price correction/i)).toBeVisible();
    await expect(page.getByRole('button', { name: /freeze comparison/i })).toBeEnabled();

    await page.getByRole('button', { name: /freeze comparison/i }).click();
    await expect.poll(() => comparisonFreezeRequested).toBe(true);
  });
});
