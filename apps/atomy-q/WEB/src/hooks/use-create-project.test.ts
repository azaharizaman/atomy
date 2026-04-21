import { describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { useCreateProject } from '@/hooks/use-create-project';
import { createTestWrapper } from '@/test/utils';

vi.mock('@/lib/api', () => {
  return {
    api: {
      post: vi.fn(),
    },
  };
});

import { api } from '@/lib/api';

describe('useCreateProject', () => {
  it('throws when API returns missing required fields', async () => {
    vi.mocked(api.post).mockResolvedValueOnce({
      data: { data: { id: 'p1' } },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useCreateProject(), { wrapper: Wrapper });

    result.current.mutate({
      name: 'N',
      start_date: '2026-01-01',
      end_date: '2026-02-01',
      project_manager_id: 'u1',
    });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect((result.current.error as Error).message).toContain('Invalid project payload');
  });

  it('returns mapped result when payload is valid', async () => {
    vi.mocked(api.post).mockResolvedValueOnce({
      data: {
        data: {
          id: 'p1',
          name: 'Project',
          status: 'planning',
          start_date: '2026-01-01',
          end_date: '2026-02-01',
          project_manager_id: 'u1',
        },
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useCreateProject(), { wrapper: Wrapper });

    result.current.mutate({
      name: 'Project',
      start_date: '2026-01-01',
      end_date: '2026-02-01',
      project_manager_id: 'u1',
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toMatchObject({ id: 'p1', name: 'Project', project_manager_id: 'u1' });
  });
});
