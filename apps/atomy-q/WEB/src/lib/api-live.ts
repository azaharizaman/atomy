// src/lib/api-live.ts
'use client';

import { api } from '@/lib/api';

export type FetchResponseType = 'json' | 'text' | 'blob' | 'arraybuffer';

export interface FetchLiveOrFailOptions {
  signal?: AbortSignal;
  headers?: Record<string, string>;
  params?: Record<string, string | number | undefined>;
  responseType?: FetchResponseType;
}

type LiveApiError = Error & {
  status?: number;
  response?: unknown;
};

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
    const err: LiveApiError = new Error(`Failed to load ${endpoint}: ${message}`);
    if (e instanceof Error) {
      err.cause = e;
    }
    const axiosErr = e as { response?: { status?: number; data?: unknown }; status?: number };
    if (axiosErr.response) {
      err.status = axiosErr.response.status;
      err.response = axiosErr.response.data;
    } else if (axiosErr.status) {
      err.status = axiosErr.status;
    }
    throw err;
  }
}
