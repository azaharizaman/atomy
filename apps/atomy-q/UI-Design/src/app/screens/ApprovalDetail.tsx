import { useState } from 'react';
import {
  ArrowLeft, CheckCircle, XCircle, AlertTriangle, ShieldAlert,
  FileText, BarChart3, Clock, User, Hash, ChevronRight,
  Star, Building, DollarSign, Info, Shield
} from 'lucide-react';
import { Link } from 'react-router';

type ApprovalStatus = 'pending_approval' | 'approved' | 'rejected';
type RiskLevel = 'critical' | 'high' | 'medium' | 'low';
type TabId = 'scoring' | 'matrix' | 'risk' | 'trail';

interface VendorScore {
  name: string;
  overallScore: number;
  priceScore: number;
  deliveryScore: number;
  qualityScore: number;
  riskScore: number;
  recommended: boolean;
}

interface RiskItem {
  id: string;
  title: string;
  level: RiskLevel;
  vendor: string;
  description: string;
}

interface TrailEntry {
  id: string;
  sequence: number;
  eventType: string;
  actor: string;
  timestamp: string;
  entryHash: string;
  previousHash: string;
  verified: boolean;
}

const gateReasons = [
  { id: 'g1', reason: 'Recommended vendor has a single-source dependency flagged as critical risk', icon: ShieldAlert },
  { id: 'g2', reason: 'Total award value exceeds delegated authority threshold ($150,000)', icon: DollarSign },
  { id: 'g3', reason: 'Unresolved conflict of interest declaration for shortlisted vendor', icon: AlertTriangle },
];

const vendorScores: VendorScore[] = [
  { name: 'PrimeSource Co.', overallScore: 91, priceScore: 92, deliveryScore: 88, qualityScore: 95, riskScore: 87, recommended: true },
  { name: 'TechCorp Solutions', overallScore: 86, priceScore: 84, deliveryScore: 91, qualityScore: 88, riskScore: 79, recommended: false },
  { name: 'FastParts Ltd.', overallScore: 78, priceScore: 88, deliveryScore: 76, qualityScore: 74, riskScore: 81, recommended: false },
  { name: 'GlobalSupply Inc.', overallScore: 74, priceScore: 76, deliveryScore: 82, qualityScore: 70, riskScore: 68, recommended: false },
];

const matrixSummary = {
  lineItems: 8,
  categories: 3,
  vendors: 4,
  lowestTotal: 186400,
  recommendedTotal: 189200,
  lowestVendor: 'PrimeSource Co.',
  terms: [
    { label: 'Payment Terms', recommended: 'Net 60', best: 'Net 60 (PrimeSource)' },
    { label: 'Delivery Lead Time', recommended: '2 weeks', best: '10 days (FastParts)' },
    { label: 'Warranty Period', recommended: '5 years', best: '5 years (PrimeSource)' },
  ],
};

const riskItems: RiskItem[] = [
  { id: 'ri1', title: 'Single Source Dependency', level: 'critical', vendor: 'PrimeSource Co.', description: '4 of 13 critical line items sole-sourced. Business continuity risk if supply disruption occurs.' },
  { id: 'ri2', title: 'Missing Conflict of Interest Declaration', level: 'high', vendor: 'TechCorp Solutions', description: 'Vendor has a known relationship with a board member. COI declaration not filed for this RFQ.' },
  { id: 'ri3', title: 'FX Exposure — GBP Pricing', level: 'medium', vendor: 'FastParts Ltd.', description: 'Quote submitted in GBP. AUD/GBP rate movement of ±5% could affect budget by $9,800.' },
];

const trailEntries: TrailEntry[] = [
  { id: 't1', sequence: 1, eventType: 'matrix_built', actor: 'System', timestamp: 'Mar 6, 2026 09:14 AM', entryHash: 'a3f8c2...d91e', previousHash: '—', verified: true },
  { id: 't2', sequence: 2, eventType: 'scoring_computed', actor: 'System', timestamp: 'Mar 6, 2026 09:15 AM', entryHash: 'b7e1d4...f20a', previousHash: 'a3f8c2...d91e', verified: true },
  { id: 't3', sequence: 3, eventType: 'approval_evaluated', actor: 'System', timestamp: 'Mar 6, 2026 09:15 AM', entryHash: 'c9a2f6...e83b', previousHash: 'b7e1d4...f20a', verified: true },
  { id: 't4', sequence: 4, eventType: 'pending_approval', actor: 'Approval Engine', timestamp: 'Mar 6, 2026 09:16 AM', entryHash: 'd4b3e8...a17c', previousHash: 'c9a2f6...e83b', verified: true },
];

const riskLevelConfig: Record<RiskLevel, { bg: string; border: string; text: string; badgeBg: string; badgeText: string; icon: typeof AlertTriangle }> = {
  critical: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', badgeBg: 'bg-red-600', badgeText: 'text-white', icon: ShieldAlert },
  high: { bg: 'bg-orange-50', border: 'border-orange-200', text: 'text-orange-700', badgeBg: 'bg-orange-500', badgeText: 'text-white', icon: AlertTriangle },
  medium: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', badgeBg: 'bg-amber-400', badgeText: 'text-white', icon: Info },
  low: { bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-slate-600', badgeBg: 'bg-slate-300', badgeText: 'text-slate-700', icon: Info },
};

const eventTypeLabels: Record<string, { label: string; color: string }> = {
  matrix_built: { label: 'Matrix Built', color: 'bg-indigo-100 text-indigo-700' },
  scoring_computed: { label: 'Scoring Computed', color: 'bg-purple-100 text-purple-700' },
  approval_evaluated: { label: 'Approval Evaluated', color: 'bg-amber-100 text-amber-700' },
  pending_approval: { label: 'Pending Approval', color: 'bg-orange-100 text-orange-700' },
  approval_override: { label: 'Override', color: 'bg-red-100 text-red-700' },
};

const tabs: { id: TabId; label: string; icon: typeof Star }[] = [
  { id: 'scoring', label: 'Scoring', icon: Star },
  { id: 'matrix', label: 'Matrix', icon: BarChart3 },
  { id: 'risk', label: 'Risk', icon: ShieldAlert },
  { id: 'trail', label: 'Decision Trail', icon: Shield },
];

export function ApprovalDetail() {
  const [status, setStatus] = useState<ApprovalStatus>('pending_approval');
  const [activeTab, setActiveTab] = useState<TabId>('scoring');
  const [approveModal, setApproveModal] = useState(false);
  const [rejectModal, setRejectModal] = useState(false);
  const [decisionReason, setDecisionReason] = useState('');
  const [decidedBy] = useState('Alex Kumar');
  const [decidedAt, setDecidedAt] = useState('');

  const statusConfig = {
    pending_approval: { label: 'Pending Approval', bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', dot: 'bg-amber-500' },
    approved: { label: 'Approved', bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { label: 'Rejected', bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', dot: 'bg-red-500' },
  };

  const currentStatus = statusConfig[status];
  const highestRisk = riskItems.reduce((max, r) => {
    const order: Record<RiskLevel, number> = { critical: 4, high: 3, medium: 2, low: 1 };
    return order[r.level] > order[max] ? r.level : max;
  }, 'low' as RiskLevel);
  const hasRiskIssues = highestRisk === 'critical' || highestRisk === 'high';

  const handleApprove = () => {
    if (!decisionReason.trim()) return;
    setStatus('approved');
    setDecidedAt('Mar 9, 2026 10:32 AM');
    setApproveModal(false);
  };

  const handleReject = () => {
    if (!decisionReason.trim()) return;
    setStatus('rejected');
    setDecidedAt('Mar 9, 2026 10:32 AM');
    setRejectModal(false);
  };

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Page Header */}
      <div className={`bg-white border-b px-6 py-4 flex-shrink-0 ${status === 'pending_approval' ? 'border-amber-200' : 'border-slate-200'}`}>
        {/* Breadcrumb */}
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="hover:text-slate-700 cursor-pointer">Approvals</span>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>RUN-2401-001</span>
        </div>

        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-3 mb-1">
              <h1 className="text-slate-900">Approval Detail</h1>
              <div className={`flex items-center gap-1.5 px-2.5 py-1 rounded-full border ${currentStatus.bg} ${currentStatus.border}`}>
                <span className={`w-1.5 h-1.5 rounded-full ${currentStatus.dot}`} />
                <span className={`text-xs ${currentStatus.text}`} style={{ fontWeight: 600 }}>{currentStatus.label}</span>
              </div>
            </div>
            <div className="flex items-center gap-4 text-xs text-slate-500 mt-1">
              <span>Run ID: <b className="text-slate-700">RUN-2401-001</b></span>
              <span className="text-slate-300">|</span>
              <span>RFQ: <b className="text-slate-700">RFQ-2401</b></span>
              <span className="text-slate-300">|</span>
              <span>Server Infrastructure Components</span>
              <span className="text-slate-300">|</span>
              <span className="flex items-center gap-1"><Clock size={11} /> Created Mar 6, 2026</span>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <FileText size={14} />
              Export Evidence
            </button>
          </div>
        </div>

        {/* Recommended Vendor + Approval Gates */}
        <div className="grid grid-cols-3 gap-4 mt-4">
          {/* Recommended Vendor Card */}
          <div className="col-span-1 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div className="text-xs text-emerald-600 mb-2" style={{ fontWeight: 600 }}>RECOMMENDED VENDOR</div>
            <div className="flex items-center gap-2 mb-2">
              <div className="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                <Building size={15} className="text-emerald-700" />
              </div>
              <div>
                <div className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>PrimeSource Co.</div>
                <div className="text-emerald-600 text-xs">Preferred Vendor</div>
              </div>
            </div>
            <div className="flex items-center gap-4 mt-2">
              <div>
                <span className="text-xs text-slate-500">Score</span>
                <div className="text-emerald-700 text-lg" style={{ fontWeight: 700 }}>91<span className="text-xs text-slate-400">/100</span></div>
              </div>
              <div className="w-px h-8 bg-emerald-200" />
              <div>
                <span className="text-xs text-slate-500">Total</span>
                <div className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>$189,200</div>
              </div>
              <div className="w-px h-8 bg-emerald-200" />
              <div>
                <span className="text-xs text-slate-500">Risk</span>
                <div className="flex items-center gap-1 mt-0.5">
                  {hasRiskIssues ? (
                    <span className="text-xs bg-red-600 text-white px-1.5 py-0.5 rounded" style={{ fontWeight: 600 }}>High</span>
                  ) : (
                    <span className="text-xs bg-emerald-600 text-white px-1.5 py-0.5 rounded" style={{ fontWeight: 600 }}>Low</span>
                  )}
                </div>
              </div>
            </div>
          </div>

          {/* Approval Gate Reasons */}
          <div className="col-span-2 rounded-xl border border-amber-200 bg-amber-50/60 p-4">
            <div className="flex items-center gap-2 mb-3">
              <AlertTriangle size={14} className="text-amber-600" />
              <span className="text-xs text-amber-700" style={{ fontWeight: 700 }}>APPROVAL GATE REASONS</span>
              <span className="text-xs text-amber-600 ml-auto">{gateReasons.length} item{gateReasons.length !== 1 ? 's' : ''} requiring review</span>
            </div>
            <div className="space-y-2">
              {gateReasons.map((gate) => {
                const Icon = gate.icon;
                return (
                  <div key={gate.id} className="flex items-start gap-2.5 bg-white/70 rounded-lg border border-amber-200/60 px-3 py-2">
                    <Icon size={13} className="text-amber-600 mt-0.5 flex-shrink-0" />
                    <span className="text-slate-700 text-xs leading-relaxed">{gate.reason}</span>
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* Decision Summary (after decision made) */}
        {status !== 'pending_approval' && (
          <div className={`mt-4 rounded-xl border p-4 ${status === 'approved' ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'}`}>
            <div className="flex items-center gap-2 mb-2">
              {status === 'approved' ? (
                <CheckCircle size={15} className="text-emerald-600" />
              ) : (
                <XCircle size={15} className="text-red-600" />
              )}
              <span className={`text-sm ${status === 'approved' ? 'text-emerald-700' : 'text-red-700'}`} style={{ fontWeight: 700 }}>
                {status === 'approved' ? 'Approved' : 'Rejected'} by {decidedBy}
              </span>
              <span className="text-xs text-slate-500 ml-2">{decidedAt}</span>
            </div>
            <p className={`text-xs leading-relaxed ${status === 'approved' ? 'text-emerald-600' : 'text-red-600'}`}>
              <b>Reason:</b> {decisionReason}
            </p>
          </div>
        )}
      </div>

      {/* Evidence Tabs */}
      <div className="border-b border-slate-200 bg-white px-6 flex-shrink-0">
        <div className="flex items-center gap-0">
          {tabs.map((tab) => {
            const Icon = tab.icon;
            const isActive = activeTab === tab.id;
            const hasAlert = tab.id === 'risk' && hasRiskIssues;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center gap-2 px-4 py-3 text-sm border-b-2 transition-colors ${
                  isActive
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                }`}
                style={{ fontWeight: isActive ? 600 : 400 }}
              >
                <Icon size={14} />
                {tab.label}
                {hasAlert && (
                  <span className="w-1.5 h-1.5 rounded-full bg-red-500" />
                )}
              </button>
            );
          })}
        </div>
      </div>

      {/* Tab Content */}
      <div className="flex-1 overflow-auto p-5">
        {activeTab === 'scoring' && <ScoringTab />}
        {activeTab === 'matrix' && <MatrixTab />}
        {activeTab === 'risk' && <RiskTab />}
        {activeTab === 'trail' && <TrailTab />}
      </div>

      {/* Sticky Action Bar */}
      <div className="bg-white border-t border-slate-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <Link
          to="/"
          className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50"
        >
          <ArrowLeft size={14} />
          Back to Queue
        </Link>
        {status === 'pending_approval' && (
          <div className="flex items-center gap-3">
            <button
              onClick={() => { setDecisionReason(''); setRejectModal(true); }}
              className="flex items-center gap-2 border border-red-300 bg-white text-red-700 rounded-lg px-4 py-2.5 text-sm hover:bg-red-50"
              style={{ fontWeight: 500 }}
            >
              <XCircle size={14} />
              Reject
            </button>
            <button
              onClick={() => { setDecisionReason(''); setApproveModal(true); }}
              className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2.5 text-sm"
              style={{ fontWeight: 500 }}
            >
              <CheckCircle size={14} />
              Approve
            </button>
          </div>
        )}
        {status !== 'pending_approval' && (
          <div className={`flex items-center gap-2 px-3 py-2 rounded-lg ${status === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`}>
            {status === 'approved' ? <CheckCircle size={14} /> : <XCircle size={14} />}
            <span className="text-sm" style={{ fontWeight: 600 }}>
              Decision: {status === 'approved' ? 'Approved' : 'Rejected'}
            </span>
          </div>
        )}
      </div>

      {/* Approve Modal */}
      {approveModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div className="flex items-center gap-3 px-6 py-5 bg-emerald-50 rounded-t-2xl border-b border-emerald-200">
              <div className="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                <CheckCircle size={18} className="text-emerald-600" />
              </div>
              <div>
                <h2 className="text-emerald-900 text-sm">Confirm Approval</h2>
                <p className="text-emerald-600 text-xs">RUN-2401-001 · PrimeSource Co. (Score: 91)</p>
              </div>
            </div>
            <div className="p-6 space-y-4">
              <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-3">
                <div className="text-emerald-700 text-xs" style={{ fontWeight: 500 }}>
                  You are approving the recommended vendor <b>PrimeSource Co.</b> for RFQ-2401 (Server Infrastructure Components) with a total award value of <b>$189,200</b>.
                </div>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>
                  Approval Reason <span className="text-red-500">*</span>
                </label>
                <textarea
                  rows={3}
                  value={decisionReason}
                  onChange={(e) => setDecisionReason(e.target.value)}
                  className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-emerald-400 outline-none"
                  placeholder="Provide reason for approval (mandatory)..."
                />
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Additional Notes (optional)</label>
                <textarea
                  rows={2}
                  className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-emerald-400 outline-none"
                  placeholder="Any conditions or follow-up actions..."
                />
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setApproveModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button
                onClick={handleApprove}
                disabled={!decisionReason.trim()}
                className={`rounded-lg px-4 py-2 text-sm text-white ${decisionReason.trim() ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-emerald-300 cursor-not-allowed'}`}
                style={{ fontWeight: 500 }}
              >
                Confirm Approval
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Reject Modal */}
      {rejectModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div className="flex items-center gap-3 px-6 py-5 bg-red-50 rounded-t-2xl border-b border-red-200">
              <div className="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <XCircle size={18} className="text-red-600" />
              </div>
              <div>
                <h2 className="text-red-900 text-sm">Confirm Rejection</h2>
                <p className="text-red-600 text-xs">RUN-2401-001 · PrimeSource Co. (Score: 91)</p>
              </div>
            </div>
            <div className="p-6 space-y-4">
              <div className="bg-red-50 border border-red-200 rounded-xl p-3">
                <div className="text-red-700 text-xs" style={{ fontWeight: 500 }}>
                  You are rejecting the comparison run <b>RUN-2401-001</b> for RFQ-2401. This will require re-evaluation or a new comparison run.
                </div>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>
                  Rejection Reason <span className="text-red-500">*</span>
                </label>
                <textarea
                  rows={3}
                  value={decisionReason}
                  onChange={(e) => setDecisionReason(e.target.value)}
                  className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-red-400 outline-none"
                  placeholder="Provide reason for rejection (mandatory)..."
                />
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Proposed Next Steps</label>
                <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option>Return for re-evaluation with updated criteria</option>
                  <option>Request additional vendor documentation</option>
                  <option>Initiate new comparison run</option>
                  <option>Escalate to senior leadership</option>
                </select>
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setRejectModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button
                onClick={handleReject}
                disabled={!decisionReason.trim()}
                className={`rounded-lg px-4 py-2 text-sm text-white ${decisionReason.trim() ? 'bg-red-600 hover:bg-red-700' : 'bg-red-300 cursor-not-allowed'}`}
                style={{ fontWeight: 500 }}
              >
                Confirm Rejection
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

/* ─── Scoring Tab ─── */
function ScoringTab() {
  return (
    <div className="space-y-5">
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
          <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
            <Star size={14} className="text-slate-600" />
          </div>
          <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Vendor Ranking</span>
          <span className="ml-auto text-slate-400 text-xs">Weighted score model v3.0 · {vendorScores.length} vendors</span>
        </div>
        <table className="w-full">
          <thead>
            <tr className="border-b border-slate-100 bg-slate-50">
              <th className="text-left px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>RANK</th>
              <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>VENDOR</th>
              <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>PRICE</th>
              <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>DELIVERY</th>
              <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>QUALITY</th>
              <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>RISK</th>
              <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>OVERALL</th>
            </tr>
          </thead>
          <tbody>
            {vendorScores
              .sort((a, b) => b.overallScore - a.overallScore)
              .map((v, i) => (
                <tr key={v.name} className={`border-b border-slate-50 hover:bg-slate-50 ${v.recommended ? 'bg-emerald-50/40' : ''}`}>
                  <td className="px-5 py-3">
                    <span className={`text-sm ${i === 0 ? 'text-emerald-700' : 'text-slate-600'}`} style={{ fontWeight: 600 }}>#{i + 1}</span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <span className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{v.name}</span>
                      {v.recommended && (
                        <span className="text-xs bg-emerald-600 text-white px-1.5 py-0.5 rounded" style={{ fontWeight: 600 }}>Recommended</span>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-center">
                    <ScorePill score={v.priceScore} />
                  </td>
                  <td className="px-4 py-3 text-center">
                    <ScorePill score={v.deliveryScore} />
                  </td>
                  <td className="px-4 py-3 text-center">
                    <ScorePill score={v.qualityScore} />
                  </td>
                  <td className="px-4 py-3 text-center">
                    <ScorePill score={v.riskScore} />
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span className={`text-sm ${v.recommended ? 'text-emerald-700' : 'text-slate-800'}`} style={{ fontWeight: 700 }}>
                      {v.overallScore}
                    </span>
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>

      {/* Score Distribution Visual */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 className="text-slate-900 text-sm mb-4" style={{ fontWeight: 600 }}>Score Distribution</h3>
        <div className="space-y-3">
          {vendorScores
            .sort((a, b) => b.overallScore - a.overallScore)
            .map((v) => (
              <div key={v.name} className="flex items-center gap-3">
                <span className="text-xs text-slate-600 w-36 truncate" style={{ fontWeight: 500 }}>{v.name}</span>
                <div className="flex-1 h-6 bg-slate-100 rounded-full overflow-hidden relative">
                  <div
                    className={`h-full rounded-full transition-all ${v.recommended ? 'bg-emerald-500' : 'bg-indigo-400'}`}
                    style={{ width: `${v.overallScore}%` }}
                  />
                  <span className="absolute inset-y-0 right-2 flex items-center text-xs text-slate-600" style={{ fontWeight: 600 }}>
                    {v.overallScore}
                  </span>
                </div>
              </div>
            ))}
        </div>
      </div>
    </div>
  );
}

function ScorePill({ score }: { score: number }) {
  const color = score >= 90 ? 'text-emerald-700 bg-emerald-50' : score >= 80 ? 'text-indigo-700 bg-indigo-50' : score >= 70 ? 'text-amber-700 bg-amber-50' : 'text-red-700 bg-red-50';
  return (
    <span className={`inline-block text-xs px-2 py-0.5 rounded ${color}`} style={{ fontWeight: 600 }}>{score}</span>
  );
}

/* ─── Matrix Tab ─── */
function MatrixTab() {
  return (
    <div className="space-y-5">
      {/* Summary Cards */}
      <div className="grid grid-cols-4 gap-3">
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
          <div className="text-xs text-slate-500 mb-1">Line Items</div>
          <div className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>{matrixSummary.lineItems}</div>
          <div className="text-xs text-slate-400">{matrixSummary.categories} categories</div>
        </div>
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
          <div className="text-xs text-slate-500 mb-1">Vendors Compared</div>
          <div className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>{matrixSummary.vendors}</div>
          <div className="text-xs text-slate-400">All responded</div>
        </div>
        <div className="bg-white rounded-xl border border-emerald-200 shadow-sm p-4 bg-emerald-50">
          <div className="text-xs text-emerald-600 mb-1">Lowest Total</div>
          <div className="text-emerald-700 text-lg" style={{ fontWeight: 700 }}>${matrixSummary.lowestTotal.toLocaleString()}</div>
          <div className="text-xs text-emerald-600">{matrixSummary.lowestVendor}</div>
        </div>
        <div className="bg-white rounded-xl border border-indigo-200 shadow-sm p-4 bg-indigo-50">
          <div className="text-xs text-indigo-600 mb-1">Recommended Total</div>
          <div className="text-indigo-700 text-lg" style={{ fontWeight: 700 }}>${matrixSummary.recommendedTotal.toLocaleString()}</div>
          <div className="text-xs text-indigo-600">PrimeSource Co.</div>
        </div>
      </div>

      {/* Terms Summary */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
          <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
            <BarChart3 size={14} className="text-slate-600" />
          </div>
          <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Commercial Terms — Recommended Vendor</span>
        </div>
        <table className="w-full">
          <thead>
            <tr className="border-b border-slate-100 bg-slate-50">
              <th className="text-left px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>TERM</th>
              <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>RECOMMENDED VENDOR</th>
              <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>BEST IN CLASS</th>
            </tr>
          </thead>
          <tbody>
            {matrixSummary.terms.map((t, i) => (
              <tr key={t.label} className={`border-b border-slate-50 ${i % 2 === 0 ? '' : 'bg-slate-50/30'}`}>
                <td className="px-5 py-3 text-slate-600 text-sm">{t.label}</td>
                <td className="px-4 py-3 text-slate-800 text-sm" style={{ fontWeight: 500 }}>{t.recommended}</td>
                <td className="px-4 py-3 text-emerald-700 text-sm" style={{ fontWeight: 500 }}>{t.best}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="flex justify-center">
        <Link to="/comparison" className="flex items-center gap-2 text-indigo-600 text-sm hover:text-indigo-700" style={{ fontWeight: 500 }}>
          <BarChart3 size={14} />
          View Full Comparison Matrix
          <ChevronRight size={14} />
        </Link>
      </div>
    </div>
  );
}

/* ─── Risk Tab ─── */
function RiskTab() {
  return (
    <div className="space-y-5">
      {/* Risk Summary Bar */}
      <div className="flex items-center gap-2 px-4 py-3 bg-white rounded-xl border border-slate-200 shadow-sm">
        <span className="text-slate-500 text-xs mr-2" style={{ fontWeight: 500 }}>RISK SUMMARY</span>
        {(['critical', 'high', 'medium', 'low'] as RiskLevel[]).map((level) => {
          const count = riskItems.filter(r => r.level === level).length;
          const cfg = riskLevelConfig[level];
          const Icon = cfg.icon;
          return (
            <div key={level} className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full border ${cfg.bg} ${cfg.border}`}>
              <Icon size={12} className={cfg.text} />
              <span className={`text-xs capitalize ${cfg.text}`} style={{ fontWeight: 600 }}>{level}</span>
              <span className={`text-xs px-1.5 py-0.5 rounded-full ${cfg.badgeBg} ${cfg.badgeText}`} style={{ fontWeight: 700 }}>{count}</span>
            </div>
          );
        })}
      </div>

      {/* Risk Items */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
          <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
            <ShieldAlert size={14} className="text-slate-600" />
          </div>
          <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Identified Risks</span>
          <span className="ml-auto text-slate-400 text-xs">{riskItems.length} item{riskItems.length !== 1 ? 's' : ''}</span>
        </div>
        <div className="divide-y divide-slate-100">
          {riskItems.map((risk) => {
            const cfg = riskLevelConfig[risk.level];
            const Icon = cfg.icon;
            return (
              <div key={risk.id} className="flex items-start gap-4 px-5 py-4">
                <div className={`w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${cfg.bg} border ${cfg.border}`}>
                  <Icon size={15} className={cfg.text} />
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap mb-1">
                    <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{risk.title}</span>
                    <span className={`text-xs px-2 py-0.5 rounded-full border capitalize ${cfg.bg} ${cfg.border} ${cfg.text}`} style={{ fontWeight: 600 }}>{risk.level}</span>
                    <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{risk.vendor}</span>
                  </div>
                  <p className="text-slate-500 text-xs leading-relaxed">{risk.description}</p>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      <div className="flex justify-center">
        <Link to="/risk" className="flex items-center gap-2 text-indigo-600 text-sm hover:text-indigo-700" style={{ fontWeight: 500 }}>
          <ShieldAlert size={14} />
          View Full Risk & Compliance Review
          <ChevronRight size={14} />
        </Link>
      </div>
    </div>
  );
}

/* ─── Trail Tab ─── */
function TrailTab() {
  const allVerified = trailEntries.every(e => e.verified);

  return (
    <div className="space-y-5">
      {/* Integrity Banner */}
      <div className={`flex items-center gap-3 px-4 py-3 rounded-xl border ${allVerified ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'}`}>
        {allVerified ? (
          <CheckCircle size={15} className="text-emerald-600" />
        ) : (
          <AlertTriangle size={15} className="text-red-600" />
        )}
        <span className={`text-sm ${allVerified ? 'text-emerald-700' : 'text-red-700'}`} style={{ fontWeight: 600 }}>
          {allVerified ? 'Hash Chain Verified' : 'Integrity Warning — Chain Broken'}
        </span>
        <span className={`text-xs ${allVerified ? 'text-emerald-600' : 'text-red-600'}`}>
          {trailEntries.length} entries · All hashes validated
        </span>
        {allVerified && (
          <span className="ml-auto text-xs bg-emerald-600 text-white px-2 py-0.5 rounded" style={{ fontWeight: 600 }}>Verified</span>
        )}
      </div>

      {/* Trail Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
          <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
            <Shield size={14} className="text-slate-600" />
          </div>
          <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Decision Trail</span>
          <span className="ml-auto text-slate-400 text-xs">Immutable audit ledger</span>
        </div>
        <table className="w-full">
          <thead>
            <tr className="border-b border-slate-100 bg-slate-50">
              <th className="text-left px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>SEQ</th>
              <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>EVENT</th>
              <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>ACTOR</th>
              <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>TIMESTAMP</th>
              <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>
                <div className="flex items-center gap-1">
                  <Hash size={11} />
                  ENTRY HASH
                </div>
              </th>
              <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>STATUS</th>
            </tr>
          </thead>
          <tbody>
            {trailEntries.map((entry) => {
              const eventCfg = eventTypeLabels[entry.eventType] || { label: entry.eventType, color: 'bg-slate-100 text-slate-600' };
              return (
                <tr key={entry.id} className="border-b border-slate-50 hover:bg-slate-50">
                  <td className="px-5 py-3">
                    <span className="text-slate-600 text-sm" style={{ fontWeight: 600 }}>#{entry.sequence}</span>
                  </td>
                  <td className="px-4 py-3">
                    <span className={`text-xs px-2 py-0.5 rounded ${eventCfg.color}`} style={{ fontWeight: 600 }}>{eventCfg.label}</span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-1.5">
                      <User size={12} className="text-slate-400" />
                      <span className="text-slate-700 text-xs" style={{ fontWeight: 500 }}>{entry.actor}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-slate-600 text-xs">{entry.timestamp}</span>
                  </td>
                  <td className="px-4 py-3">
                    <code className="text-xs text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-mono">{entry.entryHash}</code>
                  </td>
                  <td className="px-4 py-3 text-center">
                    {entry.verified ? (
                      <CheckCircle size={14} className="text-emerald-500 mx-auto" />
                    ) : (
                      <XCircle size={14} className="text-red-500 mx-auto" />
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}
