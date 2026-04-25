'use client';

import React from 'react';
import { AlertTriangle, FileCheck2, ShieldCheck } from 'lucide-react';
import { AiNarrativePanel } from '@/components/ai/ai-narrative-panel';
import { EmptyState, SectionCard } from '@/components/ds/Card';
import { PageHeader } from '@/components/ds/FilterBar';
import { formatVendorGovernanceWarning, useVendorGovernance } from '@/hooks/use-vendor-governance';

function scoreLabel(label: string, score: number) {
  return (
    <div className="rounded-md border border-slate-200 bg-white px-4 py-3">
      <p className="text-xs font-medium uppercase text-slate-500">{label}</p>
      <p className="mt-1 text-2xl font-semibold text-slate-900">{score}</p>
    </div>
  );
}

function formatDisplayDate(value: string | null): string {
  if (value === null) {
    return 'n/a';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return 'n/a';
  }

  return date.toLocaleDateString(undefined, { dateStyle: 'medium' });
}

function VendorEsgCompliancePageContent({ vendorId }: { vendorId: string }) {
  const governanceQuery = useVendorGovernance(vendorId);

  if (governanceQuery.isLoading) {
    return <p className="text-sm text-slate-500">Loading governance records...</p>;
  }

  if (governanceQuery.isError || !governanceQuery.data) {
    return (
      <div className="space-y-4">
        <PageHeader title="ESG / Compliance" subtitle="Governance unavailable" />
        <SectionCard title="Governance unavailable">
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load governance data"
            description={
              governanceQuery.error instanceof Error
                ? governanceQuery.error.message
                : 'Vendor governance monitoring is unavailable.'
            }
          />
        </SectionCard>
      </div>
    );
  }

  const governance = governanceQuery.data;

  return (
    <div className="space-y-4">
      <PageHeader title="ESG / Compliance" subtitle={`Vendor ${governance.vendorId}`} />

      <AiNarrativePanel
        featureKey="governance_ai_narrative"
        title="AI Governance Narrative"
        subtitle="Assistive interpretation of the current governance posture."
        summary={governance.narrative}
        fallbackCopy="Governance narrative is unavailable. Continue with the evidence and findings below."
      />

      <SectionCard title="Health scores" subtitle="Monitoring signals are advisory and do not automatically change eligibility">
        <div className="grid gap-3 md:grid-cols-4">
          {scoreLabel('ESG', governance.scores.esgScore)}
          {scoreLabel('Compliance', governance.scores.complianceHealthScore)}
          {scoreLabel('Risk watch', governance.scores.riskWatchScore)}
          {scoreLabel('Evidence freshness', governance.scores.evidenceFreshnessScore)}
        </div>
      </SectionCard>

      <SectionCard title="Warning flags">
        {governance.warningFlags.length === 0 ? (
          <p className="text-sm text-slate-500">No governance warning flags.</p>
        ) : (
          <div className="flex flex-wrap gap-2">
            {governance.warningFlags.map((flag) => (
              <span key={flag} className="rounded border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800">
                {formatVendorGovernanceWarning(flag)}
              </span>
            ))}
          </div>
        )}
      </SectionCard>

      <SectionCard title="Evidence registry" subtitle={`${governance.evidence.length} records`}>
        {governance.evidence.length === 0 ? (
          <EmptyState icon={<FileCheck2 size={20} />} title="No evidence recorded" description="Add evidence through the governance API when reviews are completed." />
        ) : (
          <div className="space-y-2">
            {governance.evidence.map((record) => (
              <div key={record.id} className="rounded-md border border-slate-200 px-4 py-3 text-sm">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <p className="font-medium text-slate-800">{record.title}</p>
                  <span className="rounded border border-slate-200 px-2 py-0.5 text-xs text-slate-600">{record.reviewStatus}</span>
                </div>
                <p className="mt-1 text-xs text-slate-500">
                  {record.domain} / {record.type} / {record.source}
                </p>
                <p className="mt-1 text-xs text-slate-500">
                  Observed {formatDisplayDate(record.observedAt)} · Expires {formatDisplayDate(record.expiresAt)}
                </p>
              </div>
            ))}
          </div>
        )}
      </SectionCard>

      <SectionCard title="Findings" subtitle={`${governance.findings.length} records`}>
        {governance.findings.length === 0 ? (
          <EmptyState icon={<ShieldCheck size={20} />} title="No open findings recorded" description="Risk, compliance, and ESG findings will appear here when present." />
        ) : (
          <div className="space-y-2">
            {governance.findings.map((record) => (
              <div key={record.id} className="rounded-md border border-slate-200 px-4 py-3 text-sm">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <p className="font-medium text-slate-800">{record.issueType}</p>
                  <span className="rounded border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">
                    {record.severity} / {record.status}
                  </span>
                </div>
                <p className="mt-1 text-xs text-slate-500">
                  {record.domain} · remediation owner {record.remediationOwner ?? 'unassigned'} · due {record.remediationDueAt ?? 'n/a'}
                </p>
              </div>
            ))}
          </div>
        )}
      </SectionCard>
    </div>
  );
}

export default function VendorEsgCompliancePage({ params }: { params: Promise<{ vendorId: string }> }) {
  const { vendorId } = React.use(params);
  return <VendorEsgCompliancePageContent vendorId={vendorId} />;
}
