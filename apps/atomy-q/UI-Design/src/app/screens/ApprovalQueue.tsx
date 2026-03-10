import { useState, useMemo } from 'react';
import {
  CheckCircle, XCircle, AlertTriangle, Clock, ChevronRight,
  Search, Filter, Users, Send, Pause, MoreHorizontal,
  ShieldAlert, ArrowUpRight, FileQuestion, DollarSign,
  AlertOctagon, Flame, ArrowUp, Minus, Inbox
} from 'lucide-react';
import { Link, useNavigate } from 'react-router';
import {
  approvalItems, users, statusColors, formatCurrency, formatDateTime,
  getUserById, getRfqById
} from '../data/mockData';
import { RichTooltip, TooltipBadge } from '../components/RichTooltip';
import { SlideOver } from '../components/SlideOver';

type FilterTab = 'all' | 'pending' | 'awaiting_evidence' | 'snoozed';

const filterTabs: { id: FilterTab; label: string; count: (items: typeof approvalItems) => number }[] = [
  { id: 'all', label: 'All', count: (items) => items.length },
  { id: 'pending', label: 'Pending', count: (items) => items.filter((i) => i.status === 'pending').length },
  { id: 'awaiting_evidence', label: 'Awaiting Evidence', count: (items) => items.filter((i) => i.status === 'awaiting_evidence').length },
  { id: 'snoozed', label: 'Snoozed', count: (items) => items.filter((i) => i.status === 'snoozed').length },
];

const typeConfig: Record<string, { label: string; color: string; icon: typeof ShieldAlert }> = {
  comparison: { label: 'Comparison', color: 'bg-indigo-100 text-indigo-700', icon: ArrowUpRight },
  risk_escalation: { label: 'Risk Escalation', color: 'bg-red-100 text-red-700', icon: ShieldAlert },
  policy_exception: { label: 'Policy Exception', color: 'bg-amber-100 text-amber-700', icon: AlertTriangle },
  override: { label: 'Override', color: 'bg-purple-100 text-purple-700', icon: AlertOctagon },
};

const priorityConfig: Record<string, { icon: typeof Flame; color: string; label: string }> = {
  urgent: { icon: Flame, color: 'text-red-600', label: 'Urgent' },
  high: { icon: ArrowUp, color: 'text-orange-500', label: 'High' },
  normal: { icon: Minus, color: 'text-slate-400', label: 'Normal' },
};

function getSlaInfo(deadline: string) {
  const now = Date.now();
  const target = new Date(deadline).getTime();
  const diffMs = target - now;
  const diffHours = diffMs / (1000 * 60 * 60);

  if (diffMs < 0) {
    const overdue = Math.abs(diffHours);
    const label = overdue < 24 ? `${Math.ceil(overdue)}h overdue` : `${Math.ceil(overdue / 24)}d overdue`;
    return { label, color: 'bg-red-100 text-red-700 border-red-200', urgency: 'breached' as const };
  }
  if (diffHours < 4) {
    return { label: `${Math.ceil(diffHours * 60)}m left`, color: 'bg-red-50 text-red-600 border-red-200', urgency: 'critical' as const };
  }
  if (diffHours < 24) {
    return { label: `${Math.ceil(diffHours)}h left`, color: 'bg-amber-50 text-amber-700 border-amber-200', urgency: 'warning' as const };
  }
  const days = Math.ceil(diffHours / 24);
  return { label: `${days}d left`, color: 'bg-slate-50 text-slate-600 border-slate-200', urgency: 'ok' as const };
}

const snoozeDurations = [
  { label: '1 hour', value: 1 },
  { label: '4 hours', value: 4 },
  { label: '1 day', value: 24 },
  { label: 'Custom', value: -1 },
];

export function ApprovalQueue() {
  const navigate = useNavigate();
  const [filter, setFilter] = useState<FilterTab>('all');
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const [showReassignModal, setShowReassignModal] = useState(false);
  const [showBulkApproveModal, setShowBulkApproveModal] = useState(false);
  const [showBulkRejectModal, setShowBulkRejectModal] = useState(false);
  const [showSnoozeModal, setShowSnoozeModal] = useState(false);
  const [showEvidenceModal, setShowEvidenceModal] = useState(false);
  const [activeItemId, setActiveItemId] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');

  const [reassignUserId, setReassignUserId] = useState('');
  const [bulkReason, setBulkReason] = useState('');
  const [snoozeDuration, setSnoozeDuration] = useState(4);
  const [customSnoozeHours, setCustomSnoozeHours] = useState(8);
  const [evidenceDescription, setEvidenceDescription] = useState('');
  const [evidenceTargetUser, setEvidenceTargetUser] = useState('');

  const filteredItems = useMemo(() => {
    let items = approvalItems;
    if (filter === 'pending') items = items.filter((i) => i.status === 'pending');
    else if (filter === 'awaiting_evidence') items = items.filter((i) => i.status === 'awaiting_evidence');
    else if (filter === 'snoozed') items = items.filter((i) => i.status === 'snoozed');

    if (searchQuery) {
      const q = searchQuery.toLowerCase();
      items = items.filter(
        (i) => i.title.toLowerCase().includes(q) || i.rfqId.toLowerCase().includes(q) || i.id.toLowerCase().includes(q)
      );
    }
    return items;
  }, [filter, searchQuery]);

  const toggleSelect = (id: string) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const toggleSelectAll = () => {
    if (selectedIds.size === filteredItems.length) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(filteredItems.map((i) => i.id)));
    }
  };

  const eligibleApprovers = users.filter(
    (u) => (u.roleId === 'role-approver' || u.roleId === 'role-manager') && u.status === 'active'
  );

  const activeItem = approvalItems.find((i) => i.id === activeItemId);

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>Approval Queue</span>
        </div>

        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-3 mb-1">
              <Inbox size={20} className="text-indigo-600" />
              <h1 className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>Approval Queue</h1>
              <span className="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full" style={{ fontWeight: 600 }}>
                {approvalItems.filter((i) => i.status === 'pending').length} pending
              </span>
            </div>
            <p className="text-xs text-slate-500 mt-0.5">Prioritize and process all gated decisions across procurement workflows.</p>
          </div>
        </div>

        {/* Filter Tabs + Search */}
        <div className="flex items-center justify-between mt-4">
          <div className="flex items-center gap-0">
            {filterTabs.map((tab) => {
              const count = tab.count(approvalItems);
              return (
                <button
                  key={tab.id}
                  onClick={() => { setFilter(tab.id); setSelectedIds(new Set()); }}
                  className={`px-4 py-2 text-sm border-b-2 transition-colors ${
                    filter === tab.id
                      ? 'border-indigo-600 text-indigo-600'
                      : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                  }`}
                  style={{ fontWeight: filter === tab.id ? 600 : 400 }}
                >
                  {tab.label}
                  <span className={`ml-1.5 text-xs px-1.5 py-0.5 rounded-full ${filter === tab.id ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'}`}>
                    {count}
                  </span>
                </button>
              );
            })}
          </div>
          <div className="flex items-center gap-2">
            <div className="relative">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search approvals..."
                className="pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-700 w-56 focus:border-indigo-400 outline-none"
              />
            </div>
            <button className="flex items-center gap-1.5 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <Filter size={14} />
              Filters
            </button>
          </div>
        </div>
      </div>

      {/* Bulk Action Toolbar */}
      {selectedIds.size > 0 && (
        <div className="bg-indigo-50 border-b border-indigo-200 px-6 py-2.5 flex items-center gap-3 flex-shrink-0">
          <span className="text-xs text-indigo-700" style={{ fontWeight: 600 }}>
            {selectedIds.size} selected
          </span>
          <div className="w-px h-5 bg-indigo-200" />
          <button
            onClick={() => { setBulkReason(''); setShowBulkApproveModal(true); }}
            className="flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-1.5 text-xs"
            style={{ fontWeight: 500 }}
          >
            <CheckCircle size={13} />
            Bulk Approve
          </button>
          <button
            onClick={() => { setBulkReason(''); setShowBulkRejectModal(true); }}
            className="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg px-3 py-1.5 text-xs"
            style={{ fontWeight: 500 }}
          >
            <XCircle size={13} />
            Bulk Reject
          </button>
          <button
            onClick={() => { setReassignUserId(''); setActiveItemId(null); setShowReassignModal(true); }}
            className="flex items-center gap-1.5 border border-indigo-300 text-indigo-700 rounded-lg px-3 py-1.5 text-xs hover:bg-indigo-100"
            style={{ fontWeight: 500 }}
          >
            <Users size={13} />
            Bulk Reassign
          </button>
          <button
            onClick={() => setSelectedIds(new Set())}
            className="ml-auto text-xs text-indigo-600 hover:text-indigo-800"
            style={{ fontWeight: 500 }}
          >
            Clear Selection
          </button>
        </div>
      )}

      {/* Queue Table */}
      <div className="flex-1 overflow-auto p-5">
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50">
                <th className="w-10 px-4 py-2.5">
                  <input
                    type="checkbox"
                    checked={selectedIds.size === filteredItems.length && filteredItems.length > 0}
                    onChange={toggleSelectAll}
                    className="accent-indigo-600"
                  />
                </th>
                <th className="w-10 px-2 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }} />
                <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>TITLE</th>
                <th className="text-left px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>TYPE</th>
                <th className="text-left px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>RFQ</th>
                <th className="text-right px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>VALUE</th>
                <th className="text-left px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>ASSIGNEE</th>
                <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>STATUS</th>
                <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>SLA</th>
                <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              {filteredItems.length === 0 && (
                <tr>
                  <td colSpan={10} className="px-6 py-12 text-center">
                    <div className="text-slate-400 text-sm">No approvals match the current filter.</div>
                  </td>
                </tr>
              )}
              {filteredItems.map((item) => {
                const assignee = getUserById(item.assigneeId);
                const requester = getUserById(item.requesterId);
                const rfq = getRfqById(item.rfqId);
                const typeCfg = typeConfig[item.type] ?? typeConfig.comparison;
                const priCfg = priorityConfig[item.priority] ?? priorityConfig.normal;
                const PriorityIcon = priCfg.icon;
                const sla = getSlaInfo(item.slaDeadline);

                return (
                  <tr
                    key={item.id}
                    className={`border-b border-slate-50 hover:bg-slate-50/70 cursor-pointer transition-colors ${
                      selectedIds.has(item.id) ? 'bg-indigo-50/40' : ''
                    } ${sla.urgency === 'breached' ? 'bg-red-50/30' : ''}`}
                    onClick={() => navigate(`/approvals/${item.id}`)}
                  >
                    <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                      <input
                        type="checkbox"
                        checked={selectedIds.has(item.id)}
                        onChange={() => toggleSelect(item.id)}
                        className="accent-indigo-600"
                      />
                    </td>
                    <td className="px-2 py-3">
                      <PriorityIcon size={15} className={priCfg.color} />
                    </td>
                    <td className="px-4 py-3">
                      <RichTooltip
                        trigger={
                          <div>
                            <div className="text-sm text-slate-800 truncate max-w-[260px]" style={{ fontWeight: 600 }}>
                              {item.title}
                            </div>
                            <div className="text-xs text-slate-400 mt-0.5">{item.id}</div>
                          </div>
                        }
                        side="right"
                        width="w-80"
                      >
                        <div className="space-y-2">
                          <div className="text-sm text-slate-800" style={{ fontWeight: 700 }}>{item.title}</div>
                          <TooltipBadge label="Requester" value={requester?.name ?? '—'} />
                          <TooltipBadge label="Priority" value={priCfg.label} color={priCfg.color} />
                          <TooltipBadge label="Value" value={item.value ? formatCurrency(item.value) : '—'} />
                          <TooltipBadge
                            label="Risk Level"
                            value={sla.urgency === 'breached' ? 'SLA Breached' : sla.urgency === 'critical' ? 'Critical' : 'Normal'}
                            color={sla.urgency === 'breached' ? 'text-red-600' : sla.urgency === 'critical' ? 'text-amber-600' : 'text-slate-600'}
                          />
                          {item.reasons.length > 0 && (
                            <div className="pt-2 border-t border-slate-100">
                              <div className="text-xs text-slate-500 mb-1" style={{ fontWeight: 600 }}>Gate Reasons</div>
                              <ul className="space-y-1">
                                {item.reasons.map((r, i) => (
                                  <li key={i} className="text-xs text-slate-600 flex items-start gap-1.5">
                                    <AlertTriangle size={10} className="text-amber-500 mt-0.5 flex-shrink-0" />
                                    {r}
                                  </li>
                                ))}
                              </ul>
                            </div>
                          )}
                        </div>
                      </RichTooltip>
                    </td>
                    <td className="px-3 py-3">
                      <span className={`text-xs px-2 py-0.5 rounded ${typeCfg.color}`} style={{ fontWeight: 600 }}>
                        {typeCfg.label}
                      </span>
                    </td>
                    <td className="px-3 py-3">
                      <span className="text-xs text-slate-600" style={{ fontWeight: 500 }}>{item.rfqId}</span>
                    </td>
                    <td className="px-3 py-3 text-right">
                      <span className="text-sm text-slate-800" style={{ fontWeight: 600 }}>
                        {item.value ? formatCurrency(item.value) : '—'}
                      </span>
                    </td>
                    <td className="px-3 py-3">
                      {assignee && (
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-xs text-indigo-700" style={{ fontWeight: 600 }}>
                            {assignee.initials}
                          </div>
                          <span className="text-xs text-slate-600" style={{ fontWeight: 500 }}>{assignee.name}</span>
                        </div>
                      )}
                    </td>
                    <td className="px-3 py-3 text-center">
                      <span className={`text-xs px-2 py-0.5 rounded ${statusColors[item.status] ?? 'bg-slate-100 text-slate-600'}`} style={{ fontWeight: 600 }}>
                        {item.status.replace(/_/g, ' ')}
                      </span>
                    </td>
                    <td className="px-3 py-3 text-center">
                      <div className={`inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded-full border ${sla.color}`} style={{ fontWeight: 600 }}>
                        <Clock size={11} />
                        {sla.label}
                      </div>
                    </td>
                    <td className="px-3 py-3 text-center" onClick={(e) => e.stopPropagation()}>
                      <div className="flex items-center justify-center gap-1">
                        <button
                          onClick={() => { setActiveItemId(item.id); setReassignUserId(''); setShowReassignModal(true); }}
                          className="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                          title="Reassign"
                        >
                          <Users size={14} />
                        </button>
                        <button
                          onClick={() => { setActiveItemId(item.id); setSnoozeDuration(4); setShowSnoozeModal(true); }}
                          className="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                          title="Snooze"
                        >
                          <Pause size={14} />
                        </button>
                        <button
                          onClick={() => { setActiveItemId(item.id); setEvidenceDescription(''); setEvidenceTargetUser(''); setShowEvidenceModal(true); }}
                          className="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                          title="Request Evidence"
                        >
                          <FileQuestion size={14} />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* ─── Reassign Modal ─── */}
      <SlideOver
        open={showReassignModal}
        onOpenChange={setShowReassignModal}
        title="Reassign Approval"
        description={activeItemId ? `Reassign ${activeItemId}` : `Reassign ${selectedIds.size} selected items`}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowReassignModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
              Cancel
            </button>
            <button
              disabled={!reassignUserId}
              className={`rounded-lg px-4 py-2 text-sm text-white ${reassignUserId ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-indigo-300 cursor-not-allowed'}`}
              style={{ fontWeight: 500 }}
            >
              Reassign
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 600 }}>Select Assignee</label>
          <div className="space-y-2">
            {eligibleApprovers.map((u) => (
              <label
                key={u.id}
                className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors ${
                  reassignUserId === u.id ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 hover:bg-slate-50'
                }`}
              >
                <input
                  type="radio"
                  name="reassignUser"
                  value={u.id}
                  checked={reassignUserId === u.id}
                  onChange={(e) => setReassignUserId(e.target.value)}
                  className="accent-indigo-600"
                />
                <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs text-indigo-700" style={{ fontWeight: 600 }}>
                  {u.initials}
                </div>
                <div className="flex-1">
                  <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{u.name}</div>
                  <div className="text-xs text-slate-500">{u.role} · Authority: {formatCurrency(u.authorityLimit)}</div>
                </div>
              </label>
            ))}
          </div>
        </div>
      </SlideOver>

      {/* ─── Bulk Approve Modal ─── */}
      <SlideOver
        open={showBulkApproveModal}
        onOpenChange={setShowBulkApproveModal}
        title="Confirm Bulk Approve"
        description={`Approve ${selectedIds.size} selected items`}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowBulkApproveModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
              Cancel
            </button>
            <button
              disabled={!bulkReason.trim()}
              className={`rounded-lg px-4 py-2 text-sm text-white ${bulkReason.trim() ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-emerald-300 cursor-not-allowed'}`}
              style={{ fontWeight: 500 }}
            >
              <span className="flex items-center gap-2">
                <CheckCircle size={14} />
                Approve {selectedIds.size} Items
              </span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-2">
              <CheckCircle size={14} className="text-emerald-600" />
              <span className="text-sm text-emerald-700" style={{ fontWeight: 700 }}>Bulk Approval Summary</span>
            </div>
            <div className="grid grid-cols-2 gap-3 mt-3">
              <div className="bg-white rounded-lg border border-emerald-200 p-3">
                <div className="text-xs text-emerald-600">Items</div>
                <div className="text-lg text-emerald-700" style={{ fontWeight: 700 }}>{selectedIds.size}</div>
              </div>
              <div className="bg-white rounded-lg border border-emerald-200 p-3">
                <div className="text-xs text-emerald-600">Total Value</div>
                <div className="text-lg text-emerald-700" style={{ fontWeight: 700 }}>
                  {formatCurrency(
                    approvalItems
                      .filter((i) => selectedIds.has(i.id))
                      .reduce((sum, i) => sum + i.value, 0)
                  )}
                </div>
              </div>
            </div>
          </div>

          <div className="space-y-2">
            {approvalItems
              .filter((i) => selectedIds.has(i.id))
              .map((item) => (
                <div key={item.id} className="flex items-center gap-3 p-2 bg-slate-50 rounded-lg border border-slate-100">
                  <CheckCircle size={13} className="text-emerald-500" />
                  <div className="flex-1 min-w-0">
                    <div className="text-xs text-slate-700 truncate" style={{ fontWeight: 600 }}>{item.title}</div>
                    <div className="text-xs text-slate-400">{item.id} · {item.value ? formatCurrency(item.value) : '—'}</div>
                  </div>
                </div>
              ))}
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 600 }}>
              Shared Reason <span className="text-red-500">*</span>
            </label>
            <textarea
              rows={3}
              value={bulkReason}
              onChange={(e) => setBulkReason(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-emerald-400 outline-none"
              placeholder="Provide a shared reason for approving all selected items..."
            />
          </div>
        </div>
      </SlideOver>

      {/* ─── Bulk Reject Modal ─── */}
      <SlideOver
        open={showBulkRejectModal}
        onOpenChange={setShowBulkRejectModal}
        title="Confirm Bulk Reject"
        description={`Reject ${selectedIds.size} selected items`}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowBulkRejectModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
              Cancel
            </button>
            <button
              disabled={!bulkReason.trim()}
              className={`rounded-lg px-4 py-2 text-sm text-white ${bulkReason.trim() ? 'bg-red-600 hover:bg-red-700' : 'bg-red-300 cursor-not-allowed'}`}
              style={{ fontWeight: 500 }}
            >
              <span className="flex items-center gap-2">
                <XCircle size={14} />
                Reject {selectedIds.size} Items
              </span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-red-50 border border-red-200 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-2">
              <XCircle size={14} className="text-red-600" />
              <span className="text-sm text-red-700" style={{ fontWeight: 700 }}>Bulk Rejection</span>
            </div>
            <p className="text-xs text-red-600">
              You are about to reject {selectedIds.size} approval items. Each will require re-evaluation.
            </p>
          </div>

          <div className="space-y-2">
            {approvalItems
              .filter((i) => selectedIds.has(i.id))
              .map((item) => (
                <div key={item.id} className="flex items-center gap-3 p-2 bg-red-50/50 rounded-lg border border-red-100">
                  <XCircle size={13} className="text-red-400" />
                  <div className="flex-1 min-w-0">
                    <div className="text-xs text-slate-700 truncate" style={{ fontWeight: 600 }}>{item.title}</div>
                    <div className="text-xs text-slate-400">{item.id}</div>
                  </div>
                </div>
              ))}
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 600 }}>
              Shared Reason <span className="text-red-500">*</span>
            </label>
            <textarea
              rows={3}
              value={bulkReason}
              onChange={(e) => setBulkReason(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-red-400 outline-none"
              placeholder="Provide a shared reason for rejecting all selected items..."
            />
          </div>
        </div>
      </SlideOver>

      {/* ─── Snooze Modal ─── */}
      <SlideOver
        open={showSnoozeModal}
        onOpenChange={setShowSnoozeModal}
        title="Snooze Approval"
        description={activeItem ? activeItem.title : 'Pause this item temporarily'}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowSnoozeModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
              Cancel
            </button>
            <button
              className="bg-amber-600 hover:bg-amber-700 text-white rounded-lg px-4 py-2 text-sm"
              style={{ fontWeight: 500 }}
            >
              <span className="flex items-center gap-2">
                <Pause size={14} />
                Snooze
              </span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <label className="text-slate-600 text-xs block" style={{ fontWeight: 600 }}>Snooze Duration</label>
          <div className="grid grid-cols-2 gap-2">
            {snoozeDurations.map((d) => (
              <button
                key={d.value}
                onClick={() => setSnoozeDuration(d.value)}
                className={`p-3 rounded-lg border text-sm text-center transition-colors ${
                  snoozeDuration === d.value
                    ? 'border-amber-400 bg-amber-50 text-amber-700'
                    : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                }`}
                style={{ fontWeight: snoozeDuration === d.value ? 600 : 400 }}
              >
                {d.label}
              </button>
            ))}
          </div>
          {snoozeDuration === -1 && (
            <div>
              <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 600 }}>Custom Duration (hours)</label>
              <input
                type="number"
                min={1}
                max={168}
                value={customSnoozeHours}
                onChange={(e) => setCustomSnoozeHours(parseInt(e.target.value) || 1)}
                className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-amber-400 outline-none"
              />
            </div>
          )}
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-3">
            <div className="flex items-center gap-2">
              <Clock size={13} className="text-amber-600" />
              <span className="text-xs text-amber-700" style={{ fontWeight: 500 }}>
                The SLA timer will pause during the snooze period. You will be notified when it expires.
              </span>
            </div>
          </div>
        </div>
      </SlideOver>

      {/* ─── Request Evidence Modal ─── */}
      <SlideOver
        open={showEvidenceModal}
        onOpenChange={setShowEvidenceModal}
        title="Request Evidence"
        description={activeItem ? activeItem.title : 'Request additional documentation'}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowEvidenceModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
              Cancel
            </button>
            <button
              disabled={!evidenceDescription.trim() || !evidenceTargetUser}
              className={`rounded-lg px-4 py-2 text-sm text-white ${
                evidenceDescription.trim() && evidenceTargetUser ? 'bg-blue-600 hover:bg-blue-700' : 'bg-blue-300 cursor-not-allowed'
              }`}
              style={{ fontWeight: 500 }}
            >
              <span className="flex items-center gap-2">
                <Send size={14} />
                Send Request
              </span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 600 }}>
              What evidence is needed? <span className="text-red-500">*</span>
            </label>
            <textarea
              rows={4}
              value={evidenceDescription}
              onChange={(e) => setEvidenceDescription(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-blue-400 outline-none"
              placeholder="Describe the documentation or evidence required..."
            />
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-2" style={{ fontWeight: 600 }}>
              Request From <span className="text-red-500">*</span>
            </label>
            <div className="space-y-2">
              {users.filter((u) => u.status === 'active').map((u) => (
                <label
                  key={u.id}
                  className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors ${
                    evidenceTargetUser === u.id ? 'border-blue-400 bg-blue-50' : 'border-slate-200 hover:bg-slate-50'
                  }`}
                >
                  <input
                    type="radio"
                    name="evidenceTarget"
                    value={u.id}
                    checked={evidenceTargetUser === u.id}
                    onChange={(e) => setEvidenceTargetUser(e.target.value)}
                    className="accent-blue-600"
                  />
                  <div className="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600" style={{ fontWeight: 600 }}>
                    {u.initials}
                  </div>
                  <div>
                    <div className="text-sm text-slate-800" style={{ fontWeight: 500 }}>{u.name}</div>
                    <div className="text-xs text-slate-400">{u.role}</div>
                  </div>
                </label>
              ))}
            </div>
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
