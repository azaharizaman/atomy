import * as React from 'react';
import { cn } from '@/lib/utils';
import { ChevronRight } from 'lucide-react';

export interface AtomyQSectionHeaderAltProps {
  title: string;
  action?: { label: string; onClick: () => void };
  icon?: React.ReactNode;
  className?: string;
}

function AtomyQSectionHeaderAlt({
  title,
  action,
  icon,
  className,
}: AtomyQSectionHeaderAltProps) {
  return (
    <div className={cn('flex items-center justify-between', className)}>
      <div className="flex items-center gap-2">
        {icon && (
          <span className="text-[var(--aq-text-muted)]">{icon}</span>
        )}
        <h3 className="text-[12px] font-semibold uppercase tracking-[0.08em] text-[var(--aq-text-muted)]">
          {title}
        </h3>
      </div>
      {action && (
        <button
          onClick={action.onClick}
          className="flex items-center gap-0.5 text-[12px] text-[var(--aq-brand-500)] transition-opacity hover:opacity-80"
        >
          {action.label}
          <ChevronRight className="size-3" />
        </button>
      )}
    </div>
  );
}

export { AtomyQSectionHeaderAlt };
