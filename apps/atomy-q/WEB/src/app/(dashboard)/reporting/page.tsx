'use client';

import React from 'react';
import { BarChart2, Download, TrendingUp } from 'lucide-react';
import { AiNarrativePanel } from '@/components/ai/ai-narrative-panel';
import { Button } from '@/components/ds/Button';
import { PageHeader } from '@/components/ds/FilterBar';
import { useReportingAiSummary } from '@/hooks/use-reporting-ai-summary';

export default function ReportingPage() {
  const reportingAiSummary = useReportingAiSummary();

  return (
    <div className="space-y-6">
      <PageHeader
        title="Reporting"
        subtitle="KPIs, spend trends, and exportable reports."
        actions={
          <Button size="sm" variant="outline" disabled>
            <Download size={14} className="mr-1.5" />
            Export
          </Button>
        }
      />

      <AiNarrativePanel
        featureKey="dashboard_ai_summary"
        title="Reporting Summary"
        subtitle="Assistive interpretation of the deterministic reporting surface."
        summary={reportingAiSummary.summary}
        isLoading={reportingAiSummary.isLoading}
        isError={reportingAiSummary.isError}
        error={reportingAiSummary.error}
        fallbackCopy="Reporting summaries are unavailable. Deterministic reporting remains usable."
      />

      <div className="rounded-lg border border-slate-200 bg-white p-8">
        <div className="flex flex-col items-center justify-center text-center">
          <div className="rounded-full bg-slate-100 p-4">
            <BarChart2 size={32} className="text-slate-500" />
          </div>
          <h2 className="mt-4 text-lg font-semibold text-slate-900">Reports & analytics</h2>
          <p className="mt-2 max-w-sm text-sm text-slate-500">
            View spend by category, vendor scores, and cycle-time reports. AI summaries remain optional and never replace the factual reporting surface.
          </p>
          <div className="mt-6 flex gap-2">
            <Button size="sm" variant="outline" disabled>
              <TrendingUp size={14} className="mr-1.5" />
              Spend trend
            </Button>
            <Button size="sm" variant="outline" disabled>
              <BarChart2 size={14} className="mr-1.5" />
              Vendor scores
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}
