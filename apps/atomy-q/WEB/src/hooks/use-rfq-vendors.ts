'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { getSeedVendorsByRfqId } from '@/data/seed';

export interface RfqVendorRow {
  id: string;
  name: string;
  status: 'invited' | 'responded';
  contact: string;
}

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

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
      if (useMocks) {
        return getSeedVendorsByRfqId(rfqId).map((v) => ({
          id: v.id,
          name: v.name,
          status: v.status,
          contact: v.email,
        }));
      }
      const { data } = await api.get(`/rfqs/${encodeURIComponent(rfqId)}/invitations`);
      return normalizeInvitationPayload(data);
    },
    enabled: Boolean(rfqId),
  });
}
