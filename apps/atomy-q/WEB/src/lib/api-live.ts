'use client';

import { api } from '@/lib/api';

/**
 * Fetches from API in live mode, throwing a descriptive error on failure.
 * In mock mode, returns undefined to signal the caller should use seed data.
 *
 * @param endpoint - API endpoint path (with or without query params)
 * @param options - Optional: signal for abort, headers, etc.
 * @returns API response data, or undefined if useMocks is true
 * @throws Error with descriptive message in live mode on API failure
 */
export async function fetchLiveOrFail<T>(
  endpoint: string,
  options?: { signal?: AbortSignal; headers?: Record<string, string> }
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
    throw new Error(`Failed to load ${endpoint}: ${message}`);
  }
}