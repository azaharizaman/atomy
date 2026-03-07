import * as React from 'react';
import { cn } from '@/lib/utils';
import { Search, Bell, Sparkles, ChevronRight } from 'lucide-react';

export interface BreadcrumbItemAlt {
  label: string;
  href?: string;
}

export interface QuickActionAlt {
  label: string;
  onClick: () => void;
  variant?: 'primary' | 'secondary';
  icon?: React.ReactNode;
}

export interface AtomyQHeaderAltProps {
  breadcrumbs?: BreadcrumbItemAlt[];
  onBreadcrumbClick?: (href: string) => void;
  searchPlaceholder?: string;
  searchValue?: string;
  onSearchChange?: (value: string) => void;
  quickActions?: QuickActionAlt[];
  showAIButton?: boolean;
  onAIClick?: () => void;
  notificationCount?: number;
  onNotificationClick?: () => void;
  userName?: string;
  userInitials?: string;
  className?: string;
}

function AtomyQHeaderAlt({
  breadcrumbs = [],
  onBreadcrumbClick,
  searchPlaceholder = 'Search...',
  searchValue,
  onSearchChange,
  quickActions = [],
  showAIButton = false,
  onAIClick,
  notificationCount = 0,
  onNotificationClick,
  userName,
  userInitials = 'U',
  className,
}: AtomyQHeaderAltProps) {
  return (
    <header
      className={cn(
        'flex h-14 items-center gap-4 border-b border-[var(--aq-header-border)] bg-[var(--aq-header-bg)] px-4',
        className,
      )}
    >
      {breadcrumbs.length > 0 && (
        <nav className="flex items-center gap-1 text-[13px]" aria-label="Breadcrumb">
          {breadcrumbs.map((crumb, i) => (
            <React.Fragment key={i}>
              {i > 0 && (
                <ChevronRight className="size-3 text-[var(--aq-header-text-faint)]" />
              )}
              {crumb.href && i < breadcrumbs.length - 1 ? (
                <button
                  onClick={() => onBreadcrumbClick?.(crumb.href!)}
                  className="text-[var(--aq-header-text-muted)] transition-colors hover:text-[var(--aq-header-text-main)]"
                >
                  {crumb.label}
                </button>
              ) : (
                <span className="font-medium text-[var(--aq-header-text-main)]">
                  {crumb.label}
                </span>
              )}
            </React.Fragment>
          ))}
        </nav>
      )}

      <div className="flex-1" />

      {onSearchChange && (
        <div className="relative max-w-[240px]">
          <Search className="absolute left-3 top-1/2 size-3.5 -translate-y-1/2 text-[var(--aq-header-text-faint)]" />
          <input
            type="text"
            value={searchValue ?? ''}
            onChange={(e) => onSearchChange(e.target.value)}
            placeholder={searchPlaceholder}
            className="h-[34px] w-full rounded-lg border border-[var(--aq-header-input-border)] bg-[var(--aq-header-input-bg)] pl-8 pr-3 text-[13px] text-[var(--aq-header-text-main)] placeholder:text-[var(--aq-header-text-faint)] transition-colors focus:border-[var(--aq-brand-500)] focus:outline-none"
          />
        </div>
      )}

      {quickActions.map((action, i) => (
        <button
          key={i}
          onClick={action.onClick}
          className={cn(
            'flex h-8 items-center gap-1.5 rounded-lg px-3 text-[12px] font-medium transition-colors',
            action.variant === 'primary'
              ? 'bg-[var(--aq-brand-600)] text-white hover:opacity-90'
              : 'border border-[var(--aq-header-border)] bg-[var(--aq-bg-surface)] text-[var(--aq-header-text-main)] hover:bg-[var(--aq-bg-elevated)]',
          )}
        >
          {action.icon}
          {action.label}
        </button>
      ))}

      {showAIButton && (
        <button
          onClick={onAIClick}
          className="flex size-[34px] items-center justify-center rounded-lg border border-[var(--aq-purple-tint-20)] bg-[var(--aq-purple-tint-12)] text-[var(--aq-purple-500)] transition-colors hover:bg-[var(--aq-purple-tint-15)]"
          aria-label="AI Assistant"
        >
          <Sparkles className="size-4" />
        </button>
      )}

      {onNotificationClick && (
        <button
          onClick={onNotificationClick}
          className="relative flex size-[34px] items-center justify-center rounded-lg text-[var(--aq-header-text-muted)] transition-colors hover:bg-[var(--aq-hover-soft)] hover:text-[var(--aq-header-text-main)]"
          aria-label="Notifications"
        >
          <Bell className="size-4" />
          {notificationCount > 0 && (
            <span className="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-[var(--aq-danger-500)] text-[9px] font-bold text-white">
              {notificationCount > 9 ? '9+' : notificationCount}
            </span>
          )}
        </button>
      )}

      {userName && (
        <button
          className="flex size-[26px] items-center justify-center rounded-full text-[10px] font-bold text-white"
          style={{
            background:
              'linear-gradient(135deg, var(--aq-brand-500), var(--aq-purple-500))',
          }}
          aria-label={`User menu for ${userName}`}
        >
          {userInitials}
        </button>
      )}
    </header>
  );
}

export { AtomyQHeaderAlt };
