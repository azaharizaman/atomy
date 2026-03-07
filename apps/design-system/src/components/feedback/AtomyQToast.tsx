import * as React from 'react';
import { CheckCircle, AlertCircle, AlertTriangle, Info, X } from 'lucide-react';
import { cn } from '@/lib/utils';

export type ToastVariant = 'success' | 'error' | 'warning' | 'info';

export interface AtomyQToastProps {
  title: string;
  message?: string;
  variant?: ToastVariant;
  onDismiss?: () => void;
  action?: { label: string; onClick: () => void };
  className?: string;
}

const toastConfig = {
  success: {
    icon: CheckCircle,
    border: 'border-l-[var(--aq-success-500)]',
    iconColor: 'text-[var(--aq-success-600)]',
    bg: 'bg-white',
  },
  error: {
    icon: AlertCircle,
    border: 'border-l-[var(--aq-danger-500)]',
    iconColor: 'text-[var(--aq-danger-600)]',
    bg: 'bg-white',
  },
  warning: {
    icon: AlertTriangle,
    border: 'border-l-[var(--aq-warning-500)]',
    iconColor: 'text-[var(--aq-warning-600)]',
    bg: 'bg-white',
  },
  info: {
    icon: Info,
    border: 'border-l-[var(--aq-info-500)]',
    iconColor: 'text-[var(--aq-info-600)]',
    bg: 'bg-white',
  },
};

function AtomyQToast({ title, message, variant = 'info', onDismiss, action, className }: AtomyQToastProps) {
  const config = toastConfig[variant];
  const Icon = config.icon;

  return (
    <div
      role="alert"
      className={cn(
        'flex items-start gap-3 w-[380px] rounded-lg border border-[var(--aq-border-default)] border-l-4 p-4 shadow-lg',
        config.border,
        config.bg,
        className
      )}
    >
      <Icon className={cn('size-5 shrink-0 mt-0.5', config.iconColor)} />
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium text-[var(--aq-text-primary)]">{title}</p>
        {message && (
          <p className="text-sm text-[var(--aq-text-muted)] mt-0.5">{message}</p>
        )}
        {action && (
          <button
            onClick={action.onClick}
            className="text-sm font-medium text-[var(--aq-brand-600)] hover:text-[var(--aq-brand-700)] mt-2"
          >
            {action.label}
          </button>
        )}
      </div>
      {onDismiss && (
        <button
          onClick={onDismiss}
          className="shrink-0 rounded-md p-1 text-[var(--aq-text-muted)] hover:text-[var(--aq-text-primary)] hover:bg-[var(--aq-bg-elevated)] transition-colors"
          aria-label="Dismiss notification"
        >
          <X className="size-4" />
        </button>
      )}
    </div>
  );
}

export { AtomyQToast };
