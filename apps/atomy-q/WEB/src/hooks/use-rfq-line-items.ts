'use client';

import { useQuery } from '@tanstack/react-query';

import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText } from '@/hooks/normalize-utils';

export interface RfqLineItemRow {
  id: string;
  rfq_id: string;
  rowType?: 'heading' | 'line';
  section?: string | null;
  description: string;
  quantity: number;
  uom: string;
  unit_price: number;
  currency: string;
  specifications: string | null;
  sort_order: number;
}

interface SeedLineItem {
  id: string;
  rfqId: string;
  rowType: 'heading' | 'line';
  section: string | null;
  description: string;
  quantity: number;
  uom: string;
  unitPrice: number;
  currency: string;
  specifications: string | null;
  sortOrder: number;
}

function toRequiredNumber(value: unknown, fieldName: string): number {
  if (value === null || value === undefined) {
    throw new Error(`RFQ line item is missing ${fieldName}.`);
  }

  if (typeof value === 'string') {
    const trimmed = value.trim();
    if (trimmed === '') {
      throw new Error(`RFQ line item is missing ${fieldName}.`);
    }
    const numberValue = Number(trimmed);
    if (!Number.isFinite(numberValue)) {
      throw new Error(`RFQ line item field ${fieldName} must be numeric.`);
    }
    return numberValue;
  }

  if (value === '') {
    throw new Error(`RFQ line item is missing ${fieldName}.`);
  }

  const numberValue = Number(value);
  if (!Number.isFinite(numberValue)) {
    throw new Error(`RFQ line item field ${fieldName} must be numeric.`);
  }

  return numberValue;
}

function normalizeLineItems(payload: unknown): RfqLineItemRow[] {
  if (!isObject(payload)) {
    throw new Error('RFQ line items payload must be an object.');
  }

  if (!Array.isArray(payload.data)) {
    throw new Error('RFQ line items payload is missing data array.');
  }

  return payload.data.map((item: unknown, index: number): RfqLineItemRow => {
    if (!isObject(item)) {
      throw new Error(`RFQ line item at index ${index} must be an object.`);
    }

    const id = toText(item.id);
    const rfqId = toText(item.rfq_id ?? item.rfqId);
    const description = toText(item.description);
    const uom = toText(item.uom);
    const currency = toText(item.currency);

    if (id === null || rfqId === null || description === null || uom === null || currency === null) {
      throw new Error(`RFQ line item at index ${index} is missing required fields.`);
    }

    const rawRowType = item.rowType ?? item.row_type;
    const rawSection = item.section;
    const resolvedRowType: 'heading' | 'line' =
      rawRowType === 'heading' || rawRowType === 'line' ? rawRowType : 'line';
    const resolvedSection = typeof rawSection === 'string' ? rawSection : null;

    return {
      id,
      rfq_id: rfqId,
      rowType: resolvedRowType,
      section: resolvedSection,
      description,
      quantity: toRequiredNumber(item.quantity, 'quantity'),
      uom,
      unit_price: toRequiredNumber(item.unit_price ?? item.unitPrice, 'unit_price'),
      currency,
      specifications: toText(item.specifications ?? item.notes),
      sort_order: toRequiredNumber(item.sort_order ?? item.sortOrder ?? index + 1, 'sort_order'),
    };
  });
}

function mapSeedLineItems(items: SeedLineItem[]): RfqLineItemRow[] {
  return items.map((item) => ({
    id: item.id,
    rfq_id: item.rfqId,
    rowType: item.rowType,
    section: item.section,
    description: item.description,
    quantity: item.quantity,
    uom: item.uom,
    unit_price: item.unitPrice,
    currency: item.currency,
    specifications: item.specifications,
    sort_order: item.sortOrder,
  }));
}

export function useRfqLineItems(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  const mockQuery = useQuery({
    queryKey: ['rfqs', rfqId, 'line-items', 'mock'],
    enabled: useMocks && Boolean(rfqId),
    queryFn: async () => {
      const { getSeedLineItemsByRfqId } = await import('@/data/seed');
      return mapSeedLineItems(getSeedLineItemsByRfqId(rfqId));
    },
  });

  const liveQuery = useQuery({
    queryKey: ['rfqs', rfqId, 'line-items'],
    enabled: !useMocks && Boolean(rfqId),
    queryFn: async () => {
      const data = await fetchLiveOrFail<{ data: RfqLineItemRow[] }>(`/rfqs/${encodeURIComponent(rfqId)}/line-items`);

      if (data === undefined) {
        throw new Error(`Line items unavailable for RFQ "${rfqId}".`);
      }

      return normalizeLineItems(data);
    },
  });

  return useMocks ? mockQuery : liveQuery;
}
