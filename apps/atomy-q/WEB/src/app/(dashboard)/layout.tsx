'use client';

import React, { Suspense, useEffect } from 'react';
import { usePathname, useSearchParams, useRouter } from 'next/navigation';
import { Header } from '@/components/layout/header';
import { AppFooter } from '@/components/layout/app-footer';
import { MainSidebarNav } from '@/components/layout/main-sidebar-nav';
import { isAlphaMode } from '@/lib/alpha-mode';
import { useAuthStore } from '@/store/use-auth-store';
import { useRfqNavCounts } from '@/hooks/use-rfq-counts';
import { useFeatureFlags } from '@/hooks/use-feature-flags';

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
  const alphaMode = isAlphaMode();
  const currentStatus = searchParams.get('status');
  const user = useAuthStore((state) => state.user);
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const isLoading = useAuthStore((state) => state.isLoading);
  const { data: rfqCounts } = useRfqNavCounts();
  const { data: featureFlags, isLoading: featureFlagsLoading } = useFeatureFlags();

  // Deny access when not authenticated (after auth init). Redirect to login.
  useEffect(() => {
    if (isLoading) return;
    if (!isAuthenticated) {
      router.replace(`/login?redirect=${encodeURIComponent(pathname)}`);
    }
  }, [isLoading, isAuthenticated, router, pathname]);

  useEffect(() => {
    if (featureFlagsLoading || featureFlags === undefined) {
      return;
    }
    if (pathname.startsWith('/projects') && !featureFlags.projects) {
      router.replace('/');
      return;
    }
    if ((pathname === '/tasks' || pathname.startsWith('/tasks/')) && !featureFlags.tasks) {
      router.replace('/');
    }
  }, [featureFlagsLoading, featureFlags, pathname, router]);

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
          <MainSidebarNav
            pathname={pathname}
            currentStatus={currentStatus}
            alphaMode={alphaMode}
            featureFlags={featureFlags}
            featureFlagsLoading={featureFlagsLoading}
            rfqCounts={rfqCounts}
          />
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
