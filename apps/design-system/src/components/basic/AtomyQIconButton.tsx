import * as React from 'react';
import { cn } from '@/lib/utils';
import { AtomyQTooltip } from './AtomyQTooltip';

export interface AtomyQIconButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'default' | 'ghost' | 'outline';
  size?: 'sm' | 'md' | 'lg';
  tooltip?: string;
  'aria-label': string;
}

const sizeClasses = {
  sm: 'size-7 [&_svg]:size-3.5',
  md: 'size-9 [&_svg]:size-4',
  lg: 'size-11 [&_svg]:size-5',
};

const variantClasses = {
  default: 'bg-[var(--aq-brand-600)] text-white hover:bg-[var(--aq-brand-700)]',
  ghost: 'text-[var(--aq-text-muted)] hover:text-[var(--aq-text-primary)] hover:bg-[var(--aq-bg-elevated)]',
  outline: 'border border-[var(--aq-border-default)] text-[var(--aq-text-secondary)] hover:bg-[var(--aq-bg-elevated)]',
};

const AtomyQIconButton = React.forwardRef<HTMLButtonElement, AtomyQIconButtonProps>(
  ({ className, variant = 'ghost', size = 'md', tooltip, children, ...props }, ref) => {
    const button = (
      <button
        ref={ref}
        className={cn(
          'inline-flex items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:ring-2 focus-visible:ring-[var(--aq-brand-500)]',
          sizeClasses[size],
          variantClasses[variant],
          className
        )}
        {...props}
      >
        {children}
      </button>
    );

    if (tooltip) {
      return <AtomyQTooltip content={tooltip}>{button}</AtomyQTooltip>;
    }
    return button;
  }
);

AtomyQIconButton.displayName = 'AtomyQIconButton';

export { AtomyQIconButton };
