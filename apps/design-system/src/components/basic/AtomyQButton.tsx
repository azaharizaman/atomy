import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*="size-"])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[var(--aq-brand-500)]',
  {
    variants: {
      variant: {
        primary: 'bg-[var(--aq-brand-600)] text-white hover:bg-[var(--aq-brand-700)] active:bg-[var(--aq-brand-800)]',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80 active:bg-secondary/70',
        outline: 'border border-[var(--aq-border-default)] bg-white text-[var(--aq-text-secondary)] hover:bg-[var(--aq-bg-elevated)] active:bg-[var(--aq-bg-sunken)]',
        ghost: 'text-[var(--aq-text-secondary)] hover:bg-[var(--aq-bg-elevated)] active:bg-[var(--aq-bg-sunken)]',
        destructive: 'bg-[var(--aq-danger-600)] text-white hover:bg-[var(--aq-danger-700)] active:bg-[var(--aq-danger-700)]',
        success: 'bg-[var(--aq-success-600)] text-white hover:bg-[var(--aq-success-700)] active:bg-[var(--aq-success-700)]',
        link: 'text-[var(--aq-brand-600)] underline-offset-4 hover:underline p-0 h-auto',
      },
      size: {
        sm: 'h-8 px-3 text-xs rounded-md',
        md: 'h-9 px-4 py-2',
        lg: 'h-10 px-6 text-sm rounded-md',
        icon: 'size-9 rounded-md p-0',
        'icon-sm': 'size-8 rounded-md p-0',
      },
    },
    defaultVariants: {
      variant: 'primary',
      size: 'md',
    },
  }
);

export interface AtomyQButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean;
  loading?: boolean;
}

const AtomyQButton = React.forwardRef<HTMLButtonElement, AtomyQButtonProps>(
  ({ className, variant, size, asChild = false, loading = false, children, disabled, ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';
    return (
      <Comp
        ref={ref}
        className={cn(buttonVariants({ variant, size, className }))}
        disabled={disabled || loading}
        aria-busy={loading}
        {...props}
      >
        {loading && (
          <svg className="animate-spin size-4" viewBox="0 0 24 24" fill="none">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        )}
        {children}
      </Comp>
    );
  }
);

AtomyQButton.displayName = 'AtomyQButton';

export { AtomyQButton, buttonVariants };
