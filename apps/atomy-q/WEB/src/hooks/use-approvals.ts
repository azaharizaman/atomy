'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface ApprovalsListMeta {
  current_page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

export interface ApprovalListRow {
  id: string;
  rfq_id: string;
  rfq_title?: string;
  type: string;
  type_label?: string;
  status: string;
  priority: string;
  summary: string;
  sla?: string;
  sla_variant?: string;
  assignee?: string;
  requested_at?: string | null;
}

export interface ApprovalsListResult {
  items: ApprovalListRow[];
  meta: ApprovalsListMeta;
}

function parseApprovalsMeta(payload: unknown, fallbackPage: number, fallbackPerPage: number): ApprovalsListMeta | null {
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const m = obj?.meta;
  if (m === null || m === undefined || typeof m !== 'object') return null;
  const meta = m as Record<string, unknown>;
  const total = Number(meta.total);
  if (!Number.isFinite(total) || total < 0) return null;
  const perPage = Math.max(1, Number(meta.per_page) || fallbackPerPage);
  const currentPage = Math.max(1, Number(meta.current_page) || fallbackPage);
  const totalPagesRaw = Number(meta.total_pages);
  const totalPages =
    Number.isFinite(totalPagesRaw) && totalPagesRaw > 0
      ? Math.max(1, totalPagesRaw)
      : Math.max(1, Math.ceil(total / perPage));
  return { current_page: currentPage, per_page: perPage, total, total_pages: totalPages };
}

function normalizeApprovalRows(payload: unknown): ApprovalListRow[] {
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const raw = obj?.data;
  if (!Array.isArray(raw)) return [];
  return raw.map((item: unknown, index: number) => {
    const r = item != null && typeof item === 'object' && !Array.isArray(item) ? (item as Record<string, unknown>) : {};
    const rfqTitle = String(r.rfq_title ?? '');
    const type = String(r.type ?? 'approval');
    const typeLabel = String(r.type_label ?? type);
    return {
      id: String(r.id ?? `approval-${index}`),
      rfq_id: String(r.rfq_id ?? ''),
      rfq_title: rfqTitle || undefined,
      type,
      type_label: typeLabel,
      status: String(r.status ?? 'pending'),
      priority: String(r.priority ?? 'normal'),
      summary: String(r.summary ?? `${typeLabel} — ${rfqTitle || 'RFQ'}`),
      sla: r.sla !== undefined && r.sla !== null ? String(r.sla) : undefined,
      sla_variant: r.sla_variant !== undefined && r.sla_variant !== null ? String(r.sla_variant) : undefined,
      assignee: r.assignee !== undefined && r.assignee !== null ? String(r.assignee) : undefined,
      requested_at: r.requested_at !== undefined && r.requested_at !== null ? String(r.requested_at) : null,
    };
  });
}

export interface UseApprovalsParams {
  status?: string;
  type?: string;
  page?: number;
}

export function useApprovalsList(params: UseApprovalsParams) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['approvals', 'list', params],
    queryFn: async (): Promise<ApprovalsListResult> => {
      const page = Math.max(1, params.page ?? 1);
      if (!useMocks) {
        const { data } = await api.get('/approvals', {
          params: {
            status: params.status || undefined,
            type: params.type || undefined,
            page,
          },
        });
        const items = normalizeApprovalRows(data);
        const meta = parseApprovalsMeta(data, page, 25);
        if (meta !== null) {
          return { items, meta };
        }
        return {
          items,
          meta: {
            current_page: page,
            per_page: Math.max(1, items.length || 25),
            total: items.length,
            total_pages: 1,
          },
        };
      }

      const { getSeedPendingApprovals } = await import('@/data/seed');
      const seed = getSeedPendingApprovals();
      let rows: ApprovalListRow[] = seed.map((a) => ({
        id: a.id,
        rfq_id: a.rfqId,
        rfq_title: a.summary,
        type: a.type,
        type_label: a.type,
        status: 'pending',
        priority: a.priority,
        summary: a.summary,
        sla: a.sla,
        sla_variant: a.slaVariant,
        assignee: a.assignee,
        requested_at: null,
      }));
      if (params.type) {
        rows = rows.filter((r) => r.type === params.type);
      }
      if (params.status && params.status !== 'pending') {
        rows = [];
      }
      const perPage = 20;
      const total = rows.length;
      const totalPages = Math.max(1, Math.ceil(total / perPage));
      const start = (page - 1) * perPage;
      return {
        items: rows.slice(start, start + perPage),
        meta: { current_page: page, per_page: perPage, total, total_pages: totalPages },
      };
    },
  });
}

export interface ApprovalDetail extends ApprovalListRow {
  rfq_number?: string;
  notes?: string | null;
  comparison_run?: {
    id: string;
    name: string;
    status: string;
    is_preview: boolean;
  } | null;
  created_at?: string | null;
}

export function useApprovalDetail(id: string | undefined) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['approvals', 'detail', id],
    queryFn: async (): Promise<ApprovalDetail> => {
      if (!id) throw new Error('Approval id required');
      if (useMocks) {
        const { getSeedPendingApprovals } = await import('@/data/seed');
        const row = getSeedPendingApprovals().find((a) => a.id === id);
        if (!row) throw new Error('Not found');
        return {
          id: row.id,
          rfq_id: row.rfqId,
          type: row.type,
          type_label: row.type,
          status: 'pending',
          priority: row.priority,
          summary: row.summary,
          assignee: row.assignee,
          sla: row.sla,
          sla_variant: row.slaVariant,
          requested_at: null,
          comparison_run: null,
        };
      }
      const { data } = await api.get<{ data?: Record<string, unknown> }>(`/approvals/${encodeURIComponent(id)}`);
      const r = data?.data;
      if (!r || typeof r !== 'object') throw new Error('Not found');
      const rec = r as Record<string, unknown>;
      const base = normalizeApprovalRows({ data: [rec] })[0];
      return {
        ...base,
        rfq_number: rec.rfq_number !== undefined && rec.rfq_number !== null ? String(rec.rfq_number) : undefined,
        notes: rec.notes !== undefined && rec.notes !== null ? String(rec.notes) : null,
        comparison_run:
          rec.comparison_run !== null && rec.comparison_run !== undefined && typeof rec.comparison_run === 'object'
            ? (() => {
                const c = rec.comparison_run as Record<string, unknown>;
                return {
                  id: String(c.id ?? ''),
                  name: String(c.name ?? ''),
                  status: String(c.status ?? ''),
                  is_preview: Boolean(c.is_preview),
                };
              })()
            : null,
        created_at: rec.created_at !== undefined && rec.created_at !== null ? String(rec.created_at) : null,
      };
    },
    enabled: Boolean(id),
  });
}

export function useRfqPendingApprovalCount(rfqId: string | undefined) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['approvals', 'pending-count', rfqId],
    queryFn: async (): Promise<number> => {
      const { data } = await api.get('/approvals', {
        params: { status: 'pending', rfq_id: rfqId, per_page: 1, page: 1 },
      });
      const meta = (data as { meta?: { total?: number } })?.meta;
      return Number(meta?.total ?? 0);
    },
    enabled: Boolean(rfqId) && !useMocks,
    initialData: useMocks && rfqId ? 2 : undefined,
  });
}
