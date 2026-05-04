'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { formatCurrencyValue } from '@/lib/format-currency';

export const RFQ_STATUSES = {
  ACTIVE: 'active',
  CLOSED: 'closed',
  AWARDED: 'awarded',
  ARCHIVED: 'archived',
  DRAFT: 'draft',
  PENDING: 'pending',
  CANCELLED: 'cancelled',
  PUBLISHED: 'published',
} as const;

export type RfqStatus = (typeof RFQ_STATUSES)[keyof typeof RFQ_STATUSES];

export const isValidRfqStatus = (status: unknown): status is RfqStatus =>
  Object.values(RFQ_STATUSES).includes(status as RfqStatus);

export interface RfqListItem {
  id: string;
  displayIdentifier: string;
  title: string;
  status: RfqStatus;
  owner?: { name?: string; email?: string };
  deadline?: string;
  category?: string;
  estValue?: string;
  currency?: string;
  savings?: string;
  vendorsCount?: number;
  quotesCount?: number;
  projectId?: string | null;
}

export interface UseRfqsParams {
  q?: string;
  status?: string;
  owner?: string;
  category?: string;
  page?: number;
  projectId?: string;
}

/** Returns undefined for null, undefined, empty or whitespace-only string; otherwise a finite number. */
function parseOptionalNumber(value: unknown): number | undefined {
  if (value === null || value === undefined) return undefined;
  if (typeof value === 'string' && value.trim() === '') return undefined;
  const n = Number(value);
  return Number.isFinite(n) ? n : undefined;
}

/**
 * Coerces value to string or undefined at runtime, but only for primitive inputs.
 * Rejects objects/arrays/functions/symbols to avoid producing "[object Object]" and similar artifacts.
 */
function normalizeString(value: unknown): string | undefined {
  if (value === null || value === undefined) return undefined;
  if (typeof value === 'string') return value.trim() || undefined;
  if (typeof value === 'number' || typeof value === 'bigint') return String(value);
  return undefined;
}

/** For optional string | null fields (e.g. projectId); preserves null, normalizes primitives, rejects objects. */
function normalizeStringOrNull(value: unknown): string | null {
  if (value === null || value === undefined) return null;
  return normalizeString(value) ?? null;
}

export interface RfqsListMeta {
  current_page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

export interface RfqsListResult {
  items: RfqListItem[];
  meta: RfqsListMeta;
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

function parseRfqsMeta(payload: unknown): RfqsListMeta | null {
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const m = obj?.meta;
  if (m === null || m === undefined || typeof m !== 'object') return null;
  const meta = m as Record<string, unknown>;
  const total = isNonNegativeFiniteInt(meta.total);
  const perPage = isPositiveFiniteInt(meta.per_page);
  const currentPage = isPositiveFiniteInt(meta.current_page);
  const totalPages = isPositiveFiniteInt(meta.total_pages);
  if (total === null || perPage === null || currentPage === null || totalPages === null) return null;
  return {
    current_page: currentPage,
    per_page: perPage,
    total,
    total_pages: totalPages,
  };
}

function normalizeRfqsPayload(payload: unknown): RfqListItem[] {
  const asArray = (value: unknown): unknown[] | null => (Array.isArray(value) ? value : null);

  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const objData = obj?.data;
  const objDataObj = objData && typeof objData === 'object' ? (objData as Record<string, unknown>) : null;
  const arraySource = asArray(payload) ?? asArray(objData) ?? asArray(objDataObj?.data);

  if (!arraySource) {
    throw new Error('Invalid RFQ list response: expected data array.');
  }

  const list = arraySource;

  if (list.length === 0) return [];

  return list.map((raw: unknown) => {
    const r =
      raw != null && typeof raw === 'object' && !Array.isArray(raw) ? (raw as Record<string, unknown>) : {};
    const ownerRaw = r.owner;
    const owner =
      ownerRaw != null && typeof ownerRaw === 'object' && !Array.isArray(ownerRaw)
        ? (ownerRaw as Record<string, unknown>)
        : null;
    const ownerNameVal = String(owner?.name ?? r.owner_name ?? '');
    const ownerEmailVal = String(owner?.email ?? r.owner_email ?? '');
    const title = normalizeString(r.title ?? r.name) ?? 'Untitled';
    const currency = normalizeString(r.currency ?? r.tenant_currency ?? r.currency_code) ?? 'USD';
    return {
      id: normalizeString(r.id ?? r.rfqId ?? r.code) ?? '',
      displayIdentifier: normalizeString(r.display_identifier ?? r.displayIdentifier) ?? normalizeString(r.rfq_number ?? r.rfqNumber) ?? title,
      title,
      status: isValidRfqStatus(r.status) ? r.status : RFQ_STATUSES.ACTIVE,
      owner: ownerNameVal || ownerEmailVal ? { name: ownerNameVal, email: ownerEmailVal } : undefined,
      deadline: normalizeString(r.deadline ?? r.submissionDeadline ?? r.deadlineLabel),
      category: normalizeString(r.category),
      estValue: formatCurrencyValue(normalizeString(r.estValue ?? r.estimated_value ?? r.estimatedValue), currency),
      currency,
      savings: normalizeString(r.savings),
      vendorsCount: parseOptionalNumber(r.vendorsCount ?? r.vendors_count),
      quotesCount: parseOptionalNumber(r.quotesCount ?? r.quotes_count),
      projectId: normalizeStringOrNull(r.project_id ?? r.projectId ?? null),
    };
  });
}

export function useRfqs(params: UseRfqsParams) {
  return useQuery({
    queryKey: ['rfqs', params],
    queryFn: async (): Promise<RfqsListResult> => {
      const { projectId, ...restParams } = params;
      const apiParams: Record<string, string | number | undefined> = { ...restParams };
      if (projectId) apiParams.project_id = projectId;
      const data = await fetchLiveOrFail('/rfqs', { params: apiParams });
      const items = normalizeRfqsPayload(data).filter((x) => x.id);
      const metaFromApi = parseRfqsMeta(data);
      if (metaFromApi === null) {
        throw new Error('Invalid RFQ list response: pagination meta');
      }
      return { items, meta: metaFromApi };
    },
  });
}
