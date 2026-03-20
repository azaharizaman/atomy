'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { RfqStatus } from '@/hooks/use-rfqs';

export interface RfqOverviewRfq {
  id: string;
  rfq_number: string;
  title: string;
  status: RfqStatus;
  owner?: { id: string; name: string; email: string } | null;
  submission_deadline?: string | null;
  closing_date?: string | null;
  category?: string | null;
  estimated_value?: number | string | null;
  estValue?: number | string | null;
  savings_percentage?: number | string | null;
  savings?: string | null;
  vendors_count?: number;
  quotes_count?: number;
}

export interface RfqOverviewNormalization {
  accepted_count: number;
  total_quotes: number;
  progress_pct: number;
  uploaded_count?: number;
  needs_review_count?: number;
  ready_count?: number;
}

export interface RfqOverviewComparison {
  id: string;
  name: string;
  status: string;
  is_preview: boolean;
  created_at?: string | null;
}

export interface RfqOverviewApprovals {
  pending_count: number;
  approved_count: number;
  rejected_count: number;
  overall: 'none' | 'pending' | 'approved' | 'rejected';
}

export interface RfqOverviewActivityItem {
  id: string;
  type: string;
  actor: string;
  action: string;
  timestamp: string;
}

export interface RfqOverviewData {
  rfq: RfqOverviewRfq;
  expected_quotes: number;
  normalization: RfqOverviewNormalization;
  comparison: RfqOverviewComparison | null;
  approvals: RfqOverviewApprovals;
  activity: RfqOverviewActivityItem[];
}

function normalizeOverviewPayload(payload: unknown): RfqOverviewData {
  const raw = (payload as { data?: unknown })?.data ?? payload;
  const d = raw as Record<string, unknown>;
  const rfq = (d?.rfq ?? d) as Record<string, unknown>;
  const norm = (d?.normalization ?? {}) as Record<string, unknown>;
  const comp = d?.comparison as Record<string, unknown> | null | undefined;
  const app = (d?.approvals ?? {}) as Record<string, unknown>;
  const activity = (d?.activity ?? []) as RfqOverviewActivityItem[];

  return {
    rfq: {
      id: String(rfq?.id ?? ''),
      rfq_number: String(rfq?.rfq_number ?? ''),
      title: String(rfq?.title ?? 'Requisition'),
      status: (rfq?.status as RfqStatus) ?? 'draft',
      owner: rfq?.owner as RfqOverviewRfq['owner'],
      submission_deadline: rfq?.submission_deadline as string | null,
      closing_date: rfq?.closing_date as string | null,
      category: rfq?.category as string | null,
      estimated_value: (rfq?.estimated_value ?? rfq?.estValue) as string | number | null | undefined,
      estValue: (rfq?.estValue ?? rfq?.estimated_value) as string | number | null | undefined,
      savings_percentage: rfq?.savings_percentage as number | string | null | undefined,
      savings: rfq?.savings as string | null,
      vendors_count: (rfq?.vendors_count ?? rfq?.vendorsCount) as number | undefined,
      quotes_count: (rfq?.quotes_count ?? rfq?.quotesCount) as number | undefined,
    },
    expected_quotes: Number(d?.expected_quotes ?? rfq?.vendors_count ?? 0),
    normalization: {
      accepted_count: Number(norm?.accepted_count ?? 0),
      total_quotes: Number(norm?.total_quotes ?? 0),
      progress_pct: Number(norm?.progress_pct ?? 0),
      uploaded_count: norm?.uploaded_count !== undefined ? Number(norm.uploaded_count) : undefined,
      needs_review_count: norm?.needs_review_count !== undefined ? Number(norm.needs_review_count) : undefined,
      ready_count: norm?.ready_count !== undefined ? Number(norm.ready_count) : undefined,
    },
    comparison: comp
      ? {
          id: String(comp.id),
          name: String(comp.name ?? 'Run'),
          status: String(comp.status ?? 'draft'),
          is_preview: Boolean(comp.is_preview),
          created_at: comp.created_at as string | null,
        }
      : null,
    approvals: {
      pending_count: Number(app?.pending_count ?? 0),
      approved_count: Number(app?.approved_count ?? 0),
      rejected_count: Number(app?.rejected_count ?? 0),
      overall: (app?.overall as RfqOverviewApprovals['overall']) ?? 'none',
    },
    activity: Array.isArray(activity) ? activity : [],
  };
}

export function useRfqOverview(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', rfqId, 'overview'],
    queryFn: async (): Promise<RfqOverviewData> => {
      if (useMocks) {
        const { getSeedRfqDetail } = await import('@/data/seed');
        const detail = getSeedRfqDetail(rfqId);
        const vendorsCount = detail?.vendorsCount ?? 0;
        const quotesCount = detail?.quotesCount ?? 0;
        const accepted = Math.round(quotesCount * 0.75);
        return {
          rfq: {
            id: detail?.id ?? rfqId,
            rfq_number: detail?.id ?? rfqId,
            title: detail?.title ?? 'Requisition',
            status: (detail?.status as RfqStatus) ?? 'active',
            owner: null,
            submission_deadline: null,
            closing_date: null,
            category: null,
            estimated_value: detail?.estValue,
            estValue: detail?.estValue,
            savings: detail?.savings ?? null,
            vendors_count: vendorsCount,
            quotes_count: quotesCount,
          },
          expected_quotes: vendorsCount,
          normalization: {
            accepted_count: accepted,
            total_quotes: quotesCount,
            progress_pct: quotesCount > 0 ? Math.round((accepted / quotesCount) * 100) : 0,
            uploaded_count: Math.max(0, quotesCount - accepted),
            needs_review_count: 0,
            ready_count: accepted,
          },
          comparison: {
            id: 'run-1',
            name: 'Run #004',
            status: 'preview',
            is_preview: true,
            created_at: new Date().toISOString(),
          },
          approvals: {
            pending_count: 0,
            approved_count: 0,
            rejected_count: 0,
            overall: 'none',
          },
          activity: [
            { id: '1', type: 'quote', actor: 'Dell Technologies', action: 'Quote submitted (accepted)', timestamp: new Date(Date.now() - 7200000).toISOString() },
            { id: '2', type: 'invitation', actor: 'System', action: 'Invitation sent to HP Enterprise', timestamp: new Date(Date.now() - 14400000).toISOString() },
            { id: '3', type: 'comparison', actor: 'System', action: 'Comparison run: Run #004 (preview)', timestamp: new Date(Date.now() - 86400000).toISOString() },
            { id: '4', type: 'system', actor: 'System', action: 'RFQ published and vendors notified', timestamp: new Date(Date.now() - 259200000).toISOString() },
            { id: '5', type: 'creation', actor: 'User', action: `Created ${rfqId}`, timestamp: new Date(Date.now() - 518400000).toISOString() },
          ],
        };
      }

      const { data } = await api.get(`/rfqs/${encodeURIComponent(rfqId)}/overview`);
      return normalizeOverviewPayload(data);
    },
    enabled: Boolean(rfqId),
  });
}
