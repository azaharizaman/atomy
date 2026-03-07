import * as React from 'react';
import { cn } from '@/lib/utils';
import {
  CheckCircle2,
  Circle,
  AlertTriangle,
  ArrowRight,
} from 'lucide-react';

export interface ChecklistItemAlt {
  id: string;
  label: string;
  checked: boolean;
}

export interface StakeholderFeedbackAlt {
  name: string;
  initials: string;
  status: 'approved' | 'pending' | 'flagged';
  comment?: string;
}

export interface AtomyQOversightPanelAltProps {
  title?: string;
  verdictLabel?: string;
  verdictValue?: string;
  verdictDescription?: string;
  checklist?: ChecklistItemAlt[];
  onChecklistToggle?: (id: string) => void;
  stakeholders?: StakeholderFeedbackAlt[];
  ctaLabel?: string;
  onCtaClick?: () => void;
  children?: React.ReactNode;
  className?: string;
}

function AtomyQOversightPanelAlt({
  title = 'Oversight Panel',
  verdictLabel,
  verdictValue,
  verdictDescription,
  checklist = [],
  onChecklistToggle,
  stakeholders = [],
  ctaLabel,
  onCtaClick,
  children,
  className,
}: AtomyQOversightPanelAltProps) {
  const checkedCount = checklist.filter((c) => c.checked).length;
  const progress =
    checklist.length > 0 ? (checkedCount / checklist.length) * 100 : 0;

  return (
    <div
      className={cn(
        'flex h-full w-[380px] flex-col border-l border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)]',
        className,
      )}
      style={{ boxShadow: '-8px 0 24px rgba(0, 0, 0, 0.06)' }}
    >
      <div className="border-b border-[var(--aq-border-strong)] bg-[var(--aq-bg-elevated)] p-4">
        <h3 className="text-sm font-bold text-[var(--aq-text-primary)]">
          {title}
        </h3>
      </div>

      <div className="flex-1 space-y-5 overflow-y-auto p-4">
        {verdictValue && (
          <div>
            <div className="mb-2 text-[11px] font-bold uppercase tracking-[0.05em] text-[var(--aq-text-muted)]">
              {verdictLabel ?? 'Decision Verdict'}
            </div>
            <div className="rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-elevated)] p-3">
              <div className="text-sm font-bold text-[var(--aq-text-primary)]">
                {verdictValue}
              </div>
              {verdictDescription && (
                <p className="mt-1 text-[11px] text-[var(--aq-text-muted)]">
                  {verdictDescription}
                </p>
              )}
            </div>
          </div>
        )}

        {checklist.length > 0 && (
          <div>
            <div className="mb-2 flex items-center justify-between">
              <span className="text-[11px] font-bold uppercase tracking-[0.05em] text-[var(--aq-text-muted)]">
                Checklist
              </span>
              <span className="text-[11px] text-[var(--aq-text-subtle)]">
                {checkedCount}/{checklist.length}
              </span>
            </div>
            <div className="mb-3 h-1.5 overflow-hidden rounded-full bg-[var(--aq-bg-elevated)]">
              <div
                className="h-full rounded-full bg-[var(--aq-success-500)] transition-all duration-300"
                style={{ width: `${progress}%` }}
              />
            </div>
            <div className="space-y-1.5">
              {checklist.map((item) => (
                <button
                  key={item.id}
                  onClick={() => onChecklistToggle?.(item.id)}
                  className={cn(
                    'flex w-full items-center gap-2.5 rounded-lg border p-2.5 text-left transition-colors',
                    item.checked
                      ? 'border-[var(--aq-border-strong)] bg-[var(--aq-bg-elevated)]'
                      : 'border-[var(--aq-border-default)] bg-[var(--aq-bg-surface)] hover:bg-[var(--aq-hover-soft)]',
                  )}
                >
                  {item.checked ? (
                    <CheckCircle2 className="size-4 shrink-0 text-[var(--aq-success-500)]" />
                  ) : (
                    <Circle className="size-4 shrink-0 text-[var(--aq-text-subtle)]" />
                  )}
                  <span
                    className={cn(
                      'text-[12px]',
                      item.checked
                        ? 'text-[var(--aq-text-muted)] line-through'
                        : 'text-[var(--aq-text-secondary)]',
                    )}
                  >
                    {item.label}
                  </span>
                </button>
              ))}
            </div>
          </div>
        )}

        {stakeholders.length > 0 && (
          <div>
            <div className="mb-2 text-[11px] font-bold uppercase tracking-[0.05em] text-[var(--aq-text-muted)]">
              Stakeholder Feedback
            </div>
            <div className="space-y-2">
              {stakeholders.map((s, i) => (
                <div
                  key={i}
                  className="flex items-start gap-2.5 rounded-lg border border-[var(--aq-border-default)] p-2.5"
                >
                  <div className="flex size-7 shrink-0 items-center justify-center rounded-full bg-[var(--aq-bg-elevated)] text-[10px] font-bold text-[var(--aq-text-muted)]">
                    {s.initials}
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="text-[12px] font-medium text-[var(--aq-text-primary)]">
                        {s.name}
                      </span>
                      {s.status === 'approved' && (
                        <CheckCircle2 className="size-3 text-[var(--aq-success-500)]" />
                      )}
                      {s.status === 'flagged' && (
                        <AlertTriangle className="size-3 text-[var(--aq-warning-500)]" />
                      )}
                    </div>
                    {s.comment && (
                      <p className="mt-0.5 text-[11px] text-[var(--aq-text-muted)]">
                        {s.comment}
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {children}
      </div>

      {ctaLabel && (
        <div className="border-t border-[var(--aq-border-strong)] p-4">
          <button
            onClick={onCtaClick}
            className="flex w-full items-center justify-center gap-2 rounded-xl bg-[var(--aq-indigo-500)] py-3 text-[12px] font-bold uppercase tracking-widest text-white transition-opacity hover:opacity-90"
          >
            {ctaLabel}
            <ArrowRight className="size-3.5" />
          </button>
        </div>
      )}
    </div>
  );
}

export { AtomyQOversightPanelAlt };
