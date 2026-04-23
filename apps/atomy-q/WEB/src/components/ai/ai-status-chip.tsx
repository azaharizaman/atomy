'use client';

import React from 'react';
import { Bot, AlertTriangle, CheckCircle2, CircleSlash } from 'lucide-react';

type AiStatusChipTone = 'available' | 'degraded' | 'unavailable' | 'unknown';

const CHIP_STYLES: Record<AiStatusChipTone, { className: string; icon: React.ReactNode; label: string }> = {
  available: {
    className: 'border border-green-200 bg-green-50 text-green-700',
    icon: <CheckCircle2 size={12} />,
    label: 'AI available',
  },
  degraded: {
    className: 'border border-amber-200 bg-amber-50 text-amber-700',
    icon: <AlertTriangle size={12} />,
    label: 'AI degraded',
  },
  unavailable: {
    className: 'border border-red-200 bg-red-50 text-red-700',
    icon: <CircleSlash size={12} />,
    label: 'AI unavailable',
  },
  unknown: {
    className: 'border border-slate-200 bg-slate-100 text-slate-600',
    icon: <Bot size={12} />,
    label: 'AI status unknown',
  },
};

export function AiStatusChip({
  tone,
  label,
  className = '',
}: {
  tone: AiStatusChipTone;
  label?: string;
  className?: string;
}) {
  const config = CHIP_STYLES[tone];

  return (
    <span
      role="status"
      className={[
        'inline-flex h-5 items-center gap-1 rounded-full px-2 text-xs font-medium whitespace-nowrap',
        config.className,
        className,
      ].join(' ')}
    >
      <span aria-hidden="true">{config.icon}</span>
      {label ?? config.label}
    </span>
  );
}
