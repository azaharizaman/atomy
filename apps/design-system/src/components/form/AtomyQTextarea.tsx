import * as React from 'react';
import { cn } from '@/lib/utils';

export interface AtomyQTextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string;
  hint?: string;
  error?: string;
  maxLength?: number;
  showCount?: boolean;
}

const AtomyQTextarea = React.forwardRef<HTMLTextAreaElement, AtomyQTextareaProps>(
  ({ className, label, hint, error, maxLength, showCount = false, id, value, ...props }, ref) => {
    const textareaId = id || React.useId();
    const currentLength = typeof value === 'string' ? value.length : 0;

    return (
      <div className="flex flex-col gap-1.5">
        {label && (
          <label htmlFor={textareaId} className="text-sm font-medium text-[var(--aq-text-primary)]">
            {label}
            {props.required && <span className="text-[var(--aq-danger-500)] ml-0.5">*</span>}
          </label>
        )}
        <textarea
          ref={ref}
          id={textareaId}
          value={value}
          maxLength={maxLength}
          aria-invalid={!!error}
          className={cn(
            'flex min-h-[80px] w-full rounded-md border border-[var(--aq-border-default)] bg-[var(--input-background)] px-3 py-2 text-sm transition-colors outline-none resize-y',
            'placeholder:text-[var(--aq-text-subtle)]',
            'focus-visible:border-[var(--aq-brand-500)] focus-visible:ring-2 focus-visible:ring-[var(--aq-brand-500)]/20',
            'disabled:cursor-not-allowed disabled:opacity-50',
            error && 'border-[var(--aq-danger-500)]',
            className
          )}
          {...props}
        />
        <div className="flex justify-between">
          <div>
            {error && <p className="text-xs text-[var(--aq-danger-600)]" role="alert">{error}</p>}
            {hint && !error && <p className="text-xs text-[var(--aq-text-muted)]">{hint}</p>}
          </div>
          {showCount && maxLength && (
            <p className="text-xs text-[var(--aq-text-subtle)]">
              {currentLength}/{maxLength}
            </p>
          )}
        </div>
      </div>
    );
  }
);

AtomyQTextarea.displayName = 'AtomyQTextarea';

export { AtomyQTextarea };
