import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
  'inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 gap-1 transition-colors',
  {
    variants: {
      variant: {
        default: 'border-transparent bg-[var(--aq-brand-100)] text-[var(--aq-brand-700)]',
        secondary: 'border-transparent bg-secondary text-secondary-foreground',
        success: 'border-transparent bg-[var(--aq-success-50)] text-[var(--aq-success-700)]',
        warning: 'border-transparent bg-[var(--aq-warning-50)] text-[var(--aq-warning-700)]',
        danger: 'border-transparent bg-[var(--aq-danger-50)] text-[var(--aq-danger-700)]',
        info: 'border-transparent bg-[var(--aq-info-50)] text-[var(--aq-info-600)]',
        outline: 'border-[var(--aq-border-default)] text-[var(--aq-text-secondary)] bg-white',
        neutral: 'border-transparent bg-[var(--aq-bg-elevated)] text-[var(--aq-text-muted)]',
      },
      size: {
        sm: 'px-1.5 py-0 text-[10px]',
        md: 'px-2 py-0.5 text-xs',
        lg: 'px-2.5 py-1 text-sm',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'md',
    },
  }
);

export interface AtomyQBadgeProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof badgeVariants> {
  dot?: boolean;
}

const AtomyQBadge = React.forwardRef<HTMLSpanElement, AtomyQBadgeProps>(
  ({ className, variant, size, dot = false, children, ...props }, ref) => (
    <span
      ref={ref}
      className={cn(badgeVariants({ variant, size }), className)}
      {...props}
    >
      {dot && (
        <span className="size-1.5 rounded-full bg-current shrink-0" />
      )}
      {children}
    </span>
  )
);

AtomyQBadge.displayName = 'AtomyQBadge';

export { AtomyQBadge, badgeVariants };
