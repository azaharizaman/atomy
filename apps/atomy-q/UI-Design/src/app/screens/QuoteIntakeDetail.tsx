import { useState, useCallback, Fragment } from 'react';
import { useParams, Link } from 'react-router';
import {
  ArrowLeft, Upload, CheckCircle, XCircle, AlertTriangle, Clock,
  Eye, ThumbsUp, ThumbsDown, FileText, RefreshCw, ChevronRight, ChevronDown,
  Link2, Sparkles, Pencil, Undo2, Info, X,
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import {
  quoteSubmissions, rfqs, vendors, rfqLineItems, statusColors, parsedLineItems,
  formatDateTime, getVendorById, getRfqById, formatCurrency,
  getParsedLineItemsBySubmissionId, getRfqLineItemsByRfqId,
  type QuoteStatus, type ParsedLineItem,
} from '../data/mockData';

function StatusBadge({ status }: { status: QuoteStatus }) {
  const map: Record<QuoteStatus, { label: string; icon: typeof Clock }> = {
    processing: { label: 'Processing', icon: Clock },
    parsed: { label: 'Parsed', icon: AlertTriangle },
    accepted: { label: 'Accepted', icon: CheckCircle },
    rejected: { label: 'Rejected', icon: XCircle },
    pending_assignment: { label: 'Pending Assignment', icon: Link2 },
    error: { label: 'Error', icon: XCircle },
  };
  const { label, icon: Icon } = map[status];
  const color = statusColors[status] ?? 'bg-slate-100 text-slate-600';
  return (
    <span className={`inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full ${color}`} style={{ fontWeight: 600 }}>
      <Icon size={10} />
      {label}
    </span>
  );
}

function ConfidenceBar({ score }: { score: number }) {
  const color = score > 90 ? 'bg-emerald-500' : score >= 75 ? 'bg-amber-500' : 'bg-red-500';
  const textColor = score > 90 ? 'text-emerald-700' : score >= 75 ? 'text-amber-700' : 'text-red-700';
  return (
    <div>
      <div className="flex items-center justify-between mb-1">
        <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Parse Confidence</span>
        <span className={`text-xs ${textColor}`} style={{ fontWeight: 700 }}>{score}%</span>
      </div>
      <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
        <div className={`h-full rounded-full transition-all ${color}`} style={{ width: `${score}%` }} />
      </div>
    </div>
  );
}

export function QuoteIntakeDetail() {
  const { id } = useParams<{ id: string }>();
  const selected = quoteSubmissions.find(q => q.id === id);
  const selVendor = selected ? getVendorById(selected.vendorId) : null;
  const selRfq = selected ? getRfqById(selected.rfqId) : null;
  const selLines = selected ? getParsedLineItemsBySubmissionId(selected.id) : [];
  const selRfqLines = selected && selected.rfqId ? getRfqLineItemsByRfqId(selected.rfqId) : [];

  const [activeTab, setActiveTab] = useState<'overview' | 'lines'>('overview');
  const [showLowConf, setShowLowConf] = useState(false);
  const [showReplace, setShowReplace] = useState(false);
  const [showConfirmReparse, setShowConfirmReparse] = useState(false);
  const [showAssign, setShowAssign] = useState(false);
  const [showOverrideModal, setShowOverrideModal] = useState(false);
  const [overrideTargetLine, setOverrideTargetLine] = useState<string | null>(null);
  const [overrideSelectedRfqLine, setOverrideSelectedRfqLine] = useState('');
  const [overrideReason, setOverrideReason] = useState('');

  const [expandedLineIds, setExpandedLineIds] = useState<Set<string>>(new Set());
  const [overriddenLines, setOverriddenLines] = useState<Record<string, { rfqLineId: string; reason: string }>>(() => {
    const initial: Record<string, { rfqLineId: string; reason: string }> = {};
    parsedLineItems.forEach(pl => {
      if (pl.overridden && pl.mappedToRfqLineId) {
        initial[pl.id] = { rfqLineId: pl.mappedToRfqLineId, reason: '' };
      }
    });
    return initial;
  });

  const validationChecks = selected
    ? [
        { label: 'Vendor identity confirmed', ok: !!selected.vendorId },
        { label: 'All line items matched to RFQ', ok: selected.errors === 0 && selected.warnings === 0 },
        { label: 'Document digitally signed', ok: selected.confidence > 70 },
        { label: 'Currency and UOM consistent', ok: selected.warnings === 0 },
        { label: 'Validity period specified', ok: selected.confidence > 80 },
      ]
    : [];

  const selQuoteLevelErrors: string[] = selected && 'validationErrors' in selected
    ? (selected as typeof selected & { validationErrors?: string[] }).validationErrors ?? []
    : [];

  const toggleLineExpand = useCallback((lineId: string) => {
    setExpandedLineIds(prev => {
      const next = new Set(prev);
      if (next.has(lineId)) next.delete(lineId);
      else next.add(lineId);
      return next;
    });
  }, []);

  const openOverrideModal = (lineId: string) => {
    setOverrideTargetLine(lineId);
    const existing = overriddenLines[lineId];
    setOverrideSelectedRfqLine(existing?.rfqLineId ?? '');
    setOverrideReason(existing?.reason ?? '');
    setShowOverrideModal(true);
  };

  const confirmOverride = () => {
    if (overrideTargetLine && overrideSelectedRfqLine) {
      setOverriddenLines(prev => ({
        ...prev,
        [overrideTargetLine]: { rfqLineId: overrideSelectedRfqLine, reason: overrideReason },
      }));
    }
    setShowOverrideModal(false);
    setOverrideTargetLine(null);
    setOverrideSelectedRfqLine('');
    setOverrideReason('');
  };

  const revertOverride = (lineId: string) => {
    setOverriddenLines(prev => {
      const next = { ...prev };
      delete next[lineId];
      return next;
    });
  };

  const isLineOverridden = (lineId: string) => lineId in overriddenLines;
  const getMappedRfqLine = (line: ParsedLineItem) => {
    if (isLineOverridden(line.id)) {
      const overrideRfqLineId = overriddenLines[line.id].rfqLineId;
      return rfqLineItems.find(li => li.id === overrideRfqLineId);
    }
    if (line.rfqLineId) return rfqLineItems.find(li => li.id === line.rfqLineId);
    return undefined;
  };

  if (!selected) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center">
          <p className="text-lg text-slate-500" style={{ fontWeight: 500 }}>Quote submission not found</p>
          <Link to="/quote-intake" className="text-sm text-indigo-600 hover:underline mt-2 inline-block flex items-center justify-center gap-1">
            <ArrowLeft size={14} /> Back to Quote Intake
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-full bg-slate-50">
      {/* Header with Back link */}
      <div className="bg-white border-b border-slate-200 px-6 py-3 flex items-center gap-3">
        <Link
          to="/quote-intake"
          className="flex items-center gap-1.5 text-slate-600 hover:text-slate-900 text-sm"
          style={{ fontWeight: 500 }}
        >
          <ArrowLeft size={16} />
          Back to Quote Intake
        </Link>
      </div>

      {/* Detail Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex items-start justify-between flex-shrink-0">
        <div>
          <div className="flex items-center gap-2 mb-1">
            <h2 className="text-slate-900" style={{ fontWeight: 600 }}>{selVendor?.name ?? 'Unassigned Vendor'}</h2>
            <StatusBadge status={selected.status} />
            {selected.warnings > 0 && (
              <span className="inline-flex items-center gap-1 text-xs bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full" style={{ fontWeight: 500 }}>
                <AlertTriangle size={10} /> {selected.warnings} warning{selected.warnings > 1 ? 's' : ''}
              </span>
            )}
            {selected.errors > 0 && (
              <span className="inline-flex items-center gap-1 text-xs bg-red-50 text-red-700 px-2 py-0.5 rounded-full" style={{ fontWeight: 500 }}>
                <XCircle size={10} /> {selected.errors} error{selected.errors > 1 ? 's' : ''}
              </span>
            )}
          </div>
          <div className="text-slate-500 text-xs flex items-center gap-1.5">
            <FileText size={11} />
            {selected.fileName}
            {selRfq && <> · {selected.rfqId} · {selRfq.title}</>}
            {' · '}Uploaded {formatDateTime(selected.uploadedAt)}
          </div>
        </div>
        <div className="flex items-center gap-2 flex-shrink-0">
          {selected.status === 'pending_assignment' && (
            <button onClick={() => setShowAssign(true)} className="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg px-3 py-2" style={{ fontWeight: 500 }}>
              <Link2 size={14} />
              Assign Document
            </button>
          )}
          {selected.status === 'parsed' && selected.confidence < 80 && (
            <button onClick={() => setShowLowConf(true)} className="flex items-center gap-1.5 border border-amber-300 bg-amber-50 text-amber-700 text-sm rounded-lg px-3 py-2 hover:bg-amber-100">
              <AlertTriangle size={14} />
              Low Confidence
            </button>
          )}
          {selected.status === 'parsed' && (
            <>
              <button onClick={() => setShowReplace(true)} className="flex items-center gap-1.5 border border-slate-200 bg-white text-slate-600 text-sm rounded-lg px-3 py-2 hover:bg-slate-50">
                <RefreshCw size={14} />
                Re-Parse
              </button>
              <button className="flex items-center gap-2 border border-red-200 bg-red-50 text-red-600 text-sm rounded-lg px-3 py-2 hover:bg-red-100">
                <ThumbsDown size={14} />
                Reject
              </button>
              <button className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-lg px-3 py-2" style={{ fontWeight: 500 }}>
                <ThumbsUp size={14} />
                Accept
              </button>
              <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg px-3 py-2" style={{ fontWeight: 500 }}>
                <Sparkles size={14} />
                Accept & Normalize
              </button>
            </>
          )}
        </div>
      </div>

      {/* Tabs */}
      <div className="bg-white border-b border-slate-200 px-6 flex-shrink-0">
        <div className="flex gap-6">
          <button
            onClick={() => setActiveTab('overview')}
            className={`py-3 text-sm border-b-2 transition-colors ${activeTab === 'overview' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
            style={{ fontWeight: activeTab === 'overview' ? 600 : 400 }}
          >
            Overview
          </button>
          <button
            onClick={() => setActiveTab('lines')}
            className={`py-3 text-sm border-b-2 transition-colors ${activeTab === 'lines' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
            style={{ fontWeight: activeTab === 'lines' ? 600 : 400 }}
          >
            Parsed Line Items
            {selLines.length > 0 && (
              <span className="ml-1.5 text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full">{selLines.length}</span>
            )}
          </button>
        </div>
      </div>

      {/* Tab Content */}
      <div className="p-6">
        {activeTab === 'overview' ? (
          <div className="space-y-5">
            {selected.status === 'error' && 'parseError' in selected && (selected as typeof selected & { parseError?: string }).parseError && (
              <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
                <XCircle size={18} className="text-red-600 flex-shrink-0 mt-0.5" />
                <div>
                  <p className="text-red-800 text-sm" style={{ fontWeight: 600 }}>Processing Failed</p>
                  <p className="text-red-600 text-xs mt-1">{(selected as typeof selected & { parseError?: string }).parseError}</p>
                </div>
              </div>
            )}

            {selQuoteLevelErrors.length > 0 && (
              <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <div className="flex items-center gap-2 mb-2">
                  <AlertTriangle size={16} className="text-amber-600" />
                  <span className="text-amber-800 text-sm" style={{ fontWeight: 600 }}>Quote-Level Validation Issues</span>
                </div>
                <ul className="space-y-1.5 ml-6">
                  {selQuoteLevelErrors.map((err, i) => (
                    <li key={i} className="text-amber-700 text-xs flex items-start gap-1.5">
                      <span className="text-amber-400 mt-0.5">•</span>
                      {err}
                    </li>
                  ))}
                </ul>
              </div>
            )}

            <div className="grid grid-cols-5 gap-4">
              <div className="col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col">
                <span className="text-slate-700 text-sm mb-3" style={{ fontWeight: 600 }}>Document Preview</span>
                <div className="flex-1 bg-slate-50 rounded-lg border border-dashed border-slate-300 flex items-center justify-center min-h-[280px]">
                  <div className="text-center">
                    <FileText size={32} className="mx-auto text-slate-300 mb-2" />
                    <p className="text-slate-400 text-sm" style={{ fontWeight: 500 }}>{selected.fileName}</p>
                    <p className="text-slate-300 text-xs mt-1">Preview not available in prototype</p>
                    <button className="mt-3 text-indigo-600 text-xs hover:underline" style={{ fontWeight: 500 }}>
                      Download original
                    </button>
                  </div>
                </div>
              </div>

              <div className="col-span-3 space-y-4">
                {selVendor ? (
                  <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Vendor Details</span>
                      <span className={`text-xs px-2 py-0.5 rounded-full ${selVendor.riskLevel === 'low' ? 'bg-emerald-50 text-emerald-700' : selVendor.riskLevel === 'medium' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'}`} style={{ fontWeight: 600 }}>
                        {selVendor.riskLevel} risk
                      </span>
                    </div>
                    <div className="grid grid-cols-2 gap-3 text-xs">
                      <div>
                        <span className="text-slate-500 block mb-0.5">Name</span>
                        <span className="text-slate-800" style={{ fontWeight: 500 }}>{selVendor.name}</span>
                      </div>
                      <div>
                        <span className="text-slate-500 block mb-0.5">Country</span>
                        <span className="text-slate-800" style={{ fontWeight: 500 }}>{selVendor.country}</span>
                      </div>
                      <div>
                        <span className="text-slate-500 block mb-0.5">Score</span>
                        <span className="text-slate-800" style={{ fontWeight: 500 }}>{selVendor.overallScore}/100</span>
                      </div>
                      <div>
                        <span className="text-slate-500 block mb-0.5">Avg Response</span>
                        <span className="text-slate-800" style={{ fontWeight: 500 }}>{selVendor.avgResponseDays} days</span>
                      </div>
                    </div>
                  </div>
                ) : selected.status === 'pending_assignment' ? (
                  <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
                    <Link2 size={24} className="mx-auto text-amber-500 mb-2" />
                    <p className="text-amber-600 text-sm" style={{ fontWeight: 500 }}>Awaiting assignment</p>
                    <p className="text-amber-500 text-xs mt-1">Assign to an RFQ and vendor before parsing</p>
                  </div>
                ) : null}

                <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                  {selected.status === 'processing' ? (
                    <div className="text-center py-3">
                      <RefreshCw size={20} className="mx-auto text-blue-500 animate-spin mb-2" />
                      <p className="text-blue-600 text-sm" style={{ fontWeight: 500 }}>Parsing document...</p>
                      <p className="text-slate-400 text-xs mt-1">Extracting line items and validating structure</p>
                    </div>
                  ) : selected.status === 'error' || selected.status === 'pending_assignment' ? (
                    <div className="text-center py-3">
                      <span className="text-slate-400 text-xs">Parse confidence not available</span>
                    </div>
                  ) : (
                    <>
                      <ConfidenceBar score={selected.confidence} />
                      <div className="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div className="flex justify-between">
                          <span className="text-slate-500">Lines Parsed</span>
                          <span className="text-slate-700" style={{ fontWeight: 600 }}>{selected.lineCount}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-500">Total Amount</span>
                          <span className="text-slate-700" style={{ fontWeight: 600 }}>{formatCurrency(selected.totalAmount)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-500">Warnings</span>
                          <span className={`${selected.warnings > 0 ? 'text-amber-600' : 'text-slate-700'}`} style={{ fontWeight: 600 }}>{selected.warnings}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-500">Errors</span>
                          <span className={`${selected.errors > 0 ? 'text-red-600' : 'text-slate-700'}`} style={{ fontWeight: 600 }}>{selected.errors}</span>
                        </div>
                      </div>
                    </>
                  )}
                </div>

                <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                  <div className="flex items-center justify-between mb-3">
                    <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Validation Results</span>
                    {selected.errors === 0 && selected.warnings === 0 && selected.confidence > 0 ? (
                      <span className="flex items-center gap-1 text-emerald-600 text-xs" style={{ fontWeight: 500 }}>
                        <CheckCircle size={12} /> All checks passed
                      </span>
                    ) : selected.confidence === 0 ? (
                      <span className="flex items-center gap-1 text-slate-400 text-xs" style={{ fontWeight: 500 }}>
                        <Clock size={12} /> Awaiting parse
                      </span>
                    ) : (
                      <span className="flex items-center gap-1 text-amber-600 text-xs" style={{ fontWeight: 500 }}>
                        <AlertTriangle size={12} /> {selected.warnings + selected.errors} issue{selected.warnings + selected.errors > 1 ? 's' : ''} found
                      </span>
                    )}
                  </div>
                  {selected.confidence > 0 ? (
                    <div className="space-y-1.5">
                      {validationChecks.map(check => (
                        <div key={check.label} className={`flex items-center gap-2 ${check.ok ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200'} rounded-lg px-3 py-2`}>
                          {check.ok ? (
                            <CheckCircle size={13} className="text-emerald-500 flex-shrink-0" />
                          ) : (
                            <AlertTriangle size={13} className="text-red-500 flex-shrink-0" />
                          )}
                          <span className={`${check.ok ? 'text-emerald-700' : 'text-red-700'} text-xs`}>{check.label}</span>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="py-6 text-center text-slate-400 text-xs">Validation will run after document is parsed</div>
                  )}
                </div>
              </div>
            </div>
          </div>
        ) : (
          <div>
            {selLines.length === 0 ? (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm py-12 text-center">
                <FileText size={28} className="mx-auto text-slate-300 mb-2" />
                <p className="text-slate-400 text-sm">No parsed line items available</p>
                <p className="text-slate-300 text-xs mt-1">
                  {selected.status === 'processing' ? 'Document is still being processed...' : selected.status === 'error' ? 'Processing failed — no line items extracted' : 'Line items will appear after parsing'}
                </p>
              </div>
            ) : (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                  <h3 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Parsed Line Items</h3>
                  <span className="text-slate-500 text-xs">{selLines.length} items · {formatCurrency(selected.totalAmount)}</span>
                </div>
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-slate-100 bg-slate-50">
                      <th className="w-8 px-2 py-2.5" />
                      <th className="text-left px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>DESCRIPTION</th>
                      <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>QTY</th>
                      <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>UOM</th>
                      <th className="text-right px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>UNIT PRICE</th>
                      <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>CONFIDENCE</th>
                      <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>STATUS</th>
                      <th className="text-right px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>ACTIONS</th>
                    </tr>
                  </thead>
                  <tbody>
                    {selLines.map(line => {
                      const isExpanded = expandedLineIds.has(line.id);
                      const overridden = isLineOverridden(line.id);
                      const mapped = getMappedRfqLine(line);
                      const hasWarning = !!line.validationWarning;
                      const confColor = line.confidence > 90 ? 'text-emerald-600' : line.confidence >= 75 ? 'text-amber-600' : 'text-red-600';

                      return (
                        <Fragment key={line.id}>
                          <tr className={`border-b border-slate-50 hover:bg-slate-50 transition-colors ${overridden ? 'bg-indigo-50/30' : ''}`}>
                            <td className="px-2 py-2.5 text-center">
                              <button onClick={() => toggleLineExpand(line.id)} className="p-0.5 rounded hover:bg-slate-200 text-slate-400">
                                {isExpanded ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
                              </button>
                            </td>
                            <td className="px-3 py-2.5">
                              <div className="flex items-center gap-2">
                                <span className="text-slate-700 text-sm">{line.description}</span>
                                {overridden && (
                                  <span className="inline-flex items-center gap-1 text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full" style={{ fontWeight: 600 }}>
                                    <Info size={8} /> Override
                                  </span>
                                )}
                              </div>
                            </td>
                            <td className="px-3 py-2.5 text-center text-slate-600 text-sm">{line.quantity}</td>
                            <td className="px-3 py-2.5 text-center">
                              <span className={`text-xs px-2 py-0.5 rounded ${hasWarning && line.validationWarning?.toLowerCase().includes('uom') ? 'bg-amber-50 text-amber-700' : 'text-slate-600'}`}>{line.uom}</span>
                            </td>
                            <td className="px-3 py-2.5 text-right text-slate-800 text-sm" style={{ fontWeight: 500 }}>{formatCurrency(line.unitPrice, line.currency)}</td>
                            <td className="px-3 py-2.5 text-center">
                              <span className={`text-xs ${confColor}`} style={{ fontWeight: 600 }}>{line.confidence}%</span>
                            </td>
                            <td className="px-3 py-2.5 text-center">
                              {hasWarning ? (
                                <AlertTriangle size={14} className="text-amber-500 mx-auto" />
                              ) : mapped ? (
                                <CheckCircle size={14} className="text-emerald-500 mx-auto" />
                              ) : (
                                <span className="text-xs text-slate-400">Unmapped</span>
                              )}
                            </td>
                            <td className="px-3 py-2.5 text-right">
                              <div className="flex items-center gap-1 justify-end">
                                {selected.status === 'accepted' && (
                                  <button
                                    onClick={(e) => { e.stopPropagation(); openOverrideModal(line.id); }}
                                    className="text-xs text-indigo-600 hover:text-indigo-800 px-1.5 py-1 rounded hover:bg-indigo-50"
                                    style={{ fontWeight: 500 }}
                                  >
                                    <Pencil size={12} />
                                  </button>
                                )}
                                {overridden && (
                                  <button
                                    onClick={(e) => { e.stopPropagation(); revertOverride(line.id); }}
                                    className="text-xs text-amber-600 hover:text-amber-800 px-1.5 py-1 rounded hover:bg-amber-50"
                                    style={{ fontWeight: 500 }}
                                  >
                                    <Undo2 size={12} />
                                  </button>
                                )}
                              </div>
                            </td>
                          </tr>

                          {isExpanded && (
                            <tr className="bg-slate-50/80">
                              <td colSpan={8} className="px-5 py-3">
                                <div className="grid grid-cols-4 gap-4 text-xs">
                                  <div>
                                    <span className="text-slate-500 block mb-0.5">Currency</span>
                                    <span className="text-slate-800" style={{ fontWeight: 500 }}>{line.currency}</span>
                                  </div>
                                  <div>
                                    <span className="text-slate-500 block mb-0.5">Line Total</span>
                                    <span className="text-slate-800" style={{ fontWeight: 500 }}>{formatCurrency(line.unitPrice * line.quantity, line.currency)}</span>
                                  </div>
                                  <div>
                                    <span className="text-slate-500 block mb-0.5">Mapped To</span>
                                    {mapped ? (
                                      <span className="text-slate-800" style={{ fontWeight: 500 }}>{mapped.description}</span>
                                    ) : (
                                      <span className="text-slate-400 italic">Not mapped</span>
                                    )}
                                  </div>
                                  <div>
                                    <span className="text-slate-500 block mb-0.5">RFQ Line ID</span>
                                    <span className="text-slate-800" style={{ fontWeight: 500 }}>{overridden ? overriddenLines[line.id].rfqLineId : (line.rfqLineId ?? '—')}</span>
                                  </div>
                                </div>
                                {line.validationWarning && (
                                  <div className="mt-2 flex items-center gap-1.5 text-amber-600 text-xs bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5">
                                    <AlertTriangle size={11} className="flex-shrink-0" />
                                    {line.validationWarning}
                                  </div>
                                )}
                                {overridden && overriddenLines[line.id].reason && (
                                  <div className="mt-2 flex items-center gap-1.5 text-indigo-600 text-xs bg-indigo-50 border border-indigo-200 rounded-lg px-3 py-1.5">
                                    <Info size={11} className="flex-shrink-0" />
                                    Override reason: {overriddenLines[line.id].reason}
                                  </div>
                                )}
                                {selected.status === 'accepted' && selRfqLines.length > 0 && (
                                  <div className="mt-3 flex items-center gap-2">
                                    <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Map to RFQ line:</span>
                                    <select
                                      value={overridden ? overriddenLines[line.id].rfqLineId : (line.rfqLineId ?? '')}
                                      onChange={e => {
                                        if (e.target.value) {
                                          setOverriddenLines(prev => ({
                                            ...prev,
                                            [line.id]: { rfqLineId: e.target.value, reason: prev[line.id]?.reason ?? '' },
                                          }));
                                        }
                                      }}
                                      className="text-xs border border-slate-200 rounded px-2 py-1 bg-white text-slate-700"
                                    >
                                      <option value="">— Select —</option>
                                      {selRfqLines.map(rli => (
                                        <option key={rli.id} value={rli.id}>{rli.id}: {rli.description}</option>
                                      ))}
                                    </select>
                                  </div>
                                )}
                              </td>
                            </tr>
                          )}
                        </Fragment>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}
      </div>

      {/* SlideOvers */}
      <SlideOver open={showLowConf} onOpenChange={setShowLowConf} title="Low Confidence Extraction" description={`Parse confidence is ${selected.confidence}% — below the 80% threshold.`} width="md" footer={<div className="flex justify-end"><button onClick={() => setShowLowConf(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Dismiss</button></div>}>
        <div className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
            <AlertTriangle size={18} className="text-amber-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="text-amber-800 text-sm" style={{ fontWeight: 600 }}>Below confidence threshold</p>
              <p className="text-amber-600 text-xs mt-1">This extraction has a low confidence score. Review the options below:</p>
            </div>
          </div>
          {[{ label: 'Accept as-is', desc: 'Proceed with current parse results despite low confidence.', icon: CheckCircle, color: 'text-emerald-600' }, { label: 'Edit & Accept', desc: 'Open in normalization workspace to manually fix line mappings.', icon: Eye, color: 'text-indigo-600' }, { label: 'Reject', desc: 'Reject this submission. Vendor will need to resubmit.', icon: XCircle, color: 'text-red-600' }].map(opt => {
            const Icon = opt.icon;
            return (
              <button key={opt.label} onClick={() => setShowLowConf(false)} className="w-full flex items-start gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/20 text-left transition-colors">
                <Icon size={16} className={`${opt.color} mt-0.5 flex-shrink-0`} />
                <div>
                  <div className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{opt.label}</div>
                  <div className="text-slate-400 text-xs">{opt.desc}</div>
                </div>
              </button>
            );
          })}
        </div>
      </SlideOver>

      <SlideOver open={showReplace} onOpenChange={setShowReplace} title="Replace & Re-Parse" description="Upload a new version of this quote document." width="md" footer={<div className="flex justify-end gap-3"><button onClick={() => setShowReplace(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button><button onClick={() => { setShowReplace(false); setShowConfirmReparse(true); }} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Upload & Re-Parse</button></div>}>
        <div className="space-y-5">
          <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700 flex items-start gap-2">
            <AlertTriangle size={13} className="mt-0.5 flex-shrink-0" />
            <span>Uploading a new file will overwrite the current parsed data. This action is logged for audit.</span>
          </div>
          <div className="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-indigo-400 hover:bg-indigo-50/20 transition-colors">
            <Upload size={24} className="mx-auto text-slate-400 mb-2" />
            <p className="text-slate-600 text-sm" style={{ fontWeight: 500 }}>Drop replacement file here</p>
            <p className="text-slate-400 text-xs mt-1">PDF, XLSX, DOCX</p>
          </div>
        </div>
      </SlideOver>

      <SlideOver open={showConfirmReparse} onOpenChange={setShowConfirmReparse} title="Confirm Re-Parse" description="This will replace all existing parsed data." width="sm" footer={<div className="flex justify-end gap-3"><button onClick={() => setShowConfirmReparse(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button><button onClick={() => setShowConfirmReparse(false)} className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Confirm Re-Parse</button></div>}>
        <div className="space-y-4">
          <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <AlertTriangle size={18} className="text-red-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="text-red-800 text-sm" style={{ fontWeight: 600 }}>Warning: Data overwrite</p>
              <p className="text-red-600 text-xs mt-1">Re-parsing will permanently overwrite the currently parsed line items, mappings, and validation results for this submission.</p>
            </div>
          </div>
          <div className="bg-slate-50 rounded-lg p-3 space-y-1.5 text-xs">
            <div className="flex justify-between"><span className="text-slate-500">Current lines</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{selected.lineCount}</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Current confidence</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{selected.confidence}%</span></div>
            <div className="flex justify-between"><span className="text-slate-500">File</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{selected.fileName}</span></div>
          </div>
        </div>
      </SlideOver>

      <SlideOver open={showAssign} onOpenChange={setShowAssign} title="Assign Document" description="Link this uploaded document to an RFQ and vendor." width="md" footer={<div className="flex justify-end gap-3"><button onClick={() => setShowAssign(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button><button onClick={() => setShowAssign(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Assign & Parse</button></div>}>
        <div className="space-y-5">
          <div className="bg-slate-50 rounded-lg p-3 text-xs text-slate-700"><span style={{ fontWeight: 600 }}>File:</span> {selected.fileName}</div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Select RFQ</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option value="">— Select RFQ —</option>
              {rfqs.filter(r => r.status === 'open' || r.status === 'closed').map(r => <option key={r.id} value={r.id}>{r.id} · {r.title}</option>)}
            </select>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Select Vendor</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option value="">— Select Vendor —</option>
              {vendors.map(v => <option key={v.id} value={v.id}>{v.name} ({v.country})</option>)}
            </select>
          </div>
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700 flex items-start gap-2">
            <Eye size={13} className="mt-0.5 flex-shrink-0" />
            <span>Once assigned, the document will automatically enter the parsing pipeline.</span>
          </div>
        </div>
      </SlideOver>

      {showOverrideModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30" onClick={() => setShowOverrideModal(false)}>
          <div className="bg-white rounded-xl shadow-xl border border-slate-200 w-[420px] max-w-[90vw]" onClick={e => e.stopPropagation()}>
            <div className="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
              <h3 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Manual Override</h3>
              <button onClick={() => setShowOverrideModal(false)} className="text-slate-400 hover:text-slate-600"><X size={16} /></button>
            </div>
            <div className="px-5 py-4 space-y-4">
              {overrideTargetLine && (() => {
                const targetLine = parsedLineItems.find(pl => pl.id === overrideTargetLine);
                return targetLine ? <div className="bg-slate-50 rounded-lg p-3 text-xs"><span className="text-slate-500">Overriding: </span><span className="text-slate-800" style={{ fontWeight: 500 }}>{targetLine.description}</span></div> : null;
              })()}
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Map to RFQ Line Item</label>
                <select value={overrideSelectedRfqLine} onChange={e => setOverrideSelectedRfqLine(e.target.value)} className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option value="">— Select RFQ Line —</option>
                  {selRfqLines.map(rli => <option key={rli.id} value={rli.id}>{rli.id}: {rli.description} ({rli.quantity} {rli.uom})</option>)}
                </select>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Reason (optional)</label>
                <textarea value={overrideReason} onChange={e => setOverrideReason(e.target.value)} className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 resize-none" rows={2} placeholder="Why are you overriding the AI mapping?" />
              </div>
            </div>
            <div className="px-5 py-3 border-t border-slate-200 flex justify-end gap-3">
              <button onClick={() => setShowOverrideModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={confirmOverride} disabled={!overrideSelectedRfqLine} className="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Confirm Override</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
