'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { fetchLiveOrFail } from '@/lib/api-live';
import type { RfqStatus } from '@/hooks/use-rfqs';

export interface RfqOverviewRfq {
  id: string;
  rfq_number: string;
  title: string;
  description?: string | null;
  status: RfqStatus;
  owner?: { id: string; name: string; email: string } | null;
  submission_deadline?: string | null;
  closing_date?: string | null;
  expected_award_at?: string | null;
  technical_review_due_at?: string | null;
  financial_review_due_at?: string | null;
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

function normalizeActivityList(raw: unknown): RfqOverviewActivityItem[] {
  if (!Array.isArray(raw)) return [];
  const out: RfqOverviewActivityItem[] = [];
  raw.forEach((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      if (typeof console !== 'undefined' && typeof console.warn === 'function') {
        console.warn(`Skipping RFQ activity row at index ${index}: expected object`);
      }
      return;
    }
    const r = item as Record<string, unknown>;
    const id = r.id !== null && r.id !== undefined ? String(r.id).trim() : '';
    const type = r.type !== null && r.type !== undefined ? String(r.type).trim() : '';
    const actor = r.actor !== null && r.actor !== undefined ? String(r.actor).trim() : '';
    const action = r.action !== null && r.action !== undefined ? String(r.action).trim() : '';
    const tsRaw = r.timestamp !== null && r.timestamp !== undefined ? String(r.timestamp).trim() : '';
    if (!id || !type || !actor || !action || !tsRaw) {
      if (typeof console !== 'undefined' && typeof console.warn === 'function') {
        console.warn(`Skipping RFQ activity row at index ${index}: missing required fields`);
      }
      return;
    }
    const parsed = Date.parse(tsRaw);
    if (!Number.isFinite(parsed)) {
      if (typeof console !== 'undefined' && typeof console.warn === 'function') {
        console.warn(`Skipping RFQ activity row at index ${index}: invalid timestamp`);
      }
      return;
    }
    out.push({ id, type, actor, action, timestamp: tsRaw });
  });
  return out;
}

function normalizeOverviewPayload(payload: unknown): RfqOverviewData {
  const raw = (payload as { data?: unknown })?.data ?? payload;
  const d = raw as Record<string, unknown>;
  const rfq = (d?.rfq ?? d) as Record<string, unknown>;
  const norm = (d?.normalization ?? {}) as Record<string, unknown>;
  const comp = d?.comparison as Record<string, unknown> | null | undefined;
  const app = (d?.approvals ?? {}) as Record<string, unknown>;
  const activity = normalizeActivityList(d?.activity);

  let expectedQuotesRaw: unknown = d?.expected_quotes ?? d?.expectedQuotes ?? rfq?.vendors_count;
  if (typeof expectedQuotesRaw === 'string' && expectedQuotesRaw.trim() === '') {
    expectedQuotesRaw = undefined;
  }
  if (expectedQuotesRaw === null || expectedQuotesRaw === undefined) {
    expectedQuotesRaw = rfq?.vendors_count;
  }
  const expectedQuotesNum = Number(expectedQuotesRaw);
  const vendorFallback =
    rfq?.vendors_count !== null &&
    rfq?.vendors_count !== undefined &&
    Number.isFinite(Number(rfq.vendors_count))
      ? Number(rfq.vendors_count)
      : 0;
  const expected_quotes = Number.isFinite(expectedQuotesNum) ? expectedQuotesNum : vendorFallback;

  return {
    rfq: {
      id: String(rfq?.id ?? ''),
      rfq_number: String(rfq?.rfq_number ?? ''),
      title: String(rfq?.title ?? 'Requisition'),
      description:
        rfq?.description === null || rfq?.description === undefined
          ? null
          : (() => {
              const s = String(rfq.description).trim();
              return s === '' ? null : s;
            })(),
      status: (rfq?.status as RfqStatus) ?? 'draft',
      owner: rfq?.owner as RfqOverviewRfq['owner'],
      submission_deadline: rfq?.submission_deadline as string | null,
      closing_date: rfq?.closing_date as string | null,
      expected_award_at: (rfq?.expected_award_at ?? rfq?.expectedAwardAt ?? null) as string | null,
      technical_review_due_at: (rfq?.technical_review_due_at ?? rfq?.technicalReviewDueAt ?? null) as string | null,
      financial_review_due_at: (rfq?.financial_review_due_at ?? rfq?.financialReviewDueAt ?? null) as string | null,
      category: rfq?.category as string | null,
      estimated_value: (rfq?.estimated_value ?? rfq?.estValue) as string | number | null | undefined,
      estValue: (rfq?.estValue ?? rfq?.estimated_value) as string | number | null | undefined,
      savings_percentage: rfq?.savings_percentage as number | string | null | undefined,
      savings: rfq?.savings as string | null,
      vendors_count: (rfq?.vendors_count ?? rfq?.vendorsCount) as number | undefined,
      quotes_count: (rfq?.quotes_count ?? rfq?.quotesCount) as number | undefined,
    },
    expected_quotes,
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
    activity,
  };
}

export function useRfqOverview(rfqId: string) {
  return useQuery({
    queryKey: ['rfqs', rfqId, 'overview'],
    queryFn: async (): Promise<RfqOverviewData> => {
      const data = await fetchLiveOrFail<RfqOverviewData>(`/rfqs/${encodeURIComponent(rfqId)}/overview`);

      if (data === undefined) {
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
            description: detail?.description ?? null,
            status: (detail?.status as RfqStatus) ?? 'active',
            owner: null,
            submission_deadline: new Date(Date.now() + 86400000 * 5).toISOString(),
            closing_date: new Date(Date.now() + 86400000 * 12).toISOString(),
            expected_award_at: new Date(Date.now() + 86400000 * 45).toISOString(),
            technical_review_due_at: new Date(Date.now() + 86400000 * 18).toISOString(),
            financial_review_due_at: new Date(Date.now() + 86400000 * 28).toISOString(),
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

      const normalized = normalizeOverviewPayload(data);
      const activityRes = await fetchLiveOrFail<{ data: unknown[] }>(`/rfqs/${encodeURIComponent(rfqId)}/activity`, { params: { limit: 50 } });
      if (activityRes?.data && typeof activityRes.data === 'object') {
        const body = activityRes.data as unknown as Record<string, unknown>;
        const fromEndpoint = normalizeActivityList(body.data);
        if (fromEndpoint.length > 0) {
          return { ...normalized, activity: fromEndpoint };
        }
      }
      return normalized;
    },
    enabled: Boolean(rfqId),
  });
}
