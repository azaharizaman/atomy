import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';

import { createTestWrapper } from '@/test/utils';
import { useRfqLineItems } from './use-rfq-line-items';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useRfqLineItems (live-only)', () => {
  beforeEach(() => {
    getMock.mockReset();
  });

  it('fetches live line items', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'line-1',
            rfq_id: 'RFQ-2026-0001',
            description: 'Network switches',
            quantity: 8,
            uom: 'units',
            unit_price: 2500,
            currency: 'USD',
            specifications: '48-port',
            sort_order: 1,
          },
        ],
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useRfqLineItems('RFQ-2026-0001'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(getMock).toHaveBeenCalledTimes(1);
    expect(getMock.mock.calls[0][0]).toBe('/rfqs/RFQ-2026-0001/line-items');
    expect(result.current.data).toHaveLength(1);
    expect(result.current.data?.[0].id).toBe('line-1');
    expect(result.current.data?.[0].rowType).toBe('line');
  });
});
