'use client';

import React from 'react';
import Link from 'next/link';
import { SectionCard, Card, EmptyState } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import {
  conflictTypeLabel,
  useNormalizationReview,
} from '@/hooks/use-normalization-review';
import { type NormalizationSourceLineRow, useNormalizationSourceLines } from '@/hooks/use-normalization-source-lines';
import { useFreezeComparison } from '@/hooks/use-freeze-comparison';
import { AlertTriangle, Lock, Unlock } from 'lucide-react';

const MOCK_SOURCE_LINES = [
  { id: '1', lineNo: 1, description: 'PowerEdge R750 Server', qty: 12, unit: 'units', unitPrice: 4200, confidence: 'high' as const, conflict: false },
  { id: '2', lineNo: 2, description: 'PowerEdge R650 Server', qty: 6, unit: 'units', unitPrice: 3100, confidence: 'medium' as const, conflict: true },
  { id: '3', lineNo: 3, description: 'SAN Array 50TB', qty: 2, unit: 'units', unitPrice: 28500, confidence: 'high' as const, conflict: false },
];

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

export function NormalizePageContent({ rfqId, quoteId }: { rfqId: string; quoteId: string }) {
  const rfqQuery = useRfq(rfqId);
  const rfq = rfqQuery.data;
  const normLive = useNormalizationReview(rfqId, { enabled: !useMocks });
  const sourceLinesQuery = useNormalizationSourceLines(rfqId, { enabled: !useMocks });
  const liveSourceLines = sourceLinesQuery.data ?? [];
  const freeze = useFreezeComparison();
  const [locked, setLocked] = React.useState(false);
  const [selectedLineIds, setSelectedLineIds] = React.useState<string[]>([]);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Quote Intake', href: `/rfqs/${encodeURIComponent(rfqId)}/quote-intake` },
    { label: 'Quote', href: `/rfqs/${encodeURIComponent(rfqId)}/quote-intake/${encodeURIComponent(quoteId)}` },
    { label: 'Normalize' },
  ];

  if (rfqQuery.isError || (!useMocks && (normLive.isError || sourceLinesQuery.isError))) {
    const pageError = rfqQuery.error ?? normLive.error ?? sourceLinesQuery.error;
    const errorMessage = pageError instanceof Error ? pageError.message : 'The live normalization workspace could not be loaded.';
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <SectionCard title="Normalize workspace unavailable" subtitle="Unable to load live normalization payloads.">
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load normalize workspace"
            description={errorMessage}
          />
        </SectionCard>
      </div>
    );
  }

  const mockBlocking = useMocks && MOCK_SOURCE_LINES.some((l) => l.conflict);
  const hasBlockingIssues = useMocks ? mockBlocking : normLive.hasBlockingIssues;
  const blockingIssueCount = useMocks ? (mockBlocking ? MOCK_SOURCE_LINES.filter((l) => l.conflict).length : 0) : normLive.blockingIssueCount;
  const conflicts = normLive.conflicts;
  const openConflicts = conflicts.filter((c) => c.resolution === null);
  const sourceLines = useMocks ? MOCK_SOURCE_LINES : liveSourceLines.filter((line) => line.quote_submission_id === quoteId);

  function toggleSelection(lineId: string): void {
    setSelectedLineIds((current) => (current.includes(lineId) ? current.filter((id) => id !== lineId) : [...current, lineId]));
  }

  function isSelected(lineId: string): boolean {
    return selectedLineIds.includes(lineId);
  }

  function parseOptionalPrice(value: number | string | null | undefined): number | null {
    if (value === null || value === undefined) return null;
    if (typeof value === 'string' && value.trim() === '') return null;
    const numericValue = typeof value === 'number' ? value : Number(value);
    if (!Number.isFinite(numericValue)) return null;
    return numericValue;
  }

  function formatPrice(value: number | string | null | undefined): string {
    const numericValue = parseOptionalPrice(value);
    if (numericValue === null) return '—';
    if (!Number.isFinite(numericValue)) return '—';
    return `$${numericValue.toLocaleString()}`;
  }

  function getLineNumber(line: { lineNo?: number; sort_order?: number | null }, index: number): number {
    return line.lineNo ?? ((line.sort_order ?? index) + 1);
  }

  function checkboxId(section: 'source' | 'mapping', lineId: string): string {
    return `${section}-${lineId}`;
  }

  const freezeDisabled = hasBlockingIssues || freeze.isPending;

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      {hasBlockingIssues && (
        <Card className="border-amber-200 bg-amber-50 p-4 space-y-2">
          <p className="text-sm font-semibold text-amber-950">Blocking issues</p>
          <p className="text-xs text-amber-900">
            Resolve {blockingIssueCount || openConflicts.length || 1} blocking issue(s) before freezing the comparison.
          </p>
          {!useMocks && openConflicts.length > 0 && (
            <ul className="mt-2 space-y-1.5 text-xs text-amber-950">
              {openConflicts.map((c) => (
                <li key={c.id} className="flex items-center gap-2">
                  <StatusBadge status="pending" size="xs" label={conflictTypeLabel(c.conflict_type)} />
                  <span className="text-amber-800 font-mono truncate">{c.id}</span>
                </li>
              ))}
            </ul>
          )}
          {useMocks && (
            <ul className="mt-2 space-y-1 text-xs text-amber-900">
              {MOCK_SOURCE_LINES.filter((l) => l.conflict).map((l) => (
                <li key={l.id}>
                  <StatusBadge status="pending" size="xs" label="Ambiguous mapping" /> Line {l.lineNo}
                </li>
              ))}
            </ul>
          )}
        </Card>
      )}
      <div className="flex items-center gap-3 flex-wrap">
        <Button size="sm" variant="outline" disabled>
          Bulk Apply Mapping
        </Button>
        <Button size="sm" variant="outline" onClick={() => setLocked(!locked)}>
          {locked ? <Lock size={14} className="mr-1.5" /> : <Unlock size={14} className="mr-1.5" />}
          {locked ? 'Unlock' : 'Lock for Comparison'}
        </Button>
        <span className="text-xs text-slate-500">
          {locked ? 'Locked' : 'Unlocked'}
        </span>
        <StatusBadge status="draft" label="Fully Normalized: 18/24 lines" />
        <Button
          size="sm"
          variant="primary"
          disabled={freezeDisabled}
          onClick={() => freeze.mutate(rfqId)}
        >
          Freeze comparison
        </Button>
        {hasBlockingIssues && (
          <Button size="sm" variant="outline" type="button">
            Manual assist
          </Button>
        )}
        <Link
          href={`/rfqs/${encodeURIComponent(rfqId)}/decision-trail`}
          className="text-xs font-medium text-indigo-600 hover:underline"
        >
          Decision trail
        </Link>
      </div>
      <div className="grid gap-5 xl:grid-cols-[0.45fr,0.55fr]">
        <SectionCard title="Source lines" subtitle="Extracted vendor line items">
          <div className="space-y-2">
            {sourceLines.map((line, index) => {
              if (useMocks) {
                const mockLine = line as (typeof MOCK_SOURCE_LINES)[number];
                const lineNumber = getLineNumber(line, index);
                const sourceCheckboxId = checkboxId('source', line.id);
                return (
                  <div
                    key={line.id}
                    className="flex items-center gap-3 rounded border border-slate-200 px-3 py-2 text-xs"
                  >
                    <input
                      id={sourceCheckboxId}
                      type="checkbox"
                      className="rounded border-slate-300"
                      checked={isSelected(line.id)}
                      onChange={() => toggleSelection(line.id)}
                    />
                    <label htmlFor={sourceCheckboxId} className="sr-only">
                      Select source line {lineNumber}
                    </label>
                    <span className="w-6 text-slate-500">{lineNumber}</span>
                    <span className="flex-1 truncate text-slate-800">{mockLine.description}</span>
                    <span className="text-slate-500">{mockLine.qty} {mockLine.unit}</span>
                    <span className="font-medium tabular-nums">{formatPrice(mockLine.unitPrice)}</span>
                    <StatusBadge status={mockLine.conflict ? 'pending' : 'approved'} size="xs" label={mockLine.confidence} />
                  </div>
                );
              }

              const liveLine = line as NormalizationSourceLineRow;
              const unitPrice = parseOptionalPrice(liveLine.source_unit_price);
              const lineNumber = getLineNumber(liveLine, index);
              const sourceCheckboxId = checkboxId('source', liveLine.id);
              return (
                <div
                  key={liveLine.id}
                  className="flex items-center gap-3 rounded border border-slate-200 px-3 py-2 text-xs"
                >
                  <input
                    id={sourceCheckboxId}
                    type="checkbox"
                    className="rounded border-slate-300"
                    checked={isSelected(line.id)}
                    onChange={() => toggleSelection(line.id)}
                  />
                  <label htmlFor={sourceCheckboxId} className="sr-only">
                    Select source line {lineNumber}
                  </label>
                  <span className="w-6 text-slate-500">{lineNumber}</span>
                  <span className="flex-1 truncate text-slate-800">{liveLine.source_description}</span>
                  <span className="text-slate-500">{`${liveLine.source_quantity ?? '—'} ${liveLine.source_uom ?? ''}`.trim()}</span>
                  <span className="font-medium tabular-nums">{formatPrice(unitPrice)}</span>
                  <StatusBadge status={liveLine.has_blocking_issue ? 'pending' : 'approved'} size="xs" label={liveLine.confidence} />
                </div>
              );
            })}
            {!useMocks && sourceLines.length === 0 && (
              <p className="text-xs text-slate-500">No source lines yet.</p>
            )}
          </div>
        </SectionCard>
        <SectionCard title="Normalized mapping" subtitle="Map to RFQ line items">
          <div className="space-y-2 text-xs">
            {sourceLines.map((line, index) => {
              if (useMocks) {
                const mockLine = line as (typeof MOCK_SOURCE_LINES)[number];
                const lineNumber = getLineNumber(line, index);
                const mappingCheckboxId = checkboxId('mapping', line.id);
                return (
                  <div
                    key={line.id}
                    className="grid grid-cols-5 gap-2 rounded border border-slate-200 px-3 py-2 items-center"
                  >
                    <span className="col-span-2 flex items-center gap-2 truncate text-slate-800">
                      <input
                        id={mappingCheckboxId}
                        type="checkbox"
                        className="rounded border-slate-300"
                        checked={isSelected(line.id)}
                        onChange={() => toggleSelection(line.id)}
                      />
                      <label htmlFor={mappingCheckboxId} className="sr-only">
                        Select normalized line {lineNumber}
                      </label>
                      <span>RFQ Line #{lineNumber}</span>
                    </span>
                    <span className="text-slate-500 font-mono">43211500</span>
                    <span>{mockLine.qty} {mockLine.unit}</span>
                    <span className="tabular-nums font-medium">{formatPrice(mockLine.unitPrice)}</span>
                  </div>
                );
              }

              const liveLine = line as NormalizationSourceLineRow;
              const unitPrice = parseOptionalPrice(liveLine.rfq_line_unit_price);
              const lineNumber = getLineNumber(liveLine, index);
              const mappingCheckboxId = checkboxId('mapping', liveLine.id);
              return (
                <div
                  key={liveLine.id}
                  className="grid grid-cols-5 gap-2 rounded border border-slate-200 px-3 py-2 items-center"
                >
                  <span className="col-span-2 flex items-center gap-2 truncate text-slate-800">
                    <input
                      id={mappingCheckboxId}
                      type="checkbox"
                      className="rounded border-slate-300"
                      checked={isSelected(line.id)}
                      onChange={() => toggleSelection(line.id)}
                    />
                    <label htmlFor={mappingCheckboxId} className="sr-only">
                      Select normalized line {lineNumber}
                    </label>
                    <span>
                    {liveLine.rfq_line_description ?? `RFQ Line #${(liveLine.sort_order ?? index) + 1}`}
                    </span>
                  </span>
                  <span className="text-slate-500 font-mono">—</span>
                  <span>{`${liveLine.rfq_line_quantity ?? '—'} ${liveLine.rfq_line_uom ?? ''}`.trim()}</span>
                  <span className="tabular-nums font-medium">{formatPrice(unitPrice)}</span>
                </div>
              );
            })}
            {!useMocks && sourceLines.length === 0 && (
              <p className="text-xs text-slate-500">No normalized mappings yet.</p>
            )}
          </div>
        </SectionCard>
      </div>
    </div>
  );
}

export default function NormalizePage({
  params,
}: {
  params: Promise<{ rfqId: string; quoteId: string }>;
}) {
  const { rfqId, quoteId } = React.use(params);
  return <NormalizePageContent rfqId={rfqId} quoteId={quoteId} />;
}
