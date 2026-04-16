'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText } from '@/hooks/normalize-utils';

export interface RfqVendorRow {
  id: string;
  vendor_id: string | null;
  name: string;
  status: 'invited' | 'responded';
  contact: string;
}

function normalizeInvitationPayload(payload: unknown): RfqVendorRow[] {
  if (!isObject(payload)) {
    throw new Error('Invalid RFQ vendor response: expected object envelope with data array.');
  }

  if (!Array.isArray(payload.data)) {
    throw new Error('Invalid RFQ vendor response: expected data array.');
  }

  const list = payload.data;
  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid RFQ vendor row at index ${index}: expected object`);
    }
    const row = item as Record<string, unknown>;
    const id = toText(row.id);
    const name = toText(row.vendor_name ?? row.name);
    const contact = toText(row.vendor_email ?? row.email ?? row.contact);
    if (id === null || name === null || contact === null) {
      throw new Error(`Invalid RFQ vendor row at index ${index}: missing id, vendor_name, or contact`);
    }

    const status = row.status === 'accepted' || row.status === 'responded' ? 'responded' : row.status === 'invited' ? 'invited' : null;
    if (status === null) {
      throw new Error(`Invalid RFQ vendor row at index ${index}: invalid status`);
    }

    return {
      id,
      vendor_id: row.vendor_id !== undefined && row.vendor_id !== null ? String(row.vendor_id) : null,
      name,
      status,
      contact,
    };
  });
}

export function useRfqVendors(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', rfqId, 'vendors'],
    queryFn: async (): Promise<RfqVendorRow[]> => {
      if (useMocks) {
        const { getSeedVendorsByRfqId } = await import('@/data/seed');
        const seededPayload = {
          data: getSeedVendorsByRfqId(rfqId).map((vendor) => ({
            id: vendor.id,
            vendor_id: vendor.id,
            vendor_name: vendor.name,
            status: vendor.status,
            vendor_email: vendor.email,
          })),
        };
        return normalizeInvitationPayload(seededPayload);
      }

      const data = await fetchLiveOrFail<{ data: RfqVendorRow[] }>(`/rfqs/${encodeURIComponent(rfqId)}/invitations`);

      if (data === undefined) {
        throw new Error(`Vendor invitations unavailable for RFQ "${rfqId}".`);
      }

      return normalizeInvitationPayload(data);
    },
    enabled: Boolean(rfqId),
  });
}
