import { expect, test, type Page } from '@playwright/test';

import { alphaMockUser, seedAuthSession } from './playwright-auth-bootstrap';
import { buildCorsHeaders, fulfillJsonRoute, getRequestOrigin } from './playwright-cors-helpers';

const RFQ_ID = 'RFQ-SOURCING-RECO-001';
const RFQ_TITLE = 'Sourcing Recommendation RFQ';

interface VendorRowFixture {
  id: string;
  legal_name: string;
  display_name: string;
  registration_number: string;
  country_of_registration: string;
  primary_contact_name: string;
  primary_contact_email: string;
  primary_contact_phone: string | null;
  status: 'approved' | 'restricted';
  approval_record: {
    approved_by_user_id: string;
    approved_at: string;
    approval_note: string;
  } | null;
  created_at: string;
  updated_at: string;
  name: string;
  trading_name: string;
  country_code: string;
  email: string;
  phone: string | null;
}

interface SelectedVendorRowFixture {
  id: string;
  rfq_id: string;
  vendor_id: string;
  vendor_name: string;
  vendor_display_name: string;
  vendor_email: string;
  status: string;
  selected_at: string;
  selected_by_user_id: string;
}

interface RecommendationSetup {
  recommendationsAvailable: boolean;
}

function buildApprovedVendorFixtures(): VendorRowFixture[] {
  return [
    {
      id: 'vendor-alpha-1',
      legal_name: 'Facility Experts Pte Ltd',
      display_name: 'Facility Experts',
      registration_number: 'REG-FAC-001',
      country_of_registration: 'SG',
      primary_contact_name: 'Alicia Tan',
      primary_contact_email: 'facility.experts@atomy.test',
      primary_contact_phone: '+65 8000 1001',
      status: 'approved',
      approval_record: {
        approved_by_user_id: alphaMockUser.id,
        approved_at: '2026-04-16T10:00:00Z',
        approval_note: 'Approved for sourcing recommendation browser flow',
      },
      created_at: '2026-04-15T10:00:00Z',
      updated_at: '2026-04-16T10:00:00Z',
      name: 'Facility Experts Pte Ltd',
      trading_name: 'Facility Experts',
      country_code: 'SG',
      email: 'facility.experts@atomy.test',
      phone: '+65 8000 1001',
    },
    {
      id: 'vendor-alpha-2',
      legal_name: 'Backup Facility Vendor Pte Ltd',
      display_name: 'Backup Facility Vendor',
      registration_number: 'REG-FAC-002',
      country_of_registration: 'SG',
      primary_contact_name: 'Ben Lim',
      primary_contact_email: 'backup.facility@atomy.test',
      primary_contact_phone: '+65 8000 1002',
      status: 'approved',
      approval_record: {
        approved_by_user_id: alphaMockUser.id,
        approved_at: '2026-04-16T10:00:00Z',
        approval_note: 'Approved for sourcing recommendation browser flow',
      },
      created_at: '2026-04-15T10:00:00Z',
      updated_at: '2026-04-16T10:00:00Z',
      name: 'Backup Facility Vendor Pte Ltd',
      trading_name: 'Backup Facility Vendor',
      country_code: 'SG',
      email: 'backup.facility@atomy.test',
      phone: '+65 8000 1002',
    },
    {
      id: 'vendor-alpha-3',
      legal_name: 'Restricted Facility Vendor Pte Ltd',
      display_name: 'Restricted Facility Vendor',
      registration_number: 'REG-FAC-003',
      country_of_registration: 'SG',
      primary_contact_name: 'Rina Koh',
      primary_contact_email: 'restricted.facility@atomy.test',
      primary_contact_phone: '+65 8000 1003',
      status: 'restricted',
      approval_record: null,
      created_at: '2026-04-15T10:00:00Z',
      updated_at: '2026-04-16T10:00:00Z',
      name: 'Restricted Facility Vendor Pte Ltd',
      trading_name: 'Restricted Facility Vendor',
      country_code: 'SG',
      email: 'restricted.facility@atomy.test',
      phone: '+65 8000 1003',
    },
  ];
}

function buildRecommendationPayload(tenantId: string, recommendationsAvailable: boolean) {
  if (!recommendationsAvailable) {
    return {
      data: {
        tenant_id: tenantId,
        rfq_id: RFQ_ID,
        status: 'unavailable',
        eligible_candidates: [],
        excluded_candidates: [],
        provider_explanation: 'AI recommendation is unavailable. You can still manually select vendors.',
        deterministic_reason_set: [],
        provenance: {
          provider_name: 'openrouter',
          endpoint_group: 'sourcing_recommendation',
          status: 'unavailable',
        },
      },
    };
  }

  return {
    data: {
      tenant_id: tenantId,
      rfq_id: RFQ_ID,
      status: 'available',
      eligible_candidates: [
        {
          vendor_id: 'vendor-alpha-1',
          vendor_name: 'Facility Experts',
          fit_score: 97,
          confidence_band: 'high',
          provider_explanation: 'Provider ranked Facility Experts first.',
          deterministic_reasons: ['Strong facilities category fit.', 'Category overlap: facilities.'],
          llm_insights: ['Strong emergency maintenance fit.', 'No recent activity signal available.'],
          warning_flags: [],
          warnings: [],
        },
      ],
      excluded_candidates: [
        {
          vendor_id: 'vendor-alpha-3',
          vendor_name: 'Restricted Facility Vendor',
          reason: 'Restricted vendors are not eligible for shortlist selection.',
          status: 'restricted',
        },
      ],
      provider_explanation: 'Provider ranked Facility Experts first.',
      deterministic_reason_set: ['Strong facilities category fit.', 'Approved vendors only.'],
      provenance: {
        provider_name: 'openrouter',
        endpoint_group: 'sourcing_recommendation',
        model_revision: 'openai/gpt-4.1-mini:2026-04-24',
        prompt_template_version: 'vendor-ranking@2026-04-24',
        request_trace_id: 'trace-sourcing-1',
        input_hash: 'sha256:input-sourcing-1',
        output_hash: 'sha256:output-sourcing-1',
        latency_ms: 188,
        confidence: 0.95,
      },
    },
  };
}

async function routeSourcingRecommendationApi(page: Page, options: RecommendationSetup): Promise<{
  selectedVendorRows: SelectedVendorRowFixture[];
}> {
  const approvedVendors = buildApprovedVendorFixtures();
  const tenantId = alphaMockUser.tenantId;
  const selectedVendorRows: SelectedVendorRowFixture[] = [];

  await page.route('**/api/v1/**', async (route) => {
    const request = route.request();
    const method = request.method();
    const url = new URL(request.url());
    const { pathname } = url;

    if (method === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: buildCorsHeaders(getRequestOrigin(route)) });
      return;
    }

    if (pathname.endsWith('/feature-flags')) {
      await fulfillJsonRoute(route, { data: { projects: true, tasks: true } });
      return;
    }

    if (pathname.endsWith('/rfqs/counts')) {
      await fulfillJsonRoute(route, {
        data: { draft: 1, published: 0, closed: 0, awarded: 0, cancelled: 0, active: 1, pending: 0, archived: 0 },
      });
      return;
    }

    if (pathname.endsWith('/ai/status')) {
      const payload = {
        data: {
          mode: 'provider',
          global_health: options.recommendationsAvailable ? 'healthy' : 'degraded',
          provider_name: 'openrouter',
          reason_codes: options.recommendationsAvailable ? [] : ['AI_VENDOR_RECOMMENDATION_UNAVAILABLE'],
          generated_at: '2026-04-27T02:00:00Z',
          capability_definitions: [
            {
              feature_key: 'vendor_ai_ranking',
              capability_group: 'sourcing_recommendation_intelligence',
              requires_ai: true,
              has_manual_fallback: true,
              fallback_ui_mode: 'show_manual_continuity_banner',
              degradation_message_key: 'ai.vendor_ai_ranking.unavailable',
              operator_critical: true,
              endpoint_group: 'sourcing_recommendation',
            },
          ],
          capability_statuses: {
            vendor_ai_ranking: {
              feature_key: 'vendor_ai_ranking',
              capability_group: 'sourcing_recommendation_intelligence',
              endpoint_group: 'sourcing_recommendation',
              status: options.recommendationsAvailable ? 'available' : 'unavailable',
              available: options.recommendationsAvailable,
              fallback_ui_mode: 'show_manual_continuity_banner',
              message_key: 'ai.vendor_ai_ranking.unavailable',
              operator_critical: true,
              reason_codes: options.recommendationsAvailable ? [] : ['AI_VENDOR_RECOMMENDATION_UNAVAILABLE'],
              diagnostics: { provider: 'openrouter' },
            },
          },
          endpoint_groups: [],
        },
      };
      await fulfillJsonRoute(route, payload);
      return;
    }

    if (pathname.endsWith(`/rfqs/${RFQ_ID}`)) {
      await fulfillJsonRoute(route, {
        data: {
          id: RFQ_ID,
          title: RFQ_TITLE,
          description: 'Facilities shortlisting for the browser journey.',
          status: 'draft',
          rfq_number: RFQ_ID,
          vendorsCount: 2,
          quotesCount: 2,
          category: 'Facilities',
          submission_deadline: '2026-05-01T00:00:00.000Z',
        },
      });
      return;
    }

    if (pathname.endsWith('/vendor-recommendations')) {
      await fulfillJsonRoute(route, buildRecommendationPayload(tenantId, options.recommendationsAvailable));
      return;
    }

    if (pathname.endsWith('/selected-vendors')) {
      if (method === 'PUT') {
        const payload = request.postDataJSON() as { vendor_ids?: string[] };
        const vendorIds = Array.isArray(payload.vendor_ids) ? payload.vendor_ids : [];
        selectedVendorRows.splice(0, selectedVendorRows.length);

        for (const vendorId of vendorIds) {
          const vendor = approvedVendors.find((item) => item.id === vendorId);
          if (!vendor) {
            continue;
          }

          selectedVendorRows.push({
            id: `selected-${selectedVendorRows.length + 1}`,
            rfq_id: RFQ_ID,
            vendor_id: vendor.id,
            vendor_name: vendor.name,
            vendor_display_name: vendor.display_name,
            vendor_email: vendor.email,
            status: vendor.status,
            selected_at: '2026-04-27T02:15:00Z',
            selected_by_user_id: alphaMockUser.id,
          });
        }

        await fulfillJsonRoute(route, { data: selectedVendorRows });
        return;
      }

      await fulfillJsonRoute(route, { data: selectedVendorRows });
      return;
    }

    if (pathname.endsWith('/invitations')) {
      await fulfillJsonRoute(route, {
        data: [],
      });
      return;
    }

    if (pathname.endsWith('/vendors')) {
      const status = url.searchParams.get('status');
      const search = (url.searchParams.get('q') ?? url.searchParams.get('search') ?? '').trim().toLowerCase();
      const filtered = approvedVendors.filter((vendor) => {
        if (status && vendor.status !== status) {
          return false;
        }

        if (search === '') {
          return true;
        }

        return [vendor.display_name, vendor.email, vendor.name].some((value) =>
          String(value).toLowerCase().includes(search),
        );
      });

      await fulfillJsonRoute(route, {
        data: filtered,
        meta: { total: filtered.length, total_pages: 1, current_page: 1, per_page: 25 },
      });
      return;
    }

    if (pathname.endsWith('/overview')) {
      await fulfillJsonRoute(route, {
        data: {
          rfq: {
            id: RFQ_ID,
            rfq_number: RFQ_ID,
            title: RFQ_TITLE,
            status: 'draft',
          },
          expected_quotes: 2,
          normalization: null,
          comparison: null,
          approvals: null,
          activity: [],
        },
      });
      return;
    }

    if (pathname.endsWith('/activity')) {
      await fulfillJsonRoute(route, { data: [] });
      return;
    }

    await route.fulfill({
      status: 501,
      contentType: 'application/json',
      body: JSON.stringify({
        error: 'Not Implemented',
        message: `Unhandled sourcing recommendation API request: ${method} ${pathname}`,
      }),
    });
  });

  return { selectedVendorRows };
}

test.describe('provider sourcing recommendation e2e with mocked API responses', () => {
  test('renders provider-backed recommendations and keeps shortlist manual', async ({ page }) => {
    await page.setViewportSize({ width: 1600, height: 1200 });
    await seedAuthSession(page, alphaMockUser);
    const { selectedVendorRows } = await routeSourcingRecommendationApi(page, {
      recommendationsAvailable: true,
    });

    await page.goto(`/rfqs/${RFQ_ID}/vendors`);

    await expect(page.getByText('AI recommendation summary')).toBeVisible();
    await expect(page.getByText('Provider ranked Facility Experts first.')).toBeVisible();
    await expect(page.getByText('Key deterministic reasons: Strong facilities category fit., Approved vendors only.')).toBeVisible();

    const recommendedCheckbox = page.getByRole('checkbox', { name: /select facility experts/i });
    await expect(recommendedCheckbox).not.toBeChecked();
    await expect(page.getByRole('checkbox', { name: /select backup facility vendor/i })).not.toBeChecked();
    await expect(page.getByText('Recommended')).toBeVisible();

    await page.getByRole('button', { name: /why facility experts is recommended/i }).click();
    await expect(page.getByText('Strong emergency maintenance fit.')).toBeVisible();
    await expect(page.getByText('Category overlap: facilities.')).toBeVisible();
    await expect(page.getByText('No recent activity signal available.')).toBeVisible();

    await page.getByRole('checkbox', { name: /select backup facility vendor/i }).check();
    await page.getByRole('button', { name: /save selection/i }).click();

    await expect.poll(() => selectedVendorRows.map((row) => row.vendor_id)).toEqual(['vendor-alpha-2']);
    await page.reload();

    await expect(page.getByText('Backup Facility Vendor Pte Ltd')).toBeVisible();
    await expect(page.getByText('No approved vendors selected')).toHaveCount(0);
  });

  test('keeps manual shortlist usable when recommendation is unavailable', async ({ page }) => {
    await page.setViewportSize({ width: 1600, height: 1200 });
    await seedAuthSession(page, alphaMockUser);
    const { selectedVendorRows } = await routeSourcingRecommendationApi(page, {
      recommendationsAvailable: false,
    });

    await page.goto(`/rfqs/${RFQ_ID}/vendors`);

    await expect(page.getByText('AI recommendation summary')).toBeVisible();
    await expect(page.getByText('AI recommendation is unavailable. You can still manually select vendors.')).toBeVisible();
    await expect(page.getByText('Recommended')).toHaveCount(0);
    await expect(page.getByRole('button', { name: /why facility experts is recommended/i })).toHaveCount(0);

    await page.getByRole('checkbox', { name: /select facility experts/i }).check();
    await page.getByRole('button', { name: /save selection/i }).click();

    await expect.poll(() => selectedVendorRows.map((row) => row.vendor_id)).toEqual(['vendor-alpha-1']);
    await page.reload();

    await expect(page.getByText('Facility Experts Pte Ltd')).toBeVisible();
    await expect(page.getByText('No approved vendors selected')).toHaveCount(0);
  });
});
