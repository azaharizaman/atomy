'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText } from '@/hooks/normalize-utils';

export interface RequisitionVendorSelectionRow {
  id: string;
  rfqId: string;
  vendorId: string;
  vendorName: string;
  vendorDisplayName: string | null;
  vendorEmail: string | null;
  status: string;
  selectedAt: string;
  selectedByUserId: string | null;
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
  if (value === null || value === undefined) {
    return null;
  }

  const text = toText(value);
  if (text === null) {
    throw new Error(`${context}: missing ${field}`);
  }

  return text;
}

function normalizeSelectionRow(row: unknown, index: number): RequisitionVendorSelectionRow {
  if (!isObject(row)) {
    throw new Error(`Invalid selected vendor row at index ${index}: expected object`);
  }

  const context = `Invalid selected vendor row at index ${index}`;
  const vendorDisplayNameValue = pickField(row, 'vendor_display_name', 'vendorDisplayName', 'display_name', 'displayName');
  const vendorEmailValue = pickField(row, 'vendor_email', 'vendorEmail', 'email');

  return {
    id: requireText(pickField(row, 'id'), 'id', context),
    rfqId: requireText(pickField(row, 'rfq_id', 'rfqId'), 'rfq_id', context),
    vendorId: requireText(pickField(row, 'vendor_id', 'vendorId'), 'vendor_id', context),
    vendorName: requireText(pickField(row, 'vendor_name', 'vendorName'), 'vendor_name', context),
    vendorDisplayName: normalizeNullableText(vendorDisplayNameValue, 'vendor_display_name', context),
    vendorEmail: normalizeNullableText(vendorEmailValue, 'vendor_email', context),
    status: requireText(pickField(row, 'status'), 'status', context),
    selectedAt: requireText(pickField(row, 'selected_at', 'selectedAt'), 'selected_at', context),
    selectedByUserId: normalizeNullableText(
      pickField(row, 'selected_by_user_id', 'selectedByUserId'),
      'selected_by_user_id',
      context,
    ),
  };
}

export function normalizeRequisitionVendorSelectionPayload(payload: unknown): RequisitionVendorSelectionRow[] {
  const envelope = isObject(payload) ? payload : null;
  if (envelope === null || !Array.isArray(envelope.data)) {
    throw new Error('Invalid selected vendors payload: expected object envelope with data array.');
  }

  return envelope.data.map((row: unknown, index: number) => normalizeSelectionRow(row, index));
}

export function useRequisitionVendorSelection(rfqId: string) {
  return useQuery({
    queryKey: ['rfqs', rfqId, 'selected-vendors'],
    enabled: Boolean(rfqId),
    queryFn: async (): Promise<RequisitionVendorSelectionRow[]> => {
      const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
      if (useMocks) {
        return [];
      }

      const data = await fetchLiveOrFail<unknown>(
        `/rfqs/${encodeURIComponent(rfqId)}/selected-vendors`,
      );

      if (data === undefined) {
        throw new Error(`Selected vendors unavailable for RFQ "${rfqId}".`);
      }

      return normalizeRequisitionVendorSelectionPayload(data);
    },
  });
}
