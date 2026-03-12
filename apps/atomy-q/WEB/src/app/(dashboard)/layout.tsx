'use client';

import React from 'react';
import { usePathname } from 'next/navigation';
import {
  LayoutPanelTop,
  FileText,
  FolderArchive,
  BarChart2,
  Settings,
} from 'lucide-react';
import { NavGroup, NavItem, NavLabel, SubNavItem } from '@/components/layout/sidebar';
import { Header } from '@/components/layout/header';
import { useAuthStore } from '@/store/use-auth-store';

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const user = useAuthStore((state) => state.user);

  const displayName = user?.name || user?.email;
  const initials = displayName
    ? displayName
        .split(' ')
        .filter(Boolean)
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
    : '';

  return (
    <div className="flex h-screen bg-slate-50 overflow-hidden font-sans">
      {/* Sidebar */}
      <aside className="w-[200px] bg-white border-r border-slate-200 flex flex-col shrink-0 z-20">
        <div className="h-14 flex items-center px-4 border-b border-slate-100">
          <div className="font-bold text-lg text-indigo-600 tracking-tight flex items-center gap-2">
            <div className="w-6 h-6 rounded bg-indigo-600" />
            Atomy-Q
          </div>
        </div>

        <nav className="flex-1 overflow-y-auto p-3 space-y-0.5 custom-scrollbar">
          <NavItem
            label="Dashboard"
            icon={<LayoutPanelTop size={18} />}
            active={pathname === '/'}
            href="/"
          />

          <NavGroup
            label="Requisition"
            icon={<FileText size={18} />}
            active={pathname.startsWith('/rfqs')}
            defaultOpen={pathname.startsWith('/rfqs')}
          >
            <SubNavItem
              label="Active"
              active={pathname === '/rfqs/active' || pathname === '/rfqs'}
              href="/rfqs"
              badge={12}
            />
            <SubNavItem
              label="Closed"
              active={pathname === '/rfqs/closed'}
              href="/rfqs/closed"
              badge={5}
            />
            <SubNavItem label="Awarded" active={pathname === '/rfqs/awarded'} href="/rfqs/awarded" badge={3} />
            <SubNavItem label="Archived" active={pathname === '/rfqs/archived'} href="/rfqs/archived" />
            <SubNavItem label="Draft" active={pathname === '/rfqs/draft'} href="/rfqs/draft" badge={2} />
          </NavGroup>

          <NavItem
            label="Documents"
            icon={<FolderArchive size={18} />}
            active={pathname.startsWith('/documents')}
            href="/documents"
          />

          <NavItem
            label="Reporting"
            icon={<BarChart2 size={18} />}
            active={pathname.startsWith('/reporting')}
            href="/reporting"
          />

          <NavGroup
            label="Settings"
            icon={<Settings size={18} />}
            active={pathname.startsWith('/settings')}
            defaultOpen={pathname.startsWith('/settings')}
          >
            <SubNavItem label="Users & Roles" active={pathname === '/settings/users'} href="/settings/users" />
            <SubNavItem label="Scoring Policies" active={pathname === '/settings/scoring-policies'} href="/settings/scoring-policies" />
            <SubNavItem label="Templates" active={pathname === '/settings/templates'} href="/settings/templates" />
            <SubNavItem label="Integrations" active={pathname === '/settings/integrations'} href="/settings/integrations" />
            <SubNavItem label="Feature Flags" active={pathname === '/settings/feature-flags'} href="/settings/feature-flags" />
          </NavGroup>
        </nav>

        {user && (
          <div className="p-4 border-t border-slate-100">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-medium text-xs">
                {initials}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-slate-900 truncate">{displayName}</p>
                <p className="text-xs text-slate-500 truncate">{user.tenantId}</p>
              </div>
            </div>
          </div>
        )}
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col min-w-0">
        <Header />
        <main className="flex-1 overflow-y-auto p-6 scroll-smooth">
          <div className="max-w-7xl mx-auto space-y-6">
            {children}
          </div>
        </main>
      </div>
    </div>
  );
}
