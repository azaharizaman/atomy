import { describe, expect, it } from 'vitest';

import { normalizeVendorRecommendationPayload } from './use-vendor-recommendations';

describe('normalizeVendorRecommendationPayload', () => {
  it('parses the new recommendation payload shape and keeps legacy aliases in sync', () => {
    const result = normalizeVendorRecommendationPayload({
      data: {
        tenant_id: 'tenant-1',
        rfq_id: 'rfq-1',
        status: 'available',
        eligible_candidates: [
          {
            vendor_id: 'vendor-1',
            vendor_name: 'Alpha Procurement',
            fit_score: 93,
            confidence_band: 'high',
            provider_explanation: 'Strong category fit.',
            deterministic_reasons: ['Category overlap: facilities.'],
            llm_insights: ['Narrative support.'],
            warning_flags: ['thin_history'],
            warnings: ['Recent history is thin.'],
          },
        ],
        excluded_candidates: [
          {
            vendor_id: 'vendor-2',
            vendor_name: 'Beta Supply',
            reason: 'Does not cover the requested category.',
            status: 'excluded',
          },
        ],
        provider_explanation: 'AI ranking is based on supplier history and category match.',
        deterministic_reason_set: ['Category overlap: facilities.'],
        provenance: {
          provider: 'openrouter',
          endpoint_group: 'vendor_ranking',
          model: 'gpt-4.1-mini',
        },
        candidates: [
          {
            vendor_id: 'vendor-1',
            vendor_name: 'Alpha Procurement',
            fit_score: 93,
            confidence_band: 'high',
            provider_explanation: 'Strong category fit.',
            deterministic_reasons: ['Category overlap: facilities.'],
            llm_insights: ['Narrative support.'],
            warning_flags: ['thin_history'],
            warnings: ['Recent history is thin.'],
          },
        ],
        excluded_reasons: [
          {
            vendor_id: 'vendor-2',
            vendor_name: 'Beta Supply',
            reason: 'Does not cover the requested category.',
            status: 'excluded',
          },
        ],
      },
    });

    expect(result.status).toBe('available');
    expect(result.eligibleCandidates).toHaveLength(1);
    expect(result.eligibleCandidates[0]?.recommendedReasonSummary).toBe('Strong category fit.');
    expect(result.candidates).toBe(result.eligibleCandidates);
    expect(result.excludedCandidates).toHaveLength(1);
    expect(result.excludedReasons).toBe(result.excludedCandidates);
    expect(result.providerExplanation).toBe('AI ranking is based on supplier history and category match.');
    expect(result.deterministicReasonSet).toEqual(['Category overlap: facilities.']);
    expect(result.provenance).toMatchObject({
      provider: 'openrouter',
      endpoint_group: 'vendor_ranking',
      model: 'gpt-4.1-mini',
    });
  });

  it('accepts legacy candidate aliases', () => {
    const result = normalizeVendorRecommendationPayload({
      data: {
        tenantId: 'tenant-1',
        rfqId: 'rfq-1',
        status: 'available',
        candidates: [
          {
            vendorId: 'vendor-1',
            vendorName: 'Alpha Procurement',
            fitScore: 93,
            confidenceBand: 'high',
            recommendedReasonSummary: 'Strong category fit.',
            deterministicReasons: ['Category overlap: facilities.'],
            llmInsights: [],
            warningFlags: [],
            warnings: [],
          },
        ],
        excludedReasons: [],
        providerExplanation: 'Legacy payload',
        deterministicReasonSet: ['Category overlap: facilities.'],
        provenance: null,
      },
    });

    expect(result.status).toBe('available');
    expect(result.candidates[0]?.vendorId).toBe('vendor-1');
    expect(result.excludedReasons).toEqual([]);
  });

  it('parses structured unavailable payloads without treating them as errors', () => {
    const result = normalizeVendorRecommendationPayload({
      message: 'AI capability unavailable',
      data: {
        status: 'unavailable',
        feature_key: 'vendor_ai_ranking',
        available: false,
        fallback_ui_mode: 'show_unavailable_message',
        message_key: 'ai.vendor_ai_ranking.unavailable',
        diagnostics: {
          mode: 'provider',
        },
        reason_codes: ['provider_unavailable'],
      },
    }, {
      tenantId: 'tenant-1',
      rfqId: 'rfq-1',
    });

    expect(result.status).toBe('unavailable');
    expect(result.tenantId).toBe('tenant-1');
    expect(result.rfqId).toBe('rfq-1');
    expect(result.eligibleCandidates).toEqual([]);
    expect(result.excludedCandidates).toEqual([]);
    expect(result.providerExplanation).toBe('AI capability unavailable');
    expect(result.provenance).toBeNull();
  });

  it('parses unavailable payloads that already include recommendation context', () => {
    const result = normalizeVendorRecommendationPayload({
      data: {
        tenant_id: 'tenant-1',
        rfq_id: 'rfq-1',
        status: 'unavailable',
        eligible_candidates: [],
        excluded_candidates: [],
        provider_explanation: 'AI recommendation is unavailable. You can still manually select vendors.',
        deterministic_reason_set: [],
        provenance: {
          code: 'ai_unavailable',
        },
      },
    });

    expect(result.status).toBe('unavailable');
    expect(result.eligibleCandidates).toEqual([]);
    expect(result.excludedCandidates).toEqual([]);
    expect(result.providerExplanation).toContain('AI recommendation is unavailable');
  });
});
