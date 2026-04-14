// src/lib/api-live.ts
'use client';

import { api } from '@/lib/api';

export type FetchResponseType = 'json' | 'text' | 'blob' | 'arrayBuffer';

export interface FetchLiveOrFailOptions {
  signal?: AbortSignal;
  headers?: Record<string, string>;
  params?: Record<string, string | number | undefined>;
  responseType?: FetchResponseType;
}

/**
 * Fetches from API in live mode, throwing a descriptive error on failure.
 * In mock mode, returns undefined to signal the caller should use seed data.
 */
export async function fetchLiveOrFail<T>(
  endpoint: string,
  options?: FetchLiveOrFailOptions
): Promise<T | undefined> {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  if (useMocks) {
    return undefined;
  }

  try {
    const { data } = await api.get(endpoint, options);
    return data as T;
  } catch (e) {
    const message = e instanceof Error ? e.message : 'Unknown error';
    const err = new Error(`Failed to load ${endpoint}: ${message}`);
    if (e instanceof Error) {
      err.cause = e;
    }
    const axiosErr = e as { response?: { status?: number; data?: unknown }; status?: number };
    if (axiosErr.response) {
      (err as Record<string, unknown>).status = axiosErr.response.status;
      (err as Record<string, unknown>).response = axiosErr.response.data;
    } else if (axiosErr.status) {
      (err as Record<string, unknown>).status = axiosErr.status;
    }
    throw err;
  }
}