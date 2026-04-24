'use client';

import React from 'react';

import { AiStatusChip } from '@/components/ai/ai-status-chip';
import { AiUnavailableCallout } from '@/components/ai/ai-unavailable-callout';
import { SectionCard } from '@/components/ds/Card';
import { useAiStatus } from '@/hooks/use-ai-status';
import type { AiNarrativeSummary } from '@/hooks/use-ai-narrative-summary';

function hashText(value: string): string {
  let hash = 0;
  for (let index = 0; index < value.length; index += 1) {
    hash = ((hash << 5) - hash) + value.charCodeAt(index);
    hash |= 0;
  }

  return Math.abs(hash).toString(36);
}

function formatGeneratedAt(value: string | null): string | null {
  if (value === null) {
    return null;
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return new Intl.DateTimeFormat(globalThis.navigator?.language ?? 'en-US', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date);
}

function renderProvenance(summary: AiNarrativeSummary): React.ReactNode {
  const provider = typeof summary.provenance?.provider === 'string'
    ? summary.provenance.provider
    : (typeof summary.provenance?.provider_name === 'string' ? summary.provenance.provider_name : null);
  const endpointGroup = typeof summary.provenance?.endpoint_group === 'string' ? summary.provenance.endpoint_group : null;
  const generatedAt = formatGeneratedAt(
    typeof summary.provenance?.generated_at === 'string' ? summary.provenance.generated_at : null,
  );

  if (!provider && !endpointGroup && !generatedAt) {
    return null;
  }

  return (
    <p className="text-[11px] text-slate-500">
      {provider ? <span>Source: {provider}</span> : null}
      {provider && endpointGroup ? ' · ' : null}
      {endpointGroup ? <span>Endpoint: {endpointGroup}</span> : null}
      {(provider || endpointGroup) && generatedAt ? ' · ' : null}
      {generatedAt ? <span>Generated: {generatedAt}</span> : null}
    </p>
  );
}

export interface AiNarrativePanelProps {
  featureKey: string;
  title: string;
  subtitle?: string;
  summary: AiNarrativeSummary | null;
  isLoading?: boolean;
  isError?: boolean;
  error?: Error | null;
  fallbackCopy?: string;
  className?: string;
}

export function AiNarrativePanel({
  featureKey,
  title,
  subtitle,
  summary,
  isLoading = false,
  isError = false,
  error = null,
  fallbackCopy = 'AI narrative is unavailable.',
  className = '',
}: AiNarrativePanelProps) {
  const aiStatus = useAiStatus();
  React.useEffect(() => {
    if (isError && error instanceof Error) {
      // eslint-disable-next-line no-console
      console.error('AI narrative panel error', error);
    }
  }, [error, isError]);

  if (aiStatus.shouldHideAiControls(featureKey)) {
    return null;
  }

  const showUnavailableMessage = aiStatus.shouldShowUnavailableMessage(featureKey);
  const messageKey = aiStatus.messageKeyForFeature(featureKey);
  const hasAvailableSummary = summary?.available === true;
  const bullets = Array.isArray(summary?.bullets) ? summary.bullets : [];
  const bulletCounts = new Map<string, number>();

  return (
    <SectionCard
      title={title}
      subtitle={subtitle}
      className={className}
      actions={hasAvailableSummary ? <AiStatusChip tone="available" label="AI-derived" /> : null}
    >
      {hasAvailableSummary ? (
        <div className="space-y-3">
          {summary.headline ? <p className="text-sm font-medium text-slate-800">{summary.headline}</p> : null}
          {summary.summary ? <p className="text-sm text-slate-600">{summary.summary}</p> : null}
          {bullets.length > 0 ? (
            <ul className="space-y-1.5 text-sm text-slate-600">
              {bullets.map((bullet) => {
                const nextCount = (bulletCounts.get(bullet) ?? 0) + 1;
                bulletCounts.set(bullet, nextCount);

                return (
                <li key={`${hashText(bullet)}-${nextCount}`} className="flex items-start gap-2">
                  <span className="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400" aria-hidden="true" />
                  <span>{bullet}</span>
                </li>
                );
              })}
            </ul>
          ) : null}
          {renderProvenance(summary)}
        </div>
      ) : isLoading ? (
        <p className="text-sm text-slate-500">Loading AI summary...</p>
      ) : showUnavailableMessage || isError ? (
        <AiUnavailableCallout
          title={`${title} unavailable`}
          messageKey={messageKey}
          fallbackCopy={fallbackCopy}
        />
      ) : null
      }
    </SectionCard>
  );
}
