import { afterAll, describe, expect, it } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';

import { createTestWrapper } from '@/test/utils';
import { useRfqLineItems } from './use-rfq-line-items';

const originalMocks = process.env.NEXT_PUBLIC_USE_MOCKS;
process.env.NEXT_PUBLIC_USE_MOCKS = 'true';

describe('useRfqLineItems (seed fallback)', () => {
  afterAll(() => {
    if (originalMocks === undefined) {
      delete process.env.NEXT_PUBLIC_USE_MOCKS;
    } else {
      process.env.NEXT_PUBLIC_USE_MOCKS = originalMocks;
    }
  });

  it('returns seeded requisition line items in mock mode', async () => {
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useRfqLineItems('RFQ-2026-0001'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.length).toBeGreaterThan(0);
    expect(result.current.data?.every((item) => item.rfq_id === 'RFQ-2026-0001')).toBe(true);
    expect(result.current.data?.some((item) => item.rowType === 'heading')).toBe(true);
    expect(result.current.data?.some((item) => item.rowType === 'line')).toBe(true);
  });
});
