'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
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

function isNonNegativeFiniteInt(value: unknown): number | null {
  if (value === null || value === undefined) return null;
  if (typeof value === 'string' && value.trim() === '') return null;
  const n = Number(typeof value === 'string' ? value.trim() : value);
  if (!Number.isFinite(n) || !Number.isInteger(n) || n < 0) return null;
  return n;
}

function isPositiveFiniteInt(value: unknown): number | null {
  const n = isNonNegativeFiniteInt(value);
  if (n === null || n < 1) return null;
  return n;
}

function parseApprovalsMeta(payload: unknown): ApprovalsListMeta | null {
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const m = obj?.meta;
  if (m === null || m === undefined || typeof m !== 'object') return null;
  const meta = m as Record<string, unknown>;
  const total = isNonNegativeFiniteInt(meta.total);
  const perPage = isPositiveFiniteInt(meta.per_page);
  const currentPage = isPositiveFiniteInt(meta.current_page);
  const totalPages = isPositiveFiniteInt(meta.total_pages);
  if (total === null || perPage === null || currentPage === null || totalPages === null) return null;
  return { current_page: currentPage, per_page: perPage, total, total_pages: totalPages };
}

function requireNonEmptyStringField(value: unknown, field: string, index: number): string {
  if (value === null || value === undefined) {
    throw new Error(`Invalid approval row at index ${index}: missing ${field}`);
  }
  const s = String(value).trim();
  if (s === '') {
    throw new Error(`Invalid approval row at index ${index}: empty ${field}`);
  }
  return s;
}

function normalizeApprovalRows(payload: unknown): ApprovalListRow[] {
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const raw = obj?.data;
  if (!Array.isArray(raw)) return [];
  return raw.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid approval row at index ${index}: expected object`);
    }
    const r = item as Record<string, unknown>;
    const id = requireNonEmptyStringField(r.id, 'id', index);
    const rfq_id = requireNonEmptyStringField(r.rfq_id, 'rfq_id', index);
    const type = requireNonEmptyStringField(r.type, 'type', index);
    const status = requireNonEmptyStringField(r.status, 'status', index);
    const rfqTitle = r.rfq_title !== undefined && r.rfq_title !== null ? String(r.rfq_title).trim() : '';
    const typeLabel =
      r.type_label !== undefined && r.type_label !== null && String(r.type_label).trim() !== ''
        ? String(r.type_label).trim()
        : type;
    return {
      id,
      rfq_id,
      rfq_title: rfqTitle || undefined,
      type,
      type_label: typeLabel,
      status,
      priority:
        r.priority !== undefined && r.priority !== null && String(r.priority).trim() !== ''
          ? String(r.priority).trim()
          : 'normal',
      summary:
        r.summary !== undefined && r.summary !== null && String(r.summary).trim() !== ''
          ? String(r.summary).trim()
          : `${typeLabel} — ${rfqTitle || 'RFQ'}`,
      sla: r.sla !== undefined && r.sla !== null ? String(r.sla) : undefined,
      sla_variant: r.sla_variant !== undefined && r.sla_variant !== null ? String(r.sla_variant) : undefined,
      assignee: r.assignee !== undefined && r.assignee !== null ? String(r.assignee) : undefined,
      requested_at: r.requested_at !== undefined && r.requested_at !== null ? String(r.requested_at) : null,
    };
  });
}

export interface UseApprovalsParams {
  rfq_id?: string;
  status?: string;
  type?: string;
  page?: number;
}

export function useApprovalsList(params: UseApprovalsParams) {
  return useQuery({
    queryKey: ['approvals', 'list', {
      rfq_id: params.rfq_id ?? null,
      status: params.status ?? null,
      type: params.type ?? null,
      page: params.page ?? 1,
    }],
    queryFn: async (): Promise<ApprovalsListResult> => {
      const page = Math.max(1, params.page ?? 1);
      const data = await fetchLiveOrFail<{ data: ApprovalListRow[] }>('/approvals', {
        params: {
          rfq_id: params.rfq_id || undefined,
          status: params.status || undefined,
          type: params.type || undefined,
          page,
        },
      });

      if (data === undefined) {
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
        if (params.rfq_id) {
          rows = rows.filter((r) => r.rfq_id === params.rfq_id);
        }
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
      }

      const items = normalizeApprovalRows(data);
      const meta = parseApprovalsMeta(data);
      if (meta === null) {
        throw new Error('Invalid approvals list response: pagination meta');
      }
      return { items, meta };
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
  return useQuery({
    queryKey: ['approvals', 'detail', id],
    queryFn: async (): Promise<ApprovalDetail> => {
      if (!id) throw new Error('Approval id required');

      const data = await fetchLiveOrFail<{ data: ApprovalDetail }>(`/approvals/${encodeURIComponent(id)}`);

      if (data === undefined) {
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
                if (c.id === null || c.id === undefined || String(c.id).trim() === '') {
                  throw new Error('Invalid approval detail: comparison_run.id');
                }
                return {
                  id: String(c.id).trim(),
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
  return useQuery({
    queryKey: ['approvals', 'pending-count', rfqId],
    queryFn: async (): Promise<number> => {
      const data = await fetchLiveOrFail<{ data: ApprovalListRow[] }>('/approvals', {
        params: { status: 'pending', rfq_id: rfqId, per_page: 1, page: 1 },
      });

      if (data === undefined) {
        return 2;
      }

      const meta = parseApprovalsMeta(data);
      if (meta === null) {
        throw new Error('Invalid approvals pending-count response: pagination meta');
      }
      return meta.total;
    },
    enabled: Boolean(rfqId),
  });
}
