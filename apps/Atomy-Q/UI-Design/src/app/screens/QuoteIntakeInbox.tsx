import { useState } from 'react';
import {
  Upload, CheckCircle, XCircle, AlertTriangle, Clock, Search,
  X, Eye, ThumbsUp, ThumbsDown, FileText, RefreshCw, ChevronRight
} from 'lucide-react';

type QuoteStatus = 'processing' | 'parsed' | 'accepted' | 'rejected';

interface QuoteEntry {
  id: string;
  vendor: string;
  rfq: string;
  rfqTitle: string;
  receivedAt: string;
  status: QuoteStatus;
  confidence: number;
  fileType: string;
  issues: string[];
  lines: number;
  totalValue: string;
}

const quotes: QuoteEntry[] = [
  {
    id: 'q1', vendor: 'TechCorp Solutions', rfq: 'RFQ-2401', rfqTitle: 'Server Infrastructure Components',
    receivedAt: '2h ago', status: 'parsed', confidence: 94, fileType: 'PDF',
    issues: [], lines: 12, totalValue: '$198,400',
  },
  {
    id: 'q2', vendor: 'GlobalSupply Inc.', rfq: 'RFQ-2401', rfqTitle: 'Server Infrastructure Components',
    receivedAt: '3h ago', status: 'parsed', confidence: 58, fileType: 'XLSX',
    issues: ['2 line items could not be matched to RFQ', 'Currency not specified on 3 rows', 'UOM ambiguous: "Pcs" vs "EA"'],
    lines: 11, totalValue: '$195,800',
  },
  {
    id: 'q3', vendor: 'FastParts Ltd.', rfq: 'RFQ-2401', rfqTitle: 'Server Infrastructure Components',
    receivedAt: '5h ago', status: 'accepted', confidence: 97, fileType: 'PDF',
    issues: [], lines: 10, totalValue: '$202,600',
  },
  {
    id: 'q4', vendor: 'NovaTech AU', rfq: 'RFQ-2398', rfqTitle: 'Office Supplies Q2 2026',
    receivedAt: '6h ago', status: 'processing', confidence: 0, fileType: 'PDF',
    issues: [], lines: 0, totalValue: '—',
  },
  {
    id: 'q5', vendor: 'PrimeSource Co.', rfq: 'RFQ-2401', rfqTitle: 'Server Infrastructure Components',
    receivedAt: '8h ago', status: 'accepted', confidence: 99, fileType: 'PDF',
    issues: [], lines: 13, totalValue: '$189,200',
  },
  {
    id: 'q6', vendor: 'OldSupplier Corp', rfq: 'RFQ-2395', rfqTitle: 'Network Equipment Refresh',
    receivedAt: '1d ago', status: 'rejected', confidence: 32, fileType: 'DOC',
    issues: ['Unable to parse document structure', 'No vendor signature found', 'Missing 8 of 14 required line items'],
    lines: 6, totalValue: '$41,000',
  },
];

const StatusBadge = ({ status }: { status: QuoteStatus }) => {
  const map = {
    processing: { label: 'Processing', class: 'bg-blue-50 text-blue-700 border-blue-200', icon: Clock },
    parsed: { label: 'Parsed', class: 'bg-amber-50 text-amber-700 border-amber-200', icon: AlertTriangle },
    accepted: { label: 'Accepted', class: 'bg-emerald-50 text-emerald-700 border-emerald-200', icon: CheckCircle },
    rejected: { label: 'Rejected', class: 'bg-red-50 text-red-700 border-red-200', icon: XCircle },
  };
  const { label, class: cls, icon: Icon } = map[status];
  return (
    <span className={`inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border ${cls}`} style={{ fontWeight: 600 }}>
      <Icon size={10} />
      {label}
    </span>
  );
};

function ConfidenceBar({ score }: { score: number }) {
  const color = score >= 85 ? 'bg-emerald-500' : score >= 60 ? 'bg-amber-500' : 'bg-red-500';
  const textColor = score >= 85 ? 'text-emerald-700' : score >= 60 ? 'text-amber-700' : 'text-red-700';
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

export function QuoteIntakeInbox() {
  const [selected, setSelected] = useState<string>('q2');
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [showLowConfModal, setShowLowConfModal] = useState(false);
  const [filter, setFilter] = useState<QuoteStatus | 'all'>('all');
  const [dragging, setDragging] = useState(false);

  const selectedQuote = quotes.find(q => q.id === selected);
  const filtered = filter === 'all' ? quotes : quotes.filter(q => q.status === filter);

  return (
    <div className="flex h-full bg-slate-50">
      {/* LEFT: Queue */}
      <div className="w-80 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col">
        <div className="px-4 py-3 border-b border-slate-100 flex-shrink-0">
          <div className="flex items-center justify-between mb-2">
            <h2 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Quote Intake Queue</h2>
            <button onClick={() => setShowUploadModal(true)} className="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-2.5 py-1.5 rounded-lg" style={{ fontWeight: 500 }}>
              <Upload size={12} />
              Upload
            </button>
          </div>
          <div className="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 mb-2">
            <Search size={12} className="text-slate-400" />
            <input className="bg-transparent flex-1 text-xs text-slate-700 outline-none placeholder:text-slate-400" placeholder="Search queue..." />
          </div>
          {/* Filter Pills */}
          <div className="flex gap-1.5 flex-wrap">
            {(['all', 'processing', 'parsed', 'accepted', 'rejected'] as const).map(f => (
              <button
                key={f}
                onClick={() => setFilter(f)}
                className={`text-xs px-2.5 py-1 rounded-full border transition-colors capitalize ${filter === f ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 text-slate-500 hover:border-slate-300'}`}
                style={{ fontWeight: filter === f ? 600 : 400 }}
              >
                {f}
              </button>
            ))}
          </div>
        </div>

        <div className="flex-1 overflow-y-auto">
          {filtered.map((q) => (
            <div
              key={q.id}
              onClick={() => setSelected(q.id)}
              className={`px-4 py-3 border-b border-slate-100 cursor-pointer transition-colors ${selected === q.id ? 'bg-indigo-50 border-l-2 border-l-indigo-500' : 'hover:bg-slate-50'}`}
            >
              <div className="flex items-start justify-between gap-2 mb-1">
                <span className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{q.vendor}</span>
                <StatusBadge status={q.status} />
              </div>
              <div className="text-slate-500 text-xs mb-1.5">{q.rfq} · {q.rfqTitle}</div>
              {q.status !== 'processing' && q.confidence > 0 && (
                <div className="flex items-center gap-2">
                  <div className="flex-1 h-1 bg-slate-100 rounded-full overflow-hidden">
                    <div className={`h-full rounded-full ${q.confidence >= 85 ? 'bg-emerald-500' : q.confidence >= 60 ? 'bg-amber-500' : 'bg-red-500'}`} style={{ width: `${q.confidence}%` }} />
                  </div>
                  <span className={`text-xs ${q.confidence >= 85 ? 'text-emerald-600' : q.confidence >= 60 ? 'text-amber-600' : 'text-red-600'}`} style={{ fontWeight: 600 }}>{q.confidence}%</span>
                </div>
              )}
              {q.status === 'processing' && (
                <div className="flex items-center gap-2 text-blue-600 text-xs">
                  <RefreshCw size={10} className="animate-spin" />
                  Parsing...
                </div>
              )}
              <div className="text-slate-400 text-xs mt-1">{q.receivedAt}</div>
            </div>
          ))}
        </div>
      </div>

      {/* RIGHT: Detail */}
      {selectedQuote ? (
        <div className="flex-1 overflow-auto">
          {/* Detail Header */}
          <div className="bg-white border-b border-slate-200 px-6 py-4 flex items-start justify-between sticky top-0 z-10">
            <div>
              <div className="flex items-center gap-2 mb-1">
                <h2 className="text-slate-900">{selectedQuote.vendor}</h2>
                <StatusBadge status={selectedQuote.status} />
              </div>
              <div className="text-slate-500 text-xs">{selectedQuote.rfq} · {selectedQuote.rfqTitle} · {selectedQuote.fileType} · Received {selectedQuote.receivedAt}</div>
            </div>
            {selectedQuote.status === 'parsed' && (
              <div className="flex items-center gap-2">
                {selectedQuote.confidence < 70 && (
                  <button onClick={() => setShowLowConfModal(true)} className="flex items-center gap-1.5 border border-amber-300 bg-amber-50 text-amber-700 text-sm rounded-lg px-3 py-2 hover:bg-amber-100">
                    <AlertTriangle size={14} />
                    Low Confidence
                  </button>
                )}
                <button className="flex items-center gap-2 border border-red-200 bg-red-50 text-red-600 text-sm rounded-lg px-3 py-2 hover:bg-red-100">
                  <ThumbsDown size={14} />
                  Reject
                </button>
                <button className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-lg px-3 py-2" style={{ fontWeight: 500 }}>
                  <ThumbsUp size={14} />
                  Accept Quote
                </button>
              </div>
            )}
          </div>

          <div className="p-6 space-y-5">
            {/* Confidence + Summary */}
            <div className="grid grid-cols-3 gap-4">
              <div className="col-span-1 bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                {selectedQuote.status === 'processing' ? (
                  <div className="text-center py-4">
                    <RefreshCw size={24} className="mx-auto text-blue-500 animate-spin mb-2" />
                    <p className="text-blue-600 text-sm" style={{ fontWeight: 500 }}>Parsing document...</p>
                    <p className="text-slate-400 text-xs mt-1">Extracting line items and validating structure</p>
                  </div>
                ) : (
                  <>
                    <ConfidenceBar score={selectedQuote.confidence} />
                    <div className="mt-4 space-y-2">
                      <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Lines Parsed</span>
                        <span className="text-slate-700" style={{ fontWeight: 600 }}>{selectedQuote.lines}</span>
                      </div>
                      <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Total Value</span>
                        <span className="text-slate-700" style={{ fontWeight: 600 }}>{selectedQuote.totalValue}</span>
                      </div>
                      <div className="flex justify-between text-xs">
                        <span className="text-slate-500">File Type</span>
                        <span className="text-slate-700" style={{ fontWeight: 600 }}>{selectedQuote.fileType}</span>
                      </div>
                    </div>
                  </>
                )}
              </div>

              {/* Validation Errors */}
              <div className="col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <div className="flex items-center justify-between mb-3">
                  <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Validation Results</span>
                  {selectedQuote.issues.length === 0 ? (
                    <span className="flex items-center gap-1 text-emerald-600 text-xs" style={{ fontWeight: 500 }}>
                      <CheckCircle size={12} /> All checks passed
                    </span>
                  ) : (
                    <span className="flex items-center gap-1 text-red-600 text-xs" style={{ fontWeight: 500 }}>
                      <XCircle size={12} /> {selectedQuote.issues.length} issues found
                    </span>
                  )}
                </div>

                {selectedQuote.issues.length > 0 ? (
                  <div className="space-y-2">
                    {selectedQuote.issues.map((issue, i) => (
                      <div key={i} className="flex items-start gap-2 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                        <AlertTriangle size={13} className="text-red-500 mt-0.5 flex-shrink-0" />
                        <span className="text-red-700 text-xs">{issue}</span>
                      </div>
                    ))}
                    <div className="flex items-start gap-2 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                      <CheckCircle size={13} className="text-emerald-500 mt-0.5 flex-shrink-0" />
                      <span className="text-emerald-700 text-xs">Vendor identity confirmed</span>
                    </div>
                    <div className="flex items-start gap-2 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                      <CheckCircle size={13} className="text-emerald-500 mt-0.5 flex-shrink-0" />
                      <span className="text-emerald-700 text-xs">Document digitally signed</span>
                    </div>
                  </div>
                ) : (
                  <div className="space-y-2">
                    {['Vendor identity confirmed', 'All line items matched to RFQ', 'Document digitally signed', 'Currency and UOM are consistent', 'Validity period specified'].map(c => (
                      <div key={c} className="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                        <CheckCircle size={13} className="text-emerald-500" />
                        <span className="text-emerald-700 text-xs">{c}</span>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>

            {/* Parsed Line Items */}
            {selectedQuote.status !== 'processing' && selectedQuote.lines > 0 && (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                  <h3 className="text-slate-900">Parsed Line Items</h3>
                  <span className="text-slate-500 text-xs">{selectedQuote.lines} items</span>
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
                      { desc: 'Dell PowerEdge R750 Server', qty: 10, uom: 'EA', price: '$8,200', ok: true },
                      { desc: 'HPE ProLiant DL380 Gen10', qty: 5, uom: 'EA', price: '$9,100', ok: true },
                      { desc: '32GB DDR4 ECC RDIMM', qty: 80, uom: 'Pcs', price: '$420', ok: selectedQuote.issues.length === 0 },
                      { desc: 'Cisco Catalyst 9300 48-Port', qty: 8, uom: 'EA', price: '$5,100', ok: true },
                      { desc: 'Fortinet FortiGate 100F', qty: 2, uom: 'EA', price: '$5,900', ok: selectedQuote.issues.length === 0 },
                    ].map((row, i) => (
                      <tr key={i} className="border-b border-slate-50 hover:bg-slate-50">
                        <td className="px-5 py-3 text-slate-700 text-sm">{row.desc}</td>
                        <td className="px-4 py-3 text-center text-slate-600 text-sm">{row.qty}</td>
                        <td className="px-4 py-3 text-center">
                          <span className={`text-xs px-2 py-0.5 rounded ${!row.ok ? 'bg-amber-50 text-amber-700' : 'text-slate-600'}`}>{row.uom}</span>
                        </td>
                        <td className="px-5 py-3 text-right text-slate-800 text-sm" style={{ fontWeight: 500 }}>{row.price}</td>
                        <td className="px-4 py-3 text-center">
                          {row.ok ? (
                            <CheckCircle size={14} className="text-emerald-500 mx-auto" />
                          ) : (
                            <AlertTriangle size={14} className="text-amber-500 mx-auto" />
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
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

      {/* Upload Modal */}
      {showUploadModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-slate-900 text-sm">Upload Quote</h2>
              <button onClick={() => setShowUploadModal(false)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6">
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
              <div className="mt-4">
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Associate with RFQ</label>
                <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option>RFQ-2401 · Server Infrastructure Components</option>
                  <option>RFQ-2398 · Office Supplies Q2 2026</option>
                </select>
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setShowUploadModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setShowUploadModal(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Upload & Parse</button>
            </div>
          </div>
        </div>
      )}

      {/* Low Confidence Modal */}
      {showLowConfModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center gap-3 px-6 py-5 bg-amber-50 rounded-t-2xl border-b border-amber-200">
              <div className="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                <AlertTriangle size={20} className="text-amber-600" />
              </div>
              <div>
                <h2 className="text-amber-900 text-sm">Low Parse Confidence</h2>
                <p className="text-amber-600 text-xs">Parse confidence is {selectedQuote?.confidence}% — below the 70% threshold</p>
              </div>
            </div>
            <div className="p-6">
              <p className="text-slate-600 text-sm mb-4">This quote has a low confidence score due to parsing issues. Choose how to proceed:</p>
              <div className="space-y-2">
                {[
                  { label: 'Send to Normalization Workspace', desc: 'Manually map and resolve conflicts', icon: ChevronRight },
                  { label: 'Request re-submission from vendor', desc: 'Ask vendor to resubmit in standard format', icon: FileText },
                  { label: 'Accept anyway & flag for review', desc: 'Accept with low-confidence flag for auditors', icon: AlertTriangle },
                ].map(opt => {
                  const Icon = opt.icon;
                  return (
                    <button key={opt.label} onClick={() => setShowLowConfModal(false)} className="w-full flex items-start gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/20 text-left">
                      <Icon size={16} className="text-slate-500 mt-0.5 flex-shrink-0" />
                      <div>
                        <div className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{opt.label}</div>
                        <div className="text-slate-400 text-xs">{opt.desc}</div>
                      </div>
                    </button>
                  );
                })}
              </div>
            </div>
            <div className="flex items-center justify-end px-6 py-4 border-t border-slate-100">
              <button onClick={() => setShowLowConfModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Dismiss</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function Inbox({ size, className }: { size: number; className?: string }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
      <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
      <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
    </svg>
  );
}
