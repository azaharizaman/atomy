import * as React from 'react';
import { cn } from '@/lib/utils';

export interface AtomyQSummaryCardAltProps {
  label: string;
  value: string;
  description?: string;
  icon?: React.ReactNode;
  hero?: boolean;
  className?: string;
}

function AtomyQSummaryCardAlt({
  label,
  value,
  description,
  icon,
  hero = false,
  className,
}: AtomyQSummaryCardAltProps) {
  return (
    <div
      className={cn(
        'rounded-xl border p-4',
        hero
          ? 'border-[var(--aq-brand-400)] bg-[var(--aq-brand-600)] text-white'
          : 'border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)]',
        className,
      )}
    >
      <div className="flex items-center justify-between">
        <span
          className={cn(
            'text-[10px] font-semibold uppercase tracking-[0.05em]',
            hero ? 'text-white/70' : 'text-[var(--aq-text-muted)]',
          )}
        >
          {label}
        </span>
        {icon && (
          <span
            className={cn(
              hero ? 'text-white/60' : 'text-[var(--aq-text-subtle)]',
            )}
          >
            {icon}
          </span>
        )}
      </div>
      <div
        className={cn(
          'mt-2 text-xl font-bold',
          hero ? 'text-white' : 'text-[var(--aq-text-primary)]',
        )}
      >
        {value}
      </div>
      {description && (
        <p
          className={cn(
            'mt-1 text-[11px]',
            hero ? 'text-white/60' : 'text-[var(--aq-text-subtle)]',
          )}
        >
          {description}
        </p>
      )}
    </div>
  );
}

export { AtomyQSummaryCardAlt };
