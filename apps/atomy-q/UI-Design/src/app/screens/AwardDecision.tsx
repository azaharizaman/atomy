import { useState } from 'react';
import {
  Trophy, ArrowLeft, ChevronRight, Building, Star, TrendingDown,
  CheckCircle, XCircle, AlertTriangle, Clock, Send, Shield,
  SplitSquareHorizontal, FileText, MessageSquare, Gavel, Timer,
  BadgePercent, Lock, Users, Eye, EyeOff, Sparkles, Upload
} from 'lucide-react';
import { Link } from 'react-router';
import { SlideOver } from '../components/SlideOver';
import {
  awardDecisions, vendorScores, vendors, rfqs,
  formatCurrency, statusColors, getUserById, getVendorById,
  comparisonMatrix, formatDate
} from '../data/mockData';

type AwardType = 'single' | 'split';

const debriefStatuses: Record<string, { label: string; color: string }> = {
  sent: { label: 'Debrief Sent', color: 'bg-emerald-100 text-emerald-700' },
  pending: { label: 'Pending', color: 'bg-amber-100 text-amber-700' },
  draft: { label: 'Draft', color: 'bg-slate-100 text-slate-600' },
  not_required: { label: 'Winner', color: 'bg-indigo-100 text-indigo-700' },
};

const signoffSteps = [
  { userId: 'usr-001', role: 'Procurement Lead', status: 'approved' as const, at: '2026-03-06 09:20' },
  { userId: 'usr-003', role: 'Approver', status: 'approved' as const, at: '2026-03-06 14:30' },
  { userId: 'usr-004', role: 'Procurement Manager', status: 'pending' as const, at: '' },
];

export function AwardDecision() {
  const [activeAward] = useState(awardDecisions[0]);
  const [showAwardConfirmModal, setShowAwardConfirmModal] = useState(false);
  const [showSplitModal, setShowSplitModal] = useState(false);
  const [showDebriefModal, setShowDebriefModal] = useState(false);
  const [showProtestModal, setShowProtestModal] = useState(false);
  const [debriefVendorId, setDebriefVendorId] = useState('');
  const [awardType, setAwardType] = useState<AwardType>('single');
  const [splitAllocations, setSplitAllocations] = useState<Record<string, number>>({
    'vnd-001': 60,
    'vnd-004': 40,
  });
  const [redactPricing, setRedactPricing] = useState(true);
  const [protestGrounds, setProtestGrounds] = useState('');
  const [protestVendorId, setProtestVendorId] = useState('');
  const [protestResolution, setProtestResolution] = useState('uphold');

  const rfq = rfqs.find(r => r.id === activeAward.rfqId);
  const winnerVendor = getVendorById(activeAward.winnerVendorId);
  const winnerScore = vendorScores.find(v => v.vendorId === activeAward.winnerVendorId);
  const runnerUp = vendorScores.filter(v => v.vendorId !== activeAward.winnerVendorId).sort((a, b) => b.overall - a.overall)[0];
  const runnerUpVendor = getVendorById(runnerUp?.vendorId || '');
  const allParticipants = vendorScores.map(vs => ({ ...vs, vendor: getVendorById(vs.vendorId) }));
  const losingVendors = allParticipants.filter(v => v.vendorId !== activeAward.winnerVendorId);

  const standstillActive = true;
  const standstillDaysRemaining = 7;
  const standstillEndDate = '16 Mar 2026';

  const budgetAmount = rfq?.estimatedValue || 0;
  const savingsVsBudget = budgetAmount - activeAward.totalValue;
  const savingsVsBudgetPct = budgetAmount > 0 ? ((savingsVsBudget / budgetAmount) * 100) : 0;

  const splitTotal = Object.entries(splitAllocations).reduce((sum, [vid, pct]) => {
    const vendorTotal = comparisonMatrix.vendorTotals[vid] || 0;
    return sum + (vendorTotal * pct / 100);
  }, 0);

  const handleOpenDebrief = (vendorId: string) => {
    setDebriefVendorId(vendorId);
    setShowDebriefModal(true);
  };

  const getDebriefStatus = (vendorId: string) => {
    if (vendorId === activeAward.winnerVendorId) return 'not_required';
    if (activeAward.debriefsSent.includes(vendorId)) return 'sent';
    return 'pending';
  };

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="hover:text-slate-700 cursor-pointer">Awards</span>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>{activeAward.id}</span>
        </div>

        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-3 mb-1">
              <h1 className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>Award Decision</h1>
              <span className={`text-xs px-2.5 py-1 rounded-full ${statusColors[activeAward.status] || statusColors.draft}`} style={{ fontWeight: 600 }}>
                {activeAward.status.replace('_', ' ')}
              </span>
              {standstillActive && (
                <span className="flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-orange-100 text-orange-700 border border-orange-200" style={{ fontWeight: 600 }}>
                  <Timer size={11} />
                  Standstill: {standstillDaysRemaining}d remaining
                </span>
              )}
            </div>
            <div className="flex items-center gap-4 text-xs text-slate-500 mt-1">
              <span>Award: <b className="text-slate-700">{activeAward.id}</b></span>
              <span className="text-slate-300">|</span>
              <span>RFQ: <b className="text-slate-700">{activeAward.rfqId}</b></span>
              <span className="text-slate-300">|</span>
              <span>{rfq?.title}</span>
              <span className="text-slate-300">|</span>
              <span className="flex items-center gap-1"><Clock size={11} /> Signed off {formatDate(activeAward.signedOffAt)}</span>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowAwardConfirmModal(true)}
              className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2.5 text-sm"
              style={{ fontWeight: 500 }}
            >
              <Lock size={14} />
              Finalize Award
            </button>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="flex-1 overflow-auto p-5 space-y-5">
        {/* Top Row: Winner + Savings */}
        <div className="grid grid-cols-3 gap-4">
          {/* Winner Card */}
          <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <div className="flex items-center gap-2 mb-3">
              <Trophy size={14} className="text-emerald-600" />
              <span className="text-xs text-emerald-600" style={{ fontWeight: 700 }}>RECOMMENDED WINNER</span>
            </div>
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                <Building size={18} className="text-emerald-700" />
              </div>
              <div>
                <div className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>{winnerVendor?.name}</div>
                <div className="text-emerald-600 text-xs">{winnerVendor?.country} · {winnerVendor?.category}</div>
              </div>
            </div>
            <div className="flex items-center gap-4">
              <div>
                <span className="text-xs text-slate-500">Score</span>
                <div className="flex items-center gap-1">
                  <span className="text-emerald-700 text-xl" style={{ fontWeight: 700 }}>{winnerScore?.overall}</span>
                  <span className="text-xs text-slate-400">/100</span>
                </div>
              </div>
              <div className="w-px h-10 bg-emerald-200" />
              <div>
                <span className="text-xs text-slate-500">Total Value</span>
                <div className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{formatCurrency(activeAward.totalValue)}</div>
              </div>
              <div className="w-px h-10 bg-emerald-200" />
              <div>
                <span className="text-xs text-slate-500">Savings</span>
                <div className="flex items-center gap-1.5">
                  <span className="text-xs bg-emerald-600 text-white px-2 py-0.5 rounded" style={{ fontWeight: 700 }}>
                    {activeAward.savingsPercent}%
                  </span>
                </div>
              </div>
            </div>
          </div>

          {/* Runner-Up Comparison */}
          <div className="rounded-xl border border-slate-200 bg-white p-5">
            <div className="flex items-center gap-2 mb-3">
              <Star size={14} className="text-slate-500" />
              <span className="text-xs text-slate-500" style={{ fontWeight: 700 }}>RUNNER-UP</span>
            </div>
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                <Building size={18} className="text-slate-600" />
              </div>
              <div>
                <div className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>{runnerUpVendor?.name}</div>
                <div className="text-slate-500 text-xs">{runnerUpVendor?.country} · {runnerUpVendor?.category}</div>
              </div>
            </div>
            <div className="flex items-center gap-4">
              <div>
                <span className="text-xs text-slate-500">Score</span>
                <div className="text-slate-700 text-xl" style={{ fontWeight: 700 }}>{runnerUp?.overall}<span className="text-xs text-slate-400">/100</span></div>
              </div>
              <div className="w-px h-10 bg-slate-200" />
              <div>
                <span className="text-xs text-slate-500">Total</span>
                <div className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>
                  {formatCurrency(comparisonMatrix.vendorTotals[runnerUp?.vendorId || ''] || 0)}
                </div>
              </div>
              <div className="w-px h-10 bg-slate-200" />
              <div>
                <span className="text-xs text-slate-500">Delta</span>
                <div className="text-red-600 text-sm" style={{ fontWeight: 600 }}>
                  -{(winnerScore?.overall || 0) - (runnerUp?.overall || 0)} pts
                </div>
              </div>
            </div>
          </div>

          {/* Savings Impact */}
          <div className="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5">
            <div className="flex items-center gap-2 mb-3">
              <TrendingDown size={14} className="text-emerald-600" />
              <span className="text-xs text-emerald-600" style={{ fontWeight: 700 }}>SAVINGS IMPACT</span>
            </div>
            <div className="flex items-center gap-2 mb-4">
              <span className="text-emerald-700 text-2xl" style={{ fontWeight: 700 }}>{formatCurrency(activeAward.savings)}</span>
              <span className="text-xs bg-emerald-600 text-white px-2.5 py-1 rounded-full" style={{ fontWeight: 700 }}>
                <BadgePercent size={11} className="inline mr-0.5" />
                {activeAward.savingsPercent}%
              </span>
            </div>
            <div className="space-y-2">
              <div className="flex items-center justify-between text-xs">
                <span className="text-slate-500">Budget estimate</span>
                <span className="text-slate-700" style={{ fontWeight: 600 }}>{formatCurrency(budgetAmount)}</span>
              </div>
              <div className="flex items-center justify-between text-xs">
                <span className="text-slate-500">Award value</span>
                <span className="text-slate-700" style={{ fontWeight: 600 }}>{formatCurrency(activeAward.totalValue)}</span>
              </div>
              <div className="h-px bg-emerald-200" />
              <div className="flex items-center justify-between text-xs">
                <span className="text-emerald-600" style={{ fontWeight: 600 }}>Savings vs budget</span>
                <span className="text-emerald-700" style={{ fontWeight: 700 }}>{formatCurrency(savingsVsBudget)} ({savingsVsBudgetPct.toFixed(1)}%)</span>
              </div>
            </div>
          </div>
        </div>

        {/* Award Type + Signoff Row */}
        <div className="grid grid-cols-2 gap-4">
          {/* Award Type Toggle */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-2">
                <SplitSquareHorizontal size={14} className="text-slate-600" />
                <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Award Type</span>
              </div>
              <div className="flex items-center bg-slate-100 rounded-lg p-0.5">
                <button
                  onClick={() => setAwardType('single')}
                  className={`px-3 py-1.5 rounded-md text-xs transition-colors ${awardType === 'single' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}
                  style={{ fontWeight: awardType === 'single' ? 600 : 400 }}
                >
                  Single
                </button>
                <button
                  onClick={() => { setAwardType('split'); setShowSplitModal(true); }}
                  className={`px-3 py-1.5 rounded-md text-xs transition-colors ${awardType === 'split' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}
                  style={{ fontWeight: awardType === 'split' ? 600 : 400 }}
                >
                  Split
                </button>
              </div>
            </div>
            {awardType === 'single' ? (
              <div className="flex items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                <CheckCircle size={14} className="text-emerald-600 flex-shrink-0" />
                <div>
                  <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{winnerVendor?.name}</div>
                  <div className="text-xs text-emerald-600">100% of award · {formatCurrency(activeAward.totalValue)}</div>
                </div>
                <span className="ml-auto text-xs bg-emerald-600 text-white px-2 py-0.5 rounded" style={{ fontWeight: 600 }}>
                  Score: {winnerScore?.overall}
                </span>
              </div>
            ) : (
              <div className="space-y-2">
                {Object.entries(splitAllocations).map(([vid, pct]) => {
                  const v = getVendorById(vid);
                  const total = (comparisonMatrix.vendorTotals[vid] || 0) * pct / 100;
                  return (
                    <div key={vid} className="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-lg p-3">
                      <Building size={14} className="text-slate-500 flex-shrink-0" />
                      <div className="flex-1 min-w-0">
                        <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{v?.name}</div>
                        <div className="text-xs text-slate-500">{pct}% allocation · {formatCurrency(total)}</div>
                      </div>
                      <button
                        onClick={() => setShowSplitModal(true)}
                        className="text-xs text-indigo-600 hover:text-indigo-700"
                        style={{ fontWeight: 500 }}
                      >
                        Configure
                      </button>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          {/* Sign-off Status */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div className="flex items-center gap-2 mb-4">
              <Users size={14} className="text-slate-600" />
              <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Sign-off Status</span>
              <span className="ml-auto text-xs text-slate-400">
                {signoffSteps.filter(s => s.status === 'approved').length}/{signoffSteps.length} complete
              </span>
            </div>
            <div className="space-y-3">
              {signoffSteps.map((step, i) => {
                const user = getUserById(step.userId);
                return (
                  <div key={i} className="flex items-center gap-3">
                    <div className={`w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 ${
                      step.status === 'approved' ? 'bg-emerald-100' : 'bg-slate-100'
                    }`}>
                      {step.status === 'approved' ? (
                        <CheckCircle size={14} className="text-emerald-600" />
                      ) : (
                        <Clock size={14} className="text-slate-400" />
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="text-sm text-slate-700" style={{ fontWeight: 500 }}>{user?.name}</div>
                      <div className="text-xs text-slate-400">{step.role}</div>
                    </div>
                    {step.status === 'approved' ? (
                      <span className="text-xs text-emerald-600" style={{ fontWeight: 500 }}>{step.at}</span>
                    ) : (
                      <span className="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded" style={{ fontWeight: 600 }}>Awaiting</span>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* Standstill / Protest Panel */}
        {standstillActive && (
          <div className="bg-orange-50 rounded-xl border border-orange-200 p-5">
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <Timer size={14} className="text-orange-600" />
                <span className="text-orange-700 text-sm" style={{ fontWeight: 700 }}>Standstill Period Active</span>
              </div>
              <span className="text-xs text-orange-600">Ends {standstillEndDate}</span>
            </div>
            <div className="flex items-center gap-6">
              <div className="flex items-center gap-3">
                <div className="text-orange-700 text-2xl" style={{ fontWeight: 700 }}>{standstillDaysRemaining}</div>
                <div className="text-xs text-orange-600 leading-tight">days<br />remaining</div>
              </div>
              <div className="w-px h-10 bg-orange-200" />
              <div className="flex-1">
                <div className="w-full bg-orange-200 rounded-full h-2 mb-1">
                  <div className="bg-orange-500 h-2 rounded-full" style={{ width: `${((10 - standstillDaysRemaining) / 10) * 100}%` }} />
                </div>
                <div className="flex justify-between text-xs text-orange-500">
                  <span>Day {10 - standstillDaysRemaining} of 10</span>
                  <span>Award can finalize after standstill</span>
                </div>
              </div>
              <div className="w-px h-10 bg-orange-200" />
              <button
                onClick={() => setShowProtestModal(true)}
                className="flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg px-4 py-2 text-sm"
                style={{ fontWeight: 500 }}
              >
                <Gavel size={14} />
                Record Protest
              </button>
            </div>
          </div>
        )}

        {/* Vendor Debrief Controls */}
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
            <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
              <MessageSquare size={14} className="text-slate-600" />
            </div>
            <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Vendor Debriefs</span>
            <span className="ml-auto text-xs text-slate-400">
              {activeAward.debriefsSent.length} sent · {losingVendors.length - activeAward.debriefsSent.length} pending
            </span>
          </div>
          <table className="w-full">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50">
                <th className="text-left px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>VENDOR</th>
                <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>SCORE</th>
                <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>TOTAL</th>
                <th className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>STATUS</th>
                <th className="text-right px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>ACTION</th>
              </tr>
            </thead>
            <tbody>
              {allParticipants.sort((a, b) => b.overall - a.overall).map((p) => {
                const status = getDebriefStatus(p.vendorId);
                const cfg = debriefStatuses[status];
                return (
                  <tr key={p.vendorId} className={`border-b border-slate-50 hover:bg-slate-50 ${p.vendorId === activeAward.winnerVendorId ? 'bg-emerald-50/30' : ''}`}>
                    <td className="px-5 py-3">
                      <div className="flex items-center gap-2">
                        <span className="text-sm text-slate-800" style={{ fontWeight: 500 }}>{p.vendor?.name}</span>
                        {p.vendorId === activeAward.winnerVendorId && (
                          <Trophy size={12} className="text-emerald-600" />
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <span className={`text-xs px-2 py-0.5 rounded ${p.recommended ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`} style={{ fontWeight: 600 }}>
                        {p.overall}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-center text-sm text-slate-700" style={{ fontWeight: 500 }}>
                      {formatCurrency(comparisonMatrix.vendorTotals[p.vendorId] || 0)}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <span className={`text-xs px-2 py-0.5 rounded ${cfg.color}`} style={{ fontWeight: 600 }}>{cfg.label}</span>
                    </td>
                    <td className="px-5 py-3 text-right">
                      {status === 'pending' && (
                        <button
                          onClick={() => handleOpenDebrief(p.vendorId)}
                          className="flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-700 ml-auto"
                          style={{ fontWeight: 500 }}
                        >
                          <Send size={11} />
                          Send Debrief
                        </button>
                      )}
                      {status === 'sent' && (
                        <button
                          onClick={() => handleOpenDebrief(p.vendorId)}
                          className="flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-700 ml-auto"
                          style={{ fontWeight: 500 }}
                        >
                          <Eye size={11} />
                          View
                        </button>
                      )}
                      {status === 'not_required' && (
                        <span className="text-xs text-slate-400">—</span>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* Bottom Action Bar */}
      <div className="bg-white border-t border-slate-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <Link to="/" className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
          <ArrowLeft size={14} />
          Back
        </Link>
        <div className="flex items-center gap-3">
          <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
            <FileText size={14} />
            Export Report
          </button>
          <button
            onClick={() => setShowAwardConfirmModal(true)}
            className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2.5 text-sm"
            style={{ fontWeight: 500 }}
          >
            <Shield size={14} />
            Confirm Award
          </button>
        </div>
      </div>

      {/* ── Award Confirmation SlideOver ── */}
      <SlideOver
        open={showAwardConfirmModal}
        onOpenChange={setShowAwardConfirmModal}
        title="Award Confirmation"
        description="Review and confirm the award decision"
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowAwardConfirmModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
              Cancel
            </button>
            <button className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><CheckCircle size={14} /> Confirm Award</span>
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-2">
              <Trophy size={14} className="text-emerald-600" />
              <span className="text-sm text-emerald-700" style={{ fontWeight: 700 }}>Awarding to {winnerVendor?.name}</span>
            </div>
            <div className="grid grid-cols-3 gap-3 mt-3">
              <div>
                <div className="text-xs text-slate-500">Score</div>
                <div className="text-emerald-700" style={{ fontWeight: 700 }}>{winnerScore?.overall}/100</div>
              </div>
              <div>
                <div className="text-xs text-slate-500">Total Value</div>
                <div className="text-slate-800" style={{ fontWeight: 600 }}>{formatCurrency(activeAward.totalValue)}</div>
              </div>
              <div>
                <div className="text-xs text-slate-500">Savings</div>
                <div className="text-emerald-600" style={{ fontWeight: 600 }}>{formatCurrency(activeAward.savings)} ({activeAward.savingsPercent}%)</div>
              </div>
            </div>
          </div>

          {standstillActive && (
            <div className="bg-orange-50 border border-orange-200 rounded-xl p-4">
              <div className="flex items-center gap-2 mb-2">
                <Timer size={14} className="text-orange-600" />
                <span className="text-sm text-orange-700" style={{ fontWeight: 600 }}>Standstill Period</span>
              </div>
              <p className="text-xs text-orange-600 leading-relaxed">
                A mandatory 10-day standstill period applies. The award will not be formally issued until <b>{standstillEndDate}</b>.
                Non-winning vendors will be notified and may lodge a protest during this period.
              </p>
            </div>
          )}

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 500 }}>Award Notes</label>
            <textarea
              rows={3}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-emerald-400 outline-none"
              placeholder="Add any notes for the award record..."
            />
          </div>

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 500 }}>Sign-off Chain</label>
            <div className="space-y-2">
              {signoffSteps.map((step, i) => {
                const user = getUserById(step.userId);
                return (
                  <div key={i} className="flex items-center gap-2 text-xs">
                    {step.status === 'approved' ? (
                      <CheckCircle size={12} className="text-emerald-500" />
                    ) : (
                      <Clock size={12} className="text-slate-400" />
                    )}
                    <span className="text-slate-700" style={{ fontWeight: 500 }}>{user?.name}</span>
                    <span className="text-slate-400">({step.role})</span>
                    {step.at && <span className="ml-auto text-slate-400">{step.at}</span>}
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      </SlideOver>

      {/* ── Configure Split Award SlideOver ── */}
      <SlideOver
        open={showSplitModal}
        onOpenChange={setShowSplitModal}
        title="Configure Split Award"
        description="Allocate percentages and line items to multiple vendors"
        width="lg"
        footer={
          <div className="flex items-center justify-between">
            <div className="text-xs text-slate-500">
              Split total: <b className="text-slate-800">{formatCurrency(splitTotal)}</b>
            </div>
            <div className="flex items-center gap-3">
              <button onClick={() => setShowSplitModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply Split</button>
            </div>
          </div>
        }
      >
        <div className="space-y-5">
          {Object.entries(splitAllocations).map(([vid, pct]) => {
            const v = getVendorById(vid);
            const vs = vendorScores.find(s => s.vendorId === vid);
            const vendorTotal = comparisonMatrix.vendorTotals[vid] || 0;
            const allocatedValue = vendorTotal * pct / 100;
            return (
              <div key={vid} className="border border-slate-200 rounded-xl p-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-2">
                    <Building size={14} className="text-slate-600" />
                    <span className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{v?.name}</span>
                    <span className="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded">Score: {vs?.overall}</span>
                  </div>
                  <span className="text-sm text-indigo-600" style={{ fontWeight: 700 }}>{pct}%</span>
                </div>
                <div className="mb-3">
                  <input
                    type="range"
                    min={0}
                    max={100}
                    value={pct}
                    onChange={(e) => {
                      const newPct = Number(e.target.value);
                      const otherVid = Object.keys(splitAllocations).find(k => k !== vid) || '';
                      setSplitAllocations({ ...splitAllocations, [vid]: newPct, [otherVid]: 100 - newPct });
                    }}
                    className="w-full accent-indigo-600"
                  />
                  <div className="flex items-center justify-between text-xs text-slate-400 mt-1">
                    <span>0%</span>
                    <span className="text-slate-700" style={{ fontWeight: 500 }}>Allocated: {formatCurrency(allocatedValue)}</span>
                    <span>100%</span>
                  </div>
                </div>
                <div className="text-xs text-slate-500">
                  <span style={{ fontWeight: 500 }}>Suggested line items:</span>{' '}
                  {vid === 'vnd-001' ? 'Servers, Memory, Storage' : 'Networking, Services, Power, Accessories'}
                </div>
              </div>
            );
          })}

          <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-1">
              <TrendingDown size={14} className="text-emerald-600" />
              <span className="text-xs text-emerald-700" style={{ fontWeight: 600 }}>Real-time Savings Update</span>
            </div>
            <div className="flex items-center gap-4">
              <div>
                <div className="text-xs text-slate-500">Split Total</div>
                <div className="text-slate-800" style={{ fontWeight: 700 }}>{formatCurrency(splitTotal)}</div>
              </div>
              <div>
                <div className="text-xs text-slate-500">Budget</div>
                <div className="text-slate-700" style={{ fontWeight: 500 }}>{formatCurrency(budgetAmount)}</div>
              </div>
              <div>
                <div className="text-xs text-slate-500">Savings</div>
                <div className="text-emerald-600" style={{ fontWeight: 700 }}>
                  {formatCurrency(budgetAmount - splitTotal)} ({budgetAmount > 0 ? ((budgetAmount - splitTotal) / budgetAmount * 100).toFixed(1) : 0}%)
                </div>
              </div>
            </div>
          </div>
        </div>
      </SlideOver>

      {/* ── Vendor Debrief SlideOver ── */}
      <SlideOver
        open={showDebriefModal}
        onOpenChange={setShowDebriefModal}
        title="Vendor Debrief"
        description={`Debrief for ${getVendorById(debriefVendorId)?.name || 'vendor'}`}
        width="lg"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowDebriefModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><Send size={14} /> Send Debrief</span>
            </button>
          </div>
        }
      >
        {(() => {
          const dv = getVendorById(debriefVendorId);
          const dvScore = vendorScores.find(v => v.vendorId === debriefVendorId);
          return (
            <div className="space-y-5">
              <div className="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl p-4">
                <Building size={16} className="text-slate-600" />
                <div>
                  <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{dv?.name}</div>
                  <div className="text-xs text-slate-500">{dv?.contactEmail}</div>
                </div>
                <span className="ml-auto text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded" style={{ fontWeight: 600 }}>
                  Score: {dvScore?.overall}/100
                </span>
              </div>

              <div>
                <div className="flex items-center justify-between mb-2">
                  <label className="text-xs text-slate-600" style={{ fontWeight: 500 }}>Debrief Summary</label>
                  <button className="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-700" style={{ fontWeight: 500 }}>
                    <Sparkles size={11} />
                    Auto-generate
                  </button>
                </div>
                <textarea
                  rows={6}
                  className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none"
                  defaultValue={`Dear ${dv?.name},\n\nThank you for your participation in ${rfq?.title} (${rfq?.id}). After careful evaluation of all submissions, we regret to inform you that your bid was not selected.\n\nYour submission scored ${dvScore?.overall}/100 in our weighted evaluation. Key areas for improvement include delivery timelines and quality certifications.\n\nWe value your partnership and encourage future participation.`}
                />
              </div>

              <div className="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-xl p-3">
                <div className="flex items-center gap-2">
                  {redactPricing ? <EyeOff size={14} className="text-amber-600" /> : <Eye size={14} className="text-amber-600" />}
                  <div>
                    <div className="text-xs text-amber-700" style={{ fontWeight: 600 }}>Redact Pricing Information</div>
                    <div className="text-xs text-amber-600">Hide competitor pricing and total values from debrief</div>
                  </div>
                </div>
                <button
                  onClick={() => setRedactPricing(!redactPricing)}
                  className={`w-10 h-5 rounded-full transition-colors relative ${redactPricing ? 'bg-amber-500' : 'bg-slate-300'}`}
                >
                  <span className={`absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform ${redactPricing ? 'left-5' : 'left-0.5'}`} />
                </button>
              </div>

              <div>
                <label className="text-xs text-slate-600 block mb-2" style={{ fontWeight: 500 }}>Score Breakdown (included in debrief)</label>
                <div className="grid grid-cols-5 gap-2">
                  {['price', 'delivery', 'quality', 'risk', 'sustainability'].map((dim) => {
                    const val = dvScore?.[dim as keyof typeof dvScore] as number || 0;
                    return (
                      <div key={dim} className="bg-slate-50 rounded-lg p-2 text-center">
                        <div className="text-xs text-slate-500 capitalize mb-1">{dim}</div>
                        <div className={`text-sm ${val >= 80 ? 'text-emerald-700' : val >= 70 ? 'text-amber-700' : 'text-red-700'}`} style={{ fontWeight: 700 }}>{val}</div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          );
        })()}
      </SlideOver>

      {/* ── Record Protest SlideOver ── */}
      <SlideOver
        open={showProtestModal}
        onOpenChange={setShowProtestModal}
        title="Record Protest"
        description="Document a vendor's challenge to the award decision"
        width="lg"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowProtestModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button
              disabled={!protestVendorId || !protestGrounds.trim()}
              className={`rounded-lg px-4 py-2 text-sm text-white ${protestVendorId && protestGrounds.trim() ? 'bg-orange-600 hover:bg-orange-700' : 'bg-orange-300 cursor-not-allowed'}`}
              style={{ fontWeight: 500 }}
            >
              <span className="flex items-center gap-2"><Gavel size={14} /> Record Protest</span>
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="bg-orange-50 border border-orange-200 rounded-xl p-3">
            <div className="flex items-center gap-2">
              <AlertTriangle size={14} className="text-orange-600" />
              <span className="text-xs text-orange-700 leading-relaxed">
                Recording a protest will pause the award finalization process until a resolution is determined.
              </span>
            </div>
          </div>

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 500 }}>Protesting Vendor <span className="text-red-500">*</span></label>
            <select
              value={protestVendorId}
              onChange={(e) => setProtestVendorId(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white focus:border-orange-400 outline-none"
            >
              <option value="">Select vendor...</option>
              {losingVendors.map(v => (
                <option key={v.vendorId} value={v.vendorId}>{v.vendor?.name}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 500 }}>Grounds for Protest <span className="text-red-500">*</span></label>
            <textarea
              rows={4}
              value={protestGrounds}
              onChange={(e) => setProtestGrounds(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-orange-400 outline-none"
              placeholder="Describe the vendor's grounds for protesting the award decision..."
            />
          </div>

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 500 }}>Supporting Documents</label>
            <div className="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-slate-300 transition-colors cursor-pointer">
              <Upload size={20} className="text-slate-400 mx-auto mb-2" />
              <div className="text-sm text-slate-600" style={{ fontWeight: 500 }}>Drop files here or click to upload</div>
              <div className="text-xs text-slate-400 mt-1">PDF, DOCX, or images up to 25 MB</div>
            </div>
          </div>

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 500 }}>Resolution <span className="text-red-500">*</span></label>
            <div className="space-y-2">
              {[
                { value: 'uphold', label: 'Uphold Award', desc: 'Dismiss the protest and proceed with award' },
                { value: 're-evaluate', label: 'Re-evaluate', desc: 'Revisit scoring and re-run comparison' },
                { value: 'cancel', label: 'Cancel & Re-tender', desc: 'Cancel the award and issue a new RFQ' },
              ].map(opt => (
                <label
                  key={opt.value}
                  className={`flex items-start gap-3 border rounded-xl p-3 cursor-pointer transition-colors ${
                    protestResolution === opt.value ? 'border-orange-300 bg-orange-50' : 'border-slate-200 hover:bg-slate-50'
                  }`}
                >
                  <input
                    type="radio"
                    name="resolution"
                    value={opt.value}
                    checked={protestResolution === opt.value}
                    onChange={(e) => setProtestResolution(e.target.value)}
                    className="mt-0.5 accent-orange-600"
                  />
                  <div>
                    <div className="text-sm text-slate-800" style={{ fontWeight: 500 }}>{opt.label}</div>
                    <div className="text-xs text-slate-500">{opt.desc}</div>
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
