import * as React from 'react';
import { cn } from '@/lib/utils';

export interface AtomyQPanelAltProps {
  title?: string;
  subtitle?: string;
  right?: React.ReactNode;
  children: React.ReactNode;
  noPadding?: boolean;
  className?: string;
}

function AtomyQPanelAlt({ title, subtitle, right, children, noPadding = false, className }: AtomyQPanelAltProps) {
  return (
    <div className={cn(
      'rounded-xl border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)]',
      className
    )}>
      {(title || right) && (
        <div className="flex items-start justify-between px-4 pt-4 pb-3">
          <div>
            {title && (
              <h3 className="text-[13px] font-bold text-[var(--aq-text-primary)]">{title}</h3>
            )}
            {subtitle && (
              <p className="mt-0.5 text-[11px] text-[var(--aq-text-muted)]">{subtitle}</p>
            )}
          </div>
          {right && <div className="flex items-center gap-2">{right}</div>}
        </div>
      )}
      <div className={cn(!noPadding && 'px-4 pb-4', title && !noPadding && 'pt-0')}>
        {children}
      </div>
    </div>
  );
}

export { AtomyQPanelAlt };
