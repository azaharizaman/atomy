import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';
import { useRfqs } from '@/hooks/use-rfqs';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useRfqs (live-only)', () => {
  beforeEach(() => {
    getMock.mockReset();
  });

  it('fetches live RFQs', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'rfq-1',
            title: 'Live RFQ',
            status: 'active',
            project_id: 'project-1',
            estimated_value: 950000,
            currency: 'USD',
          },
        ],
        meta: {
          current_page: 1,
          per_page: 20,
          total: 1,
          total_pages: 1,
        },
      },
    });

    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqs({ projectId: 'project-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(getMock).toHaveBeenCalledTimes(1);
    expect(getMock.mock.calls[0][0]).toBe('/rfqs');
    expect(getMock.mock.calls[0][1]).toEqual({
      params: {
        project_id: 'project-1',
      },
    });
    expect(result.current.data?.items).toHaveLength(1);
    expect(result.current.data?.items[0]?.id).toBe('rfq-1');
    expect(result.current.data?.items[0]?.estValue).toBe('$950,000.00');
  });

  it('passes the user-facing active RFQ bucket to the API', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [],
        meta: {
          current_page: 1,
          per_page: 20,
          total: 0,
          total_pages: 1,
        },
      },
    });

    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqs({ status: 'active' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(getMock).toHaveBeenCalledWith('/rfqs', {
      params: {
        status: 'active',
      },
    });
  });
});
