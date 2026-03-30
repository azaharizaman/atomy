import { beforeAll, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';
import { api } from '@/lib/api';
import { useAward } from './use-award';

vi.mock('@/lib/api', () => ({
  api: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

describe('useAward', () => {
  it('normalizes award rows and invalidates after signoff', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'award-1',
            rfq_id: 'rfq-1',
            rfq_title: 'RFQ',
            rfq_number: 'RFQ-1',
            comparison_run_id: 'run-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Winner Vendor',
            status: 'pending',
            amount: '1000.00',
            currency: 'USD',
            split_details: [],
            protest_id: null,
            signoff_at: null,
            signed_off_by: null,
          },
        ],
      },
    });
    vi.mocked(api.post).mockResolvedValueOnce({
      data: { data: { id: 'award-1', status: 'signed_off' } },
    });

    const { queryClient, Wrapper } = createTestWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.award?.vendor_name).toBe('Winner Vendor'));

    await result.current.signoff.mutateAsync('award-1');

    expect(api.get).toHaveBeenCalledWith('/awards', { params: { rfq_id: 'rfq-1' } });
    expect(api.post).toHaveBeenCalledWith('/awards/award-1/signoff');
    expect(invalidateSpy).toHaveBeenCalled();
  });
});
