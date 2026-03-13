'use client';

import React from 'react';
import { Users, UserPlus } from 'lucide-react';
import { Button } from '@/components/ds/Button';
import { PageHeader } from '@/components/ds/FilterBar';

export default function SettingsUsersPage() {
  return (
    <div className="space-y-6">
      <PageHeader
        title="Users & Roles"
        subtitle="Manage workspace users, roles, and permissions."
        actions={
          <Button size="sm" variant="primary" disabled>
            <UserPlus size={14} className="mr-1.5" />
            Invite user
          </Button>
        }
      />

      <div className="rounded-lg border border-slate-200 bg-white p-8">
        <div className="flex flex-col items-center justify-center text-center">
          <div className="rounded-full bg-slate-100 p-4">
            <Users size={32} className="text-slate-500" />
          </div>
          <h2 className="mt-4 text-lg font-semibold text-slate-900">User management</h2>
          <p className="mt-2 max-w-sm text-sm text-slate-500">
            Invite users and assign roles. The user list and role management will appear here when the API is connected.
          </p>
          <Button size="sm" variant="outline" className="mt-6" disabled>
            <UserPlus size={14} className="mr-1.5" />
            Invite user
          </Button>
        </div>
      </div>
    </div>
  );
}
