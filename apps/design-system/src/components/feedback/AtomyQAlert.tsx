import * as React from 'react';
import { CheckCircle, AlertCircle, AlertTriangle, Info, X } from 'lucide-react';
import { cn } from '@/lib/utils';

export type AlertVariant = 'success' | 'error' | 'warning' | 'info';

export interface AtomyQAlertProps {
  title?: string;
  children: React.ReactNode;
  variant?: AlertVariant;
  dismissible?: boolean;
  onDismiss?: () => void;
  action?: { label: string; onClick: () => void };
  className?: string;
}

const alertConfig = {
  success: {
    icon: CheckCircle,
    bg: 'bg-[var(--aq-success-50)]',
    border: 'border-[var(--aq-success-500)]/30',
    iconColor: 'text-[var(--aq-success-600)]',
    titleColor: 'text-[var(--aq-success-700)]',
  },
  error: {
    icon: AlertCircle,
    bg: 'bg-[var(--aq-danger-50)]',
    border: 'border-[var(--aq-danger-500)]/30',
    iconColor: 'text-[var(--aq-danger-600)]',
    titleColor: 'text-[var(--aq-danger-700)]',
  },
  warning: {
    icon: AlertTriangle,
    bg: 'bg-[var(--aq-warning-50)]',
    border: 'border-[var(--aq-warning-500)]/30',
    iconColor: 'text-[var(--aq-warning-600)]',
    titleColor: 'text-[var(--aq-warning-700)]',
  },
  info: {
    icon: Info,
    bg: 'bg-[var(--aq-info-50)]',
    border: 'border-[var(--aq-info-500)]/30',
    iconColor: 'text-[var(--aq-info-600)]',
    titleColor: 'text-[var(--aq-info-600)]',
  },
};

function AtomyQAlert({ title, children, variant = 'info', dismissible = false, onDismiss, action, className }: AtomyQAlertProps) {
  const config = alertConfig[variant];
  const Icon = config.icon;

  return (
    <div
      role="alert"
      className={cn(
        'flex gap-3 rounded-lg border p-4',
        config.bg,
        config.border,
        className
      )}
    >
      <Icon className={cn('size-5 shrink-0 mt-0.5', config.iconColor)} />
      <div className="flex-1 min-w-0">
        {title && (
          <p className={cn('text-sm font-medium', config.titleColor)}>{title}</p>
        )}
        <div className="text-sm text-[var(--aq-text-secondary)] mt-0.5">{children}</div>
        {action && (
          <button
            onClick={action.onClick}
            className={cn('text-sm font-medium mt-2', config.titleColor)}
          >
            {action.label} &rarr;
          </button>
        )}
      </div>
      {dismissible && (
        <button
          onClick={onDismiss}
          className="shrink-0 rounded-md p-1 text-[var(--aq-text-muted)] hover:text-[var(--aq-text-primary)] transition-colors"
          aria-label="Dismiss alert"
        >
          <X className="size-4" />
        </button>
      )}
    </div>
  );
}

export { AtomyQAlert };
