import React from 'react';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { ProgressBar } from './Progress';
import { CircularProgress } from './Progress';

// ─── KPI Scorecard ────────────────────────────────────────────────────────────

type TrendDirection = 'up' | 'down' | 'neutral';
type ProgressType = 'bar' | 'circular' | 'none';

interface KPIScorecardProps {
  title: string;
  value: React.ReactNode;
  subtitle?: string;
  badge?: React.ReactNode;
  /** Leading icon (e.g. metric category). */
  leadingIcon?: React.ReactNode;
  /** Trailing control (e.g. drill-down `IconButton` with `ArrowUpRight`). */
  trailingAction?: React.ReactNode;
  trend?: { direction: TrendDirection; label: string };
  progress?: { value: number; type?: ProgressType; variant?: 'indigo' | 'green' | 'amber' | 'red' };
  onClick?: () => void;
  className?: string;
  highlight?: boolean;
}

const TREND_STYLES: Record<TrendDirection, { icon: React.ReactNode; className: string }> = {
  up:      { icon: <TrendingUp size={11} />,   className: 'text-green-600 bg-green-50' },
  down:    { icon: <TrendingDown size={11} />,  className: 'text-red-600 bg-red-50'   },
  neutral: { icon: <Minus size={11} />,         className: 'text-slate-500 bg-slate-100' },
};

export function KPIScorecard({
  title,
  value,
  subtitle,
  badge,
  leadingIcon,
  trailingAction,
  trend,
  progress,
  onClick,
  className = '',
  highlight = false,
}: KPIScorecardProps) {
  return (
    <div
      onClick={onClick}
      className={[
        'flex flex-col gap-2 p-4 bg-white rounded-lg border shadow-[0_1px_3px_0_rgba(0,0,0,0.06)]',
        highlight ? 'border-indigo-300 ring-1 ring-indigo-200' : 'border-slate-200',
        onClick ? 'cursor-pointer hover:border-indigo-300 hover:shadow-[0_2px_8px_0_rgba(0,0,0,0.08)] transition-all duration-150' : '',
        className,
      ].join(' ')}
    >
      {/* Header row */}
      <div className="flex items-start justify-between gap-2">
        <div className="flex items-start gap-2 min-w-0">
          {leadingIcon && <span className="text-slate-400 shrink-0 mt-0.5">{leadingIcon}</span>}
          <span className="text-xs font-medium text-slate-500 uppercase tracking-wide">{title}</span>
        </div>
        <div className="flex items-center gap-1.5 shrink-0">
          {badge && <span>{badge}</span>}
          {trailingAction}
        </div>
      </div>

      {/* Value */}
      <div className="flex items-end justify-between gap-3">
        <div>
          <div className="text-2xl font-semibold text-slate-900 leading-none">{value}</div>
          {subtitle && <div className="text-xs text-slate-500 mt-1 leading-tight">{subtitle}</div>}
        </div>

        {/* Circular progress (if type = circular) */}
        {progress?.type === 'circular' && (
          <CircularProgress value={progress.value} size={44} strokeWidth={4} variant={progress.variant ?? 'indigo'} />
        )}

        {/* Trend chip */}
        {trend && (
          <span className={['inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium', TREND_STYLES[trend.direction].className].join(' ')}>
            {TREND_STYLES[trend.direction].icon}
            {trend.label}
          </span>
        )}
      </div>

      {/* Linear progress bar */}
      {progress?.type !== 'circular' && progress && (
        <ProgressBar value={progress.value} variant={progress.variant ?? 'indigo'} size="xs" />
      )}
    </div>
  );
}

// ─── Metric Row (compact, horizontal layout) ──────────────────────────────────

interface MetricRowProps {
  metrics: {
    label: string;
    value: React.ReactNode;
    sub?: string;
  }[];
  className?: string;
}

export function MetricRow({ metrics, className = '' }: MetricRowProps) {
  return (
    <div className={['grid divide-x divide-slate-200', `grid-cols-${metrics.length}`, className].join(' ')}>
      {metrics.map((m, i) => (
        <div key={i} className="flex flex-col items-center py-3 px-4">
          <span className="text-xl font-semibold text-slate-900">{m.value}</span>
          <span className="text-[11px] font-medium text-slate-500 uppercase tracking-wide mt-0.5">{m.label}</span>
          {m.sub && <span className="text-xs text-slate-400 mt-0.5">{m.sub}</span>}
        </div>
      ))}
    </div>
  );
}

// ─── MetricChip (used in Active Record Menu snippet) ─────────────────────────

interface MetricChipProps {
  label: string;
  value: React.ReactNode;
  className?: string;
}

export function MetricChip({ label, value, className = '' }: MetricChipProps) {
  return (
    <div className={['flex flex-col items-center bg-slate-50 border border-slate-200 rounded-md px-2 py-1.5 min-w-14', className].join(' ')}>
      <span className="text-[10px] font-medium text-slate-400 uppercase tracking-wide whitespace-nowrap">{label}</span>
      <span className="text-sm font-semibold text-slate-800 mt-0.5 leading-none">{value}</span>
    </div>
  );
}
