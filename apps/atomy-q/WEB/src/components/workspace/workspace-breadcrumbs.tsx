'use client';

import React from 'react';
import Link from 'next/link';
import { ChevronRight } from 'lucide-react';

export interface BreadcrumbItem {
  label: string;
  href?: string;
}

export function WorkspaceBreadcrumbs({ items }: { items: BreadcrumbItem[] }) {
  return (
    <div className="flex items-center gap-1 text-xs text-slate-500">
      {items.map((item, idx) => (
        <React.Fragment key={`${item.label}-${idx}`}>
          {idx > 0 && <ChevronRight size={14} className="text-slate-300" />}
          {item.href ? (
            <Link href={item.href} className="hover:text-slate-700">
              {item.label}
            </Link>
          ) : (
            <span className="text-slate-600">{item.label}</span>
          )}
        </React.Fragment>
      ))}
    </div>
  );
}

