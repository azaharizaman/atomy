import * as React from 'react';
import { cn } from '@/lib/utils';

export type MetricTone = 'best' | 'worst' | 'neutral' | 'warning';

export interface AtomyQMetricBadgeAltProps {
  value: string;
  tone?: MetricTone;
  className?: string;
}

const toneStyles: Record<MetricTone, string> = {
  best: 'bg-[var(--aq-success-tint-10)] text-[var(--aq-success-600)] font-semibold',
  worst: 'bg-[var(--aq-danger-tint-10)] text-[var(--aq-danger-600)]',
  warning:
    'bg-[var(--aq-warning-tint-10)] text-[var(--aq-warning-600)]',
  neutral:
    'bg-[var(--aq-bg-elevated)] text-[var(--aq-text-secondary)]',
};

function AtomyQMetricBadgeAlt({
  value,
  tone = 'neutral',
  className,
}: AtomyQMetricBadgeAltProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded px-2 py-1 text-center text-[12px]',
        toneStyles[tone],
        className,
      )}
    >
      {value}
    </span>
  );
}

export { AtomyQMetricBadgeAlt };
