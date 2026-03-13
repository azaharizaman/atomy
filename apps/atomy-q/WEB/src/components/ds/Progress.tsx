'use client';

import React from 'react';

// ─── Linear Progress Bar — per Screen-Blueprint ───────────────────────────────

interface ProgressBarProps {
  value: number;
  max?: number;
  label?: string;
  showValue?: boolean;
  size?: 'xs' | 'sm' | 'md';
  variant?: 'indigo' | 'green' | 'amber' | 'red' | 'blue' | 'slate';
  className?: string;
  trackClassName?: string;
}

const TRACK_H = { xs: 'h-1', sm: 'h-1.5', md: 'h-2' };

const FILL_COLORS: Record<string, string> = {
  indigo: 'bg-indigo-500',
  green: 'bg-green-500',
  amber: 'bg-amber-500',
  red: 'bg-red-500',
  blue: 'bg-blue-500',
  slate: 'bg-slate-400',
};

export function ProgressBar({
  value,
  max = 100,
  label,
  showValue = false,
  size = 'sm',
  variant = 'indigo',
  className = '',
  trackClassName = '',
}: ProgressBarProps) {
  const pct = Math.min(100, Math.max(0, (value / max) * 100));

  return (
    <div className={['flex flex-col gap-1', className].join(' ')}>
      {(label || showValue) && (
        <div className="flex items-center justify-between">
          {label && <span className="text-xs text-slate-500">{label}</span>}
          {showValue && <span className="text-xs font-medium text-slate-600">{Math.round(pct)}%</span>}
        </div>
      )}
      <div
        className={['w-full rounded-full bg-slate-200 overflow-hidden', TRACK_H[size], trackClassName].join(' ')}
      >
        <div
          className={['h-full rounded-full transition-all duration-500', FILL_COLORS[variant]].join(' ')}
          style={{ width: `${pct}%` }}
        />
      </div>
    </div>
  );
}

// ─── Circular Progress — per Screen-Blueprint ─────────────────────────────────

interface CircularProgressProps {
  value: number;
  size?: number;
  strokeWidth?: number;
  variant?: 'indigo' | 'green' | 'amber' | 'red';
  label?: string;
  showValue?: boolean;
  className?: string;
}

const STROKE_COLORS: Record<string, string> = {
  indigo: '#6366F1',
  green: '#22C55E',
  amber: '#F59E0B',
  red: '#EF4444',
};

export function CircularProgress({
  value,
  size = 56,
  strokeWidth = 5,
  variant = 'indigo',
  label,
  showValue = true,
  className = '',
}: CircularProgressProps) {
  const r = (size - strokeWidth) / 2;
  const circ = 2 * Math.PI * r;
  const pct = Math.min(100, Math.max(0, value));
  const offset = circ - (pct / 100) * circ;

  return (
    <div
      className={['relative inline-flex items-center justify-center', className].join(' ')}
      style={{ width: size, height: size }}
    >
      <svg width={size} height={size} className="-rotate-90" aria-hidden>
        <circle
          cx={size / 2}
          cy={size / 2}
          r={r}
          fill="none"
          stroke="#E2E8F0"
          strokeWidth={strokeWidth}
        />
        <circle
          cx={size / 2}
          cy={size / 2}
          r={r}
          fill="none"
          stroke={STROKE_COLORS[variant]}
          strokeWidth={strokeWidth}
          strokeLinecap="round"
          strokeDasharray={circ}
          strokeDashoffset={offset}
          style={{ transition: 'stroke-dashoffset 0.5s ease' }}
        />
      </svg>
      {showValue && (
        <span className="absolute text-[10px] font-semibold text-slate-700">
          {label ?? `${Math.round(pct)}%`}
        </span>
      )}
    </div>
  );
}
