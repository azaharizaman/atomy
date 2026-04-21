'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';

import { rfqStoreLineItem } from '@/generated/api';
import { useAuthStore } from '@/store/use-auth-store';

export interface CreateRfqLineItemInput {
  description: string;
  quantity: number;
  uom: string;
  unit_price: number;
  currency: string;
  specifications?: string | null;
}

function extractResponseErrorMessage(error: unknown): string {
  if (error instanceof Error) {
    const message = error.message.trim();
    if (message !== '') {
      return message;
    }
  }

  if (typeof error === 'string') {
    const message = error.trim();
    if (message !== '') {
      return message;
    }
  }

  if (error !== null && typeof error === 'object') {
    const rec = error as Record<string, unknown>;
    const messageRaw = rec.message ?? rec.error;
    if (typeof messageRaw === 'string' && messageRaw.trim() !== '') {
      return messageRaw.trim();
    }

    const errorsRaw = rec.errors;
    if (errorsRaw !== null && typeof errorsRaw === 'object' && !Array.isArray(errorsRaw)) {
      const firstField = Object.values(errorsRaw as Record<string, unknown>)[0];
      if (Array.isArray(firstField)) {
        const firstMessage = firstField.find((value): value is string => typeof value === 'string' && value.trim() !== '');
        if (firstMessage) {
          return firstMessage;
        }
      }
      if (typeof firstField === 'string' && firstField.trim() !== '') {
        return firstField.trim();
      }
    }
  }

  return 'Could not create line item.';
}

export function useCreateRfqLineItem(rfqId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: CreateRfqLineItemInput) => {
      const token = useAuthStore.getState().token;

      try {
        return await rfqStoreLineItem(
          {
            path: { rfqId },
            body: {
              description: input.description,
              quantity: input.quantity,
              uom: input.uom,
              unit_price: input.unit_price,
              currency: input.currency,
              specifications: input.specifications ?? null,
            },
            credentials: 'include',
            headers: token ? { Authorization: `Bearer ${token}` } : undefined,
            throwOnError: true,
          },
        );
      } catch (error: unknown) {
        throw new Error(extractResponseErrorMessage(error));
      }
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['rfqs', rfqId] });
      await queryClient.invalidateQueries({ queryKey: ['rfqs', rfqId, 'line-items'] });
    },
  });
}
