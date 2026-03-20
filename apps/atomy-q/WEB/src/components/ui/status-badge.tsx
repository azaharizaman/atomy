import React from 'react';
import {
  Lock, RefreshCw, AlertCircle, AlertTriangle, CheckCircle2,
  Clock, Loader2, Eye, CheckCheck, XCircle, Minus
} from 'lucide-react';
import type { StatusVariant, SLAVariant, ConfidenceVariant } from '../../lib/tokens';

// ─── Status Badge ──────────────────────────────────────────────────────────────

interface StatusConfig {
  label: string;
  className: string;
  icon?: React.ReactNode;
  dot?: boolean;
}

const STATUS_MAP: Record<StatusVariant, StatusConfig> = {
  active:     { label: 'Active',      className: 'bg-green-50 text-green-700 border border-green-200',   icon: undefined,                                   dot: true  },
  approved:   { label: 'Approved',    className: 'bg-green-50 text-green-700 border border-green-200',   icon: <CheckCircle2 size={11} />,                  dot: false },
  paid:       { label: 'Paid',        className: 'bg-green-50 text-green-700 border border-green-200',   icon: <CheckCheck size={11} />,                    dot: false },
  final:      { label: 'Final',       className: 'bg-green-50 text-green-700 border border-green-200',   icon: <CheckCircle2 size={11} />,                  dot: false },
  generated:  { label: 'Generated',   className: 'bg-blue-50 text-blue-700 border border-blue-200',      icon: undefined,                                   dot: true  },
  awarded:    { label: 'Awarded',     className: 'bg-indigo-50 text-indigo-700 border border-indigo-200',icon: undefined,                                   dot: true  },
  pending:    { label: 'Pending',     className: 'bg-amber-50 text-amber-700 border border-amber-200',   icon: <Clock size={11} />,                         dot: false },
  processing: { label: 'Processing',  className: 'bg-amber-50 text-amber-700 border border-amber-200',   icon: <Loader2 size={11} className="animate-spin"/>,dot: false },
  preview:    { label: 'Preview',     className: 'bg-amber-50 text-amber-700 border border-amber-200',   icon: <Eye size={11} />,                           dot: false },
  stale:      { label: 'Stale',       className: 'bg-amber-50 text-amber-700 border border-amber-200',   icon: <RefreshCw size={11} />,                     dot: false },
  due:        { label: 'Due',         className: 'bg-amber-50 text-amber-700 border border-amber-200',   icon: <AlertTriangle size={11} />,                 dot: false },
  publishing: { label: 'Publishing',  className: 'bg-amber-50 text-amber-700 border border-amber-200',   icon: <Loader2 size={11} className="animate-spin"/>,dot: false },
  rejected:   { label: 'Rejected',    className: 'bg-red-50 text-red-700 border border-red-200',         icon: <XCircle size={11} />,                       dot: false },
  error:      { label: 'Error',       className: 'bg-red-50 text-red-700 border border-red-200',         icon: <AlertCircle size={11} />,                   dot: false },
  overdue:    { label: 'Overdue',     className: 'bg-red-50 text-red-700 border border-red-200',         icon: <AlertCircle size={11} />,                   dot: false },
  closed:     { label: 'Closed',      className: 'bg-slate-100 text-slate-600 border border-slate-200',  icon: undefined,                                   dot: true  },
  archived:   { label: 'Archived',    className: 'bg-slate-100 text-slate-500 border border-slate-200',  icon: undefined,                                   dot: true  },
  locked:     { label: 'Locked',      className: 'bg-slate-100 text-slate-600 border border-slate-200',  icon: <Lock size={11} />,                          dot: false },
  unpaid:     { label: 'Unpaid',      className: 'bg-slate-100 text-slate-600 border border-slate-200',  icon: <Minus size={11} />,                         dot: false },
  draft:      { label: 'Draft',       className: 'bg-slate-100 text-slate-500 border border-slate-200',  icon: undefined,                                   dot: true  },
  new:        { label: 'New',         className: 'bg-slate-100 text-slate-500 border border-slate-200',  icon: undefined,                                   dot: false },
};

interface StatusBadgeProps {
  status: StatusVariant;
  label?: string;          // override default label
  size?: 'xs' | 'sm';
  className?: string;
}

export function StatusBadge({ status, label, size = 'sm', className = '' }: StatusBadgeProps) {
  const config = STATUS_MAP[status];
  const sizeClass = size === 'xs' ? 'text-[10px] px-1.5 py-0 gap-1 h-4' : 'text-xs px-2 py-0.5 gap-1 h-5';
  const accessibleLabel = label ?? config.label;

  return (
    <span
      role="status"
      aria-label={accessibleLabel}
      className={[
        'inline-flex items-center rounded-full font-medium',
        sizeClass,
        config.className,
        className,
      ].join(' ')}
    >
      {config.dot && (
        <span aria-hidden="true" className={`w-1.5 h-1.5 rounded-full ${getDotColor(status)}`} />
      )}
      {config.icon && !config.dot && (
        <span aria-hidden="true">
          {config.icon}
        </span>
      )}
      {accessibleLabel}
    </span>
  );
}

function getDotColor(status: StatusVariant): string {
  const map: Partial<Record<StatusVariant, string>> = {
    active:    'bg-green-500',
    awarded:   'bg-indigo-500',
    generated: 'bg-blue-500',
    closed:    'bg-slate-400',
    archived:  'bg-slate-400',
    draft:     'bg-slate-300',
  };
  return map[status] ?? 'bg-slate-400';
}

// ─── SLA Timer Badge ───────────────────────────────────────────────────────────

interface SLATimerBadgeProps {
  variant: SLAVariant;
  value: string;       // e.g. "2d 4h", "6h", "OVERDUE"
  className?: string;
}

const SLA_STYLES: Record<SLAVariant, string> = {
  safe:    'bg-green-50 text-green-700 border border-green-200',
  warning: 'bg-amber-50 text-amber-700 border border-amber-200',
  overdue: 'bg-red-50 text-red-700 border border-red-200',
};

export function SLATimerBadge({ variant, value, className = '' }: SLATimerBadgeProps) {
  return (
    <span
      className={[
        'inline-flex items-center gap-1 rounded-full text-xs font-medium px-2 py-0.5 h-5 tabular-nums',
        SLA_STYLES[variant],
        className,
      ].join(' ')}
    >
      <Clock size={10} />
      {value}
    </span>
  );
}

// ─── Confidence Badge ──────────────────────────────────────────────────────────

interface ConfidenceBadgeProps {
  variant: ConfidenceVariant;
  showBar?: boolean;
  percentage?: number;
  className?: string;
}

const CONFIDENCE_STYLES: Record<ConfidenceVariant, { badge: string; bar: string; label: string }> = {
  high:   { badge: 'bg-green-50 text-green-700 border border-green-200',  bar: 'bg-green-500',  label: 'High'   },
  medium: { badge: 'bg-amber-50 text-amber-700 border border-amber-200',  bar: 'bg-amber-500',  label: 'Medium' },
  low:    { badge: 'bg-red-50 text-red-700 border border-red-200',        bar: 'bg-red-500',    label: 'Low'    },
};

export function ConfidenceBadge({ variant, showBar, percentage, className = '' }: ConfidenceBadgeProps) {
  const config = CONFIDENCE_STYLES[variant];
  const clampedPercentage = percentage === undefined ? undefined : Math.max(0, Math.min(100, percentage));

  if (showBar && clampedPercentage !== undefined) {
    return (
      <div className={['flex items-center gap-2', className].join(' ')}>
        <div className="flex-1 h-1.5 rounded-full bg-slate-200 overflow-hidden">
          <div
            className={['h-full rounded-full', config.bar].join(' ')}
            style={{ width: `${clampedPercentage}%` }}
          />
        </div>
        <span className={['inline-flex items-center rounded-full text-xs font-medium px-2 py-0.5 h-5', config.badge].join(' ')}>
          {config.label}
        </span>
      </div>
    );
  }

  return (
    <span className={['inline-flex items-center rounded-full text-xs font-medium px-2 py-0.5 h-5', config.badge, className].join(' ')}>
      {config.label}
    </span>
  );
}

// ─── Version Chip ─────────────────────────────────────────────────────────────

interface VersionChipProps {
  version: string;
  className?: string;
}

export function VersionChip({ version, className = '' }: VersionChipProps) {
  return (
    <span
      className={[
        'inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200',
        'font-mono',
        className,
      ].join(' ')}
    >
      {version}
    </span>
  );
}

// ─── Count Badge ──────────────────────────────────────────────────────────────

interface CountBadgeProps {
  count: number | string;
  variant?: 'default' | 'indigo' | 'red';
  className?: string;
}

export function CountBadge({ count, variant = 'default', className = '' }: CountBadgeProps) {
  const variantStyles = {
    default: 'bg-slate-200 text-slate-600',
    indigo:  'bg-indigo-100 text-indigo-700',
    red:     'bg-red-500 text-white',
  };

  return (
    <span
      className={[
        'inline-flex items-center justify-center min-w-[18px] h-[18px] rounded-full text-[10px] font-medium px-1',
        variantStyles[variant],
        className,
      ].join(' ')}
    >
      {count}
    </span>
  );
}

// ─── Status Dot ───────────────────────────────────────────────────────────────

interface StatusDotProps {
  color: 'green' | 'amber' | 'red' | 'slate';
  className?: string;
}

const DOT_COLORS = {
  green: 'bg-green-500',
  amber: 'bg-amber-500',
  red:   'bg-red-500',
  slate: 'bg-slate-400',
};

export function StatusDot({ color, className = '' }: StatusDotProps) {
  return (
    <span className={['inline-block w-2 h-2 rounded-full', DOT_COLORS[color], className].join(' ')} />
  );
}
