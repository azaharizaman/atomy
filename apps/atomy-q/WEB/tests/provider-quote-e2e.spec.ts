import path from 'node:path';

import { expect, test } from '@playwright/test';

import { seedAuthSession } from './playwright-auth-bootstrap';
import { discoverProviderQuoteFixtures } from './support/providerQuoteFixtures';

const fixtures = discoverProviderQuoteFixtures(path.resolve(process.cwd(), '../../..', 'sample'));
const fixture = fixtures[0];
const rfqId = 'rfq-provider-fake';

test.describe('provider quote e2e with mocked API responses', () => {
  test.skip(!fixture, 'No sample requisition metadata folders found under sample/.');

  test('renders quote-intake provider extraction state from folder fixtures', async ({ page }) => {
    if (!fixture) {
      test.fail(true, 'Fixture discovery returned no sample folders.');
      return;
    }

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

      if (pathname.endsWith('/quote-submissions') && url.searchParams.get('rfq_id') === rfqId) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: fixture.quotes.map((quote, index) => ({
              id: `quote-${index + 1}`,
              rfq_id: rfqId,
              vendor_id: `vendor-${index + 1}`,
              vendor_name: quote.vendorName,
              original_filename: quote.file,
              file_name: quote.file,
              status: fixture.e2e.assertions.uploadStatus[0] ?? 'needs_review',
              confidence: 91,
              submitted_at: new Date().toISOString(),
              blocking_issue_count: 0,
              extraction_origin: 'provider',
              provider_name: 'openrouter-fake',
            })),
            meta: {
              total: fixture.quotes.length,
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
              has_blocking_issues: false,
              blocking_issue_count: 0,
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

    await expect(page.getByText(/AI-assisted quote extraction/i)).toBeVisible();
    await expect(page.getByText(/provider extraction active via openrouter-fake/i)).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Quote Intake' })).toBeVisible();
    await expect(page.getByRole('cell', { name: fixture.quotes[0]?.vendorName ?? '' })).toBeVisible();
    await expect(page.getByText(fixture.quotes[0]?.file ?? '')).toBeVisible();
  });
});
