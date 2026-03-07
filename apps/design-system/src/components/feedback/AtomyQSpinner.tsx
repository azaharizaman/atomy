import * as React from 'react';
import { cn } from '@/lib/utils';

export interface AtomyQSpinnerProps {
  size?: 'sm' | 'md' | 'lg';
  label?: string;
  className?: string;
}

const sizeStyles = {
  sm: 'size-4',
  md: 'size-6',
  lg: 'size-8',
};

function AtomyQSpinner({ size = 'md', label, className }: AtomyQSpinnerProps) {
  return (
    <div className={cn('inline-flex flex-col items-center gap-2', className)} role="status">
      <svg
        className={cn('animate-spin text-[var(--aq-brand-500)]', sizeStyles[size])}
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true"
      >
        <circle
          className="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          strokeWidth="4"
        />
        <path
          className="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
        />
      </svg>
      {label && <span className="text-sm text-[var(--aq-text-muted)]">{label}</span>}
      <span className="sr-only">{label || 'Loading'}</span>
    </div>
  );
}

export { AtomyQSpinner };
