import { renderHook, waitFor } from '@testing-library/react';
import { describe, expect, it, beforeEach, vi } from 'vitest';
import { createTestWrapper } from '@/test/utils';
import {
  userIndex,
  userInvite,
  userReactivate,
  userRoles,
  userSuspend,
} from '@/generated/api/sdk.gen';
import {
  useInviteUser,
  useReactivateUser,
  useSuspendUser,
  useUserRoles,
  useUsers,
} from '@/hooks/use-users';

vi.mock('@/generated/api/sdk.gen', () => ({
  userIndex: vi.fn(),
  userInvite: vi.fn(),
  userRoles: vi.fn(),
  userSuspend: vi.fn(),
  userReactivate: vi.fn(),
}));

describe('use-users', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('normalizes users payload success', async () => {
    vi.mocked(userIndex).mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'user-1',
            name: ' Alpha Admin ',
            email: ' admin@atomy.test ',
            status: ' active ',
            role: ' admin ',
            created_at: '2026-04-17T00:00:00Z',
            last_login_at: null,
          },
        ],
        meta: {
          current_page: 1,
          per_page: 20,
          total: 1,
        },
      },
      error: undefined,
      request: {} as Request,
      response: {} as Response,
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useUsers(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data).toEqual({
      items: [
        {
          id: 'user-1',
          name: 'Alpha Admin',
          email: 'admin@atomy.test',
          status: 'active',
          role: 'admin',
          createdAt: '2026-04-17T00:00:00Z',
          lastLoginAt: null,
        },
      ],
      meta: {
        currentPage: 1,
        perPage: 20,
        total: 1,
      },
    });
  });

  it('rejects malformed users payloads', async () => {
    vi.mocked(userIndex).mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'user-1',
            email: 'admin@atomy.test',
            status: 'active',
          },
        ],
        meta: {
          current_page: 1,
          per_page: 20,
          total: 1,
        },
      },
      error: undefined,
      request: {} as Request,
      response: {} as Response,
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useUsers(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect((result.current.error as Error).message).toBe('Invalid user row at index 0: missing role');
  });

  it('normalizes roles payload success', async () => {
    vi.mocked(userRoles).mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'role-1',
            name: ' admin ',
            description: 'Workspace admin',
            tenant_id: 'tenant-1',
            is_system_role: false,
          },
          {
            id: 'role-2',
            name: ' reviewer ',
            description: null,
            tenant_id: null,
            is_system_role: true,
          },
        ],
      },
      error: undefined,
      request: {} as Request,
      response: {} as Response,
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useUserRoles(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data).toEqual([
      {
        id: 'role-1',
        name: 'admin',
        description: 'Workspace admin',
        tenantId: 'tenant-1',
        isSystemRole: false,
      },
      {
        id: 'role-2',
        name: 'reviewer',
        description: null,
        tenantId: null,
        isSystemRole: true,
      },
    ]);
  });

  it('rejects malformed roles payloads', async () => {
    vi.mocked(userRoles).mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'role-1',
          },
        ],
      },
      error: undefined,
      request: {} as Request,
      response: {} as Response,
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useUserRoles(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect((result.current.error as Error).message).toBe('Invalid user role at index 0: missing name');
  });

  it('invalidates users query after invite mutation', async () => {
    vi.mocked(userInvite).mockResolvedValueOnce({
      data: {
        data: {
          id: 'user-2',
          name: 'New User',
          email: 'new@atomy.test',
          status: 'pending',
          role: 'user',
          created_at: null,
          last_login_at: null,
        },
      },
      error: undefined,
      request: {} as Request,
      response: {} as Response,
    });

    const { queryClient, Wrapper } = createTestWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useInviteUser(), { wrapper: Wrapper });

    const response = await result.current.mutateAsync({
      email: 'new@atomy.test',
      name: 'New User',
      role: 'user',
    });

    expect(response).toEqual({
      id: 'user-2',
      name: 'New User',
      email: 'new@atomy.test',
      status: 'pending',
      role: 'user',
      createdAt: null,
      lastLoginAt: null,
    });
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['settings-users'] });
  });

  it('does not invalidate users query after invite mutation failure', async () => {
    vi.mocked(userInvite).mockRejectedValueOnce(new Error('Invite failed'));

    const { queryClient, Wrapper } = createTestWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useInviteUser(), { wrapper: Wrapper });

    await expect(result.current.mutateAsync({
      email: 'new@atomy.test',
      name: 'New User',
      role: 'user',
    })).rejects.toThrow('Invite failed');
    expect(invalidateSpy).not.toHaveBeenCalled();
  });

  it('invalidates users query after suspend mutation', async () => {
    vi.mocked(userSuspend).mockResolvedValueOnce({
      data: {
        data: {
          id: 'user-3',
          name: null,
          email: 'suspended@atomy.test',
          status: 'suspended',
          role: 'user',
          created_at: null,
          last_login_at: null,
        },
      },
      error: undefined,
      request: {} as Request,
      response: {} as Response,
    });

    const { queryClient, Wrapper } = createTestWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useSuspendUser(), { wrapper: Wrapper });

    const response = await result.current.mutateAsync('user-3');

    expect(response.status).toBe('suspended');
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['settings-users'] });
  });

  it('does not invalidate users query after suspend mutation failure', async () => {
    vi.mocked(userSuspend).mockRejectedValueOnce(new Error('Suspend failed'));

    const { queryClient, Wrapper } = createTestWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useSuspendUser(), { wrapper: Wrapper });

    await expect(result.current.mutateAsync('user-3')).rejects.toThrow('Suspend failed');
    expect(invalidateSpy).not.toHaveBeenCalled();
  });

  it('invalidates users query after reactivate mutation', async () => {
    vi.mocked(userReactivate).mockResolvedValueOnce({
      data: {
        data: {
          id: 'user-4',
          name: 'Reactivated User',
          email: 'active@atomy.test',
          status: 'active',
          role: 'admin',
          created_at: null,
          last_login_at: null,
        },
      },
      error: undefined,
      request: {} as Request,
      response: {} as Response,
    });

    const { queryClient, Wrapper } = createTestWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useReactivateUser(), { wrapper: Wrapper });

    const response = await result.current.mutateAsync('user-4');

    expect(response.status).toBe('active');
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['settings-users'] });
  });
});
