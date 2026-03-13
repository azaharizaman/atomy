'use client';

import React from 'react';
import Link from 'next/link';
import { ChevronRight } from 'lucide-react';

export interface BreadcrumbItem {
  label: string;
  href?: string;
}

/** Work surface breadcrumb bar — per Screen-Blueprint: bar + chevrons + truncate + hover indigo */
export function WorkspaceBreadcrumbs({ items, className = '' }: { items: BreadcrumbItem[]; className?: string }) {
  return (
    <div
      className={['px-5 py-2.5 border-b border-slate-200 bg-white', className].join(' ')}
    >
      <nav aria-label="Breadcrumb" className="flex items-center gap-1 text-xs">
        {items.map((item, i) => {
          const isLast = i === items.length - 1;
          const isClickable = !isLast && item.href;

          return (
            <React.Fragment key={`${item.label}-${i}`}>
              {isClickable ? (
                <Link
                  href={item.href!}
                  className="text-slate-500 hover:text-indigo-600 hover:underline underline-offset-2 transition-colors duration-100 max-w-[160px] truncate"
                >
                  {item.label}
                </Link>
              ) : (
                <span
                  className={['truncate max-w-[200px]', isLast ? 'text-slate-800 font-medium' : 'text-slate-500'].join(
                    ' '
                  )}
                  aria-current={isLast ? 'page' : undefined}
                >
                  {item.label}
                </span>
              )}
              {!isLast && <ChevronRight size={12} className="text-slate-300 shrink-0" />}
            </React.Fragment>
          );
        })}
      </nav>
    </div>
  );
}

