import React from 'react';
import { CheckCircle2, AlertTriangle, AlertCircle, Info, X } from 'lucide-react';

// ─── Alert / Banner ───────────────────────────────────────────────────────────

type AlertVariant = 'success' | 'warning' | 'error' | 'info' | 'neutral';

interface AlertProps {
  variant: AlertVariant;
  title?: string;
  children?: React.ReactNode;
  onClose?: () => void;
  actions?: React.ReactNode;
  className?: string;
  compact?: boolean;
  list?: string[];      // bullet list inside alert
}

const ALERT_CONFIG: Record<AlertVariant, {
  bg: string; border: string; icon: React.ReactNode; titleColor: string; textColor: string;
}> = {
  success: {
    bg: 'bg-green-50', border: 'border-green-200',
    icon: <CheckCircle2 size={14} className="text-green-600 shrink-0 mt-0.5" />,
    titleColor: 'text-green-800', textColor: 'text-green-700',
  },
  warning: {
    bg: 'bg-amber-50', border: 'border-amber-200',
    icon: <AlertTriangle size={14} className="text-amber-600 shrink-0 mt-0.5" />,
    titleColor: 'text-amber-800', textColor: 'text-amber-700',
  },
  error: {
    bg: 'bg-red-50', border: 'border-red-200',
    icon: <AlertCircle size={14} className="text-red-600 shrink-0 mt-0.5" />,
    titleColor: 'text-red-800', textColor: 'text-red-700',
  },
  info: {
    bg: 'bg-blue-50', border: 'border-blue-200',
    icon: <Info size={14} className="text-blue-600 shrink-0 mt-0.5" />,
    titleColor: 'text-blue-800', textColor: 'text-blue-700',
  },
  neutral: {
    bg: 'bg-slate-50', border: 'border-slate-200',
    icon: <Info size={14} className="text-slate-500 shrink-0 mt-0.5" />,
    titleColor: 'text-slate-700', textColor: 'text-slate-600',
  },
};

export function Alert({ variant, title, children, onClose, actions, className = '', compact = false, list }: AlertProps) {
  const config = ALERT_CONFIG[variant];

  return (
    <div
      className={[
        'flex gap-3 rounded-lg border',
        config.bg,
        config.border,
        compact ? 'px-3 py-2.5' : 'px-4 py-3.5',
        className,
      ].join(' ')}
      role="alert"
    >
      {config.icon}
      <div className="flex-1 min-w-0">
        {title && (
          <p className={['text-sm font-semibold leading-tight', config.titleColor].join(' ')}>
            {title}
          </p>
        )}
        {children && (
          <div className={['text-sm mt-0.5', config.textColor, title ? '' : 'font-medium'].join(' ')}>
            {children}
          </div>
        )}
        {list && (
          <ul className={['mt-1 space-y-0.5', config.textColor].join(' ')}>
            {list.map((item, i) => (
              <li key={i} className="flex items-start gap-1.5 text-xs">
                <span className="mt-1.5 w-1 h-1 rounded-full bg-current shrink-0" />
                {item}
              </li>
            ))}
          </ul>
        )}
        {actions && <div className="mt-2 flex items-center gap-2">{actions}</div>}
      </div>
      {onClose && (
        <button
          onClick={onClose}
          className={['shrink-0 p-0.5 rounded hover:opacity-70 transition-opacity', config.textColor].join(' ')}
        >
          <X size={14} />
        </button>
      )}
    </div>
  );
}

// ─── Banner (full-width, inside content area) ─────────────────────────────────

interface BannerProps {
  variant: AlertVariant;
  children: React.ReactNode;
  action?: React.ReactNode;
  className?: string;
}

const BANNER_BG: Record<AlertVariant, string> = {
  success: 'bg-green-600 text-white',
  warning: 'bg-amber-100 text-amber-900 border-b border-amber-200',
  error:   'bg-red-600 text-white',
  info:    'bg-indigo-600 text-white',
  neutral: 'bg-slate-100 text-slate-700 border-b border-slate-200',
};

export function Banner({ variant, children, action, className = '' }: BannerProps) {
  return (
    <div className={['flex items-center justify-between px-5 py-2.5 text-sm font-medium', BANNER_BG[variant], className].join(' ')}>
      <span>{children}</span>
      {action && <span className="ml-4 shrink-0">{action}</span>}
    </div>
  );
}

// ─── Checklist Item ───────────────────────────────────────────────────────────

type ChecklistStatus = 'pass' | 'warning' | 'fail' | 'pending';

interface ChecklistItemProps {
  label: string;
  status: ChecklistStatus;
  detail?: string;
}

const CHECKLIST_CONFIG: Record<ChecklistStatus, { icon: React.ReactNode; color: string }> = {
  pass:    { icon: <CheckCircle2 size={13} />, color: 'text-green-600' },
  warning: { icon: <AlertTriangle size={13} />, color: 'text-amber-600' },
  fail:    { icon: <AlertCircle size={13} />, color: 'text-red-600' },
  pending: { icon: <div className="w-3 h-3 rounded-full border-2 border-slate-300" />, color: 'text-slate-400' },
};

export function ChecklistItem({ label, status, detail }: ChecklistItemProps) {
  const { icon, color } = CHECKLIST_CONFIG[status];

  return (
    <div className="flex items-start gap-2 py-1.5">
      <span className={['mt-0.5 shrink-0', color].join(' ')}>{icon}</span>
      <div>
        <span className="text-sm text-slate-700">{label}</span>
        {detail && <p className={['text-xs mt-0.5', color].join(' ')}>{detail}</p>}
      </div>
    </div>
  );
}

export function Checklist({ items }: { items: ChecklistItemProps[] }) {
  return (
    <div className="flex flex-col divide-y divide-slate-100">
      {items.map((item, i) => <ChecklistItem key={i} {...item} />)}
    </div>
  );
}
