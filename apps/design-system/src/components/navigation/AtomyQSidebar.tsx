import * as React from 'react';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

export interface NavItem {
  label: string;
  icon: string;
  href: string;
  badge?: string | null;
  active?: boolean;
}

export interface NavSection {
  section: string;
  items: NavItem[];
}

export interface AtomyQSidebarProps {
  sections: NavSection[];
  collapsed?: boolean;
  onToggleCollapse?: () => void;
  activeHref?: string;
  onNavigate?: (href: string) => void;
  className?: string;
}

function AtomyQSidebar({
  sections,
  collapsed = false,
  onToggleCollapse,
  activeHref = '/',
  onNavigate,
  className,
}: AtomyQSidebarProps) {
  const getIcon = (name: string) => {
    const Icon = (LucideIcons as Record<string, React.ComponentType<{ className?: string }>>)[name];
    return Icon ? <Icon className="size-4 shrink-0" /> : null;
  };

  return (
    <aside
      className={cn(
        'flex flex-col h-full bg-[var(--aq-nav-bg)] text-[var(--aq-nav-text)] transition-all duration-200',
        collapsed ? 'w-16' : 'w-60',
        className
      )}
    >
      {/* Logo */}
      <div className={cn(
        'flex items-center h-14 border-b border-[var(--aq-nav-border)]',
        collapsed ? 'justify-center px-2' : 'px-4 gap-2'
      )}>
        <div className="size-8 rounded-lg bg-[var(--aq-brand-600)] flex items-center justify-center text-white font-bold text-sm shrink-0">
          AQ
        </div>
        {!collapsed && (
          <span className="font-semibold text-[var(--aq-nav-text-active)] text-sm">AtomyQ</span>
        )}
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto py-3 px-2">
        {sections.map((section) => (
          <div key={section.section} className="mb-4">
            {!collapsed && (
              <div className="px-3 mb-1 text-[10px] font-semibold uppercase tracking-wider text-[var(--aq-nav-text)]/50">
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
                        'flex items-center w-full rounded-md text-sm transition-colors',
                        collapsed ? 'justify-center p-2' : 'gap-3 px-3 py-1.5',
                        isActive
                          ? 'bg-[var(--aq-nav-active)] text-[var(--aq-nav-text-active)]'
                          : 'text-[var(--aq-nav-text)] hover:bg-[var(--aq-nav-hover)] hover:text-[var(--aq-nav-text-active)]'
                      )}
                      aria-current={isActive ? 'page' : undefined}
                      title={collapsed ? item.label : undefined}
                    >
                      {getIcon(item.icon)}
                      {!collapsed && (
                        <>
                          <span className="flex-1 text-left">{item.label}</span>
                          {item.badge && (
                            <span className="rounded-full bg-[var(--aq-brand-600)] text-white text-[10px] font-medium px-1.5 py-0.5 min-w-[18px] text-center">
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

      {/* Collapse toggle */}
      <div className="border-t border-[var(--aq-nav-border)] p-2">
        <button
          onClick={onToggleCollapse}
          className="flex items-center justify-center w-full rounded-md p-2 text-[var(--aq-nav-text)] hover:bg-[var(--aq-nav-hover)] transition-colors"
          aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        >
          {collapsed ? <ChevronRight className="size-4" /> : <ChevronLeft className="size-4" />}
        </button>
      </div>
    </aside>
  );
}

export { AtomyQSidebar };
