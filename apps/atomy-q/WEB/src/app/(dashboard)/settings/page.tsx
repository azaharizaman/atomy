'use client';

import React from 'react';
import Link from 'next/link';
import { Users, FileCheck, FileText, Plug, Flag } from 'lucide-react';
import { AlphaDeferredScreen } from '@/components/alpha/alpha-deferred-screen';
import { PageHeader } from '@/components/ds/FilterBar';
import { isSettingsNavVisible } from '@/config/nav';

const links = [
  { href: '/settings/users', label: 'Users & Roles', icon: <Users size={18} />, description: 'Manage workspace users and roles' },
  { href: '/settings/scoring-policies', label: 'Scoring Policies', icon: <FileCheck size={18} />, description: 'Configure evaluation and scoring rules' },
  { href: '/settings/templates', label: 'Templates', icon: <FileText size={18} />, description: 'RFQ and document templates' },
  { href: '/settings/integrations', label: 'Integrations', icon: <Plug size={18} />, description: 'External systems and API connections' },
  { href: '/settings/feature-flags', label: 'Feature Flags', icon: <Flag size={18} />, description: 'Enable or disable features' },
];

export default function SettingsPage() {
  if (!isSettingsNavVisible()) {
    return <AlphaDeferredScreen title="Settings" subtitle="Workspace administration is deferred in alpha." />;
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Settings"
        subtitle="Manage workspace configuration, users, and integrations."
      />

      <div className="grid gap-3 sm:grid-cols-2">
        {links.map((item) => (
          <Link
            key={item.href}
            href={item.href}
            className="flex items-start gap-4 rounded-lg border border-slate-200 bg-white p-4 transition-colors hover:border-indigo-200 hover:bg-indigo-50/50"
          >
            <span className="rounded-lg bg-slate-100 p-2 text-slate-600">{item.icon}</span>
            <div className="min-w-0 flex-1">
              <h3 className="font-medium text-slate-900">{item.label}</h3>
              <p className="mt-0.5 text-sm text-slate-500">{item.description}</p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
