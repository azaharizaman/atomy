'use client';

import React from 'react';
import Link from 'next/link';
import { PageHeader } from '@/components/ds/FilterBar';
import { Card, EmptyState, SectionCard, InfoGrid } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { useComparisonRun } from '@/hooks/use-comparison-run';
import { useComparisonRunMatrix } from '@/hooks/use-comparison-run-matrix';
import { useComparisonRunReadiness } from '@/hooks/use-comparison-run-readiness';
import { AlertTriangle, FileText, Layers3 } from 'lucide-react';

function formatTimestamp(value: string | null): string {
  if (!value) {
    return 'Unavailable';
  }

  const parsed = Date.parse(value);
  return Number.isFinite(parsed) ? new Date(parsed).toISOString() : value;
}

function MetricPill({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
      <span className="block text-[10px] font-medium uppercase tracking-wider text-slate-400">{label}</span>
      <span className="mt-0.5 block text-sm font-medium text-slate-800">{value}</span>
    </div>
  );
}

function ReadinessList({
  title,
  items,
  emptyText,
}: {
  title: string;
  items: Array<{ code: string; message: string }>;
  emptyText: string;
}) {
  return (
    <div className="rounded-lg border border-slate-200 bg-white p-3">
      <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{title}</p>
      {items.length === 0 ? (
        <p className="mt-2 text-sm text-slate-600">{emptyText}</p>
      ) : (
        <ul className="mt-2 space-y-2">
          {items.map((item) => (
            <li key={`${title}-${item.code}-${item.message}`} className="rounded-md bg-slate-50 px-3 py-2">
              <p className="text-xs font-medium text-slate-600">{item.code}</p>
              <p className="text-sm text-slate-800">{item.message}</p>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

function SnapshotLinesCard({
  snapshot,
}: {
  snapshot: NonNullable<ReturnType<typeof useComparisonRun>['data']>['snapshot'];
}) {
  if (!snapshot) {
    return (
      <EmptyState
        icon={<FileText size={20} />}
        title="Snapshot not available"
        description="The comparison run did not persist snapshot details."
      />
    );
  }

  return (
    <div className="space-y-4">
      <InfoGrid
        cols={4}
        items={[
          { label: 'RFQ version', value: snapshot.rfqVersion },
          { label: 'Normalized lines', value: snapshot.normalizedLines.length },
          { label: 'Vendors', value: snapshot.vendors.length },
          { label: 'Resolutions', value: snapshot.resolutions.length },
        ]}
      />

      {snapshot.vendors.length > 0 && (
        <div className="grid gap-3 md:grid-cols-2">
          {snapshot.vendors.map((vendor) => (
            <Card key={vendor.vendorId} padding="sm" className="bg-slate-50">
              <p className="text-sm font-semibold text-slate-800">{vendor.vendorName}</p>
              <p className="mt-0.5 text-xs text-slate-500">Submission {vendor.quoteSubmissionId ?? 'Unavailable'}</p>
            </Card>
          ))}
        </div>
      )}

      {snapshot.normalizedLines.length > 0 && (
        <div className="overflow-x-auto rounded-lg border border-slate-200">
          <table className="min-w-full border-collapse text-sm">
            <thead className="bg-slate-50">
              <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                <th className="px-3 py-2">Line</th>
                <th className="px-3 py-2">Description</th>
                <th className="px-3 py-2">Vendor</th>
                <th className="px-3 py-2">Qty</th>
                <th className="px-3 py-2">Unit price</th>
              </tr>
            </thead>
            <tbody>
              {snapshot.normalizedLines.map((line) => (
                <tr key={`${line.rfqLineItemId}-${line.sourceLineId ?? 'line'}`} className="border-b border-slate-100">
                  <td className="px-3 py-2 font-mono text-xs text-slate-700">{line.rfqLineItemId}</td>
                  <td className="px-3 py-2 text-slate-700">{line.sourceDescription}</td>
                  <td className="px-3 py-2 text-slate-600">
                    {line.vendorId ?? 'Unknown'}
                    {line.quoteSubmissionId ? <span className="block text-xs text-slate-400">{line.quoteSubmissionId}</span> : null}
                  </td>
                  <td className="px-3 py-2 text-slate-600">{line.sourceQuantity ?? '—'}</td>
                  <td className="px-3 py-2 text-slate-600">
                    {line.sourceUnitPrice ?? '—'}
                    {line.sourceUom ? <span className="block text-xs text-slate-400">{line.sourceUom}</span> : null}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

function MatrixClusterCard({
  cluster,
}: {
  cluster: NonNullable<ReturnType<typeof useComparisonRunMatrix>['data']>['clusters'][number];
}) {
  return (
    <Card className="space-y-4">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p className="text-sm font-semibold text-slate-800">{cluster.clusterKey}</p>
          <p className="text-xs text-slate-500">Basis: {cluster.basis}</p>
        </div>
        <div className="text-right">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Recommended vendor</p>
          <p className="text-sm font-medium text-slate-800">{cluster.recommendation.recommendedVendorId}</p>
          <p className="text-xs text-slate-500">{cluster.recommendation.reason}</p>
        </div>
      </div>

      <InfoGrid
        cols={3}
        items={[
          { label: 'Min price', value: cluster.statistics.minNormalizedUnitPrice.toFixed(2) },
          { label: 'Avg price', value: cluster.statistics.avgNormalizedUnitPrice.toFixed(2) },
          { label: 'Max price', value: cluster.statistics.maxNormalizedUnitPrice.toFixed(2) },
        ]}
      />

      <div className="overflow-x-auto rounded-lg border border-slate-200">
        <table className="min-w-full border-collapse text-sm">
          <thead className="bg-slate-50">
            <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
              <th className="px-3 py-2">Vendor</th>
              <th className="px-3 py-2">Line</th>
              <th className="px-3 py-2">Normalized price</th>
              <th className="px-3 py-2">Quantity</th>
              <th className="px-3 py-2">Confidence</th>
            </tr>
          </thead>
          <tbody>
            {cluster.offers.map((offer) => (
              <tr key={`${cluster.clusterKey}-${offer.vendorId}`} className="border-b border-slate-100">
                <td className="px-3 py-2 font-medium text-slate-800">{offer.vendorId}</td>
                <td className="px-3 py-2 text-slate-600">{offer.rfqLineId}</td>
                <td className="px-3 py-2 text-slate-600">{offer.normalizedUnitPrice.toFixed(2)}</td>
                <td className="px-3 py-2 text-slate-600">{offer.normalizedQuantity.toFixed(2)}</td>
                <td className="px-3 py-2 text-slate-600">{Math.round(offer.aiConfidence * 100)}%</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Card>
  );
}

export function ComparisonRunDetailPageContent({ rfqId, runId }: { rfqId: string; runId: string }) {
  const { data: rfq } = useRfq(rfqId);
  const runQuery = useComparisonRun(runId, { rfqId });
  const matrixQuery = useComparisonRunMatrix(runId, { rfqId });
  const readinessQuery = useComparisonRunReadiness(runId, { rfqId });

  const run = runQuery.data;
  const matrix = matrixQuery.data;
  const readiness = readinessQuery.data;

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Comparison Runs', href: `/rfqs/${encodeURIComponent(rfqId)}/comparison-runs` },
    { label: run?.name ?? runId },
  ];

  const isReady = Boolean(readiness?.isReady);
  const isPreviewOnly = Boolean(readiness?.isPreviewOnly);

  if (runQuery.isLoading || matrixQuery.isLoading || readinessQuery.isLoading) {
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <SectionCard title="Loading comparison run" subtitle="Fetching the live run, readiness, and matrix payloads.">
          <p className="text-sm text-slate-600">Loading comparison data…</p>
        </SectionCard>
      </div>
    );
  }

  if (runQuery.isError || matrixQuery.isError || readinessQuery.isError) {
    const error = runQuery.error ?? matrixQuery.error ?? readinessQuery.error;
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <SectionCard title="Comparison run unavailable" subtitle="Unable to load the live comparison payloads.">
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load comparison run"
            description={error instanceof Error ? error.message : 'The live comparison payload could not be loaded.'}
          />
        </SectionCard>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader
        title={run?.name ?? 'Comparison Run'}
        subtitle={run?.id ?? runId}
        actions={
          <Link
            href={`/rfqs/${encodeURIComponent(rfqId)}/decision-trail`}
            className="text-sm font-medium text-indigo-600 hover:underline"
          >
            Decision trail
          </Link>
        }
      />

      <div className="flex flex-wrap items-center gap-2">
        <StatusBadge status={run?.isPreview ? 'preview' : 'final'} label={run?.isPreview ? 'Preview' : 'Final'} />
        <StatusBadge status={isReady ? 'approved' : 'pending'} label={isReady ? 'Ready' : 'Blocked'} />
        {isPreviewOnly && <StatusBadge status="preview" label="Preview only" />}
      </div>

      {!run?.isPreview && (
        <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3">
          <p className="text-sm font-medium text-slate-800">
            <span className="font-semibold">Snapshot frozen</span>
            <span className="text-slate-600"> - this final run anchors downstream approval and award flows.</span>
          </p>
        </div>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
        <SectionCard
          title="Run details"
          subtitle="Persisted comparison metadata and snapshot anchor."
        >
          <InfoGrid
            cols={2}
            items={[
              { label: 'Run ID', value: run?.id ?? 'Unavailable' },
              { label: 'RFQ', value: rfq?.title ?? rfqId },
              { label: 'Status', value: run?.status ?? 'draft' },
              { label: 'Created', value: <time dateTime={run?.createdAt ?? undefined}>{formatTimestamp(run?.createdAt ?? null)}</time> },
            ]}
          />
          <p className="mt-3 text-xs text-slate-500">
            {run?.isPreview
              ? 'Preview comparisons are persisted as draft workflow snapshots.'
              : 'This final comparison run is the freeze anchor used by downstream approval and award screens.'}
          </p>
        </SectionCard>

        <SectionCard title="Snapshot context" subtitle="Live freeze payload persisted with the comparison run.">
          <SnapshotLinesCard snapshot={run?.snapshot} />
        </SectionCard>
      </div>

      <SectionCard
        title="Readiness"
        subtitle="Live readiness status from the comparison workflow."
        actions={<span className="text-xs text-slate-500">{readiness?.id ?? runId}</span>}
      >
        <div className="grid gap-4 lg:grid-cols-3">
          <ReadinessList title="Blockers" items={readiness?.blockers ?? []} emptyText="No blocking issues." />
          <ReadinessList title="Warnings" items={readiness?.warnings ?? []} emptyText="No warnings." />
          <div className="space-y-3">
            <MetricPill label="Ready" value={isReady ? 'Yes' : 'No'} />
            <MetricPill label="Preview only" value={isPreviewOnly ? 'Yes' : 'No'} />
            <MetricPill label="Vendor count" value={run?.snapshot?.vendors.length ?? 0} />
          </div>
        </div>
      </SectionCard>

      <SectionCard
        title="Matrix"
        subtitle="Comparison clusters from the live matrix payload."
      >
        {matrix?.clusters.length ? (
          <div className="space-y-4">
            {matrix.clusters.map((cluster) => (
              <MatrixClusterCard key={cluster.clusterKey} cluster={cluster} />
            ))}
          </div>
        ) : (
          <EmptyState
            icon={<Layers3 size={20} />}
            title="No matrix clusters"
            description="The comparison run did not return any clusters to display."
          />
        )}
      </SectionCard>

      {run?.snapshot?.vendors?.length ? (
        <SectionCard title="Snapshot vendors" subtitle="Persisted vendor context from the frozen run.">
          <div className="grid gap-3 md:grid-cols-2">
            {run.snapshot.vendors.map((vendor) => (
              <Card key={vendor.vendorId} padding="sm">
                <p className="text-sm font-semibold text-slate-800">{vendor.vendorName}</p>
                <p className="mt-0.5 text-xs text-slate-500">{vendor.vendorId}</p>
                <p className="mt-1 text-xs text-slate-400">Submission {vendor.quoteSubmissionId ?? 'Unavailable'}</p>
              </Card>
            ))}
          </div>
        </SectionCard>
      ) : null}

      {run?.snapshot?.normalizedLines?.length ? (
        <SectionCard title="Snapshot lines" subtitle="Persisted normalization context for the frozen comparison.">
          <div className="overflow-x-auto rounded-lg border border-slate-200">
            <table className="min-w-full border-collapse text-sm">
              <thead className="bg-slate-50">
                <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                  <th className="px-3 py-2">Line item</th>
                  <th className="px-3 py-2">Description</th>
                  <th className="px-3 py-2">Vendor</th>
                  <th className="px-3 py-2">Quantity</th>
                  <th className="px-3 py-2">Price</th>
                </tr>
              </thead>
              <tbody>
                {run.snapshot.normalizedLines.map((line) => (
                  <tr key={`${line.rfqLineItemId}-${line.sourceLineId ?? 'line'}`} className="border-b border-slate-100">
                    <td className="px-3 py-2 font-mono text-xs text-slate-700">{line.rfqLineItemId}</td>
                    <td className="px-3 py-2 text-slate-700">{line.sourceDescription}</td>
                    <td className="px-3 py-2 text-slate-600">
                      {line.vendorId ?? 'Unknown'}
                      {line.quoteSubmissionId ? <span className="block text-xs text-slate-400">{line.quoteSubmissionId}</span> : null}
                    </td>
                    <td className="px-3 py-2 text-slate-600">{line.sourceQuantity ?? '—'}</td>
                    <td className="px-3 py-2 text-slate-600">
                      {line.sourceUnitPrice ?? '—'}
                      {line.sourceUom ? <span className="block text-xs text-slate-400">{line.sourceUom}</span> : null}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </SectionCard>
      ) : null}
    </div>
  );
}

export default function ComparisonMatrixPage({
  params,
}: {
  params: Promise<{ rfqId: string; runId: string }>;
}) {
  const { rfqId, runId } = React.use(params);
  return <ComparisonRunDetailPageContent rfqId={rfqId} runId={runId} />;
}
