import { describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { useUpdateProjectAcl } from '@/hooks/use-update-project-acl';
import { createTestWrapper } from '@/test/utils';

vi.mock('@/lib/api', () => {
  return {
    api: {
      put: vi.fn(),
    },
  };
});

import { api } from '@/lib/api';

describe('useUpdateProjectAcl', () => {
  it('throws when API returns missing roles', async () => {
    vi.mocked(api.put).mockResolvedValueOnce({ data: { data: {} } });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useUpdateProjectAcl('p1'), { wrapper: Wrapper });

    result.current.mutate([{ userId: 'u1', role: 'viewer' }]);

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect((result.current.error as Error).message).toContain('Invalid ACL payload');
  });

  it('sends roles payload and returns normalized roles', async () => {
    vi.mocked(api.put).mockResolvedValueOnce({
      data: { data: { roles: [{ user_id: 'u1', role: 'owner' }] } },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useUpdateProjectAcl('p1'), { wrapper: Wrapper });

    result.current.mutate([{ userId: 'u1', role: 'owner' }]);

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(api.put).toHaveBeenCalledWith('/projects/p1/acl', {
      roles: [{ user_id: 'u1', role: 'owner' }],
    });
    expect(result.current.data).toEqual([{ userId: 'u1', role: 'owner' }]);
  });
});

