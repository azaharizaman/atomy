'use client';

import React from 'react';
import Link from 'next/link';
import { SectionCard, Card, EmptyState } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { useAiStatus } from '@/hooks/use-ai-status';
import { useRfq } from '@/hooks/use-rfq';
import {
  conflictTypeLabel,
  useNormalizationReview,
} from '@/hooks/use-normalization-review';
import {
  type NormalizationSourceLineRow,
  useManualNormalizationSourceLineMutations,
  useNormalizationSourceLines,
} from '@/hooks/use-normalization-source-lines';
import { useFreezeComparison } from '@/hooks/use-freeze-comparison';
import { useRfqLineItems } from '@/hooks/use-rfq-line-items';
import { useComparisonReadiness } from '@/hooks/use-comparison-readiness';
import { AlertTriangle, Lock, Pencil, Plus, Save, Trash2, Unlock, X } from 'lucide-react';

interface ManualSourceLineFormState {
  source_description: string;
  source_quantity: string;
  source_uom: string;
  source_unit_price: string;
  rfq_line_item_id: string;
  reason: string;
  note: string;
}

const NORMALIZATION_REASON_OPTIONS = [
  { value: 'supplier_document_mismatch', label: 'Supplier document mismatch' },
  { value: 'rfq_mapping_incorrect', label: 'RFQ mapping incorrect' },
  { value: 'quantity_or_uom_correction', label: 'Quantity or UOM correction' },
  { value: 'price_correction', label: 'Price correction' },
  { value: 'manual_entry_required', label: 'Manual entry required' },
  { value: 'other', label: 'Other' },
] as const;

const EMPTY_MANUAL_LINE: ManualSourceLineFormState = {
  source_description: '',
  source_quantity: '',
  source_uom: '',
  source_unit_price: '',
  rfq_line_item_id: '',
  reason: '',
  note: '',
};

function normalizeNullableField(value: string): string | null {
  const trimmed = value.trim();
  return trimmed === '' ? null : trimmed;
}

function formFromSourceLine(line: NormalizationSourceLineRow): ManualSourceLineFormState {
  return {
    source_description: line.source_description,
    source_quantity: line.source_quantity ?? '',
    source_uom: line.source_uom ?? '',
    source_unit_price: line.source_unit_price ?? '',
    rfq_line_item_id: line.rfq_line_item_id ?? '',
    reason: '',
    note: line.latest_override?.note ?? '',
  };
}

function NormalizePageContent({ rfqId, quoteId }: { rfqId: string; quoteId: string }) {
  const rfqQuery = useRfq(rfqId);
  const rfq = rfqQuery.data;
  const aiStatus = useAiStatus();
  const normLive = useNormalizationReview(rfqId);
  const comparisonReadiness = useComparisonReadiness(rfqId);
  const sourceLinesQuery = useNormalizationSourceLines(rfqId);
  const rfqLineItemsQuery = useRfqLineItems(rfqId);
  const manualSourceLines = useManualNormalizationSourceLineMutations(rfqId);
  const liveSourceLines = sourceLinesQuery.data ?? [];
  const rfqLineItems = (rfqLineItemsQuery.data ?? []).filter((line) => line.rowType !== 'heading');
  const freeze = useFreezeComparison();
  const [locked, setLocked] = React.useState(false);
  const [selectedLineIds, setSelectedLineIds] = React.useState<string[]>([]);
  const [manualForm, setManualForm] = React.useState<ManualSourceLineFormState>(EMPTY_MANUAL_LINE);
  const [editingLineId, setEditingLineId] = React.useState<string | null>(null);
  const [editForm, setEditForm] = React.useState<ManualSourceLineFormState>(EMPTY_MANUAL_LINE);
  const [currentTimeMs, setCurrentTimeMs] = React.useState<number | null>(null);

  React.useEffect(() => {
    setCurrentTimeMs(Date.now());
    const timer = setInterval(() => {
      setCurrentTimeMs(Date.now());
    }, 10000);
    return () => clearInterval(timer);
  }, [rfqId]);

  React.useEffect(() => {
    const sourceLineIds = new Set(liveSourceLines.filter((line) => line.quote_submission_id === quoteId).map((line) => line.id));
    setSelectedLineIds((current) => current.filter((id) => sourceLineIds.has(id)));
  }, [liveSourceLines, quoteId]);

  if (rfqQuery.isError) {
    const pageError = rfqQuery.error;
    const errorMessage = pageError instanceof Error ? pageError.message : 'The live normalization workspace could not be loaded.';
    return (
      <div className="space-y-5">
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

  const hasBlockingIssues = normLive.hasBlockingIssues;
  const blockingIssueCount = normLive.blockingIssueCount;
  const conflicts = normLive.conflicts;
  const openConflicts = conflicts.filter((c) => c.resolution === null);
  const sourceLines = liveSourceLines.filter((line) => line.quote_submission_id === quoteId);
  const showExtractionUnavailable = aiStatus.shouldShowUnavailableMessage('quote_document_extraction');
  const showNormalizationUnavailable = aiStatus.shouldShowUnavailableMessage('normalization_suggestions');
  const sourceLinesError =
    sourceLinesQuery.error instanceof Error ? sourceLinesQuery.error.message : 'Source-line data could not be loaded.';
  const reviewError = normLive.error instanceof Error ? normLive.error.message : 'Review data could not be loaded.';
  const submissionDeadlineMs = rfq?.submission_deadline ? Date.parse(rfq.submission_deadline) : Number.NaN;
  const hasValidSubmissionDeadline = rfq !== undefined && Number.isFinite(submissionDeadlineMs);
  const submissionWindowStillOpen =
    rfq !== undefined && (currentTimeMs === null || !hasValidSubmissionDeadline || submissionDeadlineMs > currentTimeMs);
  const comparisonFreezeBlockedByReadiness =
    !hasValidSubmissionDeadline || !comparisonReadiness.canFreezeComparison || submissionWindowStillOpen;

  function updateManualForm(field: keyof ManualSourceLineFormState, value: string): void {
    setManualForm((current) => ({ ...current, [field]: value }));
  }

  function updateEditForm(field: keyof ManualSourceLineFormState, value: string): void {
    setEditForm((current) => ({ ...current, [field]: value }));
  }

  function submitManualSourceLine(): void {
    if (locked) return;
    const description = manualForm.source_description.trim();
    const reason = manualForm.reason.trim();
    const note = normalizeNullableField(manualForm.note);
    if (description === '' || reason === '' || (reason === 'other' && note === null)) return;

    manualSourceLines.createSourceLine.mutate(
      {
        quoteSubmissionId: quoteId,
        source_description: description,
        source_quantity: normalizeNullableField(manualForm.source_quantity),
        source_uom: normalizeNullableField(manualForm.source_uom),
        source_unit_price: normalizeNullableField(manualForm.source_unit_price),
        rfq_line_item_id: normalizeNullableField(manualForm.rfq_line_item_id),
        note,
        reason,
      },
      {
        onSuccess: () => {
          setManualForm(EMPTY_MANUAL_LINE);
        },
      },
    );
  }

  function startEdit(line: NormalizationSourceLineRow): void {
    if (locked) return;
    setEditingLineId(line.id);
    setEditForm(formFromSourceLine(line));
  }

  function saveEdit(lineId: string): void {
    if (locked) return;
    const description = editForm.source_description.trim();
    const reason = editForm.reason.trim();
    const note = normalizeNullableField(editForm.note);
    if (
      description === '' ||
      reason === '' ||
      (reason === 'other' && note === null) ||
      manualSourceLines.overrideSourceLine.isPending
    ) return;

    manualSourceLines.overrideSourceLine.mutate(
      {
        id: lineId,
        override_data: {
          rfq_line_item_id: normalizeNullableField(editForm.rfq_line_item_id),
          source_description: description,
          quantity: normalizeNullableField(editForm.source_quantity),
          uom: normalizeNullableField(editForm.source_uom),
          unit_price: normalizeNullableField(editForm.source_unit_price),
        },
        reason_code: editForm.reason,
        note,
      },
      {
        onSuccess: () => {
          setEditingLineId(null);
          setEditForm(EMPTY_MANUAL_LINE);
        },
      },
    );
  }

  function deleteSourceLine(lineId: string, lineNumber: number): void {
    if (locked) return;
    if (manualSourceLines.deleteSourceLine.isPending) return;
    if (typeof window !== 'undefined' && !window.confirm(`Delete source line ${lineNumber}?`)) return;

    manualSourceLines.deleteSourceLine.mutate({ quoteSubmissionId: quoteId, id: lineId });
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

  function formatReasonCode(reasonCode: string | null | undefined): string {
    if (!reasonCode) return 'Reason not recorded';

    const option = NORMALIZATION_REASON_OPTIONS.find((item) => item.value === reasonCode);
    if (option) return option.label;

    return reasonCode.replaceAll('_', ' ');
  }

  function providerConfidenceLabel(value: string | null | undefined): string | null {
    if (!value) return null;

    const numeric = Number(value);
    if (!Number.isFinite(numeric)) {
      return value;
    }

    return `${numeric.toFixed(2)}%`;
  }

  function suggestedMappingLabel(line: NormalizationSourceLineRow): string {
    const suggestedId = line.provider_suggested?.rfq_line_item_id;
    if (!suggestedId || suggestedId.trim() === '') {
      return 'Unmapped';
    }

    const matchedLine = rfqLineItems.find((item) => item.id === suggestedId);
    if (matchedLine) {
      return rfqLineDisplay(matchedLine.id);
    }

    return 'Unmapped';
  }

  function sourceLineDisplay(line: { sort_order?: number | null }, index: number): string {
    return `Source line ${getLineNumber(line, index)}`;
  }

  function rfqLineDisplay(rfqLineItemId: string | null | undefined): string {
    if (!rfqLineItemId) return 'Unmapped';
    const matchedIndex = rfqLineItems.findIndex((item) => item.id === rfqLineItemId);
    if (matchedIndex < 0) return 'Unmapped';
    return `RFQ line ${matchedIndex + 1}`;
  }

  function rfqLineDescription(rfqLineItemId: string | null | undefined, fallback: string | null | undefined): string {
    if (!rfqLineItemId) return 'No RFQ line selected';
    const matchedLine = rfqLineItems.find((item) => item.id === rfqLineItemId);
    return matchedLine?.description ?? fallback ?? 'RFQ line not found';
  }

  const freezeDisabled = hasBlockingIssues || freeze.isPending || comparisonFreezeBlockedByReadiness;
  const sourceLineIds = sourceLines.map((line) => line.id);
  const allSourceLinesSelected = sourceLineIds.length > 0 && sourceLineIds.every((id) => selectedLineIds.includes(id));

  function toggleAllSourceLines(): void {
    setSelectedLineIds(allSourceLinesSelected ? [] : sourceLineIds);
  }

  function toggleSelection(lineId: string): void {
    setSelectedLineIds((current) => (current.includes(lineId) ? current.filter((id) => id !== lineId) : [...current, lineId]));
  }

  function isSelected(lineId: string): boolean {
    return selectedLineIds.includes(lineId);
  }

  return (
    <div className="space-y-5">
      {showExtractionUnavailable && (
        <Card className="border-amber-200 bg-amber-50 p-4 space-y-1">
          <p className="text-sm font-semibold text-amber-950">AI extraction is unavailable.</p>
          <p className="text-xs text-amber-900">
            Upload continuity remains available. Enter or correct source lines manually before mapping.
          </p>
        </Card>
      )}
      {showNormalizationUnavailable && (
        <Card className="border-amber-200 bg-amber-50 p-4 space-y-1">
          <p className="text-sm font-semibold text-amber-950">AI normalization suggestions are unavailable.</p>
          <p className="text-xs text-amber-900">Manual source-line mapping and comparison preparation remain available.</p>
        </Card>
      )}
      {sourceLinesQuery.isError && (
        <Card className="border-amber-200 bg-amber-50 p-4 space-y-1">
          <p className="text-sm font-semibold text-amber-950">Source-line data unavailable</p>
          <p className="text-xs text-amber-900">{sourceLinesError}</p>
        </Card>
      )}
      {normLive.isError && (
        <Card className="border-amber-200 bg-amber-50 p-4 space-y-1">
          <p className="text-sm font-semibold text-amber-950">Review data unavailable</p>
          <p className="text-xs text-amber-900">{reviewError}</p>
        </Card>
      )}
      {hasBlockingIssues && (
        <Card className="border-amber-200 bg-amber-50 p-4 space-y-2">
          <p className="text-sm font-semibold text-amber-950">Blocking issues</p>
          <p className="text-xs text-amber-900">
            Resolve {blockingIssueCount || openConflicts.length || 1} blocking issue(s) before freezing the comparison.
          </p>
          {openConflicts.length > 0 && (
            <ul className="mt-2 space-y-1.5 text-xs text-amber-950">
              {openConflicts.map((c, index) => (
                <li key={c.id} className="flex items-center gap-2">
                  <StatusBadge status="pending" size="xs" label={conflictTypeLabel(c.conflict_type)} />
                  <span className="text-amber-800">Blocking issue {index + 1}</span>
                </li>
              ))}
            </ul>
          )}
        </Card>
      )}
      {submissionWindowStillOpen && (
        <Card className="border-slate-200 bg-slate-50 p-4 space-y-1">
          <p className="text-sm font-semibold text-slate-900">Comparison freeze unavailable</p>
          <p className="text-xs text-slate-600">
            Submission deadline has not passed yet. Final comparison remains blocked until the RFQ closes.
          </p>
        </Card>
      )}
      {!submissionWindowStillOpen && !comparisonReadiness.allQuotesReady && (
        <Card className="border-slate-200 bg-slate-50 p-4 space-y-1">
          <p className="text-sm font-semibold text-slate-900">Comparison freeze waiting on quote readiness</p>
          <p className="text-xs text-slate-600">
            All active quote submissions must reach ready state before final comparison can be frozen.
          </p>
        </Card>
      )}
      {rfqQuery.isLoading && (
        <Card className="border-slate-200 bg-slate-50 p-4 space-y-1">
          <p className="text-sm font-semibold text-slate-900">Loading RFQ details</p>
          <p className="text-xs text-slate-600">Waiting for the RFQ record before checking the submission deadline.</p>
        </Card>
      )}
      <div className="rounded-md border border-slate-200 bg-white px-3 py-2">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex flex-wrap items-center gap-2">
            <StatusBadge status={locked ? 'approved' : 'draft'} label={locked ? 'Locked' : 'Unlocked'} />
            <StatusBadge status="draft" label={`${sourceLines.filter((line) => line.rfq_line_item_id !== null).length}/${sourceLines.length} mapped`} />
            {selectedLineIds.length > 0 && (
              <span className="text-xs font-medium text-indigo-700">{selectedLineIds.length} selected</span>
            )}
          </div>
          <div className="flex flex-wrap items-center justify-end gap-2">
            <Button
              size="sm"
              variant="outline"
              icon={locked ? <Unlock size={14} /> : <Lock size={14} />}
              disabled={locked}
              onClick={() => setLocked(!locked)}
            >
              {locked ? 'Unlock' : 'Lock for Comparison'}
            </Button>
            <Button
              size="sm"
              variant="primary"
              disabled={freezeDisabled}
              onClick={() => freeze.mutate(rfqId)}
            >
              Freeze comparison
            </Button>
            <Link
              href={`/rfqs/${encodeURIComponent(rfqId)}/decision-trail`}
              className="inline-flex h-7 items-center whitespace-nowrap rounded border border-transparent px-2 text-xs font-medium text-indigo-600 hover:bg-indigo-50"
            >
              Decision trail
            </Link>
          </div>
        </div>
      </div>
      <SectionCard title="Source line normalization" subtitle="Review extracted supplier lines, mapping, effective values, and buyer changes in one place">
        <div className="space-y-3">
          <div className="rounded border border-slate-200 bg-slate-50 p-3">
            <div className="grid gap-2 lg:grid-cols-[minmax(0,1fr)_90px_90px_110px_minmax(150px,0.8fr)_minmax(140px,0.8fr)_auto]">
              <label className="text-xs font-medium text-slate-600">
                Description
                <input
                  aria-label="Description"
                  className="mt-1 h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  value={manualForm.source_description}
                  onChange={(event) => updateManualForm('source_description', event.target.value)}
                  disabled={locked}
                />
              </label>
              <label className="text-xs font-medium text-slate-600">
                Quantity
                <input
                  aria-label="Quantity"
                  className="mt-1 h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  value={manualForm.source_quantity}
                  onChange={(event) => updateManualForm('source_quantity', event.target.value)}
                  disabled={locked}
                />
              </label>
              <label className="text-xs font-medium text-slate-600">
                UOM
                <input
                  aria-label="UOM"
                  className="mt-1 h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  value={manualForm.source_uom}
                  onChange={(event) => updateManualForm('source_uom', event.target.value)}
                  disabled={locked}
                />
              </label>
              <label className="text-xs font-medium text-slate-600">
                Unit price
                <input
                  aria-label="Unit price"
                  className="mt-1 h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  value={manualForm.source_unit_price}
                  onChange={(event) => updateManualForm('source_unit_price', event.target.value)}
                  disabled={locked}
                />
              </label>
              <label className="text-xs font-medium text-slate-600">
                RFQ line
                <select
                  aria-label="RFQ line"
                  className="mt-1 h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  value={manualForm.rfq_line_item_id}
                  onChange={(event) => updateManualForm('rfq_line_item_id', event.target.value)}
                  disabled={locked}
                >
                  <option value="">Unmapped</option>
                  {rfqLineItems.map((line, index) => (
                    <option key={line.id} value={line.id}>
                      {`RFQ line ${index + 1}: ${line.description}`}
                    </option>
                  ))}
                </select>
              </label>
              <label className="text-xs font-medium text-slate-600">
                Reason code
                <select
                  aria-label="Reason code"
                  className="mt-1 h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  value={manualForm.reason}
                  onChange={(event) => updateManualForm('reason', event.target.value)}
                  disabled={locked}
                >
                  <option value="">Select reason</option>
                  {NORMALIZATION_REASON_OPTIONS.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </label>
              <label className="text-xs font-medium text-slate-600">
                Note
                <input
                  aria-label="Note"
                  className="mt-1 h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  value={manualForm.note}
                  onChange={(event) => updateManualForm('note', event.target.value)}
                  disabled={locked}
                />
              </label>
              <div className="flex items-end">
                <Button
                  size="sm"
                  variant="outline"
                  type="button"
                  icon={<Plus size={14} />}
                  disabled={
                    locked ||
                    manualForm.source_description.trim() === '' ||
                    manualForm.reason.trim() === '' ||
                    (manualForm.reason === 'other' && manualForm.note.trim() === '') ||
                    manualSourceLines.createSourceLine.isPending
                  }
                  onClick={submitManualSourceLine}
                >
                  Add source line
                </Button>
              </div>
            </div>
          </div>
          <div className="overflow-x-auto rounded-lg border border-slate-200">
            <table aria-label="Source line normalization review" className="w-full min-w-[1120px] border-collapse bg-white">
              <thead>
                <tr className="border-b border-slate-200 bg-slate-50 text-left">
                  <th className="w-10 px-3 py-2">
                    <input
                      type="checkbox"
                      aria-label="Select all source lines"
                      className="rounded border-slate-300"
                      checked={allSourceLinesSelected}
                      onChange={toggleAllSourceLines}
                      disabled={locked}
                    />
                  </th>
                  <th className="px-3 py-2 text-xs font-medium text-slate-600">Line</th>
                  <th className="px-3 py-2 text-xs font-medium text-slate-600">Supplier source</th>
                  <th className="px-3 py-2 text-xs font-medium text-slate-600">RFQ mapping</th>
                  <th className="px-3 py-2 text-xs font-medium text-slate-600">Effective values</th>
                  <th className="px-3 py-2 text-xs font-medium text-slate-600">Review state</th>
                  <th className="px-3 py-2 text-right text-xs font-medium text-slate-600">Actions</th>
                </tr>
              </thead>
              <tbody>
                {sourceLines.map((line, index) => {
                  const liveLine = line as NormalizationSourceLineRow;
                  const sourceUnitPrice = parseOptionalPrice(liveLine.source_unit_price);
                  const effectiveUnitPrice = parseOptionalPrice(liveLine.effective_values?.unit_price ?? liveLine.source_unit_price);
                  const lineNumber = getLineNumber(liveLine, index);
                  const isEditing = editingLineId === liveLine.id;
                  return (
                    <tr key={liveLine.id} className={['border-b border-slate-100 align-top', isSelected(liveLine.id) ? 'bg-indigo-50/60' : ''].join(' ')}>
                      <td className="px-3 py-3">
                        <input
                          type="checkbox"
                          aria-label={`Select source line ${lineNumber}`}
                          className="rounded border-slate-300"
                          checked={isSelected(liveLine.id)}
                          onChange={() => toggleSelection(liveLine.id)}
                          disabled={locked}
                        />
                      </td>
                      <td className="px-3 py-3 text-xs text-slate-600">
                        <div className="font-medium text-slate-800">{sourceLineDisplay(liveLine, index)}</div>
                        <div className="mt-1">{liveLine.vendor_name}</div>
                      </td>
                      <td className="px-3 py-3">
                        {isEditing ? (
                          <div className="grid gap-2">
                            <input
                              aria-label={`Description source line ${lineNumber}`}
                              className="h-8 rounded border border-slate-300 px-2 text-xs"
                              value={editForm.source_description}
                              onChange={(event) => updateEditForm('source_description', event.target.value)}
                              disabled={locked}
                            />
                            <div className="grid grid-cols-3 gap-2">
                              <input
                                aria-label={`Quantity source line ${lineNumber}`}
                                className="h-8 rounded border border-slate-300 px-2 text-xs"
                                value={editForm.source_quantity}
                                onChange={(event) => updateEditForm('source_quantity', event.target.value)}
                                disabled={locked}
                              />
                              <input
                                aria-label={`UOM source line ${lineNumber}`}
                                className="h-8 rounded border border-slate-300 px-2 text-xs"
                                value={editForm.source_uom}
                                onChange={(event) => updateEditForm('source_uom', event.target.value)}
                                disabled={locked}
                              />
                              <input
                                aria-label={`Unit price source line ${lineNumber}`}
                                className="h-8 rounded border border-slate-300 px-2 text-xs"
                                value={editForm.source_unit_price}
                                onChange={(event) => updateEditForm('source_unit_price', event.target.value)}
                                disabled={locked}
                              />
                            </div>
                          </div>
                        ) : (
                          <div className="max-w-[280px]">
                            <div className="truncate text-sm font-medium text-slate-800">{liveLine.source_description}</div>
                            <div className="mt-1 text-xs text-slate-500">
                              {`${liveLine.source_quantity ?? '—'} ${liveLine.source_uom ?? ''}`.trim()} · {formatPrice(sourceUnitPrice)}
                            </div>
                          </div>
                        )}
                      </td>
                      <td className="px-3 py-3">
                        {isEditing ? (
                          <select
                            aria-label={`RFQ line source line ${lineNumber}`}
                            className="h-8 w-full rounded border border-slate-300 px-2 text-xs"
                            value={editForm.rfq_line_item_id}
                            onChange={(event) => updateEditForm('rfq_line_item_id', event.target.value)}
                            disabled={locked}
                          >
                            <option value="">Unmapped</option>
                            {rfqLineItems.map((item, itemIndex) => (
                              <option key={item.id} value={item.id}>
                                {`RFQ line ${itemIndex + 1}: ${item.description}`}
                              </option>
                            ))}
                          </select>
                        ) : (
                          <div className="max-w-[260px]">
                            <div className="text-xs font-semibold text-slate-800">{rfqLineDisplay(liveLine.rfq_line_item_id)}</div>
                            <div className="mt-1 truncate text-xs text-slate-500">
                              {rfqLineDescription(liveLine.rfq_line_item_id, liveLine.rfq_line_description)}
                            </div>
                            <div className="mt-1 text-[11px] text-slate-400">Suggested mapping {suggestedMappingLabel(liveLine)}</div>
                          </div>
                        )}
                      </td>
                      <td className="px-3 py-3 text-xs text-slate-600">
                        <div>{`${liveLine.effective_values?.quantity ?? liveLine.source_quantity ?? '—'} ${liveLine.effective_values?.uom ?? liveLine.source_uom ?? ''}`.trim()}</div>
                        <div className="mt-1 font-medium tabular-nums text-slate-800">{formatPrice(effectiveUnitPrice)}</div>
                      </td>
                      <td className="px-3 py-3">
                        {isEditing ? (
                          <div className="grid gap-2">
                            <select
                              aria-label={`Reason code source line ${lineNumber}`}
                              className="h-8 rounded border border-slate-300 px-2 text-xs"
                              value={editForm.reason}
                              onChange={(event) => updateEditForm('reason', event.target.value)}
                              disabled={locked}
                            >
                              <option value="">Select reason</option>
                              {NORMALIZATION_REASON_OPTIONS.map((option) => (
                                <option key={option.value} value={option.value}>
                                  {option.label}
                                </option>
                              ))}
                            </select>
                            <input
                              aria-label={`Note source line ${lineNumber}`}
                              className="h-8 rounded border border-slate-300 px-2 text-xs"
                              value={editForm.note}
                              onChange={(event) => updateEditForm('note', event.target.value)}
                              disabled={locked}
                            />
                            <div className="text-[11px] text-slate-500">
                              Provider confidence {providerConfidenceLabel(liveLine.ai_confidence) ?? 'Unavailable'}
                            </div>
                            <div className="text-[11px] text-slate-500">
                              {liveLine.is_buyer_overridden
                                ? `Override reason ${formatReasonCode(liveLine.latest_override?.reason_code)}`
                                : `Suggested mapping ${suggestedMappingLabel(liveLine)}`}
                            </div>
                          </div>
                        ) : (
                          <div className="space-y-1.5">
                            <div className="flex flex-wrap gap-1">
                              <StatusBadge status={liveLine.has_blocking_issue ? 'pending' : 'approved'} size="xs" label={liveLine.confidence} />
                              {liveLine.is_buyer_overridden ? (
                                <StatusBadge status="pending" size="xs" label="Buyer override" />
                              ) : (
                                <StatusBadge status="draft" size="xs" label="Provider suggested" />
                              )}
                            </div>
                            <div className="text-[11px] text-slate-500">
                              Provider confidence {providerConfidenceLabel(liveLine.ai_confidence) ?? 'Unavailable'}
                            </div>
                            <div className="text-[11px] text-slate-500">
                              {liveLine.is_buyer_overridden
                                ? `Override reason ${formatReasonCode(liveLine.latest_override?.reason_code)}`
                                : `Suggested mapping ${suggestedMappingLabel(liveLine)}`}
                            </div>
                          </div>
                        )}
                      </td>
                      <td className="px-3 py-3">
                        <div className="flex flex-wrap justify-end gap-1">
                          {isEditing ? (
                            <>
                              <Button
                                size="sm"
                                variant="outline"
                                type="button"
                                icon={<Save size={14} />}
                                disabled={
                                  locked ||
                                  editForm.source_description.trim() === '' ||
                                  editForm.reason.trim() === '' ||
                                  (editForm.reason === 'other' && editForm.note.trim() === '') ||
                                  manualSourceLines.overrideSourceLine.isPending
                                }
                                onClick={() => saveEdit(liveLine.id)}
                              >
                                Save source line {lineNumber}
                              </Button>
                              <Button size="sm" variant="ghost" type="button" icon={<X size={14} />} onClick={() => setEditingLineId(null)}>
                                Cancel
                              </Button>
                            </>
                          ) : (
                            <>
                              <Button size="sm" variant="ghost" type="button" icon={<Pencil size={14} />} disabled={locked} onClick={() => startEdit(liveLine)}>
                                Edit source line {lineNumber}
                              </Button>
                              <Button
                                size="sm"
                                variant="ghost"
                                type="button"
                                icon={<Trash2 size={14} />}
                                disabled={locked || manualSourceLines.deleteSourceLine.isPending}
                                onClick={() => deleteSourceLine(liveLine.id, lineNumber)}
                              >
                                Delete source line {lineNumber}
                              </Button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
                {sourceLines.length === 0 && (
                  <tr>
                    <td colSpan={7} className="px-3 py-8 text-center text-sm text-slate-500">
                      No source lines yet.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </SectionCard>
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
