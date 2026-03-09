import { useState, useMemo } from 'react';
import { useParams, Link, useNavigate } from 'react-router';
import {
  ArrowLeft, ChevronDown, Send, Bell, Lock, RotateCcw, Archive,
  Copy, Edit3, FileText, Users, Activity, LayoutList,
  Calendar, DollarSign, Tag, User, Clock, Hash,
  Mail, Globe, CheckCircle, XCircle, AlertTriangle, Loader2,
  Download, Eye, Shield, ExternalLink, MoreHorizontal,
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import { RichTooltip, TooltipBadge, StatusDot } from '../components/RichTooltip';
import {
  rfqs, rfqLineItems, vendorInvitations, vendors, quoteSubmissions,
  decisionTrailEntries, statusColors, formatCurrency, formatDate,
  formatDateTime, getUserById, getVendorById,
} from '../data/mockData';

type TabId = 'overview' | 'vendors' | 'documents' | 'activity';

const tabs: { id: TabId; label: string; icon: typeof LayoutList }[] = [
  { id: 'overview', label: 'Overview', icon: LayoutList },
  { id: 'vendors', label: 'Vendors', icon: Users },
  { id: 'documents', label: 'Documents', icon: FileText },
  { id: 'activity', label: 'Activity', icon: Activity },
];

const invitationStatusConfig: Record<string, { color: string; dot: 'good' | 'warning' | 'danger' | 'neutral' }> = {
  responded: { color: 'bg-emerald-100 text-emerald-700', dot: 'good' },
  invited: { color: 'bg-blue-100 text-blue-700', dot: 'warning' },
  declined: { color: 'bg-red-100 text-red-700', dot: 'danger' },
};

const confidenceColor = (c: number) =>
  c >= 90 ? 'text-emerald-600 bg-emerald-50' : c >= 70 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50';

const eventIcons: Record<string, typeof CheckCircle> = {
  rfq_created: FileText,
  rfq_published: Send,
  quote_accepted: CheckCircle,
  normalization_locked: Lock,
  matrix_built: LayoutList,
  scoring_computed: Activity,
  approval_required: AlertTriangle,
  sanctions_flagged: Shield,
};

export function RFQDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const rfq = rfqs.find(r => r.id === id);
  const lineItems = useMemo(() => rfqLineItems.filter(li => li.rfqId === id), [id]);
  const invitations = useMemo(() => vendorInvitations.filter(inv => inv.rfqId === id), [id]);
  const quotes = useMemo(() => quoteSubmissions.filter(qs => qs.rfqId === id), [id]);
  const trail = useMemo(() => decisionTrailEntries.filter(dt => dt.rfqId === id).sort((a, b) => b.sequence - a.sequence), [id]);
  const owner = rfq ? getUserById(rfq.owner) : null;

  const [activeTab, setActiveTab] = useState<TabId>('overview');
  const [showActions, setShowActions] = useState(false);
  const [showInviteModal, setShowInviteModal] = useState(false);
  const [showReminderModal, setShowReminderModal] = useState(false);
  const [showCloseModal, setShowCloseModal] = useState(false);
  const [showReopenModal, setShowReopenModal] = useState(false);
  const [showArchiveModal, setShowArchiveModal] = useState(false);
  const [reminderVendorId, setReminderVendorId] = useState<string | null>(null);

  if (!rfq) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <p className="text-lg text-slate-500" style={{ fontWeight: 500 }}>RFQ not found</p>
          <Link to="/rfqs" className="text-sm text-indigo-600 hover:underline mt-2 inline-block">Back to RFQ list</Link>
        </div>
      </div>
    );
  }

  const estTotal = lineItems.reduce((sum, li) => sum + li.quantity * li.estimatedUnitPrice, 0);

  return (
    <div className="min-h-screen">
      {/* Header */}
      <div className="mb-5">
        <Link to="/rfqs" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 mb-3">
          <ArrowLeft size={14} /> Back to RFQs
        </Link>
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-3">
            <div>
              <div className="flex items-center gap-3">
                <h1 className="text-xl text-slate-900" style={{ fontWeight: 700 }}>{rfq.id}</h1>
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs ${statusColors[rfq.status]}`} style={{ fontWeight: 500 }}>
                  {rfq.status}
                </span>
              </div>
              <p className="text-sm text-slate-500 mt-0.5">{rfq.title}</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            {rfq.status === 'open' && (
              <button
                onClick={() => setShowCloseModal(true)}
                className="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50"
                style={{ fontWeight: 500 }}
              >
                <Lock size={14} /> Close for Submissions
              </button>
            )}
            <div className="relative">
              <button
                onClick={() => setShowActions(!showActions)}
                className="inline-flex items-center gap-1.5 px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                style={{ fontWeight: 500 }}
              >
                Actions <ChevronDown size={14} />
              </button>
              {showActions && (
                <div className="absolute right-0 top-full mt-1 z-30 w-48 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                  <button onClick={() => { setShowActions(false); }} className="w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">
                    <Edit3 size={13} /> Edit
                  </button>
                  <button onClick={() => { setShowActions(false); }} className="w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">
                    <Copy size={13} /> Duplicate
                  </button>
                  {rfq.status === 'open' && (
                    <button onClick={() => { setShowActions(false); setShowCloseModal(true); }} className="w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">
                      <Lock size={13} /> Close for Submissions
                    </button>
                  )}
                  {rfq.status === 'closed' && (
                    <button onClick={() => { setShowActions(false); setShowReopenModal(true); }} className="w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">
                      <RotateCcw size={13} /> Reopen
                    </button>
                  )}
                  <button onClick={() => { setShowActions(false); setShowArchiveModal(true); }} className="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-600 hover:bg-red-50">
                    <Archive size={13} /> Archive
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className="flex gap-6">
        {/* Main content */}
        <div className="flex-1 min-w-0">
          {/* Tabs */}
          <div className="border-b border-slate-200 mb-5">
            <nav className="flex gap-0 -mb-px">
              {tabs.map(tab => {
                const Icon = tab.icon;
                return (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`flex items-center gap-1.5 px-4 py-2.5 text-sm border-b-2 transition-colors ${
                      activeTab === tab.id
                        ? 'border-indigo-600 text-indigo-600'
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                    }`}
                    style={{ fontWeight: activeTab === tab.id ? 600 : 400 }}
                  >
                    <Icon size={15} />
                    {tab.label}
                  </button>
                );
              })}
            </nav>
          </div>

          {/* Overview Tab */}
          {activeTab === 'overview' && (
            <div className="space-y-5">
              {/* Metadata */}
              <div className="bg-white border border-slate-200 rounded-xl p-5">
                <h3 className="text-sm text-slate-900 mb-4" style={{ fontWeight: 600 }}>RFQ Details</h3>
                <div className="grid grid-cols-3 gap-4">
                  <div className="flex items-start gap-2">
                    <Tag size={14} className="text-slate-400 mt-0.5" />
                    <div>
                      <p className="text-xs text-slate-500">Category</p>
                      <p className="text-sm text-slate-900" style={{ fontWeight: 500 }}>{rfq.category}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <User size={14} className="text-slate-400 mt-0.5" />
                    <div>
                      <p className="text-xs text-slate-500">Owner</p>
                      <p className="text-sm text-slate-900" style={{ fontWeight: 500 }}>{owner?.name ?? rfq.owner}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <Calendar size={14} className="text-slate-400 mt-0.5" />
                    <div>
                      <p className="text-xs text-slate-500">Deadline</p>
                      <p className="text-sm text-slate-900" style={{ fontWeight: 500 }}>{formatDate(rfq.deadline)}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <DollarSign size={14} className="text-slate-400 mt-0.5" />
                    <div>
                      <p className="text-xs text-slate-500">Estimated Value</p>
                      <p className="text-sm text-slate-900" style={{ fontWeight: 500 }}>{formatCurrency(rfq.estimatedValue)}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <Clock size={14} className="text-slate-400 mt-0.5" />
                    <div>
                      <p className="text-xs text-slate-500">Created</p>
                      <p className="text-sm text-slate-900" style={{ fontWeight: 500 }}>{formatDate(rfq.createdAt)}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <Hash size={14} className="text-slate-400 mt-0.5" />
                    <div>
                      <p className="text-xs text-slate-500">Line Items</p>
                      <p className="text-sm text-slate-900" style={{ fontWeight: 500 }}>{rfq.lineItemCount}</p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Line Items */}
              <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div className="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                  <h3 className="text-sm text-slate-900" style={{ fontWeight: 600 }}>Line Items</h3>
                  <span className="text-xs text-slate-500">{lineItems.length} items</span>
                </div>
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-slate-200 bg-slate-50/70">
                      <th className="px-5 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Description</th>
                      <th className="px-3 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Category</th>
                      <th className="px-3 py-2.5 text-right text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Qty</th>
                      <th className="px-3 py-2.5 text-center text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>UoM</th>
                      <th className="px-3 py-2.5 text-right text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Est. Unit Price</th>
                      <th className="px-5 py-2.5 text-right text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Est. Total</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {lineItems.map(li => (
                      <tr key={li.id} className="hover:bg-slate-50/50">
                        <td className="px-5 py-2.5 text-slate-900" style={{ fontWeight: 500 }}>{li.description}</td>
                        <td className="px-3 py-2.5">
                          <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{li.category}</span>
                        </td>
                        <td className="px-3 py-2.5 text-right text-slate-600">{li.quantity}</td>
                        <td className="px-3 py-2.5 text-center text-slate-500">{li.uom}</td>
                        <td className="px-3 py-2.5 text-right text-slate-600">{formatCurrency(li.estimatedUnitPrice)}</td>
                        <td className="px-5 py-2.5 text-right text-slate-900" style={{ fontWeight: 500 }}>{formatCurrency(li.quantity * li.estimatedUnitPrice)}</td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot>
                    <tr className="border-t border-slate-200 bg-slate-50/50">
                      <td colSpan={5} className="px-5 py-3 text-right text-sm text-slate-600" style={{ fontWeight: 600 }}>Total Estimated</td>
                      <td className="px-5 py-3 text-right text-sm text-slate-900" style={{ fontWeight: 700 }}>{formatCurrency(estTotal)}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              {/* Terms Summary */}
              <div className="bg-white border border-slate-200 rounded-xl p-5">
                <h3 className="text-sm text-slate-900 mb-3" style={{ fontWeight: 600 }}>Terms &amp; Conditions Summary</h3>
                <div className="grid grid-cols-2 gap-3 text-sm">
                  <div className="flex items-center justify-between border border-slate-100 rounded-lg px-3 py-2">
                    <span className="text-slate-500">Payment Terms</span>
                    <span className="text-slate-900" style={{ fontWeight: 500 }}>Net 30</span>
                  </div>
                  <div className="flex items-center justify-between border border-slate-100 rounded-lg px-3 py-2">
                    <span className="text-slate-500">Delivery Window</span>
                    <span className="text-slate-900" style={{ fontWeight: 500 }}>14 days</span>
                  </div>
                  <div className="flex items-center justify-between border border-slate-100 rounded-lg px-3 py-2">
                    <span className="text-slate-500">Warranty Required</span>
                    <span className="text-slate-900" style={{ fontWeight: 500 }}>Min. 3 years</span>
                  </div>
                  <div className="flex items-center justify-between border border-slate-100 rounded-lg px-3 py-2">
                    <span className="text-slate-500">Incoterm</span>
                    <span className="text-slate-900" style={{ fontWeight: 500 }}>DAP</span>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Vendors Tab */}
          {activeTab === 'vendors' && (
            <div className="space-y-5">
              <div className="flex items-center justify-between">
                <h3 className="text-sm text-slate-900" style={{ fontWeight: 600 }}>Vendor Roster ({invitations.length})</h3>
                <button
                  onClick={() => setShowInviteModal(true)}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                  style={{ fontWeight: 500 }}
                >
                  <Send size={13} /> Send Invitations
                </button>
              </div>

              <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-slate-200 bg-slate-50/70">
                      <th className="px-5 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Vendor</th>
                      <th className="px-3 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Status</th>
                      <th className="px-3 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Channel</th>
                      <th className="px-3 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Invited</th>
                      <th className="px-3 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Responded</th>
                      <th className="px-3 py-2.5 text-center text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Score</th>
                      <th className="px-5 py-2.5 text-right text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {invitations.map(inv => {
                      const vendor = getVendorById(inv.vendorId);
                      if (!vendor) return null;
                      const cfg = invitationStatusConfig[inv.status] ?? { color: 'bg-slate-100 text-slate-600', dot: 'neutral' as const };
                      return (
                        <RichTooltip
                          key={inv.id}
                          side="right"
                          trigger={
                            <tr className="hover:bg-slate-50/50 group">
                              <td className="px-5 py-3">
                                <div className="flex items-center gap-2">
                                  <div className="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600" style={{ fontWeight: 600 }}>
                                    {vendor.name.charAt(0)}
                                  </div>
                                  <div>
                                    <Link to={`/vendors/${vendor.id}`} className="text-slate-900 hover:text-indigo-600" style={{ fontWeight: 500 }}>{vendor.name}</Link>
                                    <p className="text-xs text-slate-400">{vendor.country}</p>
                                  </div>
                                </div>
                              </td>
                              <td className="px-3 py-3">
                                <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs ${cfg.color}`} style={{ fontWeight: 500 }}>
                                  {inv.status}
                                </span>
                              </td>
                              <td className="px-3 py-3">
                                <span className="inline-flex items-center gap-1 text-xs text-slate-600">
                                  {inv.channel === 'email' ? <Mail size={12} /> : <Globe size={12} />}
                                  {inv.channel}
                                </span>
                              </td>
                              <td className="px-3 py-3 text-xs text-slate-600">{formatDate(inv.invitedAt)}</td>
                              <td className="px-3 py-3 text-xs text-slate-600">{inv.respondedAt ? formatDate(inv.respondedAt) : '—'}</td>
                              <td className="px-3 py-3 text-center">
                                <span className="text-xs text-slate-700" style={{ fontWeight: 600 }}>{vendor.overallScore}</span>
                              </td>
                              <td className="px-5 py-3 text-right">
                                <div className="flex items-center justify-end gap-1">
                                  {inv.status === 'invited' && (
                                    <button
                                      onClick={() => { setReminderVendorId(inv.vendorId); setShowReminderModal(true); }}
                                      className="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50"
                                      title="Send reminder"
                                    >
                                      <Bell size={14} />
                                    </button>
                                  )}
                                  <Link to={`/vendors/${vendor.id}`} className="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50">
                                    <ExternalLink size={14} />
                                  </Link>
                                </div>
                              </td>
                            </tr>
                          }
                        >
                          <div className="space-y-2">
                            <div className="flex items-center gap-2">
                              <StatusDot status={cfg.dot} />
                              <span className="text-sm text-slate-900" style={{ fontWeight: 600 }}>{vendor.name}</span>
                            </div>
                            <div className="border-t border-slate-100 pt-2 space-y-0.5">
                              <TooltipBadge label="Overall Score" value={vendor.overallScore} color="text-indigo-600" />
                              <TooltipBadge label="Category" value={vendor.category} />
                              <TooltipBadge label="Active RFQs" value={vendor.activeRfqs} />
                              <TooltipBadge label="Avg Response" value={`${vendor.avgResponseDays}d`} />
                              <TooltipBadge label="Risk" value={vendor.riskLevel} color={vendor.riskLevel === 'low' ? 'text-emerald-600' : vendor.riskLevel === 'high' ? 'text-red-600' : 'text-amber-600'} />
                            </div>
                          </div>
                        </RichTooltip>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Documents Tab */}
          {activeTab === 'documents' && (
            <div className="space-y-5">
              <h3 className="text-sm text-slate-900" style={{ fontWeight: 600 }}>Quote Submissions ({quotes.length})</h3>

              <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-slate-200 bg-slate-50/70">
                      <th className="px-5 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>File</th>
                      <th className="px-3 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Vendor</th>
                      <th className="px-3 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Status</th>
                      <th className="px-3 py-2.5 text-center text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Confidence</th>
                      <th className="px-3 py-2.5 text-right text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Total</th>
                      <th className="px-3 py-2.5 text-center text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Lines</th>
                      <th className="px-3 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Uploaded</th>
                      <th className="px-5 py-2.5 text-right text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {quotes.map(q => {
                      const vendor = q.vendorId ? getVendorById(q.vendorId) : null;
                      return (
                        <tr key={q.id} className="hover:bg-slate-50/50">
                          <td className="px-5 py-3">
                            <div className="flex items-center gap-2">
                              <FileText size={14} className="text-slate-400" />
                              <span className="text-slate-900" style={{ fontWeight: 500 }}>{q.fileName}</span>
                            </div>
                          </td>
                          <td className="px-3 py-3 text-xs text-slate-600">{vendor?.name ?? '—'}</td>
                          <td className="px-3 py-3">
                            <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs ${statusColors[q.status]}`} style={{ fontWeight: 500 }}>
                              {q.status === 'processing' && <Loader2 size={11} className="animate-spin" />}
                              {q.status}
                            </span>
                          </td>
                          <td className="px-3 py-3 text-center">
                            {q.confidence > 0 ? (
                              <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs ${confidenceColor(q.confidence)}`} style={{ fontWeight: 600 }}>
                                {q.confidence}%
                              </span>
                            ) : (
                              <span className="text-xs text-slate-400">—</span>
                            )}
                          </td>
                          <td className="px-3 py-3 text-right text-xs text-slate-900" style={{ fontWeight: 500 }}>
                            {q.totalAmount > 0 ? formatCurrency(q.totalAmount) : '—'}
                          </td>
                          <td className="px-3 py-3 text-center text-xs text-slate-600">{q.lineCount || '—'}</td>
                          <td className="px-3 py-3 text-xs text-slate-600">{formatDateTime(q.uploadedAt)}</td>
                          <td className="px-5 py-3 text-right">
                            <div className="flex items-center justify-end gap-1">
                              <button className="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50" title="View">
                                <Eye size={14} />
                              </button>
                              <button className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100" title="Download">
                                <Download size={14} />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                    {quotes.length === 0 && (
                      <tr>
                        <td colSpan={8} className="px-5 py-10 text-center text-sm text-slate-400">No quotes submitted yet.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Activity Tab */}
          {activeTab === 'activity' && (
            <div className="space-y-5">
              <h3 className="text-sm text-slate-900" style={{ fontWeight: 600 }}>Decision Trail</h3>
              <div className="bg-white border border-slate-200 rounded-xl p-5">
                <div className="relative">
                  <div className="absolute left-4 top-0 bottom-0 w-px bg-slate-200" />
                  <div className="space-y-6">
                    {trail.map((entry, idx) => {
                      const Icon = eventIcons[entry.eventType] ?? Activity;
                      const actor = entry.actor !== 'System' && entry.actor !== 'Approval Engine' ? getUserById(entry.actor) : null;
                      return (
                        <div key={entry.id} className="relative flex gap-4 pl-0">
                          <div className="relative z-10 flex-shrink-0 w-8 h-8 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center">
                            <Icon size={14} className="text-slate-500" />
                          </div>
                          <div className="flex-1 min-w-0 pt-0.5">
                            <div className="flex items-center gap-2 flex-wrap">
                              <span className="text-sm text-slate-900" style={{ fontWeight: 500 }}>{entry.description}</span>
                            </div>
                            <div className="flex items-center gap-3 mt-1 text-xs text-slate-500">
                              <span>{actor?.name ?? entry.actor}</span>
                              <span>{formatDateTime(entry.timestamp)}</span>
                              <span className="font-mono text-slate-400" title={`Hash: ${entry.entryHash}`}>{entry.entryHash.slice(0, 8)}…</span>
                              {entry.verified && <CheckCircle size={12} className="text-emerald-500" />}
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Right Sidebar - Activity Summary */}
        <div className="w-72 flex-shrink-0">
          <div className="bg-white border border-slate-200 rounded-xl p-4 sticky top-4">
            <h4 className="text-xs text-slate-500 uppercase tracking-wider mb-3" style={{ fontWeight: 600 }}>Quick Summary</h4>
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs text-slate-500">Status</span>
                <span className={`text-xs px-2 py-0.5 rounded-full ${statusColors[rfq.status]}`} style={{ fontWeight: 500 }}>{rfq.status}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-xs text-slate-500">Vendors</span>
                <span className="text-xs text-slate-900" style={{ fontWeight: 600 }}>{rfq.vendorCount}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-xs text-slate-500">Quotes</span>
                <span className="text-xs text-slate-900" style={{ fontWeight: 600 }}>{rfq.quoteCount}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-xs text-slate-500">Est. Value</span>
                <span className="text-xs text-slate-900" style={{ fontWeight: 600 }}>{formatCurrency(rfq.estimatedValue)}</span>
              </div>
              {rfq.savings > 0 && (
                <div className="flex items-center justify-between">
                  <span className="text-xs text-slate-500">Savings</span>
                  <span className="text-xs text-emerald-600" style={{ fontWeight: 600 }}>{formatCurrency(rfq.savings)}</span>
                </div>
              )}
              <div className="flex items-center justify-between">
                <span className="text-xs text-slate-500">Deadline</span>
                <span className="text-xs text-slate-900" style={{ fontWeight: 500 }}>{formatDate(rfq.deadline)}</span>
              </div>
            </div>

            <div className="border-t border-slate-100 mt-4 pt-4">
              <h4 className="text-xs text-slate-500 uppercase tracking-wider mb-3" style={{ fontWeight: 600 }}>Recent Activity</h4>
              <div className="space-y-3">
                {trail.slice(0, 5).map(entry => {
                  const Icon = eventIcons[entry.eventType] ?? Activity;
                  return (
                    <div key={entry.id} className="flex items-start gap-2">
                      <Icon size={13} className="text-slate-400 mt-0.5 flex-shrink-0" />
                      <div className="min-w-0">
                        <p className="text-xs text-slate-700 leading-relaxed truncate">{entry.description}</p>
                        <p className="text-[11px] text-slate-400">{formatDate(entry.timestamp)}</p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {rfq.comparisonRunId && (
              <div className="border-t border-slate-100 mt-4 pt-4">
                <Link
                  to="/comparison"
                  className="flex items-center justify-center gap-1.5 w-full px-3 py-2 text-xs bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors"
                  style={{ fontWeight: 500 }}
                >
                  <LayoutList size={13} /> View Comparison
                </Link>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Send Invitations SlideOver */}
      <SlideOver
        open={showInviteModal}
        onOpenChange={setShowInviteModal}
        title="Send Vendor Invitations"
        description={`Invite vendors to respond to ${rfq.id}`}
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowInviteModal(false)} className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50" style={{ fontWeight: 500 }}>Cancel</button>
            <button onClick={() => setShowInviteModal(false)} className="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-1.5"><Send size={14} /> Send Invitations</span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <p className="text-xs text-blue-700">Select vendors to invite. They will receive an email or portal notification with the RFQ details and submission deadline.</p>
          </div>
          <div className="space-y-2">
            {vendors.map(v => {
              const alreadyInvited = invitations.some(inv => inv.vendorId === v.id);
              return (
                <label key={v.id} className={`flex items-center gap-3 border rounded-lg px-4 py-3 cursor-pointer transition-colors ${alreadyInvited ? 'border-slate-100 bg-slate-50 opacity-60' : 'border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/30'}`}>
                  <input type="checkbox" disabled={alreadyInvited} defaultChecked={alreadyInvited} className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                  <div className="flex-1">
                    <p className="text-sm text-slate-900" style={{ fontWeight: 500 }}>{v.name}</p>
                    <p className="text-xs text-slate-500">{v.category} &middot; {v.country} &middot; Score: {v.overallScore}</p>
                  </div>
                  {alreadyInvited && <span className="text-xs text-slate-400">Already invited</span>}
                </label>
              );
            })}
          </div>
        </div>
      </SlideOver>

      {/* Reminder Confirmation SlideOver */}
      <SlideOver
        open={showReminderModal}
        onOpenChange={setShowReminderModal}
        title="Send Reminder"
        description="Remind the vendor to submit their quote."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowReminderModal(false)} className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50" style={{ fontWeight: 500 }}>Cancel</button>
            <button onClick={() => { setShowReminderModal(false); setReminderVendorId(null); }} className="px-4 py-2 text-sm bg-amber-500 text-white rounded-lg hover:bg-amber-600" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-1.5"><Bell size={14} /> Send Reminder</span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          {(() => {
            const vendor = reminderVendorId ? getVendorById(reminderVendorId) : null;
            if (!vendor) return null;
            const inv = invitations.find(i => i.vendorId === reminderVendorId);
            return (
              <>
                <div className="border border-slate-200 rounded-lg p-4">
                  <p className="text-sm text-slate-900" style={{ fontWeight: 600 }}>{vendor.name}</p>
                  <p className="text-xs text-slate-500 mt-1">{vendor.contactEmail}</p>
                  {inv && (
                    <div className="mt-2 pt-2 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                      <p>Invited: {formatDate(inv.invitedAt)}</p>
                      <p>Channel: {inv.channel}</p>
                      <p>Previous reminders: {inv.reminderSent ? 'Yes' : 'None'}</p>
                    </div>
                  )}
                </div>
                <div className="bg-amber-50 border border-amber-200 rounded-lg p-3">
                  <p className="text-xs text-amber-700">A reminder will be sent via {inv?.channel ?? 'email'} with the RFQ deadline of {formatDate(rfq.deadline)}.</p>
                </div>
              </>
            );
          })()}
        </div>
      </SlideOver>

      {/* Close RFQ SlideOver */}
      <SlideOver
        open={showCloseModal}
        onOpenChange={setShowCloseModal}
        title="Close RFQ for Submissions"
        description="No new quotes will be accepted after closing."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowCloseModal(false)} className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50" style={{ fontWeight: 500 }}>Cancel</button>
            <button onClick={() => setShowCloseModal(false)} className="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-1.5"><Lock size={14} /> Close RFQ</span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="flex gap-3">
              <Lock size={18} className="text-blue-600 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm text-blue-800" style={{ fontWeight: 500 }}>This will close {rfq.id} for new submissions.</p>
                <p className="text-xs text-blue-700 mt-1">Vendors will no longer be able to submit or update quotes. You can reopen this RFQ later if needed.</p>
              </div>
            </div>
          </div>
          <div className="border border-slate-200 rounded-lg p-4 space-y-2 text-sm">
            <div className="flex justify-between">
              <span className="text-slate-500">Quotes received</span>
              <span className="text-slate-900" style={{ fontWeight: 600 }}>{rfq.quoteCount} of {rfq.vendorCount} vendors</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-500">Pending responses</span>
              <span className="text-slate-900" style={{ fontWeight: 600 }}>{invitations.filter(i => i.status === 'invited').length}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-500">Deadline</span>
              <span className="text-slate-900" style={{ fontWeight: 500 }}>{formatDate(rfq.deadline)}</span>
            </div>
          </div>
        </div>
      </SlideOver>

      {/* Reopen RFQ SlideOver */}
      <SlideOver
        open={showReopenModal}
        onOpenChange={setShowReopenModal}
        title="Reopen RFQ"
        description="Allow vendors to submit or update quotes again."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowReopenModal(false)} className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50" style={{ fontWeight: 500 }}>Cancel</button>
            <button onClick={() => setShowReopenModal(false)} className="px-4 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-1.5"><RotateCcw size={14} /> Reopen</span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div className="flex gap-3">
              <RotateCcw size={18} className="text-purple-600 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm text-purple-800" style={{ fontWeight: 500 }}>Reopening this RFQ will allow new submissions.</p>
                <p className="text-xs text-purple-700 mt-1">Any existing comparison runs will be marked as stale and will need to be regenerated. A new deadline can be set.</p>
              </div>
            </div>
          </div>
          <div>
            <label className="block text-xs text-slate-500 mb-1" style={{ fontWeight: 500 }}>New Deadline</label>
            <input type="date" className="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
          </div>
          <div>
            <label className="block text-xs text-slate-500 mb-1" style={{ fontWeight: 500 }}>Reason for Reopening</label>
            <textarea rows={3} className="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Describe why the RFQ is being reopened…" />
          </div>
        </div>
      </SlideOver>

      {/* Archive SlideOver */}
      <SlideOver
        open={showArchiveModal}
        onOpenChange={setShowArchiveModal}
        title="Confirm Archive"
        description={`Archive ${rfq.id}?`}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowArchiveModal(false)} className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50" style={{ fontWeight: 500 }}>Cancel</button>
            <button onClick={() => setShowArchiveModal(false)} className="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700" style={{ fontWeight: 500 }}>Archive</button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div className="flex gap-3">
              <Archive size={18} className="text-amber-600 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm text-amber-800" style={{ fontWeight: 500 }}>This action will move the RFQ to the archive.</p>
                <p className="text-xs text-amber-700 mt-1">Archived RFQs are read-only. This action can be undone by a Procurement Manager.</p>
              </div>
            </div>
          </div>
          <div className="border border-slate-200 rounded-lg p-4 space-y-2 text-sm">
            <div className="flex justify-between">
              <span className="text-slate-500">RFQ</span>
              <span className="text-slate-900" style={{ fontWeight: 600 }}>{rfq.id} — {rfq.title}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-500">Current Status</span>
              <span className={`text-xs px-2 py-0.5 rounded-full ${statusColors[rfq.status]}`}>{rfq.status}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-500">Value</span>
              <span className="text-slate-900" style={{ fontWeight: 500 }}>{formatCurrency(rfq.estimatedValue)}</span>
            </div>
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
