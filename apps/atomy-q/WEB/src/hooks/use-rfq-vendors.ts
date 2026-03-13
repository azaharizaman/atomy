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
  return list.map((item: Record<string, unknown>) => ({
    id: String(item.id ?? ''),
    name: String(item.vendor_name ?? item.name ?? ''),
    status: (item.status === 'accepted' || item.status === 'responded' ? 'responded' : 'invited') as 'invited' | 'responded',
    contact: String(item.vendor_email ?? item.email ?? item.contact ?? ''),
  }));
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
