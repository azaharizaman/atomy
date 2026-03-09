import { useState, useMemo } from 'react';
import {
  Upload, CheckCircle, XCircle, AlertTriangle, Clock, Search,
  Eye, ThumbsUp, ThumbsDown, FileText, RefreshCw, ChevronRight,
  Filter, ArrowUpCircle, Link2, Inbox, Sparkles,
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import { RichTooltip, TooltipBadge, StatusDot } from '../components/RichTooltip';
import {
  quoteSubmissions, rfqs, vendors, statusColors,
  formatDateTime, getVendorById, getRfqById, formatCurrency, getTimeAgo,
  type QuoteStatus,
} from '../data/mockData';

function StatusBadge({ status }: { status: QuoteStatus }) {
  const map: Record<QuoteStatus, { label: string; icon: typeof Clock }> = {
    processing: { label: 'Processing', icon: Clock },
    parsed: { label: 'Parsed', icon: AlertTriangle },
    accepted: { label: 'Accepted', icon: CheckCircle },
    rejected: { label: 'Rejected', icon: XCircle },
    pending_assignment: { label: 'Pending Assignment', icon: Link2 },
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

function InlineConfidenceBar({ score }: { score: number }) {
  const color = score > 90 ? 'bg-emerald-500' : score >= 75 ? 'bg-amber-500' : 'bg-red-500';
  const textColor = score > 90 ? 'text-emerald-600' : score >= 75 ? 'text-amber-600' : 'text-red-600';
  return (
    <div className="flex items-center gap-2">
      <div className="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
        <div className={`h-full rounded-full ${color}`} style={{ width: `${score}%` }} />
      </div>
      <span className={`text-xs ${textColor}`} style={{ fontWeight: 600 }}>{score}%</span>
    </div>
  );
}

const statusFilterOptions: Array<{ value: QuoteStatus | 'all'; label: string }> = [
  { value: 'all', label: 'All' },
  { value: 'processing', label: 'Processing' },
  { value: 'parsed', label: 'Parsed' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'pending_assignment', label: 'Pending' },
];

export function QuoteIntakeInbox() {
  const [selectedId, setSelectedId] = useState<string>(quoteSubmissions[0]?.id ?? '');
  const [statusFilter, setStatusFilter] = useState<QuoteStatus | 'all'>('all');
  const [rfqFilter, setRfqFilter] = useState<string>('all');
  const [vendorFilter, setVendorFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');

  const [showUpload, setShowUpload] = useState(false);
  const [showLowConf, setShowLowConf] = useState(false);
  const [showReplace, setShowReplace] = useState(false);
  const [showConfirmReparse, setShowConfirmReparse] = useState(false);
  const [showAssign, setShowAssign] = useState(false);
  const [dragging, setDragging] = useState(false);

  const uniqueRfqs = useMemo(() => {
    const ids = [...new Set(quoteSubmissions.map(q => q.rfqId).filter(Boolean))];
    return ids.map(id => ({ id, label: getRfqById(id)?.title ?? id }));
  }, []);

  const uniqueVendors = useMemo(() => {
    const ids = [...new Set(quoteSubmissions.map(q => q.vendorId).filter(Boolean))];
    return ids.map(id => ({ id, label: getVendorById(id)?.name ?? id }));
  }, []);

  const filtered = useMemo(() => {
    return quoteSubmissions.filter(q => {
      if (statusFilter !== 'all' && q.status !== statusFilter) return false;
      if (rfqFilter !== 'all' && q.rfqId !== rfqFilter) return false;
      if (vendorFilter !== 'all' && q.vendorId !== vendorFilter) return false;
      if (searchQuery) {
        const hay = `${q.fileName} ${getVendorById(q.vendorId)?.name ?? ''} ${q.rfqId}`.toLowerCase();
        if (!hay.includes(searchQuery.toLowerCase())) return false;
      }
      return true;
    });
  }, [statusFilter, rfqFilter, vendorFilter, searchQuery]);

  const selected = quoteSubmissions.find(q => q.id === selectedId);
  const selVendor = selected ? getVendorById(selected.vendorId) : null;
  const selRfq = selected ? getRfqById(selected.rfqId) : null;

  const validationChecks = selected
    ? [
        { label: 'Vendor identity confirmed', ok: !!selected.vendorId },
        { label: 'All line items matched to RFQ', ok: selected.errors === 0 && selected.warnings === 0 },
        { label: 'Document digitally signed', ok: selected.confidence > 70 },
        { label: 'Currency and UOM consistent', ok: selected.warnings === 0 },
        { label: 'Validity period specified', ok: selected.confidence > 80 },
      ]
    : [];

  return (
    <div className="flex h-full bg-slate-50">
      {/* LEFT: Queue List */}
      <div className="w-[340px] flex-shrink-0 bg-white border-r border-slate-200 flex flex-col">
        <div className="px-4 py-3 border-b border-slate-100 flex-shrink-0">
          <div className="flex items-center justify-between mb-2">
            <h2 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Quote Intake Queue</h2>
            <button onClick={() => setShowUpload(true)} className="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-2.5 py-1.5 rounded-lg" style={{ fontWeight: 500 }}>
              <Upload size={12} />
              Upload
            </button>
          </div>

          <div className="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 mb-2">
            <Search size={12} className="text-slate-400" />
            <input
              className="bg-transparent flex-1 text-xs text-slate-700 outline-none placeholder:text-slate-400"
              placeholder="Search queue..."
              value={searchQuery}
              onChange={e => setSearchQuery(e.target.value)}
            />
          </div>

          {/* Status pills */}
          <div className="flex gap-1.5 flex-wrap mb-2">
            {statusFilterOptions.map(f => (
              <button
                key={f.value}
                onClick={() => setStatusFilter(f.value)}
                className={`text-xs px-2.5 py-1 rounded-full border transition-colors ${statusFilter === f.value ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 text-slate-500 hover:border-slate-300'}`}
                style={{ fontWeight: statusFilter === f.value ? 600 : 400 }}
              >
                {f.label}
              </button>
            ))}
          </div>

          {/* RFQ + Vendor filter row */}
          <div className="flex gap-2">
            <div className="flex items-center gap-1 flex-1">
              <Filter size={10} className="text-slate-400" />
              <select
                value={rfqFilter}
                onChange={e => setRfqFilter(e.target.value)}
                className="flex-1 text-xs border border-slate-200 rounded px-2 py-1 bg-white text-slate-600"
              >
                <option value="all">All RFQs</option>
                {uniqueRfqs.map(r => <option key={r.id} value={r.id}>{r.id}</option>)}
              </select>
            </div>
            <select
              value={vendorFilter}
              onChange={e => setVendorFilter(e.target.value)}
              className="flex-1 text-xs border border-slate-200 rounded px-2 py-1 bg-white text-slate-600"
            >
              <option value="all">All Vendors</option>
              {uniqueVendors.map(v => <option key={v.id} value={v.id}>{v.label}</option>)}
            </select>
          </div>
        </div>

        {/* Queue Items */}
        <div className="flex-1 overflow-y-auto">
          {filtered.length === 0 && (
            <div className="px-4 py-10 text-center text-slate-400 text-xs">No submissions match filters</div>
          )}
          {filtered.map(q => {
            const vendor = getVendorById(q.vendorId);
            const rfq = getRfqById(q.rfqId);
            return (
              <div
                key={q.id}
                onClick={() => setSelectedId(q.id)}
                className={`px-4 py-3 border-b border-slate-100 cursor-pointer transition-colors ${selectedId === q.id ? 'bg-indigo-50 border-l-2 border-l-indigo-500' : 'hover:bg-slate-50'}`}
              >
                <div className="flex items-start justify-between gap-2 mb-1">
                  <span className="text-slate-800 text-xs truncate" style={{ fontWeight: 500 }}>{q.fileName}</span>
                  <StatusBadge status={q.status} />
                </div>
                <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
                  {vendor ? (
                    <RichTooltip
                      trigger={
                        <span className="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 cursor-default hover:bg-slate-200 truncate max-w-[120px]" style={{ fontWeight: 500 }}>
                          {vendor.name}
                        </span>
                      }
                      side="right"
                    >
                      <div className="text-sm text-slate-900 mb-2" style={{ fontWeight: 600 }}>{vendor.name}</div>
                      <TooltipBadge label="Country" value={vendor.country} />
                      <TooltipBadge label="Category" value={vendor.category} />
                      <TooltipBadge label="Score" value={`${vendor.overallScore}/100`} color={vendor.overallScore >= 85 ? 'text-emerald-600' : 'text-amber-600'} />
                      <StatusDot status={vendor.riskLevel === 'low' ? 'good' : vendor.riskLevel === 'medium' ? 'warning' : 'danger'} label={`${vendor.riskLevel} risk`} />
                    </RichTooltip>
                  ) : (
                    <span className="bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded text-xs" style={{ fontWeight: 500 }}>Unassigned</span>
                  )}
                  {rfq && <span className="truncate">{q.rfqId}</span>}
                </div>

                {q.status !== 'processing' && q.status !== 'pending_assignment' && q.confidence > 0 && (
                  <InlineConfidenceBar score={q.confidence} />
                )}
                {q.status === 'processing' && (
                  <div className="flex items-center gap-2 text-blue-600 text-xs">
                    <RefreshCw size={10} className="animate-spin" />
                    Parsing...
                  </div>
                )}
                {q.status === 'pending_assignment' && (
                  <div className="flex items-center gap-1.5 text-amber-600 text-xs mt-0.5">
                    <Link2 size={10} />
                    Needs assignment
                  </div>
                )}
                <div className="text-slate-400 text-xs mt-1">{getTimeAgo(q.uploadedAt)}</div>
              </div>
            );
          })}
        </div>
      </div>

      {/* RIGHT: Detail Panel */}
      {selected ? (
        <div className="flex-1 overflow-auto">
          {/* Detail Header */}
          <div className="bg-white border-b border-slate-200 px-6 py-4 flex items-start justify-between sticky top-0 z-10">
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

          <div className="p-6 space-y-5">
            {/* Confidence + Summary */}
            <div className="grid grid-cols-3 gap-4">
              <div className="col-span-1 bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                {selected.status === 'processing' || selected.status === 'pending_assignment' ? (
                  <div className="text-center py-4">
                    {selected.status === 'processing' ? (
                      <>
                        <RefreshCw size={24} className="mx-auto text-blue-500 animate-spin mb-2" />
                        <p className="text-blue-600 text-sm" style={{ fontWeight: 500 }}>Parsing document...</p>
                        <p className="text-slate-400 text-xs mt-1">Extracting line items and validating structure</p>
                      </>
                    ) : (
                      <>
                        <Link2 size={24} className="mx-auto text-amber-500 mb-2" />
                        <p className="text-amber-600 text-sm" style={{ fontWeight: 500 }}>Awaiting assignment</p>
                        <p className="text-slate-400 text-xs mt-1">Assign to an RFQ and vendor before parsing</p>
                      </>
                    )}
                  </div>
                ) : (
                  <>
                    <ConfidenceBar score={selected.confidence} />
                    <div className="mt-4 space-y-2">
                      <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Lines Parsed</span>
                        <span className="text-slate-700" style={{ fontWeight: 600 }}>{selected.lineCount}</span>
                      </div>
                      <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Total Amount</span>
                        <span className="text-slate-700" style={{ fontWeight: 600 }}>{formatCurrency(selected.totalAmount)}</span>
                      </div>
                      <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Warnings</span>
                        <span className={`${selected.warnings > 0 ? 'text-amber-600' : 'text-slate-700'}`} style={{ fontWeight: 600 }}>{selected.warnings}</span>
                      </div>
                      <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Errors</span>
                        <span className={`${selected.errors > 0 ? 'text-red-600' : 'text-slate-700'}`} style={{ fontWeight: 600 }}>{selected.errors}</span>
                      </div>
                      <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Parsed At</span>
                        <span className="text-slate-700" style={{ fontWeight: 600 }}>{formatDateTime(selected.parsedAt)}</span>
                      </div>
                    </div>
                  </>
                )}
              </div>

              {/* Validation Results */}
              <div className="col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-4">
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
                  <div className="space-y-2">
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
                  <div className="py-8 text-center text-slate-400 text-xs">Validation will run after document is parsed</div>
                )}
              </div>
            </div>

            {/* Vendor Info Card */}
            {selVendor && (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <div className="flex items-center justify-between mb-3">
                  <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Vendor Details</span>
                  <span className={`text-xs px-2 py-0.5 rounded-full ${selVendor.riskLevel === 'low' ? 'bg-emerald-50 text-emerald-700' : selVendor.riskLevel === 'medium' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'}`} style={{ fontWeight: 600 }}>
                    {selVendor.riskLevel} risk
                  </span>
                </div>
                <div className="grid grid-cols-4 gap-4 text-xs">
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
            )}

            {/* Parsed Line Items table (static placeholder from RFQ line items) */}
            {selected.lineCount > 0 && selected.status !== 'processing' && (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                  <h3 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Parsed Line Items</h3>
                  <span className="text-slate-500 text-xs">{selected.lineCount} items · {formatCurrency(selected.totalAmount)}</span>
                </div>
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-slate-100 bg-slate-50">
                      <th className="text-left px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>DESCRIPTION</th>
                      <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>QTY</th>
                      <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>UOM</th>
                      <th className="text-right px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>UNIT PRICE</th>
                      <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>STATUS</th>
                    </tr>
                  </thead>
                  <tbody>
                    {[
                      { desc: 'Dell PowerEdge R760 Server', qty: 4, uom: 'EA', price: 18200, ok: true },
                      { desc: '64GB DDR5 ECC RAM Module', qty: 32, uom: 'EA', price: 420, ok: true },
                      { desc: '1.92TB NVMe SSD Enterprise', qty: 16, uom: 'EA', price: 680, ok: selected.errors === 0 },
                      { desc: 'Rack Mount Kit 2U', qty: 4, uom: 'EA', price: 150, ok: true },
                      { desc: '10GbE Network Card Dual Port', qty: 8, uom: 'EA', price: 380, ok: selected.warnings === 0 },
                    ].slice(0, Math.min(5, selected.lineCount)).map((row, i) => (
                      <tr key={i} className="border-b border-slate-50 hover:bg-slate-50">
                        <td className="px-5 py-3 text-slate-700 text-sm">{row.desc}</td>
                        <td className="px-4 py-3 text-center text-slate-600 text-sm">{row.qty}</td>
                        <td className="px-4 py-3 text-center">
                          <span className={`text-xs px-2 py-0.5 rounded ${!row.ok ? 'bg-amber-50 text-amber-700' : 'text-slate-600'}`}>{row.uom}</span>
                        </td>
                        <td className="px-5 py-3 text-right text-slate-800 text-sm" style={{ fontWeight: 500 }}>{formatCurrency(row.price)}</td>
                        <td className="px-4 py-3 text-center">
                          {row.ok ? <CheckCircle size={14} className="text-emerald-500 mx-auto" /> : <AlertTriangle size={14} className="text-amber-500 mx-auto" />}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {selected.lineCount > 5 && (
                  <div className="px-5 py-2.5 text-center border-t border-slate-100">
                    <button className="text-indigo-600 text-xs hover:underline" style={{ fontWeight: 500 }}>
                      View all {selected.lineCount} items
                    </button>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      ) : (
        <div className="flex-1 flex items-center justify-center text-slate-400">
          <div className="text-center">
            <Inbox size={32} className="mx-auto mb-3 opacity-40" />
            <p className="text-sm">Select a quote to review</p>
          </div>
        </div>
      )}

      {/* ── SlideOver: Upload & Parse Quote ───────────────────────── */}
      <SlideOver
        open={showUpload}
        onOpenChange={setShowUpload}
        title="Upload & Parse Quote"
        description="Upload a vendor quote document for automated parsing."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowUpload(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowUpload(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><ArrowUpCircle size={14} /> Upload & Parse</span>
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div
            onDragOver={e => { e.preventDefault(); setDragging(true); }}
            onDragLeave={() => setDragging(false)}
            onDrop={e => { e.preventDefault(); setDragging(false); }}
            className={`border-2 border-dashed rounded-xl p-10 text-center transition-colors ${dragging ? 'border-indigo-400 bg-indigo-50' : 'border-slate-300 hover:border-indigo-400 hover:bg-indigo-50/20'}`}
          >
            <Upload size={28} className="mx-auto text-slate-400 mb-3" />
            <p className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>Drop quote file here</p>
            <p className="text-slate-400 text-xs mt-1">or click to browse · PDF, XLSX, DOCX</p>
            <button className="mt-4 border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Browse Files</button>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Associate with RFQ (optional)</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option value="">— None —</option>
              {rfqs.map(r => <option key={r.id} value={r.id}>{r.id} · {r.title}</option>)}
            </select>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Vendor (optional)</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option value="">— Auto-detect —</option>
              {vendors.map(v => <option key={v.id} value={v.id}>{v.name}</option>)}
            </select>
          </div>
        </div>
      </SlideOver>

      {/* ── SlideOver: Low Confidence Extraction ─────────────────── */}
      <SlideOver
        open={showLowConf}
        onOpenChange={setShowLowConf}
        title="Low Confidence Extraction"
        description={`Parse confidence is ${selected?.confidence ?? 0}% — below the 80% threshold.`}
        width="md"
        footer={
          <div className="flex items-center justify-end">
            <button onClick={() => setShowLowConf(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Dismiss</button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
            <AlertTriangle size={18} className="text-amber-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="text-amber-800 text-sm" style={{ fontWeight: 600 }}>Below confidence threshold</p>
              <p className="text-amber-600 text-xs mt-1">This extraction has a low confidence score. Review the options below:</p>
            </div>
          </div>
          {[
            { label: 'Accept as-is', desc: 'Proceed with current parse results despite low confidence.', icon: CheckCircle, color: 'text-emerald-600' },
            { label: 'Edit & Accept', desc: 'Open in normalization workspace to manually fix line mappings.', icon: Eye, color: 'text-indigo-600' },
            { label: 'Reject', desc: 'Reject this submission. Vendor will need to resubmit.', icon: XCircle, color: 'text-red-600' },
          ].map(opt => {
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

      {/* ── SlideOver: Replace & Re-Parse ────────────────────────── */}
      <SlideOver
        open={showReplace}
        onOpenChange={setShowReplace}
        title="Replace & Re-Parse"
        description="Upload a new version of this quote document."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowReplace(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => { setShowReplace(false); setShowConfirmReparse(true); }} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              Upload & Re-Parse
            </button>
          </div>
        }
      >
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

      {/* ── SlideOver: Confirm Re-Parse ──────────────────────────── */}
      <SlideOver
        open={showConfirmReparse}
        onOpenChange={setShowConfirmReparse}
        title="Confirm Re-Parse"
        description="This will replace all existing parsed data."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowConfirmReparse(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowConfirmReparse(false)} className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              Confirm Re-Parse
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <AlertTriangle size={18} className="text-red-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="text-red-800 text-sm" style={{ fontWeight: 600 }}>Warning: Data overwrite</p>
              <p className="text-red-600 text-xs mt-1">Re-parsing will permanently overwrite the currently parsed line items, mappings, and validation results for this submission.</p>
            </div>
          </div>
          {selected && (
            <div className="bg-slate-50 rounded-lg p-3 space-y-1.5 text-xs">
              <div className="flex justify-between"><span className="text-slate-500">Current lines</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{selected.lineCount}</span></div>
              <div className="flex justify-between"><span className="text-slate-500">Current confidence</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{selected.confidence}%</span></div>
              <div className="flex justify-between"><span className="text-slate-500">File</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{selected.fileName}</span></div>
            </div>
          )}
        </div>
      </SlideOver>

      {/* ── SlideOver: Assign Document ───────────────────────────── */}
      <SlideOver
        open={showAssign}
        onOpenChange={setShowAssign}
        title="Assign Document"
        description="Link this uploaded document to an RFQ and vendor."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowAssign(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowAssign(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              Assign & Parse
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          {selected && (
            <div className="bg-slate-50 rounded-lg p-3 text-xs text-slate-700">
              <span style={{ fontWeight: 600 }}>File:</span> {selected.fileName}
            </div>
          )}
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Select RFQ</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option value="">— Select RFQ —</option>
              {rfqs.filter(r => r.status === 'open' || r.status === 'closed').map(r => (
                <option key={r.id} value={r.id}>{r.id} · {r.title}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Select Vendor</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option value="">— Select Vendor —</option>
              {vendors.map(v => (
                <option key={v.id} value={v.id}>{v.name} ({v.country})</option>
              ))}
            </select>
          </div>
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700 flex items-start gap-2">
            <Eye size={13} className="mt-0.5 flex-shrink-0" />
            <span>Once assigned, the document will automatically enter the parsing pipeline.</span>
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
