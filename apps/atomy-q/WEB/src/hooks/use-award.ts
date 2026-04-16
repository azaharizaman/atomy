'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { api } from '@/lib/api';
import { getSeedAwardByRfqId } from '@/data/seed';
import type { ComparisonRunSnapshot } from '@/hooks/use-comparison-run';
import { normalizeComparisonRun } from '@/hooks/use-comparison-run';
import type { ComparisonRunMatrix, ComparisonRunMatrixOffer } from '@/hooks/use-comparison-run-matrix';
import { normalizeComparisonRunMatrix } from '@/hooks/use-comparison-run-matrix';

export interface AwardRecord {
  id: string;
  rfq_id: string;
  rfq_title?: string | null;
  rfq_number?: string | null;
  comparison_run_id?: string | null;
  vendor_id: string;
  vendor_name?: string | null;
  status: string;
  amount: number | null;
  currency?: string | null;
  split_details?: unknown[];
  protest_id?: string | null;
  signoff_at?: string | null;
  signed_off_by?: string | null;
  comparison?: {
    vendors: Array<{
      vendor_id: string;
      vendor_name?: string | null;
      quote_submission_id?: string | null;
    }>;
  } | null;
}

function parseOptionalNumber(value: unknown): number | null {
  if (value === null || value === undefined) return null;
  if (typeof value === 'number') {
    return Number.isFinite(value) ? value : null;
  }
  if (typeof value !== 'string') {
    return null;
  }

  const raw = value.trim();
  if (raw === '') return null;
  if (!/^[+-]?(?:\d+\.?\d*|\.\d+)$/.test(raw)) {
    return null;
  }

  const parsed = Number(raw);
  return Number.isFinite(parsed) ? parsed : null;
}

function requireTextField(value: unknown, fieldName: string, rowIndex: number): string {
  if (value === null || value === undefined) {
    throw new Error(`Invalid award row at index ${rowIndex}: missing ${fieldName}`);
  }

  const text = String(value).trim();
  if (text === '') {
    throw new Error(`Invalid award row at index ${rowIndex}: missing ${fieldName}`);
  }

  return text;
}

function requireNumberField(value: unknown, fieldName: string, rowIndex: number): number {
  const parsed = parseOptionalNumber(value);
  if (parsed === null) {
    throw new Error(`Invalid award row at index ${rowIndex}: missing ${fieldName}`);
  }

  return parsed;
}

function normalizeComparisonVendors(value: unknown, rowIndex: number) {
  if (value === null || value === undefined || typeof value !== 'object' || Array.isArray(value)) {
    throw new Error(`Invalid award row at index ${rowIndex}: missing comparison`);
  }

  const comparison = value as { vendors?: unknown };
  if (!Array.isArray(comparison.vendors)) {
    throw new Error(`Invalid award row at index ${rowIndex}: missing comparison.vendors`);
  }

  return comparison.vendors.map((vendor, vendorIndex) => {
    if (vendor === null || typeof vendor !== 'object' || Array.isArray(vendor)) {
      throw new Error(`Invalid award comparison vendor at index ${vendorIndex}`);
    }

    const vendorRow = vendor as Record<string, unknown>;
    return {
      vendor_id: requireTextField(vendorRow.vendor_id, `comparison vendor ${vendorIndex} vendor_id`, rowIndex),
      vendor_name:
        vendorRow.vendor_name !== undefined && vendorRow.vendor_name !== null
          ? String(vendorRow.vendor_name)
          : null,
      quote_submission_id:
        vendorRow.quote_submission_id !== undefined && vendorRow.quote_submission_id !== null
          ? String(vendorRow.quote_submission_id)
          : null,
    };
  });
}

function normalizeOptionalComparison(value: unknown, rowIndex: number): AwardRecord['comparison'] {
  if (value === null || value === undefined) {
    return null;
  }

  return {
    vendors: normalizeComparisonVendors(value, rowIndex),
  };
}

function normalizeAwardRows(payload: unknown): AwardRecord[] {
  if (payload === null || typeof payload !== 'object' || Array.isArray(payload)) {
    throw new Error('Invalid award response: expected object envelope with data array.');
  }

  const raw = payload as { data?: unknown };
  if (!Array.isArray(raw.data)) {
    throw new Error('Invalid award response: expected data array.');
  }

  const list = raw.data;
  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid award row at index ${index}: expected object`);
    }
    const row = item as Record<string, unknown>;
    return {
      id: requireTextField(row.id, 'id', index),
      rfq_id: requireTextField(row.rfq_id, 'rfq_id', index),
      rfq_title: row.rfq_title !== undefined && row.rfq_title !== null ? String(row.rfq_title) : null,
      rfq_number: row.rfq_number !== undefined && row.rfq_number !== null ? String(row.rfq_number) : null,
      comparison_run_id:
        row.comparison_run_id !== undefined && row.comparison_run_id !== null ? String(row.comparison_run_id) : null,
      vendor_id: requireTextField(row.vendor_id, 'vendor_id', index),
      vendor_name: row.vendor_name !== undefined && row.vendor_name !== null ? String(row.vendor_name) : null,
      status: requireTextField(row.status, 'status', index),
      amount: requireNumberField(row.amount, 'amount', index),
      currency: requireTextField(row.currency, 'currency', index),
      split_details: Array.isArray(row.split_details) ? row.split_details : [],
      protest_id: row.protest_id !== undefined && row.protest_id !== null ? String(row.protest_id) : null,
      signoff_at: row.signoff_at !== undefined && row.signoff_at !== null ? String(row.signoff_at) : null,
      signed_off_by: row.signed_off_by !== undefined && row.signed_off_by !== null ? String(row.signed_off_by) : null,
      comparison: normalizeOptionalComparison(row.comparison, index),
    };
  });
}

function getRequiredAwardLineIds(snapshot: ComparisonRunSnapshot | null | undefined): string[] {
  if (!snapshot) {
    return [];
  }

  return [...new Set(snapshot.normalizedLines.map((line) => line.rfqLineItemId).filter((lineId) => lineId !== ''))];
}

function getVendorOffersByLine(matrix: ComparisonRunMatrix, vendorId: string): Map<string, ComparisonRunMatrixOffer[]> {
  const offersByLine = new Map<string, ComparisonRunMatrixOffer[]>();

  matrix.clusters
    .flatMap((cluster) => cluster.offers)
    .filter((offer) => offer.vendorId === vendorId)
    .forEach((offer) => {
      const existing = offersByLine.get(offer.rfqLineId);
      if (existing) {
        existing.push(offer);
        return;
      }

      offersByLine.set(offer.rfqLineId, [offer]);
    });

  return offersByLine;
}

export function hasCompleteAwardPricingEvidence(
  snapshot: ComparisonRunSnapshot | null | undefined,
  matrix: ComparisonRunMatrix | null | undefined,
  vendorId: string,
): boolean {
  if (!snapshot || !matrix || vendorId.trim() === '') {
    return false;
  }

  const requiredLineIds = getRequiredAwardLineIds(snapshot);
  if (requiredLineIds.length === 0) {
    return false;
  }

  const vendorOffersByLine = getVendorOffersByLine(matrix, vendorId);
  return requiredLineIds.every((lineId) => {
    const currency = snapshot.currencyMeta[lineId]?.trim() ?? '';
    return currency !== '' && vendorOffersByLine.has(lineId);
  });
}

async function buildAwardCreatePayload(input: { rfqId: string; comparisonRunId: string; vendorId: string }) {
  const [runResponse, matrixResponse] = await Promise.all([
    api.get(`/comparison-runs/${encodeURIComponent(input.comparisonRunId)}`),
    api.get(`/comparison-runs/${encodeURIComponent(input.comparisonRunId)}/matrix`),
  ]);

  const run = normalizeComparisonRun(runResponse.data);
  const matrix = normalizeComparisonRunMatrix(matrixResponse.data);
  const snapshot = run.snapshot;

  if (!snapshot) {
    throw new Error('Award creation requires finalized comparison snapshot evidence.');
  }

  const requiredLineIds = getRequiredAwardLineIds(snapshot);
  if (requiredLineIds.length === 0) {
    throw new Error('Award creation requires complete finalized pricing evidence.');
  }

  const vendorOffersByLine = getVendorOffersByLine(matrix, input.vendorId);
  const missingLineCoverage = requiredLineIds.filter((lineId) => !vendorOffersByLine.has(lineId));

  if (missingLineCoverage.length > 0) {
    throw new Error('Selected vendor does not have complete finalized pricing evidence for every required line.');
  }

  const missingCurrencyLines = requiredLineIds.filter((lineId) => (snapshot.currencyMeta[lineId]?.trim() ?? '') === '');
  if (missingCurrencyLines.length > 0) {
    throw new Error('Award creation requires resolved currency metadata for every contributing line.');
  }

  const contributingOffers = requiredLineIds.flatMap((lineId) => vendorOffersByLine.get(lineId) ?? []);
  const amount = contributingOffers.reduce((sum, offer) => sum + (offer.normalizedUnitPrice * offer.normalizedQuantity), 0);

  const currencies = new Set(
    requiredLineIds
      .map((lineId) => snapshot.currencyMeta[lineId] ?? '')
      .filter((currency) => currency.trim() !== ''),
  );

  if (currencies.size !== 1) {
    throw new Error('Award creation requires one resolved comparison currency.');
  }

  return {
    rfq_id: input.rfqId,
    comparison_run_id: input.comparisonRunId,
    vendor_id: input.vendorId,
    amount,
    currency: [...currencies][0],
  };
}

export function useAward(rfqId: string) {
  const qc = useQueryClient();

  const query = useQuery({
    queryKey: ['awards', rfqId],
    queryFn: async (): Promise<AwardRecord[]> => {
      const data = await fetchLiveOrFail<{ data: AwardRecord[] }>('/awards', { params: { rfq_id: rfqId } });

      if (data === undefined) {
        const seed = getSeedAwardByRfqId(rfqId);
        if (!seed) return [];
        return [
          {
            id: `seed-award-${seed.rfqId}`,
            rfq_id: rfqId,
            vendor_id: seed.winnerVendorId,
            vendor_name: seed.winnerVendorName,
            status: 'signed_off',
            amount: seed.amount ?? null,
            currency: 'USD',
            split_details: [],
            protest_id: null,
            signoff_at: null,
            signed_off_by: null,
            comparison: {
              vendors: [
                {
                  vendor_id: seed.winnerVendorId,
                  vendor_name: seed.winnerVendorName,
                  quote_submission_id: null,
                },
              ],
            },
          },
        ];
      }

      return normalizeAwardRows(data);
    },
    enabled: Boolean(rfqId),
  });

  const signoff = useMutation({
    mutationFn: async (awardId: string) => {
      const { data } = await api.post(`/awards/${encodeURIComponent(awardId)}/signoff`);
      return data;
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['awards', rfqId] });
    },
  });

  const debrief = useMutation({
    mutationFn: async (input: { awardId: string; vendorId: string; message?: string }) => {
      const { data } = await api.post(
        `/awards/${encodeURIComponent(input.awardId)}/debrief/${encodeURIComponent(input.vendorId)}`,
        { message: input.message },
      );
      return data;
    },
  });

  const store = useMutation({
    mutationFn: async (input: { rfqId: string; comparisonRunId: string; vendorId: string }) => {
      const payload = await buildAwardCreatePayload(input);
      const { data } = await api.post('/awards', payload);
      return data;
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['awards', rfqId] });
    },
  });

  const awards = query.data ?? [];
  return {
    ...query,
    awards,
    award: awards[0] ?? null,
    signoff,
    debrief,
    store,
  };
}
