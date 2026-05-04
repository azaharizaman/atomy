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
    vi.mocked(api.get).mockResolvedValueOnce({
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
    vi.mocked(api.get).mockResolvedValueOnce({
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
    expect(api.get).toHaveBeenCalledWith('/tasks', { params: { status: 'completed' } });
  });

  it('normalizes display identifiers from task and project payloads', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'task-1',
            display_identifier: 'Review supplier quote',
            title: 'Review supplier quote',
            status: 'pending',
            project_id: 'project-1',
            project_display_identifier: 'Pump Upgrade',
          },
        ],
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useTasks(), {
      wrapper: Wrapper,
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data?.[0]).toMatchObject({
      displayIdentifier: 'Review supplier quote',
      projectDisplayIdentifier: 'Pump Upgrade',
    });
  });
});
