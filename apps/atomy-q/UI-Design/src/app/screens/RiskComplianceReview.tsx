import { useState } from 'react';
import {
  ShieldAlert, AlertTriangle, AlertOctagon, CheckCircle, Info,
  ChevronRight, X, FileText, ArrowUp, Users, DollarSign, Globe, Building
} from 'lucide-react';

type RiskLevel = 'critical' | 'high' | 'medium' | 'low';

interface RiskItem {
  id: string;
  category: string;
  title: string;
  description: string;
  level: RiskLevel;
  vendor?: string;
  escalated?: boolean;
  policyException?: boolean;
}

const risks: RiskItem[] = [
  { id: 'r1', category: 'Vendor Risk', title: 'Single Source Dependency', level: 'critical', vendor: 'PrimeSource Co.', description: '4 of 13 critical line items are sole-sourced to one vendor with no alternative. Business continuity risk is high if supply disruption occurs.', escalated: false },
  { id: 'r2', category: 'Vendor Risk', title: 'Unverified Financial Stability', level: 'high', vendor: 'GlobalSupply Inc.', description: 'Credit rating below procurement policy threshold (B- vs required BB). Recent news includes board restructure.', escalated: false },
  { id: 'r3', category: 'Compliance', title: 'Missing Conflict of Interest Declaration', level: 'high', vendor: 'TechCorp Solutions', description: 'Vendor has a known relationship with a board member. COI declaration not filed for this RFQ.', policyException: false },
  { id: 'r4', category: 'Compliance', title: 'Expired Quality Certification', level: 'medium', vendor: 'FastParts Ltd.', description: 'ISO 9001 certificate expired 3 months ago. Vendor claims renewal is in progress.', policyException: false },
  { id: 'r5', category: 'Financial', title: 'FX Exposure — GBP Pricing', level: 'medium', vendor: 'FastParts Ltd.', description: 'Quote submitted in GBP. AUD/GBP rate movement of ±5% over award period could affect budget by $9,800.', escalated: false },
  { id: 'r6', category: 'Financial', title: 'Budget Overage Risk', level: 'low', description: 'Accepted quotes exceed approved budget by 3.2% ($6,100). Requires budget owner approval if exceeded.', escalated: false },
  { id: 'r7', category: 'Geopolitical', title: 'Import Duty Changes', level: 'low', vendor: 'GlobalSupply Inc.', description: 'Pending tariff changes on semiconductor components from Q3 2026 could affect final pricing by up to 8%.', escalated: false },
];

const escalations = [
  { id: 'e1', riskId: 'r3', title: 'COI Declaration — TechCorp', escalatedTo: 'Chief Compliance Officer', date: '2d ago', status: 'Pending' },
];

const levelConfig: Record<RiskLevel, { bg: string; border: string; text: string; badgeBg: string; badgeText: string; icon: typeof AlertOctagon }> = {
  critical: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', badgeBg: 'bg-red-600', badgeText: 'text-white', icon: AlertOctagon },
  high: { bg: 'bg-orange-50', border: 'border-orange-200', text: 'text-orange-700', badgeBg: 'bg-orange-500', badgeText: 'text-white', icon: AlertTriangle },
  medium: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', badgeBg: 'bg-amber-400', badgeText: 'text-white', icon: Info },
  low: { bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-slate-600', badgeBg: 'bg-slate-300', badgeText: 'text-slate-700', icon: Info },
};

const categoryIcons: Record<string, typeof ShieldAlert> = {
  'Vendor Risk': Building,
  'Compliance': FileText,
  'Financial': DollarSign,
  'Geopolitical': Globe,
};

export function RiskComplianceReview() {
  const [selectedRisk, setSelectedRisk] = useState<RiskItem | null>(risks[0]);
  const [escalateModal, setEscalateModal] = useState<RiskItem | null>(null);
  const [exceptionModal, setExceptionModal] = useState<RiskItem | null>(null);

  const categories = Array.from(new Set(risks.map(r => r.category)));
  const criticalCount = risks.filter(r => r.level === 'critical').length;
  const highCount = risks.filter(r => r.level === 'high').length;

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-slate-900">Risk & Compliance Review</h1>
            <p className="text-slate-500 text-xs mt-0.5">RFQ-2401 · Server Infrastructure Components</p>
          </div>
          <div className="flex items-center gap-3">
            <div className={`flex items-center gap-2 px-3 py-2 rounded-xl border ${criticalCount > 0 ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-slate-200'}`}>
              <AlertOctagon size={14} className={criticalCount > 0 ? 'text-red-600' : 'text-slate-400'} />
              <span className={`text-sm ${criticalCount > 0 ? 'text-red-700' : 'text-slate-500'}`} style={{ fontWeight: 700 }}>{criticalCount} Critical</span>
            </div>
            <div className="flex items-center gap-2 px-3 py-2 rounded-xl border bg-orange-50 border-orange-200">
              <AlertTriangle size={14} className="text-orange-600" />
              <span className="text-orange-700 text-sm" style={{ fontWeight: 700 }}>{highCount} High</span>
            </div>
            <button className="flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
              <FileText size={14} />
              Export Risk Report
            </button>
          </div>
        </div>

        {/* Risk Severity Ladder */}
        <div className="flex items-center gap-2 mt-4 px-4 py-3 bg-slate-50 rounded-xl border border-slate-200">
          <span className="text-slate-500 text-xs mr-2" style={{ fontWeight: 500 }}>RISK LADDER</span>
          {(['critical', 'high', 'medium', 'low'] as RiskLevel[]).map(level => {
            const count = risks.filter(r => r.level === level).length;
            const cfg = levelConfig[level];
            const Icon = cfg.icon;
            return (
              <div key={level} className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full border ${cfg.bg} ${cfg.border}`}>
                <Icon size={12} className={cfg.text} />
                <span className={`text-xs capitalize ${cfg.text}`} style={{ fontWeight: 600 }}>{level}</span>
                <span className={`text-xs px-1.5 py-0.5 rounded-full ${cfg.badgeBg} ${cfg.badgeText}`} style={{ fontWeight: 700 }}>{count}</span>
              </div>
            );
          })}
          <div className="ml-auto flex items-center gap-1 text-xs text-slate-500">
            <Users size={12} />
            <span>{escalations.length} escalation{escalations.length !== 1 ? 's' : ''} active</span>
          </div>
        </div>
      </div>

      <div className="flex-1 flex overflow-hidden">
        {/* Main Risk Panels */}
        <div className="flex-1 overflow-auto p-5 space-y-5">
          {categories.map(cat => {
            const catRisks = risks.filter(r => r.category === cat);
            const Icon = categoryIcons[cat] || ShieldAlert;
            return (
              <div key={cat} className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
                  <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
                    <Icon size={14} className="text-slate-600" />
                  </div>
                  <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>{cat}</span>
                  <div className="flex items-center gap-1.5 ml-1">
                    {catRisks.map(r => {
                      const cfg = levelConfig[r.level];
                      return (
                        <span key={r.id} className={`w-2 h-2 rounded-full ${cfg.badgeBg}`} title={r.level} />
                      );
                    })}
                  </div>
                  <span className="ml-auto text-slate-400 text-xs">{catRisks.length} item{catRisks.length !== 1 ? 's' : ''}</span>
                </div>
                <div className="divide-y divide-slate-100">
                  {catRisks.map(risk => {
                    const cfg = levelConfig[risk.level];
                    const Icon2 = cfg.icon;
                    return (
                      <div
                        key={risk.id}
                        onClick={() => setSelectedRisk(risk)}
                        className={`flex items-start gap-4 px-5 py-4 cursor-pointer transition-colors hover:bg-slate-50 ${selectedRisk?.id === risk.id ? 'bg-indigo-50/60' : ''}`}
                      >
                        <div className={`w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${cfg.bg} border ${cfg.border}`}>
                          <Icon2 size={15} className={cfg.text} />
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 flex-wrap mb-1">
                            <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{risk.title}</span>
                            <span className={`text-xs px-2 py-0.5 rounded-full border capitalize ${cfg.bg} ${cfg.border} ${cfg.text}`} style={{ fontWeight: 600 }}>{risk.level}</span>
                            {risk.vendor && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{risk.vendor}</span>}
                            {risk.escalated && <span className="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded" style={{ fontWeight: 500 }}>Escalated</span>}
                          </div>
                          <p className="text-slate-500 text-xs leading-relaxed">{risk.description}</p>
                        </div>
                        <div className="flex items-center gap-2 flex-shrink-0">
                          {(risk.level === 'critical' || risk.level === 'high') && (
                            <button
                              onClick={e => { e.stopPropagation(); setEscalateModal(risk); }}
                              className="text-xs border border-orange-300 bg-orange-50 text-orange-700 rounded-lg px-2.5 py-1 hover:bg-orange-100"
                              style={{ fontWeight: 500 }}
                            >
                              <ArrowUp size={11} className="inline mr-0.5" />
                              Escalate
                            </button>
                          )}
                          {risk.level === 'medium' && (
                            <button
                              onClick={e => { e.stopPropagation(); setExceptionModal(risk); }}
                              className="text-xs border border-slate-200 bg-white text-slate-600 rounded-lg px-2.5 py-1 hover:bg-slate-50"
                              style={{ fontWeight: 500 }}
                            >
                              Request Exception
                            </button>
                          )}
                          <ChevronRight size={14} className="text-slate-300" />
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            );
          })}
        </div>

        {/* Escalation Rail */}
        <div className="w-72 flex-shrink-0 border-l border-slate-200 bg-white flex flex-col overflow-hidden">
          <div className="px-4 py-3 border-b border-slate-100 flex-shrink-0">
            <h3 className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>Escalation Rail</h3>
            <p className="text-slate-500 text-xs mt-0.5">{escalations.length} active escalation{escalations.length !== 1 ? 's' : ''}</p>
          </div>

          <div className="flex-1 overflow-y-auto p-3 space-y-3">
            {escalations.map(esc => (
              <div key={esc.id} className="rounded-xl border border-orange-200 bg-orange-50 p-3">
                <div className="flex items-center gap-1.5 mb-1.5">
                  <ArrowUp size={12} className="text-orange-600" />
                  <span className="text-orange-700 text-xs" style={{ fontWeight: 600 }}>{esc.title}</span>
                </div>
                <div className="space-y-1">
                  <div className="flex justify-between text-xs">
                    <span className="text-slate-500">Escalated to</span>
                    <span className="text-slate-700" style={{ fontWeight: 500 }}>{esc.escalatedTo}</span>
                  </div>
                  <div className="flex justify-between text-xs">
                    <span className="text-slate-500">Date</span>
                    <span className="text-slate-700">{esc.date}</span>
                  </div>
                  <div className="flex justify-between text-xs">
                    <span className="text-slate-500">Status</span>
                    <span className="text-amber-700" style={{ fontWeight: 600 }}>{esc.status}</span>
                  </div>
                </div>
              </div>
            ))}

            {escalations.length === 0 && (
              <div className="text-center py-8 text-slate-400">
                <ShieldAlert size={24} className="mx-auto mb-2 opacity-40" />
                <p className="text-xs">No active escalations</p>
              </div>
            )}
          </div>

          {/* Risk Detail Panel */}
          {selectedRisk && (
            <div className="border-t border-slate-200 p-4 bg-slate-50">
              <div className="text-slate-600 text-xs mb-2" style={{ fontWeight: 700 }}>SELECTED RISK</div>
              <div className="text-slate-800 text-sm mb-1" style={{ fontWeight: 600 }}>{selectedRisk.title}</div>
              <p className="text-slate-500 text-xs leading-relaxed mb-3">{selectedRisk.description}</p>
              <div className="flex gap-2">
                {(selectedRisk.level === 'critical' || selectedRisk.level === 'high') && (
                  <button onClick={() => setEscalateModal(selectedRisk)} className="flex-1 text-xs bg-orange-600 hover:bg-orange-700 text-white rounded-lg py-2" style={{ fontWeight: 500 }}>
                    Escalate
                  </button>
                )}
                {selectedRisk.level === 'medium' && (
                  <button onClick={() => setExceptionModal(selectedRisk)} className="flex-1 text-xs bg-slate-700 hover:bg-slate-800 text-white rounded-lg py-2" style={{ fontWeight: 500 }}>
                    Request Exception
                  </button>
                )}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Escalate Modal */}
      {escalateModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div className="flex items-center gap-3 px-6 py-5 bg-orange-50 rounded-t-2xl border-b border-orange-200">
              <div className="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                <ArrowUp size={18} className="text-orange-600" />
              </div>
              <div>
                <h2 className="text-orange-900 text-sm">Escalate Risk</h2>
                <p className="text-orange-600 text-xs">{escalateModal.title}</p>
              </div>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Escalate To</label>
                <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option>Chief Procurement Officer</option>
                  <option>Chief Compliance Officer</option>
                  <option>Chief Risk Officer</option>
                  <option>Finance Director</option>
                </select>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Escalation Reason</label>
                <textarea rows={3} className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-orange-400 outline-none" placeholder="Describe why this risk requires escalation..." />
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Proposed Response</label>
                <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option>Pause award process pending resolution</option>
                  <option>Require additional vendor documentation</option>
                  <option>Exclude vendor from shortlist</option>
                  <option>Proceed with noted risk</option>
                </select>
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setEscalateModal(null)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setEscalateModal(null)} className="bg-orange-600 hover:bg-orange-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Submit Escalation</button>
            </div>
          </div>
        </div>
      )}

      {/* Policy Exception Modal */}
      {exceptionModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <FileText size={16} className="text-indigo-600" />
                <h2 className="text-slate-900 text-sm">Request Policy Exception</h2>
              </div>
              <button onClick={() => setExceptionModal(null)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6 space-y-4">
              <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <div className="text-amber-700 text-sm" style={{ fontWeight: 600 }}>{exceptionModal.title}</div>
                <p className="text-amber-600 text-xs mt-1">{exceptionModal.description}</p>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Justification</label>
                <textarea rows={3} className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm resize-none focus:border-indigo-400 outline-none" placeholder="Business justification for this policy exception..." />
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Approver</label>
                <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option>Procurement Director</option>
                  <option>Chief Compliance Officer</option>
                </select>
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setExceptionModal(null)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setExceptionModal(null)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Submit Request</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
