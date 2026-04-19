'use client';

import { useMutation } from '@tanstack/react-query';

import { rfqStoreLineItem } from '@/generated/api';

export interface CreateRfqLineItemInput {
  description: string;
  quantity: number;
  uom: string;
  unit_price: number;
  currency: string;
  specifications?: string | null;
}

export function useCreateRfqLineItem(rfqId: string) {
  return useMutation({
    mutationFn: async (input: CreateRfqLineItemInput) => {
      return rfqStoreLineItem(
        { path: { rfqId }, body: { description: input.description, quantity: input.quantity, uom: input.uom, unit_price: input.unit_price, currency: input.currency, specifications: input.specifications ?? null } },
        { throwOnError: true },
      );
    },
  });
}