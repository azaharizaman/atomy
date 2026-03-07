import * as React from 'react';
import * as SelectPrimitive from '@radix-ui/react-select';
import { Check, ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface AtomyQSelectOption {
  value: string;
  label: string;
  disabled?: boolean;
}

export interface AtomyQSelectProps {
  label?: string;
  placeholder?: string;
  options: AtomyQSelectOption[];
  value?: string;
  onValueChange?: (value: string) => void;
  error?: string;
  hint?: string;
  required?: boolean;
  disabled?: boolean;
}

function AtomyQSelect({
  label,
  placeholder = 'Select...',
  options,
  value,
  onValueChange,
  error,
  hint,
  required,
  disabled,
}: AtomyQSelectProps) {
  const id = React.useId();

  return (
    <div className="flex flex-col gap-1.5">
      {label && (
        <label htmlFor={id} className="text-sm font-medium text-[var(--aq-text-primary)]">
          {label}
          {required && <span className="text-[var(--aq-danger-500)] ml-0.5">*</span>}
        </label>
      )}
      <SelectPrimitive.Root value={value} onValueChange={onValueChange} disabled={disabled}>
        <SelectPrimitive.Trigger
          id={id}
          className={cn(
            'flex h-9 w-full items-center justify-between rounded-md border border-[var(--aq-border-default)] bg-[var(--input-background)] px-3 py-1 text-sm outline-none transition-colors',
            'focus:border-[var(--aq-brand-500)] focus:ring-2 focus:ring-[var(--aq-brand-500)]/20',
            'disabled:cursor-not-allowed disabled:opacity-50',
            error && 'border-[var(--aq-danger-500)]',
            !value && 'text-[var(--aq-text-subtle)]'
          )}
          aria-invalid={!!error}
        >
          <SelectPrimitive.Value placeholder={placeholder} />
          <SelectPrimitive.Icon>
            <ChevronDown className="size-4 text-[var(--aq-text-muted)]" />
          </SelectPrimitive.Icon>
        </SelectPrimitive.Trigger>
        <SelectPrimitive.Portal>
          <SelectPrimitive.Content
            className="z-50 min-w-[8rem] overflow-hidden rounded-md border border-[var(--aq-border-default)] bg-white shadow-lg"
            position="popper"
            sideOffset={4}
          >
            <SelectPrimitive.Viewport className="p-1">
              {options.map((option) => (
                <SelectPrimitive.Item
                  key={option.value}
                  value={option.value}
                  disabled={option.disabled}
                  className="relative flex cursor-pointer select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none hover:bg-[var(--aq-bg-elevated)] focus:bg-[var(--aq-bg-elevated)] data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                >
                  <span className="absolute left-2 flex size-3.5 items-center justify-center">
                    <SelectPrimitive.ItemIndicator>
                      <Check className="size-4 text-[var(--aq-brand-600)]" />
                    </SelectPrimitive.ItemIndicator>
                  </span>
                  <SelectPrimitive.ItemText>{option.label}</SelectPrimitive.ItemText>
                </SelectPrimitive.Item>
              ))}
            </SelectPrimitive.Viewport>
          </SelectPrimitive.Content>
        </SelectPrimitive.Portal>
      </SelectPrimitive.Root>
      {error && <p className="text-xs text-[var(--aq-danger-600)]" role="alert">{error}</p>}
      {hint && !error && <p className="text-xs text-[var(--aq-text-muted)]">{hint}</p>}
    </div>
  );
}

export { AtomyQSelect };
