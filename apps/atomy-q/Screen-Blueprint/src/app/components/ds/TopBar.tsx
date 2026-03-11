import React from 'react';
import { Bell, ChevronDown, Search, Sparkles, Plus } from 'lucide-react';
import { CountBadge } from './Badge';
import { SearchInput } from './Input';
import { Button } from './Button';
import { UserMenuDropdown } from './UserMenuDropdown';
import { NotificationCenter, type NotificationItem } from './NotificationCenter';

// ─── Top Bar ──────────────────────────────────────────────────────────────────

interface TopBarProps {
  user?: { name: string; role?: string; avatarSrc?: string };
  notificationCount?: number;
  notifications?: NotificationItem[];
  onNewRFQ?: () => void;
  onAIInsights?: () => void;
  onSearch?: (q: string) => void;
  onSearchFocus?: () => void;
  onNotificationClick?: (id: string) => void;
  onMarkAllNotificationsRead?: () => void;
  onUserSettings?: () => void;
  onOpenNotifications?: () => void;
  onLogout?: () => void;
  searchValue?: string;
  className?: string;
}

const DEFAULT_NOTIFICATIONS: NotificationItem[] = [
  {
    id: 'n-1',
    title: 'Approval APR-00412 requires your review',
    description: 'SLA 1d 18h remaining.',
    timestamp: '2m ago',
    unread: true,
  },
  {
    id: 'n-2',
    title: 'Comparison Run #005 auto-approved',
    description: 'Proceed to Award tab.',
    timestamp: '1h ago',
    unread: true,
  },
  {
    id: 'n-3',
    title: 'Vendor uploaded updated quote',
    description: 'Dell Technologies · RFQ-2401',
    timestamp: '3h ago',
    unread: false,
  },
];

export function TopBar({
  user = { name: 'Alex Kumar', role: 'Procurement Manager' },
  notificationCount = 3,
  notifications = DEFAULT_NOTIFICATIONS,
  onNewRFQ,
  onAIInsights,
  onSearch,
  onSearchFocus,
  onNotificationClick,
  onMarkAllNotificationsRead,
  onUserSettings,
  onOpenNotifications,
  onLogout,
  searchValue = '',
  className = '',
}: TopBarProps) {
  const [notificationOpen, setNotificationOpen] = React.useState(false);

  return (
    <>
      <header
        className={[
          'h-14 flex items-center justify-between px-5 gap-4',
          'bg-white border-b border-slate-200',
          'shadow-[0_1px_3px_0_rgba(0,0,0,0.04)]',
          className,
        ].join(' ')}
      >
        {/* Left: Search (opens spotlight on focus) */}
        <div className="flex-1 max-w-sm">
          <SearchInput
            placeholder="Search RFQs, vendors, documents…"
            shortcut="/"
            value={searchValue}
            onChange={e => onSearch?.(e.target.value)}
            onFocus={onSearchFocus}
          />
        </div>

        {/* Right: Actions */}
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            icon={<Sparkles size={13} />}
            onClick={onAIInsights}
          >
            AI Insights
          </Button>

          <Button
            variant="primary"
            size="sm"
            icon={<Plus size={13} />}
            onClick={onNewRFQ}
          >
            New RFQ
          </Button>

          <div className="w-px h-5 bg-slate-200" />

          <div className="relative">
            <button
              className="relative p-1.5 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
              aria-label={`Notifications (${notificationCount} unread)`}
              onClick={() => {
                setNotificationOpen(true);
                onOpenNotifications?.();
              }}
            >
              <Bell size={16} />
            </button>
            {notificationCount > 0 && (
              <CountBadge
                count={notificationCount > 9 ? '9+' : notificationCount}
                variant="red"
                className="absolute -top-0.5 -right-0.5"
              />
            )}
          </div>

          <UserMenuDropdown
            user={user}
            onUserSettings={onUserSettings}
            onNotifications={() => {
              setNotificationOpen(true);
              onOpenNotifications?.();
            }}
            onLogout={onLogout}
          />
        </div>
      </header>

      <NotificationCenter
        open={notificationOpen}
        onClose={() => setNotificationOpen(false)}
        items={notifications}
        onItemClick={onNotificationClick}
        onMarkAllRead={onMarkAllNotificationsRead}
      />
    </>
  );
}

// ─── Compact Top Bar (for showcase preview within a layout) ───────────────────

export function TopBarPreview({ className = '' }: { className?: string }) {
  return (
    <div className={['h-14 flex items-center justify-between px-5 gap-4 bg-white border-b border-slate-200', className].join(' ')}>
      <div className="flex-1 max-w-xs">
        <div className="flex items-center h-8 rounded-md border border-slate-200 bg-slate-50 px-3 gap-2">
          <Search size={13} className="text-slate-400" />
          <span className="text-sm text-slate-400 flex-1">Search RFQs…</span>
          <kbd className="text-[10px] font-mono text-slate-400 bg-white border border-slate-200 px-1 rounded">/</kbd>
        </div>
      </div>
      <div className="flex items-center gap-2">
        <div className="flex items-center gap-1.5 px-2.5 h-7 rounded border border-transparent text-slate-500 hover:bg-slate-100 text-xs">
          <Sparkles size={12} /> AI Insights
        </div>
        <div className="flex items-center gap-1.5 px-2.5 h-7 rounded bg-indigo-600 text-white text-xs">
          <Plus size={12} /> New RFQ
        </div>
        <div className="w-px h-5 bg-slate-200" />
        <div className="relative p-1.5 rounded text-slate-400">
          <Bell size={15} />
          <span className="absolute top-0 right-0 w-3.5 h-3.5 bg-red-500 rounded-full text-[9px] text-white flex items-center justify-center font-bold">3</span>
        </div>
        <div className="flex items-center gap-1.5 px-1">
          <div className="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center text-[10px] font-semibold text-white">AK</div>
          <ChevronDown size={11} className="text-slate-400" />
        </div>
      </div>
    </div>
  );
}
