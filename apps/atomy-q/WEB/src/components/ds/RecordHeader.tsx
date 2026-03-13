'use client';

import React from 'react';
import { StatusBadge } from './Badge';
import type { StatusVariant } from '@/lib/tokens';

export interface RecordHeaderMeta {
  label: string;
  value: React.ReactNode;
}

interface RecordHeaderProps {
  title: string;
  status?: StatusVariant;
  metadata?: RecordHeaderMeta[];
  actions?: React.ReactNode;
  leading?: React.ReactNode;
  badges?: React.ReactNode;
  className?: string;
}

export function RecordHeader({
  title,
  status,
  metadata = [],
  actions,
  leading,
  badges,
  className = '',
}: RecordHeaderProps) {
  return (
    <div className={['bg-white border border-slate-200 rounded-lg px-4 py-3', className].join(' ')}>
      <div className="flex items-start justify-between gap-4">
        <div className="min-w-0">
          {leading != null && <div className="mb-1">{leading}</div>}
          <div className="flex items-center gap-2 flex-wrap">
            <h1 className="text-base font-semibold text-slate-900 truncate">{title}</h1>
            {status != null && <StatusBadge status={status} />}
            {badges}
          </div>
        </div>
        {actions != null && <div className="flex items-center gap-2 shrink-0">{actions}</div>}
      </div>
      {metadata.length > 0 && (
        <div className="mt-3 pt-3 border-t border-slate-100 grid grid-cols-2 md:grid-cols-4 gap-3">
          {metadata.map((item) => (
            <div key={item.label}>
              <p className="text-[10px] uppercase tracking-wide text-slate-400 font-medium">{item.label}</p>
              <div className="text-xs text-slate-700 mt-0.5">{item.value}</div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
