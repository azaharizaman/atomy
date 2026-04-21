'use client';

import { useQuery } from '@tanstack/react-query';
import { vendorIndex } from '@/generated/api/sdk.gen';
import { isObject, toText } from '@/hooks/normalize-utils';
import { useAuthStore } from '@/store/use-auth-store';

export const VENDOR_STATUSES = ['draft', 'under_review', 'approved', 'restricted', 'suspended', 'archived'] as const;
export type VendorStatusValue = (typeof VENDOR_STATUSES)[number];

export interface VendorApprovalRecord {
  approvedByUserId: string;
  approvedAt: string;
  approvalNote: string;
}

export interface VendorRow {
  id: string;
  legalName: string;
  displayName: string;
  registrationNumber: string;
  countryOfRegistration: string;
  primaryContactName: string;
  primaryContactEmail: string;
  primaryContactPhone: string | null;
  status: VendorStatusValue;
  approvalRecord: VendorApprovalRecord | null;
  createdAt: string;
  updatedAt: string;
  name: string;
  tradingName: string;
  countryCode: string;
  email: string;
  phone: string | null;
}

export interface VendorListMeta {
  currentPage: number;
  perPage: number;
  total: number;
  totalPages: number;
}

export interface VendorListResult {
  items: VendorRow[];
  meta: VendorListMeta;
}

export interface VendorListFilters {
  status?: VendorStatusValue;
  q?: string | null;
  search?: string | null;
}

export interface VendorUpsertInput {
  legalName: string;
  displayName: string;
  registrationNumber: string;
  countryOfRegistration: string;
  primaryContactName: string;
  primaryContactEmail: string;
  primaryContactPhone?: string | null;
}

export interface VendorStatusUpdateInput {
  status: VendorStatusValue;
  approvalNote?: string | null;
}

const vendorsQueryKey = ['vendors'] as const;

function requireObject(value: unknown, message: string): Record<string, unknown> {
  if (!isObject(value)) {
    throw new Error(message);
  }

  return value;
}

function pickField(item: Record<string, unknown>, ...keys: string[]): unknown {
  for (const key of keys) {
    if (key in item) {
      return item[key];
    }
  }

  return undefined;
}

function requireText(value: unknown, field: string, context: string): string {
  const text = toText(value);
  if (text === null) {
    throw new Error(`${context}: missing ${field}`);
  }

  return text;
}

function normalizeNullableText(value: unknown, field: string, context: string): string | null {
  if (value === null) {
    return null;
  }
  if (value === undefined) {
    throw new Error(`${context}: missing ${field}`);
  }

  const text = toText(value);
  if (text === null) {
    throw new Error(`${context}: missing ${field}`);
  }

  return text;
}

function requireFiniteInteger(value: unknown, field: string, context: string): number {
  const text = toText(value);
  if (text !== null) {
    const parsed = Number(text);
    if (Number.isFinite(parsed) && Number.isInteger(parsed)) {
      return parsed;
    }
  }

  if (typeof value === 'number' && Number.isFinite(value) && Number.isInteger(value)) {
    return value;
  }

  throw new Error(`${context}: ${field} must be a finite integer`);
}

function normalizeVendorStatus(value: unknown, context: string): VendorStatusValue {
  const status = requireText(value, 'status', context);
  if (!VENDOR_STATUSES.includes(status as VendorStatusValue)) {
    throw new Error(`${context}: invalid status "${status}"`);
  }

  return status as VendorStatusValue;
}

function normalizeApprovalRecord(value: unknown, context: string): VendorApprovalRecord | null {
  if (value === null) {
    return null;
  }

  const record = requireObject(value, `${context}: expected approval_record object or null`);

  return {
    approvedByUserId: requireText(
      pickField(record, 'approved_by_user_id', 'approvedByUserId'),
      'approved_by_user_id',
      context,
    ),
    approvedAt: requireText(
      pickField(record, 'approved_at', 'approvedAt'),
      'approved_at',
      context,
    ),
    approvalNote: requireText(
      pickField(record, 'approval_note', 'approvalNote'),
      'approval_note',
      context,
    ),
  };
}

function normalizeVendorRow(row: unknown, index: number): VendorRow {
  const item = requireObject(row, `Invalid vendor row at index ${index}: expected object`);
  const context = `Invalid vendor row at index ${index}`;
  const approvalRecordValue = pickField(item, 'approval_record', 'approvalRecord');
  if (approvalRecordValue === undefined) {
    throw new Error(`${context}: missing approval_record`);
  }

  const primaryContactPhoneValue = pickField(item, 'primary_contact_phone', 'primaryContactPhone');
  if (primaryContactPhoneValue === undefined) {
    throw new Error(`${context}: missing primary_contact_phone`);
  }

  const phoneValue = pickField(item, 'phone');
  if (phoneValue === undefined) {
    throw new Error(`${context}: missing phone`);
  }

  return {
    id: requireText(pickField(item, 'id'), 'id', context),
    legalName: requireText(pickField(item, 'legal_name', 'legalName'), 'legal_name', context),
    displayName: requireText(pickField(item, 'display_name', 'displayName'), 'display_name', context),
    registrationNumber: requireText(
      pickField(item, 'registration_number', 'registrationNumber'),
      'registration_number',
      context,
    ),
    countryOfRegistration: requireText(
      pickField(item, 'country_of_registration', 'countryOfRegistration'),
      'country_of_registration',
      context,
    ),
    primaryContactName: requireText(
      pickField(item, 'primary_contact_name', 'primaryContactName'),
      'primary_contact_name',
      context,
    ),
    primaryContactEmail: requireText(
      pickField(item, 'primary_contact_email', 'primaryContactEmail'),
      'primary_contact_email',
      context,
    ),
    primaryContactPhone: normalizeNullableText(primaryContactPhoneValue, 'primary_contact_phone', context),
    status: normalizeVendorStatus(pickField(item, 'status'), context),
    approvalRecord: normalizeApprovalRecord(approvalRecordValue, context),
    createdAt: requireText(pickField(item, 'created_at', 'createdAt'), 'created_at', context),
    updatedAt: requireText(pickField(item, 'updated_at', 'updatedAt'), 'updated_at', context),
    name: requireText(pickField(item, 'name'), 'name', context),
    tradingName: requireText(pickField(item, 'trading_name', 'tradingName'), 'trading_name', context),
    countryCode: requireText(pickField(item, 'country_code', 'countryCode'), 'country_code', context),
    email: requireText(pickField(item, 'email'), 'email', context),
    phone: normalizeNullableText(phoneValue, 'phone', context),
  };
}

function normalizeVendorListResponse(payload: unknown): VendorListResult {
  const envelope = requireObject(payload, 'Invalid vendors payload: expected object envelope with data array.');
  if (!Array.isArray(envelope.data)) {
    throw new Error('Invalid vendors payload: expected data array.');
  }

  const meta = requireObject(envelope.meta, 'Invalid vendors payload: expected meta object.');

  return {
    items: envelope.data.map((row: unknown, index: number) => normalizeVendorRow(row, index)),
    meta: {
      currentPage: requireFiniteInteger(pickField(meta, 'current_page', 'currentPage'), 'current_page', 'Invalid vendors payload'),
      perPage: requireFiniteInteger(pickField(meta, 'per_page', 'perPage'), 'per_page', 'Invalid vendors payload'),
      total: requireFiniteInteger(pickField(meta, 'total'), 'total', 'Invalid vendors payload'),
      totalPages: requireFiniteInteger(pickField(meta, 'total_pages', 'totalPages'), 'total_pages', 'Invalid vendors payload'),
    },
  };
}

export function normalizeVendorResponse(payload: unknown): VendorRow {
  const envelope = requireObject(payload, 'Invalid vendor payload: expected object envelope.');
  if (!('data' in envelope) || !isObject(envelope.data)) {
    throw new Error('Invalid vendor payload: expected non-null data object.');
  }

  return normalizeVendorRow(envelope.data, 0);
}

export function normalizeVendorListFilters(filters: VendorListFilters = {}): Record<string, string> {
  const query: Record<string, string> = {};
  const status = toText(filters.status);
  const q = toText(filters.q);
  const search = toText(filters.search);

  if (status !== null) {
    query.status = status;
  }
  if (q !== null) {
    query.q = q;
  }
  if (search !== null) {
    query.search = search;
  }

  return query;
}

export function buildVendorListQueryKey(filters: VendorListFilters = {}) {
  const query = normalizeVendorListFilters(filters);
  return Object.keys(query).length === 0 ? vendorsQueryKey : [...vendorsQueryKey, query] as const;
}

function extractMessageCandidate(value: unknown): string | null {
  if (value instanceof Error) {
    const message = value.message.trim();
    if (message !== '') {
      return message;
    }
  }

  if (typeof value === 'string') {
    const message = value.trim();
    if (message !== '') {
      return message;
    }
  }

  if (Array.isArray(value)) {
    for (const entry of value) {
      const candidate = extractMessageCandidate(entry);
      if (candidate !== null) {
        return candidate;
      }
    }
    return null;
  }

  if (!isObject(value)) {
    return null;
  }

  const directCandidates = [value.message, value.error, value.detail, value.title, value.statusText];
  for (const candidate of directCandidates) {
    const extracted = extractMessageCandidate(candidate);
    if (extracted !== null) {
      return extracted;
    }
  }

  const response = value.response;
  if (isObject(response)) {
    const responseCandidates = [response.data, response.error, response.message];
    for (const candidate of responseCandidates) {
      const extracted = extractMessageCandidate(candidate);
      if (extracted !== null) {
        return extracted;
      }
    }
  }

  const errors = value.errors;
  if (isObject(errors)) {
    for (const candidate of Object.values(errors)) {
      const extracted = extractMessageCandidate(candidate);
      if (extracted !== null) {
        return extracted;
      }
    }
  }

  const data = value.data;
  if (data !== undefined) {
    const extracted = extractMessageCandidate(data);
    if (extracted !== null) {
      return extracted;
    }
  }

  return null;
}

export function extractResponseErrorMessage(error: unknown): string {
  return extractMessageCandidate(error) ?? 'Request failed.';
}

async function invokeVendorIndex(filters: VendorListFilters): Promise<VendorListResult> {
  const token = useAuthStore.getState().token;
  try {
    const response = await vendorIndex({
      query: normalizeVendorListFilters(filters),
      credentials: 'include',
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
      throwOnError: true,
    });

    if (response === undefined) {
      throw new Error('Invalid vendors payload: missing response.');
    }

    return normalizeVendorListResponse(response.data);
  } catch (error: unknown) {
    throw new Error(extractResponseErrorMessage(error));
  }
}

export function useVendors(filters: VendorListFilters = {}) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
  const queryKey = buildVendorListQueryKey(filters);

  return useQuery({
    queryKey,
    enabled: !useMocks,
    queryFn: async (): Promise<VendorListResult> => invokeVendorIndex(filters),
  });
}
