'use client';

import React from 'react';
import { Bot } from 'lucide-react';
import { usePathname } from 'next/navigation';

import { Button } from '@/components/ds/Button';
import { useAiNarrativeSummary } from '@/hooks/use-ai-narrative-summary';
import { AI_INSIGHT_GLOW } from '@/lib/ai-derived-style';
import { resolveSmartAiInsightRoute } from '@/lib/ai-insight-routes';

export function SmartAiInsightsButton() {
  const pathname = usePathname();
  const route = resolveSmartAiInsightRoute(pathname);
  const [confirmOpen, setConfirmOpen] = React.useState(false);
  const insight = useAiNarrativeSummary(route?.queryPath ?? '', route?.featureKey ?? '__unsupported_ai_insights__', {
    enabled: route !== null,
    queryKey: route?.queryKey ?? ['smart-ai-insights', 'unsupported'],
    generatePath: route?.generatePath,
    generatePayload: route?.generatePayload,
  });

  const hasSummary = insight.summary?.available === true;
  const isSupported = route !== null && insight.canGenerate;
  const label = !isSupported
    ? 'AI insights unavailable'
    : insight.isGenerating
      ? 'Generating AI insights'
      : hasSummary
        ? 'Regenerate AI insights'
        : 'Generate AI insights';

  function handleClick() {
    if (!isSupported || insight.isGenerating || insight.generate === null) {
      return;
    }

    if (hasSummary) {
      setConfirmOpen(true);
      return;
    }

    insight.generate();
  }

  function handleRegenerate() {
    setConfirmOpen(false);
    insight.generate?.();
  }

  return (
    <div className="relative">
      <Button
        size="sm"
        variant={isSupported ? 'outline' : 'ghost'}
        icon={<Bot size={14} />}
        aria-label={label}
        title={isSupported ? 'Generate AI insights for this page' : 'AI insights unavailable on this page'}
        onClick={handleClick}
        loading={insight.isGenerating}
        disabled={!isSupported || insight.isGenerating}
        className={isSupported ? AI_INSIGHT_GLOW : 'text-slate-400'}
      >
        AI Insights
      </Button>

      {confirmOpen ? (
        <div
          role="dialog"
          aria-label="Regenerate AI insights"
          className="absolute right-0 top-[calc(100%+0.5rem)] z-30 w-72 rounded-lg border border-purple-200 bg-white p-3 shadow-[0_12px_30px_rgba(15,23,42,0.18)]"
        >
          <p className="text-sm font-semibold text-slate-900">Regenerate AI insights?</p>
          <p className="mt-1 text-xs text-slate-500">
            Existing AI-derived insight will be replaced with a fresh provider response.
          </p>
          <div className="mt-3 flex justify-end gap-2">
            <Button size="sm" variant="ghost" onClick={() => setConfirmOpen(false)}>
              Cancel
            </Button>
            <Button size="sm" variant="primary" onClick={handleRegenerate}>
              Regenerate
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
