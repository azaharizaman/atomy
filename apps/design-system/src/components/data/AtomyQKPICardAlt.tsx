import * as React from 'react';
import { cn } from '@/lib/utils';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';

export type KPITone = 'brand' | 'success' | 'warning' | 'danger' | 'purple' | 'neutral';

export interface AtomyQKPICardAltProps {
  label: string;
  value: string;
  delta?: string;
  trend?: 'up' | 'down' | 'flat';
  icon?: React.ReactNode;
  tone?: KPITone;
  onClick?: () => void;
  className?: string;
}

const toneConfig: Record<KPITone, { iconBg: string; iconColor: string }> = {
  brand: {
    iconBg: 'bg-[var(--aq-brand-tint-8)]',
    iconColor: 'text-[var(--aq-brand-500)]',
  },
  success: {
    iconBg: 'bg-[var(--aq-success-tint-10)]',
    iconColor: 'text-[var(--aq-success-500)]',
  },
  warning: {
    iconBg: 'bg-[var(--aq-warning-tint-10)]',
    iconColor: 'text-[var(--aq-warning-500)]',
  },
  danger: {
    iconBg: 'bg-[var(--aq-danger-tint-10)]',
    iconColor: 'text-[var(--aq-danger-500)]',
  },
  purple: {
    iconBg: 'bg-[var(--aq-purple-tint-8)]',
    iconColor: 'text-[var(--aq-purple-500)]',
  },
  neutral: {
    iconBg: 'bg-[var(--aq-bg-elevated)]',
    iconColor: 'text-[var(--aq-text-muted)]',
  },
};

const trendConfig = {
  up: { icon: TrendingUp, color: 'text-[var(--aq-success-600)]' },
  down: { icon: TrendingDown, color: 'text-[var(--aq-danger-600)]' },
  flat: { icon: Minus, color: 'text-[var(--aq-text-muted)]' },
};

function AtomyQKPICardAlt({
  label,
  value,
  delta,
  trend = 'flat',
  icon,
  tone = 'brand',
  onClick,
  className,
}: AtomyQKPICardAltProps) {
  const { iconBg, iconColor } = toneConfig[tone];
  const { icon: TrendIcon, color: trendColor } = trendConfig[trend];

  return (
    <div
      className={cn(
        'rounded-xl border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] p-4',
        onClick && 'cursor-pointer transition-shadow hover:shadow-md',
        className,
      )}
      onClick={onClick}
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : undefined}
      onKeyDown={onClick ? (e) => { if (e.key === 'Enter' || e.key === ' ') onClick(); } : undefined}
    >
      <div className="flex items-start justify-between">
        <span className="text-[12px] text-[var(--aq-text-muted)]">{label}</span>
        {icon && (
          <div
            className={cn(
              'flex size-8 items-center justify-center rounded-lg',
              iconBg,
              iconColor,
            )}
          >
            {icon}
          </div>
        )}
      </div>
      <div className="mt-2 flex items-end gap-2">
        <span
          className="text-[26px] font-bold leading-none text-[var(--aq-text-primary)]"
          style={{ letterSpacing: '-0.02em' }}
        >
          {value}
        </span>
        {delta && (
          <span
            className={cn(
              'mb-0.5 flex items-center gap-0.5 text-[11px] font-medium',
              trendColor,
            )}
          >
            <TrendIcon className="size-3" />
            {delta}
          </span>
        )}
      </div>
    </div>
  );
}

export { AtomyQKPICardAlt };
