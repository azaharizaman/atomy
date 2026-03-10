import { useState } from 'react';
import {
  ArrowLeft, ChevronRight, Send, RefreshCw, CheckCircle, XCircle,
  AlertTriangle, Clock, Server, Cloud, Code, FileText, ArrowRight,
  Building, Eye, Play, RotateCcw, Copy, ExternalLink, Search,
  Loader2, Filter, ChevronDown
} from 'lucide-react';
import { Link } from 'react-router';
import { SlideOver } from '../components/SlideOver';
import {
  handoffRecords, rfqs, vendors, awardDecisions,
  statusColors, formatCurrency, getVendorById, getRfqById,
  formatDateTime
} from '../data/mockData';

type HandoffStatus = 'ready' | 'validating' | 'validation_failed' | 'sending' | 'sent' | 'failed';

const statusSteps: { key: HandoffStatus; label: string }[] = [
  { key: 'ready', label: 'Ready' },
  { key: 'validating', label: 'Validating' },
  { key: 'sending', label: 'Sending' },
  { key: 'sent', label: 'Sent' },
];

const destinationIcons: Record<string, typeof Server> = {
  'SAP S/4HANA': Server,
  'Oracle ERP': Cloud,
};

const validationFields = [
  { field: 'po_number', label: 'PO Number', status: 'pass' as const, value: 'PO-20260305-001' },
  { field: 'vendor_code', label: 'Vendor Code', status: 'pass' as const, value: 'V10001' },
  { field: 'total_value', label: 'Total Value', status: 'pass' as const, value: '172500' },
  { field: 'currency', label: 'Currency', status: 'pass' as const, value: 'AUD' },
  { field: 'line_items', label: 'Line Items', status: 'pass' as const, value: '9' },
  { field: 'payment_terms', label: 'Payment Terms', status: 'pass' as const, value: 'Net 30' },
  { field: 'delivery_address', label: 'Delivery Address', status: 'pass' as const, value: '123 Corporate Blvd, Sydney' },
  { field: 'cost_center', label: 'Cost Center', status: 'pass' as const, value: 'CC-IT-2026' },
];

const failedValidationFields = [
  { field: 'po_number', label: 'PO Number', status: 'pass' as const, value: 'PO-20260225-002' },
  { field: 'vendor_code', label: 'Vendor Code', status: 'pass' as const, value: 'V10006' },
  { field: 'total_value', label: 'Total Value', status: 'pass' as const, value: '83500' },
  { field: 'currency', label: 'Currency', status: 'pass' as const, value: 'AUD' },
  { field: 'line_items', label: 'Line Items', status: 'pass' as const, value: '6' },
  { field: 'payment_terms', label: 'Payment Terms', status: 'warn' as const, value: 'Net 45 (non-standard)' },
  { field: 'endpoint_reachable', label: 'Endpoint Reachable', status: 'fail' as const, value: 'Connection timeout after 30s' },
  { field: 'auth_token', label: 'Auth Token', status: 'pass' as const, value: 'Valid (expires 2026-06-01)' },
];

function formatPayload(raw: string): string {
  try {
    return JSON.stringify(JSON.parse(raw), null, 2);
  } catch {
    return raw;
  }
}

function getExpandedPayload(record: typeof handoffRecords[0]): string {
  const base = JSON.parse(record.payload);
  const rfq = getRfqById(record.rfqId);
  const vendor = getVendorById(record.vendorId);
  const expanded = {
    ...base,
    vendor_name: vendor?.name,
    vendor_email: vendor?.contactEmail,
    rfq_id: record.rfqId,
    rfq_title: rfq?.title,
    award_id: record.awardId,
    destination: record.destination,
    created_at: new Date().toISOString(),
    line_items_detail: [
      { item: 'Server Equipment', qty: 4, unit_price: 18200, total: 72800 },
      { item: 'Memory Modules', qty: 32, unit_price: 410, total: 13120 },
      { item: 'Storage Drives', qty: 16, unit_price: 650, total: 10400 },
      { item: 'Network Cards', qty: 8, unit_price: 380, total: 3040 },
      { item: 'Rack Mount Kits', qty: 4, unit_price: 150, total: 600 },
    ],
  };
  return JSON.stringify(expanded, null, 2);
}

export function POContractHandoff() {
  const [selectedHandoffId, setSelectedHandoffId] = useState<string | null>(null);
  const [showValidationModal, setShowValidationModal] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showRetryModal, setShowRetryModal] = useState(false);
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');

  const selectedRecord = handoffRecords.find(h => h.id === selectedHandoffId);

  const filteredRecords = handoffRecords.filter(r => {
    if (filterStatus !== 'all' && r.status !== filterStatus) return false;
    if (searchQuery) {
      const vendor = getVendorById(r.vendorId);
      const rfq = getRfqById(r.rfqId);
      const q = searchQuery.toLowerCase();
      return (
        r.id.toLowerCase().includes(q) ||
        vendor?.name.toLowerCase().includes(q) ||
        rfq?.id.toLowerCase().includes(q) ||
        r.destination.toLowerCase().includes(q)
      );
    }
    return true;
  });

  const getStepStatus = (recordStatus: HandoffStatus, stepKey: HandoffStatus) => {
    const order: Record<HandoffStatus, number> = { ready: 0, validating: 1, validation_failed: 1, sending: 2, sent: 3, failed: 3 };
    const currentIdx = order[recordStatus];
    const stepIdx = order[stepKey];
    if (recordStatus === 'failed' && stepKey === 'sent') return 'failed';
    if (stepIdx < currentIdx) return 'complete';
    if (stepIdx === currentIdx) return recordStatus === 'failed' || recordStatus === 'validation_failed' ? 'failed' : 'active';
    return 'pending';
  };

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>PO/Contract Handoff</span>
        </div>

        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>PO/Contract Handoff</h1>
            <p className="text-xs text-slate-500 mt-1">Send approved award outcomes to ERP and downstream procurement systems</p>
          </div>
          <button
            onClick={() => setShowCreateModal(true)}
            className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2.5 text-sm"
            style={{ fontWeight: 500 }}
          >
            <Send size={14} />
            Create Handoff
          </button>
        </div>

        {/* Summary Stats */}
        <div className="grid grid-cols-4 gap-3 mt-4">
          {[
            { label: 'Total Handoffs', value: handoffRecords.length, color: 'text-slate-800' },
            { label: 'Sent', value: handoffRecords.filter(r => r.status === 'sent').length, color: 'text-emerald-700' },
            { label: 'Failed', value: handoffRecords.filter(r => r.status === 'failed').length, color: 'text-red-700' },
            { label: 'Ready', value: handoffRecords.filter(r => r.status === 'ready').length, color: 'text-blue-700' },
          ].map(stat => (
            <div key={stat.label} className="bg-slate-50 rounded-lg px-4 py-3 border border-slate-100">
              <div className="text-xs text-slate-500">{stat.label}</div>
              <div className={`text-xl ${stat.color}`} style={{ fontWeight: 700 }}>{stat.value}</div>
            </div>
          ))}
        </div>
      </div>

      {/* Filter Bar */}
      <div className="bg-white border-b border-slate-200 px-6 py-3 flex items-center gap-3 flex-shrink-0">
        <div className="relative flex-1 max-w-xs">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Search handoffs..."
            className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-800 focus:border-indigo-400 outline-none"
          />
        </div>
        <div className="flex items-center gap-1.5">
          <Filter size={13} className="text-slate-400" />
          {['all', 'ready', 'sent', 'failed'].map(s => (
            <button
              key={s}
              onClick={() => setFilterStatus(s)}
              className={`px-3 py-1.5 rounded-lg text-xs transition-colors ${
                filterStatus === s ? 'bg-indigo-100 text-indigo-700' : 'text-slate-500 hover:bg-slate-100'
              }`}
              style={{ fontWeight: filterStatus === s ? 600 : 400 }}
            >
              {s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1)}
            </button>
          ))}
        </div>
      </div>

      {/* Main Content */}
      <div className="flex-1 overflow-auto p-5">
        <div className="space-y-4">
          {filteredRecords.map(record => {
            const vendor = getVendorById(record.vendorId);
            const rfq = getRfqById(record.rfqId);
            const award = awardDecisions.find(a => a.id === record.awardId);
            const DestIcon = destinationIcons[record.destination] || Server;

            return (
              <div key={record.id} className={`bg-white rounded-xl border shadow-sm overflow-hidden ${
                record.status === 'failed' ? 'border-red-200' : record.status === 'sent' ? 'border-emerald-200' : 'border-slate-200'
              }`}>
                {/* Record Header */}
                <div className="flex items-center gap-4 px-5 py-4">
                  <div className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${
                    record.status === 'failed' ? 'bg-red-100' : record.status === 'sent' ? 'bg-emerald-100' : 'bg-blue-100'
                  }`}>
                    <DestIcon size={18} className={
                      record.status === 'failed' ? 'text-red-600' : record.status === 'sent' ? 'text-emerald-600' : 'text-blue-600'
                    } />
                  </div>

                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{record.id}</span>
                      <span className={`text-xs px-2 py-0.5 rounded ${statusColors[record.status] || ''}`} style={{ fontWeight: 600 }}>
                        {record.status}
                      </span>
                      <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">
                        <DestIcon size={10} className="inline mr-1" />
                        {record.destination}
                      </span>
                    </div>
                    <div className="flex items-center gap-3 text-xs text-slate-500 mt-1">
                      <span className="flex items-center gap-1"><Building size={10} /> {vendor?.name}</span>
                      <span className="text-slate-300">|</span>
                      <span>RFQ: {rfq?.id}</span>
                      <span className="text-slate-300">|</span>
                      <span style={{ fontWeight: 500 }}>{formatCurrency(award?.totalValue || 0)}</span>
                      {record.sentAt && (
                        <>
                          <span className="text-slate-300">|</span>
                          <span className="flex items-center gap-1"><Clock size={10} /> {formatDateTime(record.sentAt)}</span>
                        </>
                      )}
                    </div>
                  </div>

                  <div className="flex items-center gap-2 flex-shrink-0">
                    <button
                      onClick={() => { setSelectedHandoffId(record.id); setShowValidationModal(true); }}
                      className="flex items-center gap-1.5 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-600 hover:bg-slate-50"
                    >
                      <Eye size={12} />
                      Validation
                    </button>
                    {record.status === 'failed' && (
                      <button
                        onClick={() => { setSelectedHandoffId(record.id); setShowRetryModal(true); }}
                        className="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg px-3 py-2 text-xs"
                        style={{ fontWeight: 500 }}
                      >
                        <RotateCcw size={12} />
                        Retry
                      </button>
                    )}
                    {record.status === 'ready' && (
                      <button className="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-xs" style={{ fontWeight: 500 }}>
                        <Play size={12} />
                        Send
                      </button>
                    )}
                  </div>
                </div>

                {/* Error Message for Failed */}
                {record.status === 'failed' && record.errorMessage && (
                  <div className="mx-5 mb-3 bg-red-50 border border-red-200 rounded-lg p-3">
                    <div className="flex items-start gap-2">
                      <XCircle size={14} className="text-red-500 mt-0.5 flex-shrink-0" />
                      <div>
                        <span className="text-xs text-red-700" style={{ fontWeight: 600 }}>Error: </span>
                        <span className="text-xs text-red-600">{record.errorMessage}</span>
                      </div>
                    </div>
                  </div>
                )}

                {/* Status Timeline */}
                <div className="px-5 pb-4">
                  <div className="flex items-center gap-0">
                    {statusSteps.map((step, i) => {
                      const status = getStepStatus(record.status as HandoffStatus, step.key);
                      return (
                        <div key={step.key} className="flex items-center flex-1">
                          <div className="flex flex-col items-center">
                            <div className={`w-6 h-6 rounded-full flex items-center justify-center ${
                              status === 'complete' ? 'bg-emerald-100' :
                              status === 'active' ? 'bg-indigo-100' :
                              status === 'failed' ? 'bg-red-100' :
                              'bg-slate-100'
                            }`}>
                              {status === 'complete' && <CheckCircle size={13} className="text-emerald-600" />}
                              {status === 'active' && <Loader2 size={13} className="text-indigo-600 animate-spin" />}
                              {status === 'failed' && <XCircle size={13} className="text-red-600" />}
                              {status === 'pending' && <div className="w-2 h-2 rounded-full bg-slate-300" />}
                            </div>
                            <span className={`text-xs mt-1 ${
                              status === 'complete' ? 'text-emerald-600' :
                              status === 'active' ? 'text-indigo-600' :
                              status === 'failed' ? 'text-red-600' :
                              'text-slate-400'
                            }`} style={{ fontWeight: status === 'active' || status === 'failed' ? 600 : 400 }}>
                              {step.label}
                            </span>
                          </div>
                          {i < statusSteps.length - 1 && (
                            <div className={`flex-1 h-px mx-2 ${
                              status === 'complete' ? 'bg-emerald-300' : 'bg-slate-200'
                            }`} />
                          )}
                        </div>
                      );
                    })}
                  </div>
                </div>

                {/* Payload Preview */}
                <div className="border-t border-slate-100 px-5 py-3 bg-slate-50/50">
                  <details className="group">
                    <summary className="flex items-center gap-2 cursor-pointer text-xs text-slate-500 hover:text-slate-700">
                      <Code size={12} />
                      <span style={{ fontWeight: 500 }}>Payload Preview</span>
                      <ChevronDown size={12} className="group-open:rotate-180 transition-transform" />
                      <button
                        onClick={(e) => { e.preventDefault(); navigator.clipboard.writeText(record.payload); }}
                        className="ml-auto flex items-center gap-1 text-slate-400 hover:text-slate-600"
                      >
                        <Copy size={11} />
                        Copy
                      </button>
                    </summary>
                    <div className="mt-3 relative">
                      <pre className="bg-slate-900 text-slate-100 rounded-lg p-4 overflow-x-auto text-xs font-mono leading-relaxed">
                        {formatPayload(record.payload).split('\n').map((line, idx) => (
                          <div key={idx} className="flex">
                            <span className="text-slate-500 select-none w-6 text-right mr-3 flex-shrink-0">{idx + 1}</span>
                            <span>{line.replace(/"(\w+)":/g, (_, k) => `"${k}":`).split(/(".*?")/).map((part, pi) =>
                              part.startsWith('"') && part.endsWith('"') ? (
                                part.includes(':') ? <span key={pi} className="text-sky-400">{part}</span> :
                                <span key={pi} className="text-amber-300">{part}</span>
                              ) : /\d/.test(part) ? (
                                <span key={pi} className="text-emerald-400">{part}</span>
                              ) : (
                                <span key={pi}>{part}</span>
                              )
                            )}</span>
                          </div>
                        ))}
                      </pre>
                    </div>
                  </details>
                </div>
              </div>
            );
          })}

          {filteredRecords.length === 0 && (
            <div className="text-center py-16 text-slate-400">
              <Send size={32} className="mx-auto mb-3 opacity-40" />
              <div className="text-sm" style={{ fontWeight: 500 }}>No handoff records found</div>
              <div className="text-xs mt-1">Adjust filters or create a new handoff</div>
            </div>
          )}
        </div>
      </div>

      {/* Bottom Bar */}
      <div className="bg-white border-t border-slate-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <Link to="/" className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
          <ArrowLeft size={14} />
          Back
        </Link>
        <div className="flex items-center gap-2 text-xs text-slate-400">
          <span>{handoffRecords.length} records</span>
          <span className="text-slate-300">|</span>
          <span>Last sync: {formatDateTime(handoffRecords[0]?.sentAt || '')}</span>
        </div>
      </div>

      {/* ── Payload Validation SlideOver ── */}
      <SlideOver
        open={showValidationModal}
        onOpenChange={setShowValidationModal}
        title="Payload Validation"
        description={selectedRecord ? `${selectedRecord.id} — ${selectedRecord.destination}` : ''}
        width="lg"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowValidationModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
          </div>
        }
      >
        {selectedRecord && (() => {
          const vendor = getVendorById(selectedRecord.vendorId);
          const fields = selectedRecord.status === 'failed' ? failedValidationFields : validationFields;
          const passCount = fields.filter(f => f.status === 'pass').length;
          const failCount = fields.filter(f => f.status === 'fail').length;
          const warnCount = fields.filter(f => f.status === 'warn').length;

          return (
            <div className="space-y-5">
              <div className="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl p-4">
                <Building size={16} className="text-slate-600" />
                <div className="flex-1 min-w-0">
                  <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{vendor?.name}</div>
                  <div className="text-xs text-slate-500">{selectedRecord.destination} · {selectedRecord.id}</div>
                </div>
                <span className={`text-xs px-2 py-0.5 rounded ${statusColors[selectedRecord.status]}`} style={{ fontWeight: 600 }}>
                  {selectedRecord.status}
                </span>
              </div>

              {/* Validation Summary */}
              <div className="flex items-center gap-3">
                <div className="flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                  <CheckCircle size={13} className="text-emerald-600" />
                  <span className="text-xs text-emerald-700" style={{ fontWeight: 600 }}>{passCount} Passed</span>
                </div>
                {warnCount > 0 && (
                  <div className="flex items-center gap-1.5 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                    <AlertTriangle size={13} className="text-amber-600" />
                    <span className="text-xs text-amber-700" style={{ fontWeight: 600 }}>{warnCount} Warning</span>
                  </div>
                )}
                {failCount > 0 && (
                  <div className="flex items-center gap-1.5 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                    <XCircle size={13} className="text-red-600" />
                    <span className="text-xs text-red-700" style={{ fontWeight: 600 }}>{failCount} Failed</span>
                  </div>
                )}
              </div>

              {/* Field-by-field */}
              <div className="border border-slate-200 rounded-xl overflow-hidden">
                <div className="bg-slate-50 px-4 py-2.5 border-b border-slate-200">
                  <span className="text-xs text-slate-500" style={{ fontWeight: 600 }}>FIELD VALIDATION</span>
                </div>
                <div className="divide-y divide-slate-100">
                  {fields.map(f => (
                    <div key={f.field} className={`flex items-center gap-3 px-4 py-3 ${
                      f.status === 'fail' ? 'bg-red-50/50' : f.status === 'warn' ? 'bg-amber-50/50' : ''
                    }`}>
                      {f.status === 'pass' && <CheckCircle size={14} className="text-emerald-500 flex-shrink-0" />}
                      {f.status === 'warn' && <AlertTriangle size={14} className="text-amber-500 flex-shrink-0" />}
                      {f.status === 'fail' && <XCircle size={14} className="text-red-500 flex-shrink-0" />}
                      <div className="flex-1 min-w-0">
                        <span className="text-sm text-slate-700" style={{ fontWeight: 500 }}>{f.label}</span>
                        <code className="text-xs text-slate-400 ml-2 font-mono">{f.field}</code>
                      </div>
                      <span className={`text-xs ${
                        f.status === 'fail' ? 'text-red-600' : f.status === 'warn' ? 'text-amber-600' : 'text-slate-600'
                      }`} style={{ fontWeight: f.status !== 'pass' ? 600 : 400 }}>
                        {f.value}
                      </span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Full Payload */}
              <div>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-xs text-slate-600" style={{ fontWeight: 600 }}>Full Payload</span>
                  <button className="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600">
                    <Copy size={11} />
                    Copy JSON
                  </button>
                </div>
                <pre className="bg-slate-900 text-slate-100 rounded-lg p-4 overflow-x-auto text-xs font-mono leading-relaxed max-h-64">
                  {getExpandedPayload(selectedRecord).split('\n').map((line, idx) => (
                    <div key={idx} className="flex">
                      <span className="text-slate-500 select-none w-6 text-right mr-3 flex-shrink-0">{idx + 1}</span>
                      <span>{line}</span>
                    </div>
                  ))}
                </pre>
              </div>
            </div>
          );
        })()}
      </SlideOver>

      {/* ── Create PO/Contract Handoff SlideOver ── */}
      <SlideOver
        open={showCreateModal}
        onOpenChange={setShowCreateModal}
        title="Create PO/Contract Handoff"
        description="Send an approved award to a downstream system"
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowCreateModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><Send size={14} /> Create & Send</span>
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 500 }}>Award</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white focus:border-indigo-400 outline-none">
              {awardDecisions.map(a => {
                const r = getRfqById(a.rfqId);
                const v = getVendorById(a.winnerVendorId);
                return (
                  <option key={a.id} value={a.id}>
                    {a.id} — {r?.title} ({v?.name}) · {formatCurrency(a.totalValue)}
                  </option>
                );
              })}
            </select>
          </div>

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 500 }}>Destination System</label>
            <div className="grid grid-cols-2 gap-2">
              {[
                { name: 'SAP S/4HANA', icon: Server, status: 'healthy' },
                { name: 'Oracle ERP', icon: Cloud, status: 'degraded' },
              ].map(sys => (
                <label key={sys.name} className="flex items-center gap-3 border border-slate-200 rounded-xl p-3 cursor-pointer hover:bg-slate-50">
                  <input type="radio" name="destination" value={sys.name} className="accent-indigo-600" defaultChecked={sys.name === 'SAP S/4HANA'} />
                  <sys.icon size={16} className="text-slate-600" />
                  <div>
                    <div className="text-sm text-slate-800" style={{ fontWeight: 500 }}>{sys.name}</div>
                    <span className={`text-xs ${sys.status === 'healthy' ? 'text-emerald-600' : 'text-amber-600'}`}>
                      {sys.status}
                    </span>
                  </div>
                </label>
              ))}
            </div>
          </div>

          <div className="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
            <div className="text-xs text-indigo-600 mb-2" style={{ fontWeight: 600 }}>HANDOFF SUMMARY</div>
            <div className="grid grid-cols-2 gap-2 text-xs">
              <div>
                <span className="text-slate-500">Vendor</span>
                <div className="text-slate-800" style={{ fontWeight: 500 }}>TechCorp Solutions</div>
              </div>
              <div>
                <span className="text-slate-500">Total Value</span>
                <div className="text-slate-800" style={{ fontWeight: 500 }}>{formatCurrency(172500)}</div>
              </div>
              <div>
                <span className="text-slate-500">Line Items</span>
                <div className="text-slate-800" style={{ fontWeight: 500 }}>9</div>
              </div>
              <div>
                <span className="text-slate-500">Payment Terms</span>
                <div className="text-slate-800" style={{ fontWeight: 500 }}>Net 30</div>
              </div>
            </div>
          </div>

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 500 }}>Notes (optional)</label>
            <textarea
              rows={2}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none"
              placeholder="Add any notes for the handoff..."
            />
          </div>
        </div>
      </SlideOver>

      {/* ── Retry Handoff SlideOver ── */}
      <SlideOver
        open={showRetryModal}
        onOpenChange={setShowRetryModal}
        title="Retry Handoff"
        description={selectedRecord ? `Retry ${selectedRecord.id} to ${selectedRecord.destination}` : ''}
        width="lg"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowRetryModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><RefreshCw size={14} /> Retry Now</span>
            </button>
          </div>
        }
      >
        {selectedRecord && (() => {
          const vendor = getVendorById(selectedRecord.vendorId);
          return (
            <div className="space-y-5">
              {/* Failure Reason */}
              <div className="bg-red-50 border border-red-200 rounded-xl p-4">
                <div className="flex items-start gap-2">
                  <XCircle size={15} className="text-red-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <div className="text-sm text-red-700 mb-1" style={{ fontWeight: 600 }}>Failure Reason</div>
                    <p className="text-xs text-red-600 leading-relaxed">{selectedRecord.errorMessage}</p>
                    <div className="text-xs text-red-500 mt-2">
                      Failed at: {formatDateTime(selectedRecord.sentAt)}
                    </div>
                  </div>
                </div>
              </div>

              {/* Record Info */}
              <div className="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl p-4">
                <Building size={16} className="text-slate-600" />
                <div className="flex-1 min-w-0">
                  <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{vendor?.name}</div>
                  <div className="text-xs text-slate-500">{selectedRecord.destination} · {formatCurrency(awardDecisions.find(a => a.id === selectedRecord.awardId)?.totalValue || 0)}</div>
                </div>
              </div>

              {/* Editable Payload */}
              <div>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-xs text-slate-600" style={{ fontWeight: 600 }}>Payload (editable)</span>
                  <div className="flex items-center gap-2">
                    <button className="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600">
                      <RotateCcw size={11} />
                      Reset
                    </button>
                    <button className="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600">
                      <Copy size={11} />
                      Copy
                    </button>
                  </div>
                </div>
                <textarea
                  rows={12}
                  className="w-full bg-slate-900 text-emerald-400 rounded-lg p-4 text-xs font-mono leading-relaxed resize-none outline-none border border-slate-700 focus:border-indigo-500"
                  defaultValue={getExpandedPayload(selectedRecord)}
                />
              </div>

              <div className="bg-amber-50 border border-amber-200 rounded-xl p-3">
                <div className="flex items-center gap-2">
                  <AlertTriangle size={14} className="text-amber-600 flex-shrink-0" />
                  <span className="text-xs text-amber-700 leading-relaxed">
                    Retrying will re-validate the payload and attempt to send to <b>{selectedRecord.destination}</b>. 
                    Check the destination system status before retrying.
                  </span>
                </div>
              </div>
            </div>
          );
        })()}
      </SlideOver>
    </div>
  );
}
