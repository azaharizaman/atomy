import * as React from 'react';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface AtomyQKPICardProps {
  label: string;
  value: string;
  change?: number;
  trend?: 'up' | 'down' | 'flat';
  period?: string;
  icon?: React.ReactNode;
  className?: string;
}

const trendConfig = {
  up: { icon: TrendingUp, color: 'text-[var(--aq-success-600)]', bg: 'bg-[var(--aq-success-50)]' },
  down: { icon: TrendingDown, color: 'text-[var(--aq-danger-600)]', bg: 'bg-[var(--aq-danger-50)]' },
  flat: { icon: Minus, color: 'text-[var(--aq-text-muted)]', bg: 'bg-[var(--aq-bg-elevated)]' },
};

function AtomyQKPICard({ label, value, change, trend = 'flat', period, icon, className }: AtomyQKPICardProps) {
  const { icon: TrendIcon, color, bg } = trendConfig[trend];

  return (
    <div className={cn(
      'flex flex-col gap-2 rounded-lg border border-[var(--aq-border-default)] bg-white p-5 shadow-sm',
      className
    )}>
      <div className="flex items-center justify-between">
        <span className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)]">
          {label}
        </span>
        {icon && <div className="text-[var(--aq-text-subtle)]">{icon}</div>}
      </div>
      <div className="flex items-end gap-3">
        <span className="text-2xl font-semibold text-[var(--aq-text-primary)] font-mono tracking-tight">
          {value}
        </span>
        {change !== undefined && (
          <span className={cn('inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-xs font-medium', bg, color)}>
            <TrendIcon className="size-3" />
            {Math.abs(change)}%
          </span>
        )}
      </div>
      {period && (
        <span className="text-xs text-[var(--aq-text-subtle)]">{period}</span>
      )}
    </div>
  );
}

export { AtomyQKPICard };
