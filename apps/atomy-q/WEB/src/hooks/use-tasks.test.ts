import { describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { useTasks } from '@/hooks/use-tasks';
import { createTestWrapper } from '@/test/utils';

vi.mock('@/lib/api', () => {
  return {
    api: {
      get: vi.fn(),
    },
  };
});

import { api } from '@/lib/api';

describe('useTasks', () => {
  it('passes assignee_id param through to API', async () => {
    (api.get as unknown as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
      data: { data: [] },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useTasks({ assignee_id: 'user-1' }), {
      wrapper: Wrapper,
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(api.get).toHaveBeenCalledWith('/tasks', { params: { assignee_id: 'user-1' } });
  });

  it('filters locally by status when params.status is set', async () => {
    (api.get as unknown as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
      data: {
        data: [
          { id: 't1', title: 'A', status: 'pending' },
          { id: 't2', title: 'B', status: 'completed' },
        ],
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useTasks({ status: 'completed' }), {
      wrapper: Wrapper,
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data?.map((t) => t.id)).toEqual(['t2']);
  });
});

