'use client';

import React from 'react';
import { AlertTriangle, RotateCcw, UserPlus, Users, UserX } from 'lucide-react';
import { Button } from '@/components/ds/Button';
import { EmptyState, SectionCard } from '@/components/ds/Card';
import { PageHeader } from '@/components/ds/FilterBar';
import {
  useInviteUser,
  useReactivateUser,
  useSuspendUser,
  useUserRoles,
  useUsers,
} from '@/hooks/use-users';

function formatStatusLabel(status: string): string {
  return status
    .replaceAll('_', ' ')
    .trim()
    .replace(/\s+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

function getStatusClasses(status: string): string {
  const normalized = status.trim().toLowerCase();
  if (normalized === 'active') {
    return 'border-green-200 bg-green-50 text-green-700';
  }
  if (normalized === 'pending' || normalized === 'pending_activation') {
    return 'border-amber-200 bg-amber-50 text-amber-700';
  }
  if (normalized === 'suspended' || normalized === 'inactive') {
    return 'border-slate-200 bg-slate-100 text-slate-600';
  }

  return 'border-slate-200 bg-slate-50 text-slate-600';
}

function getRoleLabel(role: string, roles: { id: string; name: string }[]): string {
  const match = roles.find((entry) => entry.id === role || entry.name === role);
  return match?.name ?? role;
}

function getRoleMeta(role: string): string {
  return role
    .replaceAll('_', ' ')
    .trim()
    .replace(/\s+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

export default function SettingsUsersPage() {
  const usersQuery = useUsers();
  const rolesQuery = useUserRoles();
  const inviteUserMutation = useInviteUser();
  const suspendUserMutation = useSuspendUser();
  const reactivateUserMutation = useReactivateUser();

  const users = usersQuery.data?.items ?? [];
  const roles = rolesQuery.data ?? [];
  const [inviteEmail, setInviteEmail] = React.useState('');
  const [inviteRole, setInviteRole] = React.useState('');
  const [mutationError, setMutationError] = React.useState<string | null>(null);
  const [pendingUserAction, setPendingUserAction] = React.useState<{
    userId: string;
    action: 'suspend' | 'reactivate';
  } | null>(null);

  const isLoading = usersQuery.isLoading || rolesQuery.isLoading;
  const loadError = usersQuery.error ?? rolesQuery.error;

  React.useEffect(() => {
    if (inviteRole !== '' || roles.length === 0) {
      return;
    }

    setInviteRole(roles[0].id);
  }, [inviteRole, roles]);

  const roleOptions = React.useMemo(
    () =>
      roles.map((role) => ({
        value: role.id,
        label: role.name,
      })),
    [roles],
  );

  const selectedRoleLabel = inviteRole ? getRoleLabel(inviteRole, roles) : '';

  const handleInviteSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setMutationError(null);

    const email = inviteEmail.trim();
    if (!email || !inviteRole) {
      return;
    }

    try {
      await inviteUserMutation.mutateAsync({ email, role: inviteRole });
      setInviteEmail('');
    } catch (error) {
      setMutationError(error instanceof Error ? error.message : 'Unable to invite user.');
    }
  };

  const handleToggleAccess = async (userId: string, action: 'suspend' | 'reactivate') => {
    setMutationError(null);
    setPendingUserAction({ userId, action });

    try {
      if (action === 'suspend') {
        await suspendUserMutation.mutateAsync(userId);
      } else {
        await reactivateUserMutation.mutateAsync(userId);
      }
    } catch (error) {
      setMutationError(error instanceof Error ? error.message : 'Unable to update user access.');
    } finally {
      setPendingUserAction(null);
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <PageHeader title="Users & Roles" subtitle="Loading users and roles…" />
        <SectionCard title="Workspace users" subtitle="Loading live user directory">
          <div className="flex items-center justify-center py-12" aria-busy="true">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent" />
          </div>
        </SectionCard>
      </div>
    );
  }

  if (loadError) {
    const message = loadError instanceof Error ? loadError.message : 'Unable to load users and roles.';

    return (
      <div className="space-y-6">
        <PageHeader title="Users & Roles" subtitle="Live tenant user management" />
        <SectionCard title="Unable to load users & roles" subtitle="The live roster could not be loaded.">
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load the users page"
            description={message}
          />
        </SectionCard>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Users & Roles"
        subtitle="Review workspace access, invite new users, and manage pending activation without claiming delivery has happened yet."
      />

      <SectionCard
        title="Invite user"
        subtitle="Create a pending activation record. The invite becomes active when the user accepts it."
      >
        <form onSubmit={handleInviteSubmit} className="flex flex-col gap-3 lg:flex-row lg:items-end">
          <div className="min-w-0 flex-1">
            <label htmlFor="invite-user-email" className="mb-1 block text-xs font-medium text-slate-600">
              Email
            </label>
            <input
              id="invite-user-email"
              name="email"
              type="email"
              value={inviteEmail}
              onChange={(event) => setInviteEmail(event.target.value)}
              placeholder="name@company.com"
              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
              autoComplete="email"
            />
          </div>

          <div className="w-full lg:w-56">
            <label htmlFor="invite-user-role" className="mb-1 block text-xs font-medium text-slate-600">
              Role
            </label>
            <select
              id="invite-user-role"
              name="role"
              value={inviteRole}
              onChange={(event) => setInviteRole(event.target.value)}
              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
            >
              <option value="" disabled>
                Select a role
              </option>
              {roleOptions.map((role) => (
                <option key={role.value} value={role.value}>
                  {role.label}
                </option>
              ))}
            </select>
          </div>

          <Button
            type="submit"
            size="sm"
            variant="primary"
            loading={inviteUserMutation.isPending}
            disabled={!inviteEmail.trim() || !inviteRole}
          >
            <UserPlus size={14} className="mr-1.5" />
            Invite user
          </Button>
        </form>

        <p className="mt-3 text-xs text-slate-500">
          Invites create a pending activation user record. We do not claim delivery from this screen.
          {selectedRoleLabel ? ` The selected role is ${selectedRoleLabel}.` : ''}
        </p>

        {mutationError && (
          <div
            role="alert"
            className="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          >
            {mutationError}
          </div>
        )}
      </SectionCard>

      <SectionCard title="Workspace users" subtitle={`${users.length} user${users.length === 1 ? '' : 's'} in this tenant`}>
        {users.length === 0 ? (
          <EmptyState
            icon={<Users size={20} />}
            title="No users yet"
            description="Invite the first user to start building the workspace roster."
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full border-collapse">
              <thead>
                <tr className="border-b border-slate-200 bg-slate-50">
                  <th className="px-3 py-2 text-left text-xs font-medium text-slate-600">User</th>
                  <th className="px-3 py-2 text-left text-xs font-medium text-slate-600">Status</th>
                  <th className="px-3 py-2 text-left text-xs font-medium text-slate-600">Role</th>
                  <th className="px-3 py-2 text-right text-xs font-medium text-slate-600">Action</th>
                </tr>
              </thead>
              <tbody>
                {users.map((user) => {
                  const status = user.status.trim().toLowerCase();
                  const shouldSuspend = status === 'active';
                  const canReactivate = status === 'suspended' || status === 'inactive';
                  const action = shouldSuspend ? 'suspend' : 'reactivate';
                  const isActionPending = pendingUserAction?.userId === user.id && pendingUserAction.action === action;
                  const roleLabel = getRoleMeta(getRoleLabel(user.role, roles));

                  return (
                    <tr key={user.id} className="border-b border-slate-100 last:border-b-0">
                      <td className="px-3 py-3 align-top">
                        <div className="text-sm font-medium text-slate-800">{user.name ?? user.email}</div>
                        <div className="text-xs text-slate-500">{user.email}</div>
                      </td>
                      <td className="px-3 py-3 align-top">
                        <span
                          className={[
                            'inline-flex rounded-full border px-2 py-0.5 text-xs font-medium',
                            getStatusClasses(user.status),
                          ].join(' ')}
                        >
                          {formatStatusLabel(user.status)}
                        </span>
                      </td>
                      <td className="px-3 py-3 align-top">
                        <span className="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700">
                          {roleLabel}
                        </span>
                      </td>
                      <td className="px-3 py-3 align-top text-right">
                        {shouldSuspend || canReactivate ? (
                          <Button
                            size="sm"
                            variant={shouldSuspend ? 'destructive' : 'outline'}
                            loading={isActionPending}
                            onClick={() => void handleToggleAccess(user.id, action)}
                            aria-label={shouldSuspend ? `Suspend ${user.name ?? user.email}` : `Reactivate ${user.name ?? user.email}`}
                          >
                            {shouldSuspend ? (
                              <>
                                <UserX size={14} className="mr-1.5" />
                                Suspend
                              </>
                            ) : (
                              <>
                                <RotateCcw size={14} className="mr-1.5" />
                                Reactivate
                              </>
                            )}
                          </Button>
                        ) : (
                          <span className="text-xs text-slate-500">Pending activation</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </SectionCard>
    </div>
  );
}
