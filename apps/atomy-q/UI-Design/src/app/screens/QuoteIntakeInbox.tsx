import { useState, useMemo } from 'react';
import { useNavigate } from 'react-router';
import {
  Upload, Search, Filter, ArrowUpCircle, Clock, AlertTriangle,
  CheckCircle, XCircle, Link2,
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import {
  quoteSubmissions, rfqs, vendors, statusColors,
  getTimeAgo, getVendorById, getRfqById,
  type QuoteStatus,
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

function InlineConfidence({ score }: { score: number }) {
  if (score <= 0) return <span className="text-slate-400 text-xs">—</span>;
  const color = score > 90 ? 'text-emerald-600' : score >= 75 ? 'text-amber-600' : 'text-red-600';
  return <span className={`text-xs ${color}`} style={{ fontWeight: 600 }}>{score}%</span>;
}

const statusFilterOptions: Array<{ value: QuoteStatus | 'all'; label: string }> = [
  { value: 'all', label: 'All' },
  { value: 'processing', label: 'Processing' },
  { value: 'parsed', label: 'Parsed' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'error', label: 'Error' },
  { value: 'pending_assignment', label: 'Pending' },
];

export function QuoteIntakeInbox() {
  const navigate = useNavigate();
  const [statusFilter, setStatusFilter] = useState<QuoteStatus | 'all'>('all');
  const [rfqFilter, setRfqFilter] = useState<string>('all');
  const [vendorFilter, setVendorFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [showUpload, setShowUpload] = useState(false);
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

  return (
    <div className="min-h-full bg-slate-50">
      {/* Header */}
      <div className="flex items-center justify-between mb-5">
        <div>
          <h1 className="text-xl text-slate-900" style={{ fontWeight: 700 }}>Quote Intake</h1>
          <p className="text-sm text-slate-500 mt-0.5">{filtered.length} submissions</p>
        </div>
        <button
          onClick={() => setShowUpload(true)}
          className="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 transition-colors"
          style={{ fontWeight: 500 }}
        >
          <Upload size={16} />
          Upload
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white border border-slate-200 rounded-xl p-4 mb-4">
        <div className="flex items-center gap-3 flex-wrap">
          <div className="relative flex-1 min-w-[260px]">
            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={e => setSearchQuery(e.target.value)}
              placeholder="Search by file name, vendor, or RFQ..."
              className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400"
            />
          </div>
          <div className="flex gap-1.5 flex-wrap">
            {statusFilterOptions.map(f => (
              <button
                key={f.value}
                onClick={() => setStatusFilter(f.value)}
                className={`text-xs px-3 py-1.5 rounded-lg border transition-colors ${statusFilter === f.value ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 text-slate-500 hover:border-slate-300'}`}
                style={{ fontWeight: statusFilter === f.value ? 600 : 400 }}
              >
                {f.label}
              </button>
            ))}
          </div>
          <div className="flex items-center gap-1">
            <Filter size={14} className="text-slate-400" />
            <select
              value={rfqFilter}
              onChange={e => setRfqFilter(e.target.value)}
              className="text-xs border border-slate-200 rounded-lg pl-3 pr-7 py-2 bg-white text-slate-600"
            >
              <option value="all">All RFQs</option>
              {uniqueRfqs.map(r => <option key={r.id} value={r.id}>{r.id}</option>)}
            </select>
          </div>
          <select
            value={vendorFilter}
            onChange={e => setVendorFilter(e.target.value)}
            className="text-xs border border-slate-200 rounded-lg pl-3 pr-7 py-2 bg-white text-slate-600"
          >
            <option value="all">All Vendors</option>
            {uniqueVendors.map(v => <option key={v.id} value={v.id}>{v.label}</option>)}
          </select>
        </div>
      </div>

      {/* Data Table */}
      <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-200 bg-slate-50/70">
                <th className="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>File</th>
                <th className="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Vendor</th>
                <th className="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>RFQ</th>
                <th className="text-center px-4 py-3 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Status</th>
                <th className="text-center px-4 py-3 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Conf.</th>
                <th className="text-right px-4 py-3 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Uploaded</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filtered.length === 0 && (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-sm text-slate-400">
                    No submissions match filters.
                  </td>
                </tr>
              )}
              {filtered.map(q => {
                const vendor = getVendorById(q.vendorId);
                return (
                  <tr
                    key={q.id}
                    onClick={() => navigate(`/quote-intake/${q.id}`)}
                    className="group hover:bg-slate-50/80 transition-colors cursor-pointer"
                  >
                    <td className="px-4 py-3">
                      <span className="text-slate-900" style={{ fontWeight: 500 }}>{q.fileName}</span>
                    </td>
                    <td className="px-4 py-3">
                      <span className="text-slate-600">{vendor?.name ?? '—'}</span>
                    </td>
                    <td className="px-4 py-3">
                      <span className="text-slate-500">{q.rfqId || '—'}</span>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <StatusBadge status={q.status} />
                    </td>
                    <td className="px-4 py-3 text-center">
                      <InlineConfidence score={q.confidence} />
                    </td>
                    <td className="px-4 py-3 text-right text-slate-400">
                      {getTimeAgo(q.uploadedAt)}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* Upload SlideOver */}
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
    </div>
  );
}
