'use client';

import React from 'react';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { ProgressBar } from './Progress';
import { CircularProgress } from './Progress';

type TrendDirection = 'up' | 'down' | 'neutral';
type ProgressType = 'bar' | 'circular' | 'none';

interface KPIScorecardProps {
  title: string;
  value: React.ReactNode;
  subtitle?: string;
  badge?: React.ReactNode;
  trend?: { direction: TrendDirection; label: string };
  progress?: {
    value: number;
    type?: ProgressType;
    variant?: 'indigo' | 'green' | 'amber' | 'red';
  };
  onClick?: () => void;
  className?: string;
  highlight?: boolean;
}

const TREND_STYLES: Record<TrendDirection, { icon: React.ReactNode; className: string }> = {
  up: { icon: <TrendingUp size={11} />, className: 'text-green-600 bg-green-50' },
  down: { icon: <TrendingDown size={11} />, className: 'text-red-600 bg-red-50' },
  neutral: { icon: <Minus size={11} />, className: 'text-slate-500 bg-slate-100' },
};

export function KPIScorecard({
  title,
  value,
  subtitle,
  badge,
  trend,
  progress,
  onClick,
  className = '',
  highlight = false,
}: KPIScorecardProps) {
  return (
    <div
      onClick={onClick}
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : undefined}
      onKeyDown={
        onClick
          ? (e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                onClick();
              }
            }
          : undefined
      }
      className={[
        'flex flex-col gap-2 p-4 bg-white rounded-lg border shadow-[0_1px_3px_0_rgba(0,0,0,0.06)]',
        highlight ? 'border-indigo-300 ring-1 ring-indigo-200' : 'border-slate-200',
        onClick
          ? 'cursor-pointer hover:border-indigo-300 hover:shadow-[0_2px_8px_0_rgba(0,0,0,0.08)] transition-all duration-150'
          : '',
        className,
      ].join(' ')}
    >
      <div className="flex items-start justify-between gap-2">
        <span className="text-xs font-medium text-slate-500 uppercase tracking-wide">{title}</span>
        {badge != null && <span className="shrink-0">{badge}</span>}
      </div>

      <div className="flex items-end justify-between gap-3">
        <div>
          <div className="text-2xl font-semibold text-slate-900 leading-none">{value}</div>
          {subtitle != null && <div className="text-xs text-slate-500 mt-1 leading-tight">{subtitle}</div>}
        </div>

        {progress?.type === 'circular' && (
          <CircularProgress
            value={progress.value}
            size={44}
            strokeWidth={4}
            variant={progress.variant ?? 'indigo'}
          />
        )}

        {trend != null && (
          <span
            className={[
              'inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium',
              TREND_STYLES[trend.direction].className,
            ].join(' ')}
          >
            {TREND_STYLES[trend.direction].icon}
            {trend.label}
          </span>
        )}
      </div>

      {progress != null && progress.type !== 'circular' && (
        <ProgressBar value={progress.value} variant={progress.variant ?? 'indigo'} size="xs" />
      )}
    </div>
  );
}
