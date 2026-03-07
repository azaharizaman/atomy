import * as React from 'react';
import { cn } from '@/lib/utils';

export type StatTone = 'brand' | 'success' | 'warning' | 'danger' | 'neutral';

export interface AtomyQStatAltProps {
  label: string;
  value: string;
  tone?: StatTone;
  className?: string;
}

const toneStyles: Record<
  StatTone,
  { bg: string; border: string; valueColor: string }
> = {
  brand: {
    bg: 'bg-[var(--aq-brand-tint-4)]',
    border: 'border-[var(--aq-brand-tint-12)]',
    valueColor: 'text-[var(--aq-brand-600)]',
  },
  success: {
    bg: 'bg-[var(--aq-success-tint-6)]',
    border: 'border-[var(--aq-success-tint-15)]',
    valueColor: 'text-[var(--aq-success-600)]',
  },
  warning: {
    bg: 'bg-[var(--aq-warning-tint-6)]',
    border: 'border-[var(--aq-warning-tint-15)]',
    valueColor: 'text-[var(--aq-warning-600)]',
  },
  danger: {
    bg: 'bg-[var(--aq-danger-tint-6)]',
    border: 'border-[var(--aq-danger-tint-15)]',
    valueColor: 'text-[var(--aq-danger-600)]',
  },
  neutral: {
    bg: 'bg-[var(--aq-bg-elevated)]',
    border: 'border-[var(--aq-border-strong)]',
    valueColor: 'text-[var(--aq-text-primary)]',
  },
};

function AtomyQStatAlt({
  label,
  value,
  tone = 'neutral',
  className,
}: AtomyQStatAltProps) {
  const styles = toneStyles[tone];

  return (
    <div
      className={cn('rounded-lg border p-3', styles.bg, styles.border, className)}
    >
      <div className="text-[11px] text-[var(--aq-text-muted)]">{label}</div>
      <div
        className={cn('mt-1 text-xl font-extrabold', styles.valueColor)}
        style={{ letterSpacing: '-0.02em' }}
      >
        {value}
      </div>
    </div>
  );
}

export { AtomyQStatAlt };
