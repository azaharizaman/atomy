import * as React from 'react';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

export interface NavItemAlt {
  label: string;
  icon: string;
  href: string;
  badge?: string | null;
}

export interface NavSectionAlt {
  section: string;
  items: NavItemAlt[];
}

export interface AtomyQSidebarAltProps {
  sections: NavSectionAlt[];
  collapsed?: boolean;
  onToggleCollapse?: () => void;
  activeHref?: string;
  onNavigate?: (href: string) => void;
  brandName?: string;
  brandLogo?: React.ReactNode;
  footer?: React.ReactNode;
  className?: string;
}

function AtomyQSidebarAlt({
  sections,
  collapsed = false,
  onToggleCollapse,
  activeHref = '/',
  onNavigate,
  brandName = 'AtomyQ',
  brandLogo,
  footer,
  className,
}: AtomyQSidebarAltProps) {
  const getIcon = (name: string) => {
    const Icon = (
      LucideIcons as unknown as Record<string, React.ComponentType<{ className?: string }>>
    )[name];
    return Icon ? <Icon className="size-[15px] shrink-0" /> : null;
  };

  return (
    <aside
      className={cn(
        'flex h-full flex-col border-r border-[var(--aq-nav-border)] bg-[var(--aq-nav-bg)] text-[var(--aq-nav-text)] transition-all duration-200',
        collapsed ? 'w-14' : 'w-[220px]',
        className,
      )}
    >
      <div
        className={cn(
          'flex h-14 shrink-0 items-center border-b border-[var(--aq-nav-border)]',
          collapsed ? 'justify-center px-2' : 'gap-2.5 px-4',
        )}
      >
        {brandLogo ?? (
          <div
            className="flex size-[30px] shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white"
            style={{
              background:
                'linear-gradient(135deg, var(--aq-brand-500), var(--aq-brand-700))',
            }}
          >
            {brandName.slice(0, 2).toUpperCase()}
          </div>
        )}
        {!collapsed && (
          <span className="text-sm font-semibold text-[var(--aq-text-inverse)]">
            {brandName}
          </span>
        )}
      </div>

      <nav className="flex-1 overflow-y-auto px-2 py-3">
        {sections.map((section) => (
          <div key={section.section} className="mb-4">
            {!collapsed && (
              <div className="mb-1.5 px-2.5 text-[9px] font-semibold uppercase tracking-[0.1em] text-[var(--aq-nav-text-muted)]">
                {section.section}
              </div>
            )}
            <ul className="space-y-0.5">
              {section.items.map((item) => {
                const isActive = activeHref === item.href;
                return (
                  <li key={item.href}>
                    <button
                      onClick={() => onNavigate?.(item.href)}
                      className={cn(
                        'flex w-full items-center rounded-md text-[13px] transition-colors',
                        collapsed
                          ? 'justify-center p-2'
                          : 'gap-2.5 px-2.5 py-[7px]',
                        isActive
                          ? 'border-l-2 border-[var(--aq-brand-500)] bg-[var(--aq-nav-active-bg)] text-[var(--aq-brand-400)]'
                          : 'border-l-2 border-transparent text-[var(--aq-nav-text)] hover:bg-[var(--aq-nav-hover)] hover:text-[var(--aq-text-inverse)]',
                      )}
                      aria-current={isActive ? 'page' : undefined}
                      title={collapsed ? item.label : undefined}
                    >
                      {getIcon(item.icon)}
                      {!collapsed && (
                        <>
                          <span className="flex-1 text-left">{item.label}</span>
                          {item.badge && (
                            <span className="min-w-[18px] rounded-[10px] bg-[var(--aq-brand-500)] px-[5px] py-[1px] text-center text-[10px] font-semibold text-white">
                              {item.badge}
                            </span>
                          )}
                        </>
                      )}
                    </button>
                  </li>
                );
              })}
            </ul>
          </div>
        ))}
      </nav>

      <div className="shrink-0 border-t border-[var(--aq-nav-border)]">
        {footer && !collapsed && <div className="px-3 py-2">{footer}</div>}
        <div className="p-2">
          <button
            onClick={onToggleCollapse}
            className="flex w-full items-center justify-center rounded-md p-2 text-[var(--aq-nav-text)] transition-colors hover:bg-[var(--aq-nav-hover)]"
            aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          >
            {collapsed ? (
              <ChevronRight className="size-4" />
            ) : (
              <ChevronLeft className="size-4" />
            )}
          </button>
        </div>
      </div>
    </aside>
  );
}

export { AtomyQSidebarAlt };
