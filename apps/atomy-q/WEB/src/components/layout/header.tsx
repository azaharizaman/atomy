import React from 'react';
import Link from 'next/link';
import { Bell, Bot, ChevronRight, Plus } from 'lucide-react';
import { usePathname, useRouter } from 'next/navigation';
import { useAuthStore } from '@/store/use-auth-store';
import { Button } from '@/components/ds/Button';
import { CountBadge } from '@/components/ds/Badge';
import { SearchInput } from '@/components/ds/Input';
import { useRfq } from '@/hooks/use-rfq';
import { buildHeaderBreadcrumbs } from '@/lib/header-breadcrumbs';

function getWorkspaceRfqId(pathname: string): string | null {
  if (!pathname.startsWith('/rfqs/')) return null;
  const segments = pathname.split('/').filter(Boolean);
  return segments[0] === 'rfqs' && segments[1] && segments[1] !== 'new' ? segments[1] : null;
}

export function Header() {
  const pathname = usePathname();
  const router = useRouter();
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);
  const rfqId = getWorkspaceRfqId(pathname);
  const { data: rfq } = useRfq(rfqId ?? '', { enabled: rfqId !== null });

  const displayName = user?.name || user?.email || 'User';
  const initials = displayName
    .split(' ')
    .filter(Boolean)
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
  const breadcrumbItems = buildHeaderBreadcrumbs({
    pathname,
    rfqTitle: rfq?.title,
  });

  return (
    <header className="h-14 border-b border-slate-200 bg-white flex items-center justify-between px-4 sticky top-0 z-10">
      <div className="flex items-center gap-4 min-w-0">
        <nav aria-label="Breadcrumb" className="flex min-w-0 items-center gap-1 text-sm">
          {breadcrumbItems.map((item, index) => {
            const isLast = index === breadcrumbItems.length - 1;
            const isClickable = !isLast && item.href;

            return (
              <React.Fragment key={`${item.label}-${index}`}>
                {isClickable ? (
                  <Link href={item.href!} className="max-w-[180px] truncate text-slate-500 hover:text-indigo-600">
                    {item.label}
                  </Link>
                ) : (
                  <span
                    className={[
                      'truncate',
                      isLast ? 'max-w-[220px] font-medium text-slate-900' : 'max-w-[180px] text-slate-500',
                    ].join(' ')}
                    aria-current={isLast ? 'page' : undefined}
                  >
                    {item.label}
                  </span>
                )}
                {!isLast && <ChevronRight size={12} className="shrink-0 text-slate-300" />}
              </React.Fragment>
            );
          })}
        </nav>
      </div>

      <div className="flex items-center gap-3">
        <SearchInput placeholder="Search…" shortcut="/" containerClassName="w-72" />

        <Button
          size="sm"
          variant="primary"
          icon={<Plus size={14} />}
          onClick={() => router.push('/rfqs/new')}
        >
          New RFQ
        </Button>
        <Button size="sm" variant="ghost" icon={<Bot size={14} />}>
          AI Insights
        </Button>

        <button className="relative text-slate-500 hover:text-slate-700" aria-label="Notifications" type="button">
          <Bell size={18} />
          <CountBadge count={1} variant="red" className="absolute -top-1 -right-1" />
        </button>

        <details className="relative">
          <summary className="list-none cursor-pointer">
            <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-medium text-xs">
              {initials}
            </div>
          </summary>
          <div className="absolute right-0 top-[110%] w-56 bg-white border border-slate-200 rounded-md shadow-lg p-2 z-20">
            <div className="px-2 py-1.5">
              <div className="text-sm font-medium text-slate-900 truncate">{displayName}</div>
              {user?.tenantId && <div className="text-xs text-slate-500 truncate">{user.tenantId}</div>}
            </div>
            <div className="h-px bg-slate-100 my-1" />
            <button
              type="button"
              className="w-full text-left px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50 rounded"
              onClick={() => router.push('/settings/account')}
            >
              Account settings
            </button>
            <button
              type="button"
              className="w-full text-left px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50 rounded"
              onClick={() => {
                logout();
                router.push('/login');
              }}
            >
              Sign out
            </button>
          </div>
        </details>
      </div>
    </header>
  );
}
