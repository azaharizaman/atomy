import * as React from 'react';
import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import { Check, Minus } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface AtomyQCheckboxProps
  extends React.ComponentPropsWithoutRef<typeof CheckboxPrimitive.Root> {
  label?: string;
  description?: string;
  indeterminate?: boolean;
}

const AtomyQCheckbox = React.forwardRef<
  React.ComponentRef<typeof CheckboxPrimitive.Root>,
  AtomyQCheckboxProps
>(({ className, label, description, indeterminate, id, ...props }, ref) => {
  const checkboxId = id || React.useId();

  return (
    <div className="flex items-start gap-2">
      <CheckboxPrimitive.Root
        ref={ref}
        id={checkboxId}
        checked={indeterminate ? 'indeterminate' : props.checked}
        className={cn(
          'peer size-4 shrink-0 rounded border border-[var(--aq-border-strong)] mt-0.5 transition-colors outline-none',
          'focus-visible:ring-2 focus-visible:ring-[var(--aq-brand-500)]/20',
          'disabled:cursor-not-allowed disabled:opacity-50',
          'data-[state=checked]:bg-[var(--aq-brand-600)] data-[state=checked]:border-[var(--aq-brand-600)] data-[state=checked]:text-white',
          'data-[state=indeterminate]:bg-[var(--aq-brand-600)] data-[state=indeterminate]:border-[var(--aq-brand-600)] data-[state=indeterminate]:text-white',
          className
        )}
        {...props}
      >
        <CheckboxPrimitive.Indicator className="flex items-center justify-center">
          {indeterminate ? <Minus className="size-3" /> : <Check className="size-3" />}
        </CheckboxPrimitive.Indicator>
      </CheckboxPrimitive.Root>
      {(label || description) && (
        <div className="flex flex-col">
          {label && (
            <label htmlFor={checkboxId} className="text-sm font-medium text-[var(--aq-text-primary)] cursor-pointer leading-tight">
              {label}
            </label>
          )}
          {description && (
            <p className="text-xs text-[var(--aq-text-muted)] mt-0.5">{description}</p>
          )}
        </div>
      )}
    </div>
  );
});

AtomyQCheckbox.displayName = 'AtomyQCheckbox';

export { AtomyQCheckbox };
