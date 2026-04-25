'use client';

import React from 'react';
import { AlertTriangle } from 'lucide-react';
import { AiNarrativePanel } from '@/components/ai/ai-narrative-panel';
import { PageHeader } from '@/components/ds/FilterBar';
import { EmptyState, SectionCard } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { useRfq } from '@/hooks/use-rfq';
import { useRfqAiSummary } from '@/hooks/use-rfq-ai-summary';

function RiskPageContent({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId, { enabled: true });
  const rfqAiSummary = useRfqAiSummary(rfqId);

  return (
    <div className="space-y-5">
      <PageHeader title="Risk & Compliance" subtitle="Flags and screening results for this RFQ" />
      <AiNarrativePanel
        featureKey="rfq_ai_insights"
        title="Risk Narrative"
        subtitle="Assistive interpretation of current RFQ risk signals."
        summary={rfqAiSummary.summary}
        isLoading={rfqAiSummary.isLoading}
        isError={rfqAiSummary.isError}
        error={rfqAiSummary.error}
        fallbackCopy="Risk insights are unavailable. Continue with manual governance review."
      />
      <SectionCard title="Screening summary">
        <div className="flex items-center gap-2">
          <StatusBadge status="approved" label="No issues" />
          <span className="text-sm text-slate-500">No persisted RFQ risk items are currently recorded.</span>
        </div>
      </SectionCard>
      <SectionCard title="Manual governance review" subtitle="Deterministic review remains authoritative">
        <EmptyState
          icon={<AlertTriangle size={20} />}
          title="Review vendor evidence and findings"
          description="Use the vendor governance surfaces to inspect evidence, findings, sanctions history, and due diligence records."
        />
      </SectionCard>
    </div>
  );
}

export default function RiskPage({ params }: { params: Promise<{ rfqId: string }> }) {
  return <RiskPageContent params={params} />;
}
