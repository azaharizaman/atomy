import React from 'react';
import { ChevronRight, Home } from 'lucide-react';

export interface BreadcrumbItem {
  label: string;
  href?: string;
  onClick?: () => void;
}

interface BreadcrumbBarProps {
  items: BreadcrumbItem[];
  showHome?: boolean;
  className?: string;
}

export function BreadcrumbBar({ items, showHome = false, className = '' }: BreadcrumbBarProps) {
  return (
    <nav
      aria-label="Breadcrumb"
      className={['flex items-center gap-1 text-xs', className].join(' ')}
    >
      {showHome && (
        <>
          <span className="text-slate-400 hover:text-slate-600 cursor-pointer p-0.5 rounded transition-colors">
            <Home size={12} />
          </span>
          {items.length > 0 && <ChevronRight size={12} className="text-slate-300 shrink-0" />}
        </>
      )}

      {items.map((item, i) => {
        const isLast = i === items.length - 1;
        const isClickable = !isLast && (item.href || item.onClick);

        return (
          <React.Fragment key={i}>
            {isClickable ? (
              <a
                href={item.href}
                onClick={e => {
                  if (item.onClick) { e.preventDefault(); item.onClick(); }
                }}
                className="text-slate-500 hover:text-indigo-600 hover:underline underline-offset-2 transition-colors duration-100 max-w-[160px] truncate"
              >
                {item.label}
              </a>
            ) : (
              <span
                className={[
                  'truncate max-w-[200px]',
                  isLast ? 'text-slate-800 font-medium' : 'text-slate-500',
                ].join(' ')}
              >
                {item.label}
              </span>
            )}
            {!isLast && (
              <ChevronRight size={12} className="text-slate-300 shrink-0" />
            )}
          </React.Fragment>
        );
      })}
    </nav>
  );
}

// ─── Breadcrumb Bar with background (for Work Surface top bar) ────────────────

interface WorkSurfaceBreadcrumbProps {
  items: BreadcrumbItem[];
  className?: string;
}

export function WorkSurfaceBreadcrumb({ items, className = '' }: WorkSurfaceBreadcrumbProps) {
  return (
    <div className={['px-5 py-2.5 border-b border-slate-200 bg-white', className].join(' ')}>
      <BreadcrumbBar items={items} showHome />
    </div>
  );
}
