import { describe, expect, it } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

// Ensure the hook runs in seed/mock mode.
process.env.NEXT_PUBLIC_USE_MOCKS = 'true';

import { useRfqs } from '@/hooks/use-rfqs';

describe('useRfqs (seed fallback)', () => {
  it('filters by projectId when provided', async () => {
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useRfqs({ projectId: '01JNE4ZHT9S0VQ7E2GQW1QYJ7B' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError || result.current.isSuccess).toBe(true));
    if (result.current.isError) throw result.current.error;
    const items = result.current.data ?? [];
    expect(items.length).toBeGreaterThan(0);
    expect(items.every((x) => x.projectId === '01JNE4ZHT9S0VQ7E2GQW1QYJ7B')).toBe(true);
  });

  it('returns empty list when projectId matches none', async () => {
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useRfqs({ projectId: 'no-such-project' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError || result.current.isSuccess).toBe(true));
    if (result.current.isError) throw result.current.error;
    expect(result.current.data).toEqual([]);
  });
});

