import * as React from 'react';
import { cn } from '@/lib/utils';

export type StatusTone =
  | 'success'
  | 'warning'
  | 'danger'
  | 'info'
  | 'neutral'
  | 'brand';

export interface AtomyQStatusBadgeAltProps {
  children: React.ReactNode;
  tone?: StatusTone;
  dot?: boolean;
  className?: string;
}

const toneStyles: Record<
  StatusTone,
  { bg: string; text: string; border: string; dot: string }
> = {
  success: {
    bg: 'bg-[var(--aq-success-tint-8)]',
    text: 'text-[var(--aq-success-600)]',
    border: 'border-[var(--aq-success-tint-20)]',
    dot: 'bg-[var(--aq-success-500)]',
  },
  warning: {
    bg: 'bg-[var(--aq-warning-tint-8)]',
    text: 'text-[var(--aq-warning-600)]',
    border: 'border-[var(--aq-warning-tint-20)]',
    dot: 'bg-[var(--aq-warning-500)]',
  },
  danger: {
    bg: 'bg-[var(--aq-danger-tint-8)]',
    text: 'text-[var(--aq-danger-600)]',
    border: 'border-[var(--aq-danger-tint-20)]',
    dot: 'bg-[var(--aq-danger-500)]',
  },
  info: {
    bg: 'bg-[var(--aq-brand-tint-8)]',
    text: 'text-[var(--aq-brand-600)]',
    border: 'border-[var(--aq-brand-tint-20)]',
    dot: 'bg-[var(--aq-brand-500)]',
  },
  neutral: {
    bg: 'bg-[var(--aq-bg-elevated)]',
    text: 'text-[var(--aq-text-muted)]',
    border: 'border-[var(--aq-border-strong)]',
    dot: 'bg-[var(--aq-text-subtle)]',
  },
  brand: {
    bg: 'bg-[var(--aq-brand-tint-8)]',
    text: 'text-[var(--aq-brand-600)]',
    border: 'border-[var(--aq-brand-tint-20)]',
    dot: 'bg-[var(--aq-brand-500)]',
  },
};

function AtomyQStatusBadgeAlt({
  children,
  tone = 'neutral',
  dot = false,
  className,
}: AtomyQStatusBadgeAltProps) {
  const styles = toneStyles[tone];

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold',
        styles.bg,
        styles.text,
        styles.border,
        className,
      )}
    >
      {dot && <span className={cn('size-[5px] rounded-full', styles.dot)} />}
      {children}
    </span>
  );
}

export { AtomyQStatusBadgeAlt };
