'use client';

import React from 'react';
import { useQuery, type UseQueryResult } from '@tanstack/react-query';

import { api } from '@/lib/api';
import {
  createAiStatusFallback,
  createDeterministicAiStatusSnapshot,
  createMockAiStatusSnapshot,
  isAiQueryEnabled,
  normalizeAiStatusPayload,
  resolveAiStatusPath,
  type AiStatusSnapshot,
} from '@/lib/ai-status';
import {
  isFeatureAvailable as isFeatureAvailableForStatus,
  messageKeyForFeature as messageKeyForFeatureForStatus,
  shouldHideAiControls as shouldHideAiControlsForStatus,
  shouldShowUnavailableMessage as shouldShowUnavailableMessageForStatus,
} from '@/lib/ai-capabilities';

type AiStatusRefetch = () => Promise<unknown>;

export interface AiStatusContextValue {
  status: AiStatusSnapshot;
  error: Error | null;
  isLoading: boolean;
  isReady: boolean;
  isError: boolean;
  refetch: AiStatusRefetch;
  isFeatureAvailable: (featureKey: string) => boolean;
  shouldHideAiControls: (featureKey: string) => boolean;
  shouldShowUnavailableMessage: (featureKey: string) => boolean;
  messageKeyForFeature: (featureKey: string) => string | null;
}

export const AiStatusContext = React.createContext<AiStatusContextValue | null>(null);

async function fetchAiStatus(): Promise<AiStatusSnapshot> {
  const { data } = await api.get(resolveAiStatusPath());
  return normalizeAiStatusPayload(data);
}

function getDisabledAiStatusSnapshot(): AiStatusSnapshot {
  if (process.env.NEXT_PUBLIC_USE_MOCKS === 'true') {
    return createMockAiStatusSnapshot();
  }

  if (process.env.NEXT_PUBLIC_AI_MODE === 'deterministic') {
    return createDeterministicAiStatusSnapshot();
  }

  return createAiStatusFallback('off');
}

export function useAiStatusQuery(): UseQueryResult<AiStatusSnapshot, Error> {
  const enabled = isAiQueryEnabled();
  const disabledSnapshot = getDisabledAiStatusSnapshot();

  return useQuery({
    queryKey: ['ai-status'],
    queryFn: fetchAiStatus,
    enabled,
    initialData: enabled ? undefined : disabledSnapshot,
    staleTime: 60 * 1000,
    retry: false,
    refetchOnReconnect: false,
    refetchOnWindowFocus: false,
  });
}

export function buildAiStatusContextValue(
  query: {
    data?: AiStatusSnapshot;
    error?: Error | null;
    isLoading: boolean;
    enabled?: boolean;
    refetch?: AiStatusRefetch;
  },
): AiStatusContextValue {
  const status =
    query.data ??
    (query.error
      ? createAiStatusFallback('error')
      : query.enabled === false
        ? getDisabledAiStatusSnapshot()
        : createAiStatusFallback('unknown'));
  const shouldNoOpRefetch = query.enabled === false || status.mode === 'off' || status.mode === 'deterministic';

  return {
    status,
    error: query.error ?? null,
    isLoading: query.isLoading,
    isReady: !query.isLoading && query.error == null,
    isError: status.requestState === 'error',
    refetch: shouldNoOpRefetch ? (() => Promise.resolve(status)) : query.refetch ?? (() => Promise.resolve(status)),
    isFeatureAvailable: (featureKey) => isFeatureAvailableForStatus(status, featureKey),
    shouldHideAiControls: (featureKey) => shouldHideAiControlsForStatus(status, featureKey),
    shouldShowUnavailableMessage: (featureKey) =>
      shouldShowUnavailableMessageForStatus(status, featureKey),
    messageKeyForFeature: (featureKey) => messageKeyForFeatureForStatus(status, featureKey),
  };
}

export function useAiStatus(): AiStatusContextValue {
  const context = React.useContext(AiStatusContext);

  if (context === null) {
    return buildAiStatusContextValue({
      data: createAiStatusFallback('off'),
      error: null,
      isLoading: false,
      enabled: false,
      refetch: () => Promise.resolve(createAiStatusFallback('off')),
    });
  }

  return context;
}
