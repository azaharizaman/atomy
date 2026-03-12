import React from 'react';
import { Bell, Bot, Plus } from 'lucide-react';
import { usePathname, useRouter } from 'next/navigation';
import { useAuthStore } from '@/store/use-auth-store';
import { Button } from '@/components/ds/Button';
import { CountBadge } from '@/components/ds/Badge';
import { SearchInput } from '@/components/ds/Input';

export function Header() {
  const pathname = usePathname();
  const router = useRouter();
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);

  const displayName = user?.name || user?.email || 'User';
  const initials = displayName
    .split(' ')
    .filter(Boolean)
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  const breadcrumb = pathname === '/' ? 'Dashboard' : pathname;

  return (
    <header className="h-14 border-b border-slate-200 bg-white flex items-center justify-between px-4 sticky top-0 z-10">
      <div className="flex items-center gap-4 min-w-0">
        <span className="text-sm text-slate-500 font-medium truncate">Atomy-Q / {breadcrumb}</span>
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
