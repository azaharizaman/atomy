'use client';

import React from 'react';

/** Compact metric chip for Active Record Menu Zone 1 — per Screen-Blueprint MetricChip */
export function MetricChip({ label, value, className = '' }: { label: string; value: React.ReactNode; className?: string }) {
  return (
    <div
      className={[
        'flex flex-col items-center bg-slate-50 border border-slate-200 rounded-md px-2 py-1.5 min-w-14',
        className,
      ].join(' ')}
    >
      <span className="text-[10px] font-medium text-slate-400 uppercase tracking-wide whitespace-nowrap">{label}</span>
      <span className="text-sm font-semibold text-slate-800 mt-0.5 leading-none">{value}</span>
    </div>
  );
}

