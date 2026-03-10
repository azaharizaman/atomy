import React from 'react';
import { ChevronRight } from 'lucide-react';
import { CountBadge, StatusDot } from './Badge';

// ─── Nav Item (flat, no children) ─────────────────────────────────────────────

interface NavItemProps {
  label: string;
  icon?: React.ReactNode;
  active?: boolean;
  onClick?: () => void;
  badge?: number | string;
  collapsed?: boolean;     // icon-only rail mode
  className?: string;
}

export function NavItem({ label, icon, active, onClick, badge, collapsed, className = '' }: NavItemProps) {
  return (
    <button
      onClick={onClick}
      title={collapsed ? label : undefined}
      className={[
        'flex items-center gap-2.5 rounded-md transition-colors duration-100 text-left focus:outline-none focus:ring-2 focus:ring-indigo-500/50 w-full',
        collapsed ? 'justify-center px-0 py-2' : 'px-2.5 py-1.5',
        active
          ? 'bg-indigo-50 text-indigo-700 font-medium border-l-2 border-indigo-500 pl-[9px]'
          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
        className,
      ].join(' ')}
    >
      {icon && (
        <span className={['shrink-0', active ? 'text-indigo-600' : 'text-slate-400'].join(' ')}>
          {icon}
        </span>
      )}
      {!collapsed && (
        <>
          <span className="flex-1 text-sm truncate">{label}</span>
          {badge !== undefined && (
            <CountBadge count={badge} variant={active ? 'indigo' : 'default'} />
          )}
        </>
      )}
      {collapsed && badge !== undefined && (
        <CountBadge count={badge} variant={active ? 'indigo' : 'default'} className="absolute top-0 right-0 translate-x-1 -translate-y-1" />
      )}
    </button>
  );
}

// ─── Nav Group (expandable accordion section) ─────────────────────────────────

interface NavGroupProps {
  label: string;
  icon?: React.ReactNode;
  children: React.ReactNode;
  defaultOpen?: boolean;
  active?: boolean;     // true if any child is active
  collapsed?: boolean;
  badge?: number;
}

export function NavGroup({ label, icon, children, defaultOpen, active, collapsed, badge }: NavGroupProps) {
  const [open, setOpen] = React.useState(defaultOpen ?? false);

  // If an accordion — expand when a child becomes active
  React.useEffect(() => {
    if (active) setOpen(true);
  }, [active]);

  if (collapsed) {
    return (
      <div className="relative flex justify-center">
        <button
          title={label}
          className={[
            'w-9 h-9 flex items-center justify-center rounded-md transition-colors',
            active ? 'text-indigo-600 bg-indigo-50' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-100',
          ].join(' ')}
        >
          {icon}
        </button>
        {badge !== undefined && (
          <CountBadge count={badge} variant="indigo" className="absolute top-0 right-0 translate-x-1 -translate-y-1" />
        )}
      </div>
    );
  }

  return (
    <div>
      <button
        onClick={() => setOpen(o => !o)}
        className={[
          'flex items-center gap-2.5 px-2.5 py-1.5 rounded-md w-full text-left transition-colors duration-100 focus:outline-none',
          active ? 'text-slate-900 font-medium' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
        ].join(' ')}
      >
        {icon && (
          <span className={['shrink-0', active ? 'text-indigo-600' : 'text-slate-400'].join(' ')}>
            {icon}
          </span>
        )}
        <span className="flex-1 text-sm truncate">{label}</span>
        {badge !== undefined && <CountBadge count={badge} variant={active ? 'indigo' : 'default'} />}
        <ChevronRight
          size={13}
          className={['text-slate-400 transition-transform duration-200 shrink-0', open ? 'rotate-90' : ''].join(' ')}
        />
      </button>
      {open && (
        <div className="ml-4 pl-2 border-l border-slate-200 mt-0.5 mb-1 flex flex-col gap-0.5">
          {children}
        </div>
      )}
    </div>
  );
}

// ─── Sub Nav Item (child of NavGroup) ─────────────────────────────────────────

interface SubNavItemProps {
  label: string;
  active?: boolean;
  onClick?: () => void;
  badge?: number | string;
}

export function SubNavItem({ label, active, onClick, badge }: SubNavItemProps) {
  return (
    <button
      onClick={onClick}
      className={[
        'flex items-center justify-between gap-2 px-2 py-1 rounded text-sm w-full text-left transition-colors',
        active
          ? 'text-indigo-700 font-medium bg-indigo-50'
          : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50',
      ].join(' ')}
    >
      <span className="truncate">{label}</span>
      {badge !== undefined && <CountBadge count={badge} variant={active ? 'indigo' : 'default'} />}
    </button>
  );
}

// ─── Sidebar Nav Label (group divider) ───────────────────────────────────────

export function NavLabel({ label, collapsed }: { label: string; collapsed?: boolean }) {
  if (collapsed) return <div className="mx-auto w-6 h-px bg-slate-200 my-2" />;
  return (
    <span className="block px-2.5 pt-3 pb-1 text-[10px] font-semibold tracking-widest uppercase text-slate-400">
      {label}
    </span>
  );
}

// ─── Navigation Link (Active Record Menu) ─────────────────────────────────────

interface NavigationLinkProps {
  label: string;
  icon?: React.ReactNode;
  active?: boolean;
  onClick?: () => void;
  badge?: number | string;
  statusDot?: 'green' | 'amber' | 'red' | 'slate';
  className?: string;
}

export function NavigationLink({ label, icon, active, onClick, badge, statusDot, className = '' }: NavigationLinkProps) {
  return (
    <button
      onClick={onClick}
      className={[
        'flex items-center gap-2.5 w-full text-left px-3 py-1.5 rounded-md transition-colors duration-100',
        'focus:outline-none focus:ring-2 focus:ring-indigo-500/50',
        active
          ? 'bg-indigo-50 text-indigo-700 font-medium border-l-2 border-indigo-500 pl-[10px]'
          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
        className,
      ].join(' ')}
    >
      {icon && (
        <span className={['shrink-0', active ? 'text-indigo-500' : 'text-slate-400'].join(' ')}>
          {icon}
        </span>
      )}
      <span className="flex-1 text-sm truncate">{label}</span>
      {statusDot && <StatusDot color={statusDot} />}
      {badge !== undefined && (
        <CountBadge count={badge} variant={active ? 'indigo' : 'default'} />
      )}
    </button>
  );
}
