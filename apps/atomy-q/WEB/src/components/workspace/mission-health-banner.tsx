'use client';

import React from 'react';
import { AlertTriangle, Ban, CheckCircle2 } from 'lucide-react';

import { StatusBadge } from '@/components/ds/Badge';
import type { MissionHealth, MissionHealthResult } from '@/lib/rfq-mission-health';

function badgeForHealth(health: MissionHealth): { status: 'active' | 'pending' | 'error'; label: string } {
  if (health === 'nominal') {
    return { status: 'active', label: 'All clear' };
  }
  if (health === 'attention') {
    return { status: 'pending', label: 'Needs attention' };
  }
  return { status: 'error', label: 'Blocked' };
}

export interface MissionHealthBannerProps {
  result: MissionHealthResult;
  className?: string;
}

/**
 * Single composite headline for RFQ mission control — not a stack of per-area health badges.
 */
export function MissionHealthBanner({ result, className = '' }: MissionHealthBannerProps) {
  const { status, label } = badgeForHealth(result.health);
  const Icon =
    result.health === 'nominal' ? CheckCircle2 : result.health === 'attention' ? AlertTriangle : Ban;

  return (
    <div
      role="status"
      aria-live="polite"
      className={[
        'rounded-lg border border-slate-200 bg-white px-4 py-3 flex flex-row items-start gap-3 shadow-sm',
        className,
      ].join(' ')}
    >
      <span
        className={[
          'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md border',
          result.health === 'nominal'
            ? 'border-green-200 bg-green-50 text-green-700'
            : result.health === 'attention'
              ? 'border-amber-200 bg-amber-50 text-amber-800'
              : 'border-red-200 bg-red-50 text-red-700',
        ].join(' ')}
        aria-hidden
      >
        <Icon size={18} strokeWidth={2} />
      </span>
      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Mission health</span>
          <StatusBadge status={status} label={label} size="sm" />
        </div>
        {result.reason ? (
          <p className="mt-1 text-sm text-slate-600">{result.reason}</p>
        ) : (
          <p className="mt-1 text-sm text-slate-500">No blocking issues detected from current overview signals.</p>
        )}
      </div>
    </div>
  );
}
