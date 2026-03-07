import * as React from 'react';
import { cn } from '@/lib/utils';
import { X } from 'lucide-react';

export interface AtomyQSlideOverAltProps {
  open: boolean;
  onClose: () => void;
  title: string;
  subtitle?: string;
  children: React.ReactNode;
  className?: string;
}

function AtomyQSlideOverAlt({ open, onClose, title, subtitle, children, className }: AtomyQSlideOverAltProps) {
  React.useEffect(() => {
    if (!open) return;
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      <div
        className="absolute inset-0 bg-[var(--aq-overlay)]"
        onClick={onClose}
        aria-hidden="true"
      />
      <div
        className={cn(
          'relative z-10 flex h-full w-[34vw] min-w-[420px] max-w-[620px] flex-col border-l border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)]',
          'animate-in slide-in-from-right duration-200',
          className
        )}
        style={{ boxShadow: '-16px 0 40px rgba(2, 6, 23, 0.2)' }}
        role="dialog"
        aria-modal="true"
        aria-label={title}
      >
        <div className="flex items-start justify-between border-b border-[var(--aq-border-strong)] px-5 py-4">
          <div>
            <h2 className="text-base font-bold text-[var(--aq-text-primary)]">{title}</h2>
            {subtitle && (
              <p className="mt-0.5 text-[11px] text-[var(--aq-text-muted)]">{subtitle}</p>
            )}
          </div>
          <button
            onClick={onClose}
            className="rounded-lg p-1.5 text-[var(--aq-text-subtle)] transition-colors hover:bg-[var(--aq-hover-soft)] hover:text-[var(--aq-text-primary)]"
            aria-label="Close panel"
          >
            <X className="size-4" />
          </button>
        </div>
        <div className="flex-1 overflow-auto p-5">{children}</div>
      </div>
    </div>
  );
}

export { AtomyQSlideOverAlt };
