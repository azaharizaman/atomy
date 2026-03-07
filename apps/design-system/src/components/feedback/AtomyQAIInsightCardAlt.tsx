import * as React from 'react';
import { cn } from '@/lib/utils';
import { Sparkles } from 'lucide-react';

export interface AtomyQAIInsightCardAltProps {
  title?: string;
  children: React.ReactNode;
  actions?: React.ReactNode;
  className?: string;
}

function AtomyQAIInsightCardAlt({
  title,
  children,
  actions,
  className,
}: AtomyQAIInsightCardAltProps) {
  return (
    <div
      className={cn(
        'rounded-lg border border-[var(--aq-purple-tint-15)] bg-[var(--aq-purple-tint-6)] p-3',
        className,
      )}
    >
      <div className="flex items-center gap-2">
        <div className="flex size-5 items-center justify-center rounded bg-[var(--aq-purple-tint-12)]">
          <Sparkles className="size-3 text-[var(--aq-purple-500)]" />
        </div>
        {title && (
          <span className="text-[11px] font-semibold text-[var(--aq-purple-500)]">
            {title}
          </span>
        )}
      </div>
      <div className="mt-2 text-[12px] leading-relaxed text-[var(--aq-text-secondary)]">
        {children}
      </div>
      {actions && <div className="mt-3 flex items-center gap-2">{actions}</div>}
    </div>
  );
}

export { AtomyQAIInsightCardAlt };
