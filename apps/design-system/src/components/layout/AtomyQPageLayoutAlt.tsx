import * as React from 'react';
import { cn } from '@/lib/utils';

export interface AtomyQPageLayoutAltProps {
  title: string;
  subtitle?: string;
  actions?: React.ReactNode;
  children: React.ReactNode;
  className?: string;
}

function AtomyQPageLayoutAlt({ title, subtitle, actions, children, className }: AtomyQPageLayoutAltProps) {
  return (
    <div className={cn('min-h-full p-6 bg-[var(--aq-bg-canvas)]', className)}>
      <div className="flex items-start justify-between mb-6">
        <div>
          <h1 className="text-xl font-bold tracking-tight text-[var(--aq-text-primary)]" style={{ letterSpacing: '-0.01em' }}>
            {title}
          </h1>
          {subtitle && (
            <p className="mt-1 text-[13px] text-[var(--aq-text-subtle)]">{subtitle}</p>
          )}
        </div>
        {actions && <div className="flex items-center gap-2">{actions}</div>}
      </div>
      {children}
    </div>
  );
}

export { AtomyQPageLayoutAlt };
