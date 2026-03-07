import * as React from 'react';
import * as ProgressPrimitive from '@radix-ui/react-progress';
import { cn } from '@/lib/utils';

export interface AtomyQProgressProps
  extends React.ComponentPropsWithoutRef<typeof ProgressPrimitive.Root> {
  value: number;
  max?: number;
  label?: string;
  showValue?: boolean;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'default' | 'success' | 'warning' | 'danger';
}

const sizeStyles = {
  sm: 'h-1.5',
  md: 'h-2.5',
  lg: 'h-4',
};

const variantStyles = {
  default: 'bg-[var(--aq-brand-600)]',
  success: 'bg-[var(--aq-success-500)]',
  warning: 'bg-[var(--aq-warning-500)]',
  danger: 'bg-[var(--aq-danger-500)]',
};

const AtomyQProgress = React.forwardRef<
  React.ComponentRef<typeof ProgressPrimitive.Root>,
  AtomyQProgressProps
>(({ className, value, max = 100, label, showValue = false, size = 'md', variant = 'default', ...props }, ref) => {
  const percentage = Math.round((value / max) * 100);

  return (
    <div className="flex flex-col gap-1.5 w-full">
      {(label || showValue) && (
        <div className="flex items-center justify-between">
          {label && <span className="text-xs font-medium text-[var(--aq-text-secondary)]">{label}</span>}
          {showValue && <span className="text-xs text-[var(--aq-text-muted)] font-mono">{percentage}%</span>}
        </div>
      )}
      <ProgressPrimitive.Root
        ref={ref}
        className={cn(
          'relative w-full overflow-hidden rounded-full bg-[var(--aq-bg-elevated)]',
          sizeStyles[size],
          className
        )}
        value={value}
        max={max}
        {...props}
      >
        <ProgressPrimitive.Indicator
          className={cn('h-full rounded-full transition-all duration-300', variantStyles[variant])}
          style={{ width: `${percentage}%` }}
        />
      </ProgressPrimitive.Root>
    </div>
  );
});

AtomyQProgress.displayName = 'AtomyQProgress';

export { AtomyQProgress };
