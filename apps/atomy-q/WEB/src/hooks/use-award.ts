'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface AwardRecord {
  id: string;
  tenant_id: string;
  rfq_id: string;
  comparison_run_id?: string | null;
  vendor_id: string;
  winner_vendor_id?: string;
  vendor_name?: string | null;
  winner_vendor_name?: string | null;
  status: string;
  amount: number;
  currency: string;
  savings_percentage?: number;
  signoff_at?: string | null;
  signed_off_by?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface AwardWorkflowBody {
  rfq_id: string;
  comparison_run_id: string;
  vendor_id: string;
}

function normalizeAward(raw: unknown): AwardRecord {
  const row = raw as Record<string, unknown>;
  return {
    id: String(row.id ?? ''),
    tenant_id: String(row.tenant_id ?? ''),
    rfq_id: String(row.rfq_id ?? ''),
    comparison_run_id: row.comparison_run_id != null ? String(row.comparison_run_id) : null,
    vendor_id: String(row.vendor_id ?? row.winner_vendor_id ?? ''),
    winner_vendor_id: row.winner_vendor_id != null ? String(row.winner_vendor_id) : undefined,
    vendor_name: row.vendor_name != null ? String(row.vendor_name) : null,
    winner_vendor_name: row.winner_vendor_name != null ? String(row.winner_vendor_name) : null,
    status: String(row.status ?? 'draft'),
    amount: Number(row.amount ?? 0),
    currency: String(row.currency ?? 'USD'),
    savings_percentage: row.savings_percentage != null ? Number(row.savings_percentage) : undefined,
    signoff_at: row.signoff_at != null ? String(row.signoff_at) : null,
    signed_off_by: row.signed_off_by != null ? String(row.signed_off_by) : null,
    created_at: row.created_at != null ? String(row.created_at) : null,
    updated_at: row.updated_at != null ? String(row.updated_at) : null,
  };
}

function unwrapData(payload: unknown): unknown {
  if (payload !== null && typeof payload === 'object' && !Array.isArray(payload) && 'data' in payload) {
    return (payload as { data?: unknown }).data;
  }

  return payload;
}

export function useAward(rfqId: string) {
  return useQuery({
    queryKey: ['awards', rfqId],
    queryFn: async (): Promise<AwardRecord | null> => {
      const { data } = await api.get('/awards', { params: { rfq_id: rfqId } });
      const payload = unwrapData(data);
      if (Array.isArray(payload)) {
        if (payload.length === 0) {
          return null;
        }

        return normalizeAward(payload[0]);
      }

      if (payload === null || payload === undefined || typeof payload !== 'object') {
        return null;
      }

      return normalizeAward(payload);
    },
    enabled: Boolean(rfqId),
  });
}

export function useAwardRfq() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (body: AwardWorkflowBody) => {
      const { data } = await api.post('/awards', body);
      return normalizeAward(unwrapData(data));
    },
    onSuccess: async (_data, body) => {
      await queryClient.invalidateQueries({ queryKey: ['awards', body.rfq_id] });
      await queryClient.invalidateQueries({ queryKey: ['rfqs', body.rfq_id] });
      await queryClient.invalidateQueries({ queryKey: ['rfqs', body.rfq_id, 'overview'] });
      await queryClient.invalidateQueries({ queryKey: ['comparison-runs', body.rfq_id] });
      await queryClient.invalidateQueries({ queryKey: ['decision-trail', body.rfq_id] });
    },
  });
}
