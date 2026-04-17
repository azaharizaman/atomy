import React from 'react';
import { render, screen, within, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import SettingsUsersPage from './page';
import {
  useInviteUser,
  useReactivateUser,
  useSuspendUser,
  useUserRoles,
  useUsers,
} from '@/hooks/use-users';

vi.mock('@/hooks/use-users', () => ({
  useInviteUser: vi.fn(),
  useReactivateUser: vi.fn(),
  useSuspendUser: vi.fn(),
  useUserRoles: vi.fn(),
  useUsers: vi.fn(),
}));

interface QueryState {
  data?: unknown;
  isLoading?: boolean;
  isError?: boolean;
  error?: Error | null;
}

function mockQueries({
  users = {},
  roles = {},
  inviteMutateAsync = vi.fn().mockResolvedValue(undefined),
  suspendMutateAsync = vi.fn().mockResolvedValue(undefined),
  reactivateMutateAsync = vi.fn().mockResolvedValue(undefined),
}: {
  users?: QueryState;
  roles?: QueryState;
  inviteMutateAsync?: ReturnType<typeof vi.fn>;
  suspendMutateAsync?: ReturnType<typeof vi.fn>;
  reactivateMutateAsync?: ReturnType<typeof vi.fn>;
} = {}) {
  vi.mocked(useUsers).mockReturnValue({
    data: users.data as never,
    isLoading: users.isLoading ?? false,
    isError: users.isError ?? false,
    error: users.error ?? null,
  } as never);
  vi.mocked(useUserRoles).mockReturnValue({
    data: roles.data as never,
    isLoading: roles.isLoading ?? false,
    isError: roles.isError ?? false,
    error: roles.error ?? null,
  } as never);
  vi.mocked(useInviteUser).mockReturnValue({
    mutateAsync: inviteMutateAsync,
    isPending: false,
  } as never);
  vi.mocked(useSuspendUser).mockReturnValue({
    mutateAsync: suspendMutateAsync,
    isPending: false,
  } as never);
  vi.mocked(useReactivateUser).mockReturnValue({
    mutateAsync: reactivateMutateAsync,
    isPending: false,
  } as never);

  return {
    inviteMutateAsync,
    suspendMutateAsync,
    reactivateMutateAsync,
  };
}

describe('SettingsUsersPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders the loading state', () => {
    mockQueries({
      users: { isLoading: true },
      roles: { isLoading: true },
    });

    render(<SettingsUsersPage />);

    expect(screen.getByRole('heading', { name: 'Users & Roles' })).toBeInTheDocument();
    expect(screen.getByText(/loading users and roles/i)).toBeInTheDocument();
    expect(screen.getByText(/loading live user directory/i)).toBeInTheDocument();
  });

  it('renders the empty state', () => {
    mockQueries({
      users: { data: { items: [] } },
      roles: {
        data: [
          { id: 'admin', name: 'admin' },
          { id: 'user', name: 'user' },
        ],
      },
    });

    render(<SettingsUsersPage />);

    expect(screen.getByRole('heading', { name: 'Users & Roles' })).toBeInTheDocument();
    expect(screen.getByText('No users yet')).toBeInTheDocument();
    expect(screen.getByText(/invite the first user/i)).toBeInTheDocument();
    expect(screen.getByLabelText('Email')).toBeInTheDocument();
    expect(screen.getByLabelText('Role')).toBeInTheDocument();
  });

  it('renders a populated list and status actions', () => {
    mockQueries({
      users: {
        data: {
          items: [
            {
              id: 'user-1',
              name: 'Alice Admin',
              email: 'alice@example.com',
              status: 'active',
              role: 'admin',
              createdAt: null,
              lastLoginAt: null,
            },
            {
              id: 'user-2',
              name: 'Ben Suspended',
              email: 'ben@example.com',
              status: 'suspended',
              role: 'user',
              createdAt: null,
              lastLoginAt: null,
            },
            {
              id: 'user-3',
              name: 'Pat Pending',
              email: 'pat@example.com',
              status: 'pending_activation',
              role: 'user',
              createdAt: null,
              lastLoginAt: null,
            },
          ],
        },
      },
      roles: {
        data: [
          { id: 'admin', name: 'admin' },
          { id: 'user', name: 'user' },
        ],
      },
    });

    render(<SettingsUsersPage />);

    expect(screen.getByText('Alice Admin')).toBeInTheDocument();
    expect(screen.getByText('alice@example.com')).toBeInTheDocument();
    expect(screen.getByText('Active')).toBeInTheDocument();
    expect(screen.getByText('Admin')).toBeInTheDocument();

    expect(screen.getByText('Ben Suspended')).toBeInTheDocument();
    expect(screen.getByText('Ben Suspended').closest('tr')).toHaveTextContent('Reactivate');
    expect(screen.getByText('Ben Suspended').closest('tr')).toHaveTextContent('Suspended');
    expect(screen.getByText('Ben Suspended').closest('tr')).toHaveTextContent('User');

    expect(screen.getByText('Pat Pending').closest('tr')).toHaveTextContent('Pending activation');
    expect(within(screen.getByText('Pat Pending').closest('tr') as HTMLElement).queryByRole('button')).toBeNull();
  });

  it('renders the error state', () => {
    mockQueries({
      users: {
        isError: true,
        error: new Error('Users API failed'),
      },
      roles: {
        data: [{ id: 'admin', name: 'admin' }],
      },
    });

    render(<SettingsUsersPage />);

    expect(screen.getByText('Could not load the users page')).toBeInTheDocument();
    expect(screen.getByText('Users API failed')).toBeInTheDocument();
  });

  it('wires the invite action and surfaces invite errors in page', async () => {
    const user = userEvent.setup();
    const inviteMutateAsync = vi.fn().mockRejectedValueOnce(new Error('Invite failed'));

    mockQueries({
      users: { data: { items: [] } },
      roles: {
        data: [
          { id: 'admin', name: 'admin' },
          { id: 'user', name: 'user' },
        ],
      },
      inviteMutateAsync,
    });

    render(<SettingsUsersPage />);

    await user.type(screen.getByLabelText('Email'), 'invitee@example.com');
    await user.selectOptions(screen.getByLabelText('Role'), 'user');
    await user.click(screen.getByRole('button', { name: 'Invite user' }));

    await waitFor(() =>
      expect(inviteMutateAsync).toHaveBeenCalledWith({
        email: 'invitee@example.com',
        role: 'user',
      }),
    );
    expect(await screen.findByRole('alert')).toHaveTextContent('Invite failed');
  });

  it('wires the suspend action button', async () => {
    const user = userEvent.setup();
    const suspendMutateAsync = vi.fn().mockResolvedValueOnce(undefined);

    mockQueries({
      users: {
        data: {
          items: [
            {
              id: 'user-1',
              name: 'Alice Admin',
              email: 'alice@example.com',
              status: 'active',
              role: 'admin',
              createdAt: null,
              lastLoginAt: null,
            },
          ],
        },
      },
      roles: { data: [{ id: 'admin', name: 'admin' }] },
      suspendMutateAsync,
    });

    render(<SettingsUsersPage />);

    const row = screen.getByText('Alice Admin').closest('tr');
    expect(row).not.toBeNull();
    await user.click(within(row as HTMLElement).getByRole('button', { name: 'Suspend Alice Admin' }));

    expect(suspendMutateAsync).toHaveBeenCalledWith('user-1');
  });

  it('wires the reactivate action button', async () => {
    const user = userEvent.setup();
    const reactivateMutateAsync = vi.fn().mockResolvedValueOnce(undefined);

    mockQueries({
      users: {
        data: {
          items: [
            {
              id: 'user-2',
              name: 'Ben Suspended',
              email: 'ben@example.com',
              status: 'inactive',
              role: 'user',
              createdAt: null,
              lastLoginAt: null,
            },
          ],
        },
      },
      roles: { data: [{ id: 'user', name: 'user' }] },
      reactivateMutateAsync,
    });

    render(<SettingsUsersPage />);

    const row = screen.getByText('Ben Suspended').closest('tr');
    expect(row).not.toBeNull();
    await user.click(within(row as HTMLElement).getByRole('button', { name: 'Reactivate Ben Suspended' }));

    expect(reactivateMutateAsync).toHaveBeenCalledWith('user-2');
  });
});
