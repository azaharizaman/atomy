import * as React from 'react';
import * as RadioGroupPrimitive from '@radix-ui/react-radio-group';
import { Circle } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface AtomyQRadioOption {
  value: string;
  label: string;
  description?: string;
  disabled?: boolean;
}

export interface AtomyQRadioGroupProps
  extends Omit<React.ComponentPropsWithoutRef<typeof RadioGroupPrimitive.Root>, 'children'> {
  label?: string;
  options: AtomyQRadioOption[];
  error?: string;
}

const AtomyQRadioGroup = React.forwardRef<
  React.ComponentRef<typeof RadioGroupPrimitive.Root>,
  AtomyQRadioGroupProps
>(({ className, label, options, error, ...props }, ref) => (
  <div className="flex flex-col gap-2">
    {label && (
      <span className="text-sm font-medium text-[var(--aq-text-primary)]">{label}</span>
    )}
    <RadioGroupPrimitive.Root ref={ref} className={cn('flex flex-col gap-2', className)} {...props}>
      {options.map((option) => {
        const itemId = `radio-${option.value}`;
        return (
          <div key={option.value} className="flex items-start gap-2">
            <RadioGroupPrimitive.Item
              id={itemId}
              value={option.value}
              disabled={option.disabled}
              className={cn(
                'aspect-square size-4 rounded-full border border-[var(--aq-border-strong)] mt-0.5 outline-none transition-colors',
                'focus-visible:ring-2 focus-visible:ring-[var(--aq-brand-500)]/20',
                'disabled:cursor-not-allowed disabled:opacity-50',
                'data-[state=checked]:border-[var(--aq-brand-600)]'
              )}
            >
              <RadioGroupPrimitive.Indicator className="flex items-center justify-center">
                <Circle className="size-2 fill-[var(--aq-brand-600)] text-[var(--aq-brand-600)]" />
              </RadioGroupPrimitive.Indicator>
            </RadioGroupPrimitive.Item>
            <div className="flex flex-col">
              <label htmlFor={itemId} className="text-sm font-medium text-[var(--aq-text-primary)] cursor-pointer leading-tight">
                {option.label}
              </label>
              {option.description && (
                <p className="text-xs text-[var(--aq-text-muted)] mt-0.5">{option.description}</p>
              )}
            </div>
          </div>
        );
      })}
    </RadioGroupPrimitive.Root>
    {error && <p className="text-xs text-[var(--aq-danger-600)]" role="alert">{error}</p>}
  </div>
));

AtomyQRadioGroup.displayName = 'AtomyQRadioGroup';

export { AtomyQRadioGroup };
