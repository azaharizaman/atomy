'use client';

import React, { Suspense, useEffect } from 'react';
import { usePathname, useSearchParams, useRouter } from 'next/navigation';
import {
  LayoutPanelTop,
  FileText,
  FolderKanban,
  ListTodo,
  FolderArchive,
  BarChart2,
  ShieldCheck,
  Settings,
} from 'lucide-react';
import { NavGroup, NavItem, SubNavItem } from '@/components/layout/sidebar';
import { Header } from '@/components/layout/header';
import { AppFooter } from '@/components/layout/app-footer';
import { useAuthStore } from '@/store/use-auth-store';
import { RFQ_STATUSES } from '@/hooks/use-rfqs';
import { useRfqNavCounts } from '@/hooks/use-rfq-counts';

/** True when on an RFQ workspace route (e.g. /rfqs/[rfqId]/overview). Use Workspace layout only (Rail + Active Record Menu + Work surface). */
function isRfqWorkspacePath(pathname: string): boolean {
  if (!pathname.startsWith('/rfqs/') || pathname === '/rfqs' || pathname === '/rfqs/') return false;
  if (pathname.startsWith('/rfqs/new')) return false;
  const segments = pathname.split('/').filter(Boolean);
  return segments.length >= 2 && segments[0] === 'rfqs';
}

function DashboardLayoutContent({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const router = useRouter();
  const currentStatus = searchParams.get('status');
  const user = useAuthStore((state) => state.user);
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const isLoading = useAuthStore((state) => state.isLoading);
  const { data: rfqCounts } = useRfqNavCounts();

  // Deny access when not authenticated (after auth init). Redirect to login.
  useEffect(() => {
    if (isLoading) return;
    if (!isAuthenticated) {
      router.replace(`/login?redirect=${encodeURIComponent(pathname)}`);
    }
  }, [isLoading, isAuthenticated, router, pathname]);

  // While auth is loading or user is not authenticated, show minimal loading then redirect (handled in useEffect).
  if (isLoading || !isAuthenticated) {
    return (
      <div className="flex h-screen items-center justify-center bg-slate-50">
        <div className="text-sm text-slate-500">{isLoading ? 'Loading…' : 'Redirecting to login…'}</div>
      </div>
    );
  }

  // RFQ workspace (e.g. /rfqs/01KK.../overview) uses Workspace layout only: Rail + Header + Active Record Menu + content.
  // Do not wrap with Default layout (sidebar + main) so the rfqs/[rfqId] layout owns the full view.
  if (isRfqWorkspacePath(pathname)) {
    return <>{children}</>;
  }

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

          <NavItem
            label="Projects"
            icon={<FolderKanban size={18} />}
            active={pathname.startsWith('/projects')}
            href="/projects"
          />

          <NavItem
            label="Task Inbox"
            icon={<ListTodo size={18} />}
            active={pathname.startsWith('/tasks')}
            href="/tasks"
          />

          <NavGroup
            label="Requisition"
            icon={<FileText size={18} />}
            active={pathname.startsWith('/rfqs')}
            defaultOpen={pathname.startsWith('/rfqs')}
          >
            <SubNavItem
              label="Active"
              active={pathname === '/rfqs' && (!currentStatus || currentStatus === RFQ_STATUSES.ACTIVE)}
              href="/rfqs"
              badge={rfqCounts && rfqCounts.active > 0 ? rfqCounts.active : undefined}
            />
            <SubNavItem
              label="Pending"
              active={pathname === '/rfqs' && currentStatus === RFQ_STATUSES.PENDING}
              href="/rfqs?status=pending"
              badge={rfqCounts && rfqCounts.pending > 0 ? rfqCounts.pending : undefined}
            />
            <SubNavItem
              label="Closed"
              active={pathname === '/rfqs' && currentStatus === RFQ_STATUSES.CLOSED}
              href={`/rfqs?status=${RFQ_STATUSES.CLOSED}`}
              badge={rfqCounts && rfqCounts.closed > 0 ? rfqCounts.closed : undefined}
            />
            <SubNavItem
              label="Awarded"
              active={pathname === '/rfqs' && currentStatus === RFQ_STATUSES.AWARDED}
              href={`/rfqs?status=${RFQ_STATUSES.AWARDED}`}
              badge={rfqCounts && rfqCounts.awarded > 0 ? rfqCounts.awarded : undefined}
            />
            <SubNavItem
              label="Archived"
              active={pathname === '/rfqs' && currentStatus === RFQ_STATUSES.ARCHIVED}
              href={`/rfqs?status=${RFQ_STATUSES.ARCHIVED}`}
              badge={rfqCounts && rfqCounts.archived > 0 ? rfqCounts.archived : undefined}
            />
            <SubNavItem
              label="Draft"
              active={pathname === '/rfqs' && currentStatus === RFQ_STATUSES.DRAFT}
              href={`/rfqs?status=${RFQ_STATUSES.DRAFT}`}
              badge={rfqCounts && rfqCounts.draft > 0 ? rfqCounts.draft : undefined}
            />
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

          <NavItem
            label="Approval Queue"
            icon={<ShieldCheck size={18} />}
            active={pathname.startsWith('/approvals')}
            href="/approvals"
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
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <Header />
        <main className="flex-1 overflow-y-auto overflow-x-hidden p-6 scroll-smooth">
          <div className="max-w-7xl mx-auto space-y-6 min-w-0">
            {children}
          </div>
        </main>
        <AppFooter />
      </div>
    </div>
  );
}

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <Suspense
      fallback={
        <div className="flex h-screen items-center justify-center bg-slate-50">
          <div className="text-sm text-slate-500">Loading…</div>
        </div>
      }
    >
      <DashboardLayoutContent>{children}</DashboardLayoutContent>
    </Suspense>
  );
}
