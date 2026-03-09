import { useState, type ReactNode } from 'react';
import { useParams, Link } from 'react-router';
import {
  ArrowLeft, CheckCircle, XCircle, AlertTriangle, ShieldAlert,
  FileText, BarChart3, Clock, User, Hash, ChevronRight,
  Star, Building, DollarSign, Info, Shield, RotateCcw,
  MessageSquarePlus, Paperclip, CalendarClock, Tag, Users,
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import { RichTooltip, TooltipBadge } from '../components/RichTooltip';
import {
  approvalItems, approvalHistory, vendorScores, comparisonMatrix,
  riskItems, decisionTrailEntries, documents, users, rfqs, vendors,
  statusColors, formatCurrency, formatDateTime, getUserById,
  getVendorById, getRfqById, riskLevelConfig,
} from '../data/mockData';
import type { ApprovalStatus, ApprovalType, RiskLevel } from '../data/mockData';

type TabId = 'scoring' | 'matrix' | 'risk' | 'trail';

const approvalStatusConfig: Record<ApprovalStatus, { label: string; bg: string; border: string; text: string; dot: string }> = {
  pending: { label: 'Pending Approval', bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', dot: 'bg-amber-500' },
  approved: { label: 'Approved', bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700', dot: 'bg-emerald-500' },
  rejected: { label: 'Rejected', bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', dot: 'bg-red-500' },
  returned: { label: 'Returned for Revision', bg: 'bg-purple-50', border: 'border-purple-200', text: 'text-purple-700', dot: 'bg-purple-500' },
  awaiting_evidence: { label: 'Awaiting Evidence', bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-700', dot: 'bg-blue-500' },
  snoozed: { label: 'Snoozed', bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-slate-600', dot: 'bg-slate-400' },
};

const approvalTypeConfig: Record<ApprovalType, { label: string; color: string }> = {
  comparison: { label: 'Comparison', color: 'bg-indigo-100 text-indigo-700' },
  risk_escalation: { label: 'Risk Escalation', color: 'bg-red-100 text-red-700' },
  policy_exception: { label: 'Policy Exception', color: 'bg-amber-100 text-amber-700' },
  override: { label: 'Override', color: 'bg-purple-100 text-purple-700' },
};

const priorityConfig: Record<string, { label: string; color: string }> = {
  urgent: { label: 'Urgent', color: 'bg-red-600 text-white' },
  high: { label: 'High', color: 'bg-orange-500 text-white' },
  normal: { label: 'Normal', color: 'bg-slate-200 text-slate-700' },
  low: { label: 'Low', color: 'bg-slate-100 text-slate-500' },
};

const riskIcons: Record<RiskLevel, typeof AlertTriangle> = {
  critical: ShieldAlert,
  high: AlertTriangle,
  medium: Info,
  low: Info,
};

const eventTypeLabels: Record<string, { label: string; color: string }> = {
  rfq_created: { label: 'RFQ Created', color: 'bg-blue-100 text-blue-700' },
  rfq_published: { label: 'RFQ Published', color: 'bg-blue-100 text-blue-700' },
  quote_accepted: { label: 'Quote Accepted', color: 'bg-emerald-100 text-emerald-700' },
  normalization_locked: { label: 'Normalization Locked', color: 'bg-indigo-100 text-indigo-700' },
  matrix_built: { label: 'Matrix Built', color: 'bg-indigo-100 text-indigo-700' },
  scoring_computed: { label: 'Scoring Computed', color: 'bg-purple-100 text-purple-700' },
  approval_required: { label: 'Approval Required', color: 'bg-amber-100 text-amber-700' },
  approval_evaluated: { label: 'Approval Evaluated', color: 'bg-amber-100 text-amber-700' },
  pending_approval: { label: 'Pending Approval', color: 'bg-orange-100 text-orange-700' },
  approval_override: { label: 'Override', color: 'bg-red-100 text-red-700' },
  sanctions_flagged: { label: 'Sanctions Flagged', color: 'bg-red-100 text-red-700' },
};

const tabs: { id: TabId; label: string; icon: typeof Star }[] = [
  { id: 'scoring', label: 'Scoring', icon: Star },
  { id: 'matrix', label: 'Matrix Summary', icon: BarChart3 },
  { id: 'risk', label: 'Risk', icon: ShieldAlert },
  { id: 'trail', label: 'Decision Trail', icon: Shield },
];

export function ApprovalDetail() {
  const { id } = useParams<{ id: string }>();
  const approval = approvalItems.find((a) => a.id === id);

  const [status, setStatus] = useState<ApprovalStatus>(approval?.status ?? 'pending');
  const [activeTab, setActiveTab] = useState<TabId>('scoring');

  const [confirmOpen, setConfirmOpen] = useState(false);
  const [confirmAction, setConfirmAction] = useState<'approve' | 'reject'>('approve');
  const [decisionReason, setDecisionReason] = useState('');
  const [evidenceFile, setEvidenceFile] = useState('');

  const [returnOpen, setReturnOpen] = useState(false);
  const [returnReason, setReturnReason] = useState('');
  const [revisionInstructions, setRevisionInstructions] = useState('');

  const [requestEvidenceOpen, setRequestEvidenceOpen] = useState(false);
  const [evidenceDescription, setEvidenceDescription] = useState('');
  const [targetUserId, setTargetUserId] = useState('');

  const [decidedBy, setDecidedBy] = useState('');
  const [decidedAt, setDecidedAt] = useState('');
  const [savedReason, setSavedReason] = useState('');

  if (!approval) {
    return (
      <div className="flex items-center justify-center h-full bg-slate-50">
        <div className="text-center space-y-3">
          <AlertTriangle size={32} className="text-amber-500 mx-auto" />
          <h2 className="text-slate-800 text-lg" style={{ fontWeight: 600 }}>Approval Not Found</h2>
          <p className="text-slate-500 text-sm">No approval item matches ID "{id}"</p>
          <Link to="/approvals" className="inline-flex items-center gap-2 text-indigo-600 text-sm hover:text-indigo-700">
            <ArrowLeft size={14} /> Back to Queue
          </Link>
        </div>
      </div>
    );
  }

  const rfq = getRfqById(approval.rfqId);
  const assignee = getUserById(approval.assigneeId);
  const requester = getUserById(approval.requesterId);
  const currentStatusConfig = approvalStatusConfig[status];
  const typeConfig = approvalTypeConfig[approval.type];
  const priConfig = priorityConfig[approval.priority];

  const rfqRisks = riskItems.filter((r) => r.rfqId === approval.rfqId);
  const highestRisk = rfqRisks.reduce((max, r) => {
    const order: Record<RiskLevel, number> = { critical: 4, high: 3, medium: 2, low: 1 };
    return order[r.level] > order[max] ? r.level : max;
  }, 'low' as RiskLevel);
  const hasRiskIssues = highestRisk === 'critical' || highestRisk === 'high';

  const rfqTrailEntries = decisionTrailEntries.filter((e) => e.rfqId === approval.rfqId);
  const priorHistory = approvalHistory.filter((h) => h.approvalId === approval.id);

  const recommendedVendor = vendorScores.find((v) => v.recommended);
  const recommendedVendorInfo = recommendedVendor ? getVendorById(recommendedVendor.vendorId) : undefined;

  const slaOverdue = new Date(approval.slaDeadline) < new Date();

  const handleConfirmDecision = () => {
    if (!decisionReason.trim()) return;
    setStatus(confirmAction === 'approve' ? 'approved' : 'rejected');
    setDecidedBy(assignee?.name ?? 'Unknown');
    setDecidedAt(formatDateTime(new Date().toISOString()));
    setSavedReason(decisionReason);
    setConfirmOpen(false);
    setDecisionReason('');
    setEvidenceFile('');
  };

  const handleReturnForRevision = () => {
    if (!returnReason.trim()) return;
    setStatus('returned');
    setDecidedBy(assignee?.name ?? 'Unknown');
    setDecidedAt(formatDateTime(new Date().toISOString()));
    setSavedReason(returnReason);
    setReturnOpen(false);
    setReturnReason('');
    setRevisionInstructions('');
  };

  const handleRequestEvidence = () => {
    if (!evidenceDescription.trim() || !targetUserId) return;
    setStatus('awaiting_evidence');
    setDecidedBy(assignee?.name ?? 'Unknown');
    setDecidedAt(formatDateTime(new Date().toISOString()));
    setSavedReason(`Evidence requested: ${evidenceDescription}`);
    setRequestEvidenceOpen(false);
    setEvidenceDescription('');
    setTargetUserId('');
  };

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* ── Page Header ── */}
      <div className={`bg-white border-b px-6 py-4 flex-shrink-0 ${status === 'pending' ? 'border-amber-200' : 'border-slate-200'}`}>
        {/* Breadcrumb */}
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <Link to="/approvals" className="hover:text-slate-700">Approvals</Link>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>{approval.id}</span>
        </div>

        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-3 mb-1">
              <h1 className="text-slate-900">{approval.title}</h1>
              <div className={`flex items-center gap-1.5 px-2.5 py-1 rounded-full border ${currentStatusConfig.bg} ${currentStatusConfig.border}`}>
                <span className={`w-1.5 h-1.5 rounded-full ${currentStatusConfig.dot}`} />
                <span className={`text-xs ${currentStatusConfig.text}`} style={{ fontWeight: 600 }}>{currentStatusConfig.label}</span>
              </div>
            </div>
            <div className="flex items-center gap-3 text-xs text-slate-500 mt-1 flex-wrap">
              <span className={`px-2 py-0.5 rounded text-xs ${typeConfig.color}`} style={{ fontWeight: 600 }}>
                <Tag size={10} className="inline -mt-0.5 mr-1" />{typeConfig.label}
              </span>
              <span className={`px-2 py-0.5 rounded text-xs ${priConfig.color}`} style={{ fontWeight: 600 }}>{priConfig.label}</span>
              <span className="text-slate-300">|</span>
              <span>RFQ: <b className="text-slate-700">{approval.rfqId}</b></span>
              {rfq && (
                <>
                  <span className="text-slate-300">|</span>
                  <span>{rfq.title}</span>
                </>
              )}
              <span className="text-slate-300">|</span>
              <span className="flex items-center gap-1"><Clock size={11} /> Created {formatDateTime(approval.createdAt)}</span>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <FileText size={14} />
              Export Evidence
            </button>
          </div>
        </div>

        {/* ── Approval Metadata Cards ── */}
        <div className="grid grid-cols-6 gap-3 mt-4">
          <MetadataCard label="Assignee" icon={User}>
            <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{assignee?.name ?? '—'}</span>
            <span className="text-slate-400 text-xs">{assignee?.role}</span>
          </MetadataCard>
          <MetadataCard label="Requester" icon={Users}>
            <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{requester?.name ?? '—'}</span>
            <span className="text-slate-400 text-xs">{requester?.role}</span>
          </MetadataCard>
          <MetadataCard label="Value" icon={DollarSign}>
            <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>{formatCurrency(approval.value)}</span>
          </MetadataCard>
          <MetadataCard label="SLA Deadline" icon={CalendarClock}>
            <span className={`text-sm ${slaOverdue ? 'text-red-600' : 'text-slate-800'}`} style={{ fontWeight: 600 }}>
              {formatDateTime(approval.slaDeadline)}
            </span>
            {slaOverdue && <span className="text-xs text-red-500" style={{ fontWeight: 600 }}>Overdue</span>}
          </MetadataCard>
          <MetadataCard label="Recommended Vendor" icon={Building}>
            <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{recommendedVendor?.name ?? '—'}</span>
            {recommendedVendor && <span className="text-emerald-600 text-xs">Score: {recommendedVendor.overall}</span>}
          </MetadataCard>
          <MetadataCard label="Risk Level" icon={ShieldAlert}>
            <span className={`text-xs px-2 py-0.5 rounded capitalize ${riskLevelConfig[highestRisk].badgeBg} ${riskLevelConfig[highestRisk].badgeText}`} style={{ fontWeight: 600 }}>
              {highestRisk}
            </span>
            <span className="text-slate-400 text-xs">{rfqRisks.length} items</span>
          </MetadataCard>
        </div>

        {/* ── Gate Reasons ── */}
        {approval.reasons.length > 0 && (
          <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50/60 p-4">
            <div className="flex items-center gap-2 mb-3">
              <AlertTriangle size={14} className="text-amber-600" />
              <span className="text-xs text-amber-700" style={{ fontWeight: 700 }}>APPROVAL GATE REASONS</span>
              <span className="text-xs text-amber-600 ml-auto">{approval.reasons.length} item{approval.reasons.length !== 1 ? 's' : ''} requiring review</span>
            </div>
            <div className="space-y-2">
              {approval.reasons.map((reason, idx) => (
                <div key={idx} className="flex items-start gap-2.5 bg-white/70 rounded-lg border border-amber-200/60 px-3 py-2">
                  <ShieldAlert size={13} className="text-amber-600 mt-0.5 flex-shrink-0" />
                  <span className="text-slate-700 text-xs leading-relaxed">{reason}</span>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* ── Recommended Vendor Summary ── */}
        {recommendedVendor && (
          <div className="mt-4 grid grid-cols-3 gap-4">
            <div className="col-span-1 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
              <div className="text-xs text-emerald-600 mb-2" style={{ fontWeight: 600 }}>RECOMMENDED VENDOR</div>
              <div className="flex items-center gap-2 mb-2">
                <div className="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                  <Building size={15} className="text-emerald-700" />
                </div>
                <div>
                  <div className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>{recommendedVendor.name}</div>
                  {recommendedVendorInfo && (
                    <div className="text-emerald-600 text-xs">{recommendedVendorInfo.country} · {recommendedVendorInfo.category}</div>
                  )}
                </div>
              </div>
              <div className="flex items-center gap-4 mt-2">
                <div>
                  <span className="text-xs text-slate-500">Score</span>
                  <div className="text-emerald-700 text-lg" style={{ fontWeight: 700 }}>{recommendedVendor.overall}<span className="text-xs text-slate-400">/100</span></div>
                </div>
                <div className="w-px h-8 bg-emerald-200" />
                <div>
                  <span className="text-xs text-slate-500">Total</span>
                  <div className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>
                    {comparisonMatrix.vendorTotals[recommendedVendor.vendorId]
                      ? formatCurrency(comparisonMatrix.vendorTotals[recommendedVendor.vendorId])
                      : formatCurrency(approval.value)}
                  </div>
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

            {/* Matrix excerpt — vendor totals */}
            <div className="col-span-2 rounded-xl border border-slate-200 bg-white p-4">
              <div className="text-xs text-slate-500 mb-3" style={{ fontWeight: 600 }}>COMPARISON RUN SUMMARY</div>
              <div className="grid grid-cols-4 gap-2">
                {vendorScores.map((vs) => {
                  const vendor = getVendorById(vs.vendorId);
                  const total = comparisonMatrix.vendorTotals[vs.vendorId];
                  return (
                    <div key={vs.vendorId} className={`rounded-lg border p-3 ${vs.recommended ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-100'}`}>
                      <div className="text-slate-700 text-xs truncate" style={{ fontWeight: 600 }}>{vendor?.name ?? vs.name}</div>
                      <div className="text-slate-900 text-sm mt-1" style={{ fontWeight: 700 }}>{total ? formatCurrency(total) : '—'}</div>
                      <div className="flex items-center gap-1.5 mt-1">
                        <span className={`text-xs ${vs.recommended ? 'text-emerald-700' : 'text-slate-500'}`} style={{ fontWeight: 600 }}>Score: {vs.overall}</span>
                        {vs.recommended && <span className="text-xs bg-emerald-600 text-white px-1 py-0.5 rounded" style={{ fontWeight: 600 }}>★</span>}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        )}

        {/* ── Decision Summary (after decision) ── */}
        {status !== 'pending' && savedReason && (
          <div className={`mt-4 rounded-xl border p-4 ${
            status === 'approved' ? 'border-emerald-200 bg-emerald-50'
            : status === 'rejected' ? 'border-red-200 bg-red-50'
            : status === 'returned' ? 'border-purple-200 bg-purple-50'
            : 'border-blue-200 bg-blue-50'
          }`}>
            <div className="flex items-center gap-2 mb-2">
              {status === 'approved' && <CheckCircle size={15} className="text-emerald-600" />}
              {status === 'rejected' && <XCircle size={15} className="text-red-600" />}
              {status === 'returned' && <RotateCcw size={15} className="text-purple-600" />}
              {status === 'awaiting_evidence' && <MessageSquarePlus size={15} className="text-blue-600" />}
              <span className={`text-sm ${
                status === 'approved' ? 'text-emerald-700'
                : status === 'rejected' ? 'text-red-700'
                : status === 'returned' ? 'text-purple-700'
                : 'text-blue-700'
              }`} style={{ fontWeight: 700 }}>
                {approvalStatusConfig[status].label} by {decidedBy}
              </span>
              <span className="text-xs text-slate-500 ml-2">{decidedAt}</span>
            </div>
            <p className={`text-xs leading-relaxed ${
              status === 'approved' ? 'text-emerald-600'
              : status === 'rejected' ? 'text-red-600'
              : status === 'returned' ? 'text-purple-600'
              : 'text-blue-600'
            }`}>
              <b>Reason:</b> {savedReason}
            </p>
          </div>
        )}

        {/* ── Prior Approval History ── */}
        {priorHistory.length > 0 && (
          <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4">
            <div className="text-xs text-slate-500 mb-3" style={{ fontWeight: 600 }}>PRIOR APPROVAL HISTORY</div>
            <div className="space-y-2">
              {priorHistory.map((h) => {
                const actor = getUserById(h.actor);
                return (
                  <div key={h.id} className="flex items-start gap-3 px-3 py-2 bg-slate-50 rounded-lg">
                    <div className={`w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 ${h.action === 'approved' ? 'bg-emerald-100' : 'bg-red-100'}`}>
                      {h.action === 'approved' ? <CheckCircle size={12} className="text-emerald-600" /> : <XCircle size={12} className="text-red-600" />}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>{actor?.name ?? h.actor}</span>
                        <span className={`text-xs px-1.5 py-0.5 rounded capitalize ${h.action === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}`} style={{ fontWeight: 600 }}>{h.action}</span>
                        <span className="text-xs text-slate-400">{formatDateTime(h.timestamp)}</span>
                      </div>
                      <p className="text-slate-500 text-xs mt-1 leading-relaxed">{h.reason}</p>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}
      </div>

      {/* ── Evidence Tabs ── */}
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
                {hasAlert && <span className="w-1.5 h-1.5 rounded-full bg-red-500" />}
              </button>
            );
          })}
        </div>
      </div>

      {/* ── Tab Content ── */}
      <div className="flex-1 overflow-auto p-5">
        {activeTab === 'scoring' && <ScoringTab />}
        {activeTab === 'matrix' && <MatrixTab rfqId={approval.rfqId} />}
        {activeTab === 'risk' && <RiskTab rfqId={approval.rfqId} />}
        {activeTab === 'trail' && <TrailTab entries={rfqTrailEntries} />}
      </div>

      {/* ── Sticky Action Bar ── */}
      <div className="bg-white border-t border-slate-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <Link
          to="/approvals"
          className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50"
        >
          <ArrowLeft size={14} />
          Back to Queue
        </Link>
        {status === 'pending' && (
          <div className="flex items-center gap-3">
            <button
              onClick={() => { setEvidenceDescription(''); setTargetUserId(''); setRequestEvidenceOpen(true); }}
              className="flex items-center gap-2 border border-blue-300 bg-white text-blue-700 rounded-lg px-4 py-2.5 text-sm hover:bg-blue-50"
              style={{ fontWeight: 500 }}
            >
              <MessageSquarePlus size={14} />
              Request Evidence
            </button>
            <button
              onClick={() => { setReturnReason(''); setRevisionInstructions(''); setReturnOpen(true); }}
              className="flex items-center gap-2 border border-purple-300 bg-white text-purple-700 rounded-lg px-4 py-2.5 text-sm hover:bg-purple-50"
              style={{ fontWeight: 500 }}
            >
              <RotateCcw size={14} />
              Return for Revision
            </button>
            <button
              onClick={() => { setDecisionReason(''); setEvidenceFile(''); setConfirmAction('reject'); setConfirmOpen(true); }}
              className="flex items-center gap-2 border border-red-300 bg-white text-red-700 rounded-lg px-4 py-2.5 text-sm hover:bg-red-50"
              style={{ fontWeight: 500 }}
            >
              <XCircle size={14} />
              Reject
            </button>
            <button
              onClick={() => { setDecisionReason(''); setEvidenceFile(''); setConfirmAction('approve'); setConfirmOpen(true); }}
              className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2.5 text-sm"
              style={{ fontWeight: 500 }}
            >
              <CheckCircle size={14} />
              Approve
            </button>
          </div>
        )}
        {status !== 'pending' && (
          <div className={`flex items-center gap-2 px-3 py-2 rounded-lg ${currentStatusConfig.bg} ${currentStatusConfig.text}`}>
            {status === 'approved' ? <CheckCircle size={14} /> : status === 'rejected' ? <XCircle size={14} /> : status === 'returned' ? <RotateCcw size={14} /> : <MessageSquarePlus size={14} />}
            <span className="text-sm" style={{ fontWeight: 600 }}>Decision: {currentStatusConfig.label}</span>
          </div>
        )}
      </div>

      {/* ── Confirm Decision SlideOver ── */}
      <SlideOver
        open={confirmOpen}
        onOpenChange={setConfirmOpen}
        title={confirmAction === 'approve' ? 'Confirm Approval' : 'Confirm Rejection'}
        description={`${approval.id} · ${recommendedVendor?.name ?? 'N/A'} (Score: ${recommendedVendor?.overall ?? '—'})`}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setConfirmOpen(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button
              onClick={handleConfirmDecision}
              disabled={!decisionReason.trim()}
              className={`rounded-lg px-4 py-2 text-sm text-white ${
                decisionReason.trim()
                  ? confirmAction === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'
                  : confirmAction === 'approve' ? 'bg-emerald-300 cursor-not-allowed' : 'bg-red-300 cursor-not-allowed'
              }`}
              style={{ fontWeight: 500 }}
            >
              {confirmAction === 'approve' ? 'Confirm Approval' : 'Confirm Rejection'}
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className={`rounded-xl border p-3 ${confirmAction === 'approve' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'}`}>
            <div className={`text-xs ${confirmAction === 'approve' ? 'text-emerald-700' : 'text-red-700'}`} style={{ fontWeight: 500 }}>
              You are {confirmAction === 'approve' ? 'approving' : 'rejecting'} <b>{approval.title}</b> for {approval.rfqId}
              {rfq ? ` (${rfq.title})` : ''} with a total value of <b>{formatCurrency(approval.value)}</b>.
            </div>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>
              {confirmAction === 'approve' ? 'Approval' : 'Rejection'} Reason <span className="text-red-500">*</span>
            </label>
            <textarea
              rows={4}
              value={decisionReason}
              onChange={(e) => setDecisionReason(e.target.value)}
              className={`w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none outline-none focus:border-${confirmAction === 'approve' ? 'emerald' : 'red'}-400`}
              placeholder={`Provide reason for ${confirmAction === 'approve' ? 'approval' : 'rejection'} (mandatory)...`}
            />
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>
              <Paperclip size={11} className="inline -mt-0.5 mr-1" /> Evidence Attachment (optional)
            </label>
            <input
              type="text"
              value={evidenceFile}
              onChange={(e) => setEvidenceFile(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-indigo-400"
              placeholder="Paste document name or URL..."
            />
            {documents.filter((d) => d.rfqId === approval.rfqId).length > 0 && (
              <div className="mt-2 space-y-1">
                <span className="text-xs text-slate-400">Related documents:</span>
                {documents.filter((d) => d.rfqId === approval.rfqId).slice(0, 3).map((doc) => (
                  <div key={doc.id} className="text-xs text-indigo-600 flex items-center gap-1.5 cursor-pointer hover:text-indigo-700" onClick={() => setEvidenceFile(doc.name)}>
                    <FileText size={11} /> {doc.name} <span className="text-slate-400">({doc.size})</span>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </SlideOver>

      {/* ── Return for Revision SlideOver ── */}
      <SlideOver
        open={returnOpen}
        onOpenChange={setReturnOpen}
        title="Return for Revision"
        description={`${approval.id} — send back to requester for changes`}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setReturnOpen(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button
              onClick={handleReturnForRevision}
              disabled={!returnReason.trim()}
              className={`rounded-lg px-4 py-2 text-sm text-white ${returnReason.trim() ? 'bg-purple-600 hover:bg-purple-700' : 'bg-purple-300 cursor-not-allowed'}`}
              style={{ fontWeight: 500 }}
            >
              Return for Revision
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="rounded-xl border border-purple-200 bg-purple-50 p-3">
            <div className="text-purple-700 text-xs" style={{ fontWeight: 500 }}>
              This will return <b>{approval.title}</b> to the requester ({requester?.name ?? '—'}) for revision.
              The comparison run will remain locked until the requester resubmits.
            </div>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>
              Reason for Return <span className="text-red-500">*</span>
            </label>
            <textarea
              rows={3}
              value={returnReason}
              onChange={(e) => setReturnReason(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none outline-none focus:border-purple-400"
              placeholder="Why is this being returned? (mandatory)..."
            />
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>
              Revision Instructions <span className="text-red-500">*</span>
            </label>
            <textarea
              rows={3}
              value={revisionInstructions}
              onChange={(e) => setRevisionInstructions(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none outline-none focus:border-purple-400"
              placeholder="Describe what changes are needed..."
            />
          </div>
        </div>
      </SlideOver>

      {/* ── Request Evidence SlideOver ── */}
      <SlideOver
        open={requestEvidenceOpen}
        onOpenChange={setRequestEvidenceOpen}
        title="Request Evidence"
        description="Request additional evidence or documentation before making a decision"
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setRequestEvidenceOpen(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button
              onClick={handleRequestEvidence}
              disabled={!evidenceDescription.trim() || !targetUserId}
              className={`rounded-lg px-4 py-2 text-sm text-white ${evidenceDescription.trim() && targetUserId ? 'bg-blue-600 hover:bg-blue-700' : 'bg-blue-300 cursor-not-allowed'}`}
              style={{ fontWeight: 500 }}
            >
              Send Request
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="rounded-xl border border-blue-200 bg-blue-50 p-3">
            <div className="text-blue-700 text-xs" style={{ fontWeight: 500 }}>
              This approval will be placed on hold until the requested evidence is provided.
            </div>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>
              What evidence is needed? <span className="text-red-500">*</span>
            </label>
            <textarea
              rows={4}
              value={evidenceDescription}
              onChange={(e) => setEvidenceDescription(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none outline-none focus:border-blue-400"
              placeholder="Describe the evidence or documentation required..."
            />
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>
              Assign To <span className="text-red-500">*</span>
            </label>
            <select
              value={targetUserId}
              onChange={(e) => setTargetUserId(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white outline-none focus:border-blue-400"
            >
              <option value="">Select a user...</option>
              {users.filter((u) => u.status === 'active').map((u) => (
                <option key={u.id} value={u.id}>{u.name} — {u.role}</option>
              ))}
            </select>
          </div>
        </div>
      </SlideOver>
    </div>
  );
}

/* ─── Metadata Card ─── */
function MetadataCard({ label, icon: Icon, children }: { label: string; icon: typeof User; children: ReactNode }) {
  return (
    <div className="rounded-lg border border-slate-200 bg-white px-3 py-2.5">
      <div className="flex items-center gap-1.5 mb-1">
        <Icon size={11} className="text-slate-400" />
        <span className="text-xs text-slate-500 uppercase tracking-wide" style={{ fontWeight: 600, fontSize: '0.65rem' }}>{label}</span>
      </div>
      <div className="flex flex-col">{children}</div>
    </div>
  );
}

/* ─── Score Pill ─── */
function ScorePill({ score }: { score: number }) {
  const color = score >= 90 ? 'text-emerald-700 bg-emerald-50' : score >= 80 ? 'text-indigo-700 bg-indigo-50' : score >= 70 ? 'text-amber-700 bg-amber-50' : 'text-red-700 bg-red-50';
  return (
    <span className={`inline-block text-xs px-2 py-0.5 rounded ${color}`} style={{ fontWeight: 600 }}>{score}</span>
  );
}

/* ─── Scoring Tab ─── */
function ScoringTab() {
  const sorted = [...vendorScores].sort((a, b) => b.overall - a.overall);

  return (
    <div className="space-y-5">
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
          <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
            <Star size={14} className="text-slate-600" />
          </div>
          <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Vendor Ranking</span>
          <span className="ml-auto text-slate-400 text-xs">Weighted score model · {vendorScores.length} vendors</span>
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
              <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>SUST.</th>
              <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>OVERALL</th>
            </tr>
          </thead>
          <tbody>
            {sorted.map((v, i) => {
              const vendorInfo = getVendorById(v.vendorId);
              return (
                <tr key={v.vendorId} className={`border-b border-slate-50 hover:bg-slate-50 ${v.recommended ? 'bg-emerald-50/40' : ''}`}>
                  <td className="px-5 py-3">
                    <span className={`text-sm ${i === 0 ? 'text-emerald-700' : 'text-slate-600'}`} style={{ fontWeight: 600 }}>#{i + 1}</span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <RichTooltip
                        trigger={
                          <span className="text-slate-800 text-sm cursor-pointer underline decoration-dotted underline-offset-2 hover:text-indigo-700" style={{ fontWeight: 500 }}>
                            {v.name}
                          </span>
                        }
                        side="right"
                        align="start"
                      >
                        <div className="space-y-1">
                          <div className="text-slate-800 text-sm mb-2" style={{ fontWeight: 700 }}>{v.name}</div>
                          {vendorInfo && (
                            <>
                              <TooltipBadge label="Country" value={vendorInfo.country} />
                              <TooltipBadge label="Category" value={vendorInfo.category} />
                              <TooltipBadge label="Overall Score" value={vendorInfo.overallScore} color="text-emerald-700" />
                              <TooltipBadge label="Risk Level" value={vendorInfo.riskLevel} color={vendorInfo.riskLevel === 'low' ? 'text-emerald-700' : 'text-amber-700'} />
                              <TooltipBadge label="Total Quotes" value={vendorInfo.totalQuotes} />
                              <TooltipBadge label="Avg Response" value={`${vendorInfo.avgResponseDays}d`} />
                              <TooltipBadge label="Sanctions" value={vendorInfo.sanctionsStatus} color={vendorInfo.sanctionsStatus === 'clear' ? 'text-emerald-700' : 'text-red-700'} />
                            </>
                          )}
                        </div>
                      </RichTooltip>
                      {v.recommended && (
                        <span className="text-xs bg-emerald-600 text-white px-1.5 py-0.5 rounded" style={{ fontWeight: 600 }}>Recommended</span>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-center"><ScorePill score={v.price} /></td>
                  <td className="px-4 py-3 text-center"><ScorePill score={v.delivery} /></td>
                  <td className="px-4 py-3 text-center"><ScorePill score={v.quality} /></td>
                  <td className="px-4 py-3 text-center"><ScorePill score={v.risk} /></td>
                  <td className="px-4 py-3 text-center"><ScorePill score={v.sustainability} /></td>
                  <td className="px-4 py-3 text-center">
                    <span className={`text-sm ${v.recommended ? 'text-emerald-700' : 'text-slate-800'}`} style={{ fontWeight: 700 }}>
                      {v.overall}
                    </span>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {/* Score Distribution Visual */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 className="text-slate-900 text-sm mb-4" style={{ fontWeight: 600 }}>Score Distribution</h3>
        <div className="space-y-3">
          {sorted.map((v) => (
            <div key={v.vendorId} className="flex items-center gap-3">
              <span className="text-xs text-slate-600 w-36 truncate" style={{ fontWeight: 500 }}>{v.name}</span>
              <div className="flex-1 h-6 bg-slate-100 rounded-full overflow-hidden relative">
                <div
                  className={`h-full rounded-full transition-all ${v.recommended ? 'bg-emerald-500' : 'bg-indigo-400'}`}
                  style={{ width: `${v.overall}%` }}
                />
                <span className="absolute inset-y-0 right-2 flex items-center text-xs text-slate-600" style={{ fontWeight: 600 }}>
                  {v.overall}
                </span>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

/* ─── Matrix Tab ─── */
function MatrixTab({ rfqId }: { rfqId: string }) {
  const lowestVendorId = Object.entries(comparisonMatrix.vendorTotals)
    .sort(([, a], [, b]) => a - b)[0]?.[0];
  const lowestTotal = lowestVendorId ? comparisonMatrix.vendorTotals[lowestVendorId] : 0;
  const lowestVendor = lowestVendorId ? getVendorById(lowestVendorId) : undefined;

  const recommended = vendorScores.find((v) => v.recommended);
  const recommendedTotal = recommended ? comparisonMatrix.vendorTotals[recommended.vendorId] : 0;

  const totalLineItems = comparisonMatrix.categories.reduce((sum, cat) => sum + cat.items.length, 0);

  return (
    <div className="space-y-5">
      {/* Summary Cards */}
      <div className="grid grid-cols-4 gap-3">
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
          <div className="text-xs text-slate-500 mb-1">Line Items</div>
          <div className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>{totalLineItems}</div>
          <div className="text-xs text-slate-400">{comparisonMatrix.categories.length} categories</div>
        </div>
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
          <div className="text-xs text-slate-500 mb-1">Vendors Compared</div>
          <div className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>{vendorScores.length}</div>
          <div className="text-xs text-slate-400">All responded</div>
        </div>
        <div className="bg-white rounded-xl border border-emerald-200 shadow-sm p-4 bg-emerald-50">
          <div className="text-xs text-emerald-600 mb-1">Lowest Total</div>
          <div className="text-emerald-700 text-lg" style={{ fontWeight: 700 }}>{formatCurrency(lowestTotal)}</div>
          <div className="text-xs text-emerald-600">{lowestVendor?.name ?? '—'}</div>
        </div>
        <div className="bg-white rounded-xl border border-indigo-200 shadow-sm p-4 bg-indigo-50">
          <div className="text-xs text-indigo-600 mb-1">Recommended Total</div>
          <div className="text-indigo-700 text-lg" style={{ fontWeight: 700 }}>{formatCurrency(recommendedTotal)}</div>
          <div className="text-xs text-indigo-600">{recommended?.name ?? '—'}</div>
        </div>
      </div>

      {/* Terms Summary */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
          <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
            <BarChart3 size={14} className="text-slate-600" />
          </div>
          <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Commercial Terms</span>
        </div>
        <table className="w-full">
          <thead>
            <tr className="border-b border-slate-100 bg-slate-50">
              <th className="text-left px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>TERM</th>
              {vendorScores.map((vs) => (
                <th key={vs.vendorId} className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>
                  {vs.name.split(' ')[0]}
                  {vs.recommended && <Star size={10} className="inline ml-1 text-emerald-600 -mt-0.5" />}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {comparisonMatrix.terms.map((t, i) => (
              <tr key={t.label} className={`border-b border-slate-50 ${i % 2 === 0 ? '' : 'bg-slate-50/30'}`}>
                <td className="px-5 py-3 text-slate-600 text-sm">{t.label}</td>
                {vendorScores.map((vs) => (
                  <td key={vs.vendorId} className={`px-4 py-3 text-sm ${vs.recommended ? 'text-emerald-700' : 'text-slate-800'}`} style={{ fontWeight: 500 }}>
                    {t.values[vs.vendorId] ?? '—'}
                  </td>
                ))}
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
function RiskTab({ rfqId }: { rfqId: string }) {
  const rfqRisks = riskItems.filter((r) => r.rfqId === rfqId);

  return (
    <div className="space-y-5">
      {/* Risk Summary Bar */}
      <div className="flex items-center gap-2 px-4 py-3 bg-white rounded-xl border border-slate-200 shadow-sm">
        <span className="text-slate-500 text-xs mr-2" style={{ fontWeight: 500 }}>RISK SUMMARY</span>
        {(['critical', 'high', 'medium', 'low'] as RiskLevel[]).map((level) => {
          const count = rfqRisks.filter((r) => r.level === level).length;
          const cfg = riskLevelConfig[level];
          const Icon = riskIcons[level];
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
          <span className="ml-auto text-slate-400 text-xs">{rfqRisks.length} item{rfqRisks.length !== 1 ? 's' : ''}</span>
        </div>
        <div className="divide-y divide-slate-100">
          {rfqRisks.map((risk) => {
            const cfg = riskLevelConfig[risk.level];
            const Icon = riskIcons[risk.level];
            const vendor = getVendorById(risk.vendorId);
            return (
              <div key={risk.id} className="flex items-start gap-4 px-5 py-4">
                <div className={`w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${cfg.bg} border ${cfg.border}`}>
                  <Icon size={15} className={cfg.text} />
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap mb-1">
                    <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{risk.title}</span>
                    <span className={`text-xs px-2 py-0.5 rounded-full border capitalize ${cfg.bg} ${cfg.border} ${cfg.text}`} style={{ fontWeight: 600 }}>{risk.level}</span>
                    <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{vendor?.name ?? risk.vendorId}</span>
                    {risk.escalated && <span className="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded" style={{ fontWeight: 600 }}>Escalated</span>}
                    <span className="text-xs bg-slate-50 text-slate-500 px-2 py-0.5 rounded capitalize">{risk.category}</span>
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
function TrailTab({ entries }: { entries: typeof decisionTrailEntries }) {
  const allVerified = entries.every((e) => e.verified);

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
          {entries.length} entries · {allVerified ? 'All hashes validated' : 'Validation failed'}
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
            {entries.map((entry) => {
              const eventCfg = eventTypeLabels[entry.eventType] ?? { label: entry.eventType, color: 'bg-slate-100 text-slate-600' };
              const actor = entry.actor === 'System' || entry.actor === 'Approval Engine'
                ? null
                : getUserById(entry.actor);
              return (
                <tr key={entry.id} className="border-b border-slate-50 hover:bg-slate-50">
                  <td className="px-5 py-3">
                    <span className="text-slate-600 text-sm" style={{ fontWeight: 600 }}>#{entry.sequence}</span>
                  </td>
                  <td className="px-4 py-3">
                    <div>
                      <span className={`text-xs px-2 py-0.5 rounded ${eventCfg.color}`} style={{ fontWeight: 600 }}>{eventCfg.label}</span>
                      {entry.description && (
                        <p className="text-slate-400 text-xs mt-1 truncate max-w-xs">{entry.description}</p>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-1.5">
                      <User size={12} className="text-slate-400" />
                      <span className="text-slate-700 text-xs" style={{ fontWeight: 500 }}>
                        {actor?.name ?? entry.actor}
                      </span>
                      {entry.actorRole && entry.actorRole !== entry.actor && (
                        <span className="text-slate-400 text-xs">· {entry.actorRole}</span>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-slate-600 text-xs">{formatDateTime(entry.timestamp)}</span>
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
