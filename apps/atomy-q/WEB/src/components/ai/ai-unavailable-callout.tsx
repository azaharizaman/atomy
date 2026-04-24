'use client';

import React from 'react';
import { AlertTriangle } from 'lucide-react';

const MESSAGE_COPY: Record<string, string> = {
  'ai.status.off': 'AI features are disabled for this environment.',
  'ai.status.degraded': 'AI features are temporarily degraded. Continue with the manual path where needed.',
  'ai.status.mock_mode': 'AI is running in deterministic mock mode. Use the manual path for AI-assisted workflows.',
  'ai.status.unavailable': 'AI status could not be loaded. Continue without AI or retry when the service recovers.',
  'ai.status.provider_unavailable': 'The configured AI provider is temporarily unavailable.',
  'ai.feature.unavailable': 'This AI feature is not available right now.',
};

export function AiUnavailableCallout({
  title = 'AI unavailable',
  messageKey,
  fallbackCopy,
  actions,
  className = '',
}: {
  title?: string;
  messageKey?: string | null;
  fallbackCopy?: string;
  actions?: React.ReactNode;
  className?: string;
}) {
  const description =
    (messageKey ? MESSAGE_COPY[messageKey] : undefined) ??
    fallbackCopy ??
    'This AI capability is unavailable right now.';

  return (
    <section
      className={[
        'rounded-lg border border-amber-200 bg-amber-50/70 p-3 shadow-[0_1px_3px_0_rgba(0,0,0,0.04)]',
        className,
      ].join(' ')}
    >
      <div className="flex items-start gap-3">
        <div className="mt-0.5 shrink-0 text-amber-600" aria-hidden="true">
          <AlertTriangle size={16} />
        </div>
        <div className="min-w-0 flex-1 space-y-2">
          <div className="space-y-1">
            <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
            <p className="text-sm text-slate-600">{description}</p>
          </div>
          {messageKey ? (
            <div className="inline-flex max-w-full rounded border border-amber-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-500">
              <span className="truncate">{messageKey}</span>
            </div>
          ) : null}
          {actions ? <div className="flex flex-wrap items-center gap-2 pt-1">{actions}</div> : null}
        </div>
      </div>
    </section>
  );
}
