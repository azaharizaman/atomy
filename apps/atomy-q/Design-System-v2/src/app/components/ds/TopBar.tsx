import React from 'react';
import { Bell, ChevronDown, Search, Sparkles, Plus } from 'lucide-react';
import { CountBadge } from './Badge';
import { Avatar } from './Avatar';
import { SearchInput } from './Input';
import { Button } from './Button';

// ─── Top Bar ──────────────────────────────────────────────────────────────────

interface TopBarProps {
  user?: { name: string; role?: string; avatarSrc?: string };
  notificationCount?: number;
  onNewRFQ?: () => void;
  onAIInsights?: () => void;
  onSearch?: (q: string) => void;
  searchValue?: string;
  className?: string;
}

export function TopBar({
  user = { name: 'Alex Kumar', role: 'Procurement Manager' },
  notificationCount = 3,
  onNewRFQ,
  onAIInsights,
  onSearch,
  searchValue = '',
  className = '',
}: TopBarProps) {
  return (
    <header
      className={[
        'h-14 flex items-center justify-between px-5 gap-4',
        'bg-white border-b border-slate-200',
        'shadow-[0_1px_3px_0_rgba(0,0,0,0.04)]',
        className,
      ].join(' ')}
    >
      {/* Left: Search */}
      <div className="flex-1 max-w-sm">
        <SearchInput
          placeholder="Search RFQs, vendors, documents…"
          shortcut="/"
          value={searchValue}
          onChange={e => onSearch?.(e.target.value)}
        />
      </div>

      {/* Right: Actions */}
      <div className="flex items-center gap-2">
        {/* AI Insights ghost button */}
        <Button
          variant="ghost"
          size="sm"
          icon={<Sparkles size={13} />}
          onClick={onAIInsights}
        >
          AI Insights
        </Button>

        {/* New RFQ primary button */}
        <Button
          variant="primary"
          size="sm"
          icon={<Plus size={13} />}
          onClick={onNewRFQ}
        >
          New RFQ
        </Button>

        {/* Divider */}
        <div className="w-px h-5 bg-slate-200" />

        {/* Notification bell */}
        <div className="relative">
          <button
            className="relative p-1.5 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
            aria-label={`Notifications (${notificationCount} unread)`}
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

        {/* User avatar + dropdown */}
        <button
          className="flex items-center gap-2 pl-1 pr-0.5 py-0.5 rounded-md hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
          aria-label="User menu"
        >
          <Avatar name={user.name} src={user.avatarSrc} size="sm" />
          <span className="text-xs font-medium text-slate-700 max-w-24 truncate hidden sm:block">
            {user.name.split(' ')[0]}
          </span>
          <ChevronDown size={12} className="text-slate-400" />
        </button>
      </div>
    </header>
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
