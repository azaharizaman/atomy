'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { getSeedAwardByRfqId } from '@/data/seed';

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
  const raw = typeof value === 'string' ? value.trim() : value;
  if (raw === '') return null;
  const n = Number(raw);
  return Number.isFinite(n) ? n : null;
}

function normalizeAwardRows(payload: unknown): AwardRecord[] {
  const raw = payload && typeof payload === 'object' ? (payload as { data?: unknown }) : null;
  const list = Array.isArray(raw?.data) ? raw?.data : [];
  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid award row at index ${index}: expected object`);
    }
    const row = item as Record<string, unknown>;
    return {
      id: String(row.id ?? ''),
      rfq_id: String(row.rfq_id ?? ''),
      rfq_title: row.rfq_title !== undefined && row.rfq_title !== null ? String(row.rfq_title) : null,
      rfq_number: row.rfq_number !== undefined && row.rfq_number !== null ? String(row.rfq_number) : null,
      comparison_run_id:
        row.comparison_run_id !== undefined && row.comparison_run_id !== null ? String(row.comparison_run_id) : null,
      vendor_id: String(row.vendor_id ?? ''),
      vendor_name: row.vendor_name !== undefined && row.vendor_name !== null ? String(row.vendor_name) : null,
      status: String(row.status ?? 'pending'),
      amount: parseOptionalNumber(row.amount),
      currency: row.currency !== undefined && row.currency !== null ? String(row.currency) : null,
      split_details: Array.isArray(row.split_details) ? row.split_details : [],
      protest_id: row.protest_id !== undefined && row.protest_id !== null ? String(row.protest_id) : null,
      signoff_at: row.signoff_at !== undefined && row.signoff_at !== null ? String(row.signoff_at) : null,
      signed_off_by: row.signed_off_by !== undefined && row.signed_off_by !== null ? String(row.signed_off_by) : null,
      comparison:
        row.comparison !== undefined && row.comparison !== null && typeof row.comparison === 'object' && !Array.isArray(row.comparison)
          ? {
              vendors: Array.isArray((row.comparison as { vendors?: unknown }).vendors)
                ? ((row.comparison as { vendors?: unknown }).vendors as unknown[]).map((vendor, vendorIndex) => {
                    if (vendor === null || typeof vendor !== 'object' || Array.isArray(vendor)) {
                      throw new Error(`Invalid award comparison vendor at index ${vendorIndex}`);
                    }
                    const vendorRow = vendor as Record<string, unknown>;
                    return {
                      vendor_id: String(vendorRow.vendor_id ?? ''),
                      vendor_name:
                        vendorRow.vendor_name !== undefined && vendorRow.vendor_name !== null
                          ? String(vendorRow.vendor_name)
                          : null,
                      quote_submission_id:
                        vendorRow.quote_submission_id !== undefined && vendorRow.quote_submission_id !== null
                          ? String(vendorRow.quote_submission_id)
                          : null,
                    };
                  })
                : [],
            }
          : null,
    };
  });
}

export function useAward(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
  const qc = useQueryClient();

  const query = useQuery({
    queryKey: ['awards', rfqId],
    queryFn: async (): Promise<AwardRecord[]> => {
      if (useMocks) {
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

      const { data } = await api.get('/awards', { params: { rfq_id: rfqId } });
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

  const awards = query.data ?? [];
  return {
    ...query,
    awards,
    award: awards[0] ?? null,
    signoff,
    debrief,
  };
}
