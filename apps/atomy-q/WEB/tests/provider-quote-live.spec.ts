import fs from 'node:fs';
import path from 'node:path';

import { expect, test } from '@playwright/test';

import { seedAuthSession } from './playwright-auth-bootstrap';
import { discoverProviderQuoteFixtures } from './support/providerQuoteFixtures';

const liveEnabled = process.env.AI_PROVIDER_E2E === 'true';
const requestedFixtureId = process.env.PROVIDER_QUOTE_FIXTURE?.trim() ?? '';
const sampleRoot = path.resolve(__dirname, '../../../../sample');
const discoveredFixtures = discoverProviderQuoteFixtures(sampleRoot);
// Default to one live fixture to control provider cost and quota pressure; set
// AI_PROVIDER_E2E=true plus PROVIDER_QUOTE_FIXTURE=<requisition_id> to target another sample.
const fixtures = requestedFixtureId === ''
  ? discoveredFixtures.slice(0, 1)
  : discoveredFixtures.filter((fixture) => fixture.requisitionId === requestedFixtureId);

test.describe('provider-backed quote e2e with live OpenRouter', () => {
  test.describe.configure({ mode: 'serial' });
  test.setTimeout(180_000);
  test.skip(!liveEnabled, 'Set AI_PROVIDER_E2E=true to run live provider quote e2e.');
  test.skip(discoveredFixtures.length === 0, 'No sample requisition metadata folders found under sample/.');
  test.skip(
    requestedFixtureId !== '' && fixtures.length === 0,
    `No sample requisition matched PROVIDER_QUOTE_FIXTURE=${requestedFixtureId}.`,
  );

  for (const fixture of fixtures) {
    test(`uploads provider quotes and closes normalization for ${fixture.requisitionId}`, async ({ page, request }) => {
      const apiBase = process.env.NEXT_PUBLIC_API_URL ?? process.env.E2E_API_URL ?? 'http://localhost:8000/api/v1';
      const email = process.env.E2E_EMAIL ?? 'user1@example.com';
      const password = process.env.E2E_PASSWORD ?? 'secret';

      expect(process.env.AI_MODE).toBe('provider');
      expect(fixture.e2e.provider).toBe('openrouter');
      expect(fixture.e2e.documentParser.pdfEngine).toBe('mistral-ocr');

      const loginResponse = await request.post(`${apiBase}/auth/login`, {
        data: { email, password },
      });
      expect(loginResponse.ok()).toBeTruthy();

      const loginPayload = await loginResponse.json();
      const token = String(loginPayload.access_token ?? loginPayload.token ?? '');
      expect(token).not.toBe('');

      const user = loginPayload.user as {
        id: string;
        name: string;
        email: string;
        role: string;
        tenant_id?: string;
        tenantId?: string;
      };

      await seedAuthSession(page, {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        tenantId: String(user.tenant_id ?? user.tenantId ?? ''),
      }, {
        token,
        refreshToken: loginPayload.refresh_token ?? null,
      });

      const timestamp = Date.now();
      const createRfqResponse = await request.post(`${apiBase}/rfqs`, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Idempotency-Key': `provider-quote-rfq-${fixture.requisitionId}-${timestamp}`,
        },
        data: {
          title: `${fixture.title} ${timestamp}`,
          description: fixture.title,
          category: 'Services',
          department: 'Procurement',
          estimated_value: 1000,
          submission_deadline: new Date(Date.now() + fixture.submissionDeadlineDaysFromNow * 86400000).toISOString(),
        },
      });
      expect(createRfqResponse.ok()).toBeTruthy();
      const createRfqPayload = await createRfqResponse.json();
      const rfqId = String(createRfqPayload.data?.id ?? '');
      expect(rfqId).not.toBe('');

      const createdLineItemIds: string[] = [];

      for (const lineItem of fixture.rfqLineItems) {
        const lineResponse = await request.post(`${apiBase}/rfqs/${encodeURIComponent(rfqId)}/line-items`, {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
          data: {
            description: lineItem.description,
            quantity: lineItem.quantity,
            uom: lineItem.uom,
            unit_price: 0,
            currency: fixture.currency,
          },
        });
        expect(lineResponse.ok()).toBeTruthy();
        const linePayload = await lineResponse.json();
        createdLineItemIds.push(String(linePayload.data?.id ?? ''));
      }
      expect(createdLineItemIds.every((id) => id !== '')).toBeTruthy();

      for (const [index, quote] of fixture.quotes.entries()) {
        const uploadResponse = await request.post(`${apiBase}/quote-submissions/upload`, {
          headers: {
            Authorization: `Bearer ${token}`,
            'Idempotency-Key': `provider-quote-upload-${fixture.requisitionId}-${index}-${timestamp}`,
          },
          multipart: {
            rfq_id: rfqId,
            vendor_id: `vendor-${index + 1}`,
            vendor_name: quote.vendorName,
            file: {
              name: quote.file,
              mimeType: quote.mimeType,
              buffer: fs.readFileSync(quote.filePath),
            },
          },
        });
        expect(uploadResponse.ok()).toBeTruthy();

        const uploadPayload = await uploadResponse.json();
        expect(fixture.e2e.assertions.uploadStatus).toContain(uploadPayload.data?.status);
      }

      await expect
        .poll(async () => {
          const sourceLinesResponse = await request.get(
            `${apiBase}/normalization/${encodeURIComponent(rfqId)}/source-lines`,
            {
              headers: {
                Authorization: `Bearer ${token}`,
              },
            },
          );
          expect(sourceLinesResponse.ok()).toBeTruthy();

          const sourceLinesPayload = await sourceLinesResponse.json();
          expect(Array.isArray(sourceLinesPayload.data)).toBeTruthy();

          return sourceLinesPayload.data.length;
        }, {
          timeout: 120_000,
          intervals: [1_000, 2_000, 5_000],
        })
        .toBeGreaterThanOrEqual(fixture.e2e.assertions.sourceLinesMin);

      const sourceLinesResponse = await request.get(
        `${apiBase}/normalization/${encodeURIComponent(rfqId)}/source-lines`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      );
      expect(sourceLinesResponse.ok()).toBeTruthy();

      const sourceLinesPayload = await sourceLinesResponse.json();
      const sourceLines = Array.isArray(sourceLinesPayload.data) ? sourceLinesPayload.data as Array<Record<string, unknown>> : [];
      expect(sourceLines.length).toBeGreaterThanOrEqual(fixture.e2e.assertions.sourceLinesMin);

      const firstSourceLine = sourceLines[0];
      expect(firstSourceLine).toBeTruthy();
      const sourceLineId = String(firstSourceLine.id ?? '');
      expect(sourceLineId).not.toBe('');

      const overrideResponse = await request.put(
        `${apiBase}/normalization/source-lines/${encodeURIComponent(sourceLineId)}/override`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
          data: {
            override_data: {
              rfq_line_item_id: createdLineItemIds[0],
              unit_price: firstSourceLine.source_unit_price ?? '0',
            },
            reason_code: 'manual_entry_required',
            note: 'Live normalization alpha readiness proof',
          },
        },
      );
      expect(overrideResponse.ok()).toBeTruthy();

      await expect
        .poll(async () => {
          const conflictsResponse = await request.get(
            `${apiBase}/normalization/${encodeURIComponent(rfqId)}/conflicts`,
            {
              headers: {
                Authorization: `Bearer ${token}`,
              },
            },
          );
          expect(conflictsResponse.ok()).toBeTruthy();
          const conflictsPayload = await conflictsResponse.json();

          return Boolean(conflictsPayload.meta?.has_blocking_issues);
        }, {
          timeout: 60_000,
          intervals: [1_000, 2_000, 5_000],
        })
        .toBe(false);

      const quoteSubmissionsResponse = await request.get(
        `${apiBase}/quote-submissions?rfq_id=${encodeURIComponent(rfqId)}`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      );
      expect(quoteSubmissionsResponse.ok()).toBeTruthy();

      const quoteSubmissionsPayload = await quoteSubmissionsResponse.json();
      const visibleSubmission = (quoteSubmissionsPayload.data as Array<{ id?: string; vendor_name?: string; status?: string }> | undefined)
        ?.find((submission) => submission.status !== 'failed' && submission.vendor_name);
      const visibleVendorName = visibleSubmission?.vendor_name;
      expect(visibleVendorName).toBeTruthy();
      const visibleQuoteId = String(visibleSubmission?.id ?? '');
      expect(visibleQuoteId).not.toBe('');

      await page.goto(`/rfqs/${encodeURIComponent(rfqId)}/quote-intake`);
      await expect(page.getByRole('heading', { name: 'Quote Intake' })).toBeVisible();
      await page.goto(`/rfqs/${encodeURIComponent(rfqId)}/quote-intake/${encodeURIComponent(visibleQuoteId)}/normalize`);
      await expect(page.getByText(/source lines/i)).toBeVisible();
      await expect(page.getByRole('button', { name: /freeze comparison/i })).toBeEnabled();

      const freezeResponse = await request.post(`${apiBase}/comparison-runs/final`, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        data: {
          rfq_id: rfqId,
        },
      });
      expect(freezeResponse.ok()).toBeTruthy();
    });
  }
});
