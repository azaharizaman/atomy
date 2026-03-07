import * as React from 'react';
import { Check, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface StepItem {
  id: string;
  label: string;
  status: 'completed' | 'active' | 'pending' | 'error';
  description?: string;
}

export interface AtomyQStepperProps {
  steps: StepItem[];
  orientation?: 'horizontal' | 'vertical';
  className?: string;
}

const statusConfig = {
  completed: {
    dot: 'bg-[var(--aq-success-600)] text-white',
    label: 'text-[var(--aq-text-primary)]',
    line: 'bg-[var(--aq-success-600)]',
  },
  active: {
    dot: 'bg-[var(--aq-brand-600)] text-white ring-4 ring-[var(--aq-brand-100)]',
    label: 'text-[var(--aq-brand-600)] font-semibold',
    line: 'bg-[var(--aq-border-default)]',
  },
  pending: {
    dot: 'bg-[var(--aq-bg-elevated)] text-[var(--aq-text-subtle)] border-2 border-[var(--aq-border-default)]',
    label: 'text-[var(--aq-text-muted)]',
    line: 'bg-[var(--aq-border-default)]',
  },
  error: {
    dot: 'bg-[var(--aq-danger-600)] text-white',
    label: 'text-[var(--aq-danger-600)]',
    line: 'bg-[var(--aq-danger-200)]',
  },
};

function AtomyQStepper({ steps, orientation = 'horizontal', className }: AtomyQStepperProps) {
  if (orientation === 'vertical') {
    return (
      <div className={cn('flex flex-col', className)}>
        {steps.map((step, idx) => {
          const config = statusConfig[step.status];
          const isLast = idx === steps.length - 1;
          return (
            <div key={step.id} className="flex gap-3">
              <div className="flex flex-col items-center">
                <div className={cn('flex size-7 items-center justify-center rounded-full text-xs shrink-0', config.dot)}>
                  {step.status === 'completed' ? <Check className="size-3.5" /> :
                   step.status === 'error' ? <AlertCircle className="size-3.5" /> :
                   idx + 1}
                </div>
                {!isLast && (
                  <div className={cn('w-0.5 flex-1 min-h-[24px] my-1', config.line)} />
                )}
              </div>
              <div className="pb-6">
                <span className={cn('text-sm', config.label)}>{step.label}</span>
                {step.description && (
                  <p className="text-xs text-[var(--aq-text-muted)] mt-0.5">{step.description}</p>
                )}
              </div>
            </div>
          );
        })}
      </div>
    );
  }

  return (
    <div className={cn('flex items-center', className)}>
      {steps.map((step, idx) => {
        const config = statusConfig[step.status];
        const isLast = idx === steps.length - 1;
        return (
          <React.Fragment key={step.id}>
            <div className="flex flex-col items-center gap-1.5">
              <div className={cn('flex size-7 items-center justify-center rounded-full text-xs shrink-0', config.dot)}>
                {step.status === 'completed' ? <Check className="size-3.5" /> :
                 step.status === 'error' ? <AlertCircle className="size-3.5" /> :
                 idx + 1}
              </div>
              <span className={cn('text-xs whitespace-nowrap', config.label)}>{step.label}</span>
            </div>
            {!isLast && (
              <div className={cn('flex-1 h-0.5 mx-2 mt-[-18px]', config.line)} />
            )}
          </React.Fragment>
        );
      })}
    </div>
  );
}

export { AtomyQStepper };
