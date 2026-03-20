import { describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { useProjectAcl } from '@/hooks/use-project-acl';
import { createTestWrapper } from '@/test/utils';

vi.mock('@/lib/api', () => {
  return {
    api: {
      get: vi.fn(),
    },
  };
});

import { api } from '@/lib/api';

describe('useProjectAcl', () => {
  it('fetches ACL roles from /projects/:id/acl', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({
      data: { data: { roles: [{ user_id: 'u1', role: 'viewer' }] } },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useProjectAcl('p1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(api.get).toHaveBeenCalledWith('/projects/p1/acl');
    expect(result.current.data).toEqual([{ userId: 'u1', role: 'viewer' }]);
  });
});

