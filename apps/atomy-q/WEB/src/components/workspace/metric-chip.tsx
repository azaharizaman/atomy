'use client';

import React from 'react';

export function MetricChip({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-slate-100 border border-slate-200">
      <span className="text-[10px] font-semibold tracking-wide uppercase text-slate-500">{label}</span>
      <span className="text-xs font-semibold text-slate-800 tabular-nums">{value}</span>
    </div>
  );
}

