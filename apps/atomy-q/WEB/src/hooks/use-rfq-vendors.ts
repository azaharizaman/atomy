'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { getSeedVendorsByRfqId } from '@/data/seed';

export interface RfqVendorRow {
  id: string;
  vendor_id: string | null;
  name: string;
  status: 'invited' | 'responded';
  contact: string;
}

function normalizeInvitationPayload(payload: unknown): RfqVendorRow[] {
  const raw = payload as { data?: unknown[] };
  const list = Array.isArray(raw?.data) ? raw.data : [];
  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid RFQ vendor row at index ${index}: expected object`);
    }
    const row = item as Record<string, unknown>;
    return {
      id: String(row.id ?? ''),
      vendor_id: row.vendor_id !== undefined && row.vendor_id !== null ? String(row.vendor_id) : null,
      name: String(row.vendor_name ?? row.name ?? ''),
      status: (row.status === 'accepted' || row.status === 'responded' ? 'responded' : 'invited') as 'invited' | 'responded',
      contact: String(row.vendor_email ?? row.email ?? row.contact ?? ''),
    };
  });
}

export function useRfqVendors(rfqId: string) {
  return useQuery({
    queryKey: ['rfqs', rfqId, 'vendors'],
    queryFn: async (): Promise<RfqVendorRow[]> => {
      const data = await fetchLiveOrFail<{ data: RfqVendorRow[] }>(`/rfqs/${encodeURIComponent(rfqId)}/invitations`);

      return normalizeInvitationPayload(data);
    },
    enabled: Boolean(rfqId),
  });
}
