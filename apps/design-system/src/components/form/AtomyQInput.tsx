import * as React from 'react';
import { cn } from '@/lib/utils';
import { AlertCircle } from 'lucide-react';

export interface AtomyQInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  hint?: string;
  error?: string;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

const AtomyQInput = React.forwardRef<HTMLInputElement, AtomyQInputProps>(
  ({ className, type = 'text', label, hint, error, leftIcon, rightIcon, id, ...props }, ref) => {
    const inputId = id || React.useId();
    const errorId = error ? `${inputId}-error` : undefined;
    const hintId = hint ? `${inputId}-hint` : undefined;

    return (
      <div className="flex flex-col gap-1.5">
        {label && (
          <label
            htmlFor={inputId}
            className="text-sm font-medium text-[var(--aq-text-primary)]"
          >
            {label}
            {props.required && <span className="text-[var(--aq-danger-500)] ml-0.5">*</span>}
          </label>
        )}
        <div className="relative">
          {leftIcon && (
            <div className="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--aq-text-subtle)] [&_svg]:size-4">
              {leftIcon}
            </div>
          )}
          <input
            ref={ref}
            id={inputId}
            type={type}
            aria-invalid={!!error}
            aria-describedby={[errorId, hintId].filter(Boolean).join(' ') || undefined}
            className={cn(
              'flex h-9 w-full rounded-md border border-[var(--aq-border-default)] bg-[var(--input-background)] px-3 py-1 text-sm transition-colors outline-none',
              'placeholder:text-[var(--aq-text-subtle)]',
              'focus-visible:border-[var(--aq-brand-500)] focus-visible:ring-2 focus-visible:ring-[var(--aq-brand-500)]/20',
              'disabled:cursor-not-allowed disabled:opacity-50',
              error && 'border-[var(--aq-danger-500)] focus-visible:ring-[var(--aq-danger-500)]/20',
              leftIcon && 'pl-9',
              rightIcon && 'pr-9',
              className
            )}
            {...props}
          />
          {rightIcon && !error && (
            <div className="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--aq-text-subtle)] [&_svg]:size-4">
              {rightIcon}
            </div>
          )}
          {error && (
            <div className="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--aq-danger-500)]">
              <AlertCircle className="size-4" />
            </div>
          )}
        </div>
        {error && (
          <p id={errorId} className="text-xs text-[var(--aq-danger-600)]" role="alert">
            {error}
          </p>
        )}
        {hint && !error && (
          <p id={hintId} className="text-xs text-[var(--aq-text-muted)]">
            {hint}
          </p>
        )}
      </div>
    );
  }
);

AtomyQInput.displayName = 'AtomyQInput';

export { AtomyQInput };
