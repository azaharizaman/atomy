import * as React from 'react';
import * as SwitchPrimitive from '@radix-ui/react-switch';
import { cn } from '@/lib/utils';

export interface AtomyQSwitchProps
  extends React.ComponentPropsWithoutRef<typeof SwitchPrimitive.Root> {
  label?: string;
  description?: string;
}

const AtomyQSwitch = React.forwardRef<
  React.ComponentRef<typeof SwitchPrimitive.Root>,
  AtomyQSwitchProps
>(({ className, label, description, id, ...props }, ref) => {
  const switchId = id || React.useId();

  return (
    <div className="flex items-center justify-between gap-3">
      {(label || description) && (
        <div className="flex flex-col">
          {label && (
            <label htmlFor={switchId} className="text-sm font-medium text-[var(--aq-text-primary)] cursor-pointer">
              {label}
            </label>
          )}
          {description && (
            <p className="text-xs text-[var(--aq-text-muted)] mt-0.5">{description}</p>
          )}
        </div>
      )}
      <SwitchPrimitive.Root
        ref={ref}
        id={switchId}
        className={cn(
          'peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--aq-brand-500)]/20',
          'disabled:cursor-not-allowed disabled:opacity-50',
          'data-[state=checked]:bg-[var(--aq-brand-600)] data-[state=unchecked]:bg-[var(--switch-background)]',
          className
        )}
        {...props}
      >
        <SwitchPrimitive.Thumb
          className={cn(
            'pointer-events-none block size-4 rounded-full bg-white shadow-sm transition-transform',
            'data-[state=checked]:translate-x-4 data-[state=unchecked]:translate-x-0'
          )}
        />
      </SwitchPrimitive.Root>
    </div>
  );
});

AtomyQSwitch.displayName = 'AtomyQSwitch';

export { AtomyQSwitch };
