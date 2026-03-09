import { useState } from 'react';
import {
  ShieldAlert, AlertTriangle, AlertOctagon, CheckCircle, Info,
  ChevronRight, ChevronDown, X, FileText, ArrowUp, Users, DollarSign,
  Globe, Building, Shield, Search, Clock, Eye
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import {
  riskItems, vendors, dueDiligenceChecklists, sanctionsScreenings,
  riskLevelConfig, statusColors, formatDateTime, getVendorById,
} from '../data/mockData';
import type { RiskLevel } from '../data/mockData';

const categoryIcons: Record<string, typeof ShieldAlert> = {
  commercial: Building,
  policy: FileText,
  sanctions: Shield,
  performance: Users,
};

const categoryLabels: Record<string, string> = {
  commercial: 'Commercial Risk',
  policy: 'Policy & Compliance',
  sanctions: 'Sanctions',
  performance: 'Performance Risk',
};

const levelIcons: Record<RiskLevel, typeof AlertOctagon> = {
  critical: AlertOctagon,
  high: AlertTriangle,
  medium: Info,
  low: Info,
};

export function RiskComplianceReview() {
  const [selectedRiskId, setSelectedRiskId] = useState<string | null>(riskItems[0]?.id ?? null);
  const [expandedDd, setExpandedDd] = useState<string | null>(null);

  const [showEscalation, setShowEscalation] = useState(false);
  const [showException, setShowException] = useState(false);
  const [showScreening, setShowScreening] = useState(false);
  const [showHistory, setShowHistory] = useState(false);

  const [escalationRisk, setEscalationRisk] = useState<typeof riskItems[0] | null>(null);
  const [exceptionRisk, setExceptionRisk] = useState<typeof riskItems[0] | null>(null);
  const [screeningVendorId, setScreeningVendorId] = useState<string>('');
  const [historyVendorId, setHistoryVendorId] = useState<string>('');

  const selectedRisk = riskItems.find(r => r.id === selectedRiskId) ?? null;
  const categories = Array.from(new Set(riskItems.map(r => r.category)));
  const criticalCount = riskItems.filter(r => r.level === 'critical').length;
  const highCount = riskItems.filter(r => r.level === 'high').length;
  const escalatedCount = riskItems.filter(r => r.escalated).length;

  const screeningVendor = getVendorById(screeningVendorId);
  const screeningResults = sanctionsScreenings.filter(s => s.vendorId === screeningVendorId);
  const historyVendor = getVendorById(historyVendorId);
  const historyResults = sanctionsScreenings.filter(s => s.vendorId === historyVendorId);

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
              <FileText size={14} /> Export Risk Report
            </button>
          </div>
        </div>

        {/* Risk Severity Ladder */}
        <div className="flex items-center gap-2 mt-4 px-4 py-3 bg-slate-50 rounded-xl border border-slate-200">
          <span className="text-slate-500 text-xs mr-2" style={{ fontWeight: 500 }}>RISK LADDER</span>
          {(['critical', 'high', 'medium', 'low'] as RiskLevel[]).map(level => {
            const count = riskItems.filter(r => r.level === level).length;
            const cfg = riskLevelConfig[level];
            const Icon = levelIcons[level];
            return (
              <div key={level} className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full border ${cfg.bg} ${cfg.border}`}>
                <Icon size={12} className={cfg.text} />
                <span className={`text-xs capitalize ${cfg.text}`} style={{ fontWeight: 600 }}>{level}</span>
                <span className={`text-xs px-1.5 py-0.5 rounded-full ${cfg.badgeBg} ${cfg.badgeText}`} style={{ fontWeight: 700 }}>{count}</span>
              </div>
            );
          })}
          <div className="ml-auto flex items-center gap-1 text-xs text-slate-500">
            <ArrowUp size={12} />
            <span>{escalatedCount} escalation{escalatedCount !== 1 ? 's' : ''} active</span>
          </div>
        </div>
      </div>

      <div className="flex-1 flex overflow-hidden">
        {/* Main Risk Panels */}
        <div className="flex-1 overflow-auto p-5 space-y-5">
          {/* Risk Panels by Category */}
          {categories.map(cat => {
            const catRisks = riskItems.filter(r => r.category === cat);
            const Icon = categoryIcons[cat] || ShieldAlert;
            const label = categoryLabels[cat] || cat;
            return (
              <div key={cat} className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
                  <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
                    <Icon size={14} className="text-slate-600" />
                  </div>
                  <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>{label}</span>
                  <div className="flex items-center gap-1.5 ml-1">
                    {catRisks.map(r => (
                      <span key={r.id} className={`w-2 h-2 rounded-full ${riskLevelConfig[r.level].dot}`} title={r.level} />
                    ))}
                  </div>
                  <span className="ml-auto text-slate-400 text-xs">{catRisks.length} item{catRisks.length !== 1 ? 's' : ''}</span>
                </div>
                <div className="divide-y divide-slate-100">
                  {catRisks.map(risk => {
                    const cfg = riskLevelConfig[risk.level];
                    const RiskIcon = levelIcons[risk.level];
                    const vendor = risk.vendorId ? getVendorById(risk.vendorId) : null;
                    return (
                      <div
                        key={risk.id}
                        onClick={() => setSelectedRiskId(risk.id)}
                        className={`flex items-start gap-4 px-5 py-4 cursor-pointer transition-colors hover:bg-slate-50 ${selectedRiskId === risk.id ? 'bg-indigo-50/60' : ''}`}
                      >
                        <div className={`w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${cfg.bg} border ${cfg.border}`}>
                          <RiskIcon size={15} className={cfg.text} />
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 flex-wrap mb-1">
                            <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{risk.title}</span>
                            <span className={`text-xs px-2 py-0.5 rounded-full border capitalize ${cfg.bg} ${cfg.border} ${cfg.text}`} style={{ fontWeight: 600 }}>{risk.level}</span>
                            {vendor && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{vendor.name}</span>}
                            {risk.escalated && <span className="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded" style={{ fontWeight: 500 }}>Escalated</span>}
                            <span className={`text-xs px-2 py-0.5 rounded-full ${statusColors[risk.status] ?? 'bg-slate-100 text-slate-600'}`} style={{ fontWeight: 500 }}>{risk.status}</span>
                          </div>
                          <p className="text-slate-500 text-xs leading-relaxed">{risk.description}</p>
                        </div>
                        <div className="flex items-center gap-2 flex-shrink-0">
                          {(risk.level === 'critical' || risk.level === 'high') && !risk.escalated && (
                            <button
                              onClick={e => { e.stopPropagation(); setEscalationRisk(risk); setShowEscalation(true); }}
                              className="text-xs border border-orange-300 bg-orange-50 text-orange-700 rounded-lg px-2.5 py-1 hover:bg-orange-100"
                              style={{ fontWeight: 500 }}
                            >
                              <ArrowUp size={11} className="inline mr-0.5" /> Escalate
                            </button>
                          )}
                          {risk.level === 'medium' && (
                            <button
                              onClick={e => { e.stopPropagation(); setExceptionRisk(risk); setShowException(true); }}
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

          {/* Vendor Due Diligence Checklists */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="flex items-center gap-2 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
              <Shield size={14} className="text-indigo-600" />
              <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Vendor Due Diligence</span>
            </div>
            <div className="divide-y divide-slate-100">
              {dueDiligenceChecklists.map(dd => {
                const vendor = getVendorById(dd.vendorId);
                const isExpanded = expandedDd === dd.vendorId;
                const completedCount = dd.items.filter(i => i.status === 'complete').length;
                return (
                  <div key={dd.vendorId}>
                    <div
                      onClick={() => setExpandedDd(isExpanded ? null : dd.vendorId)}
                      className="flex items-center gap-4 px-5 py-3 cursor-pointer hover:bg-slate-50"
                    >
                      {isExpanded ? <ChevronDown size={14} className="text-slate-400" /> : <ChevronRight size={14} className="text-slate-400" />}
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{vendor?.name ?? dd.vendorId}</span>
                      <span className="text-xs text-slate-400">{completedCount}/{dd.items.length} complete</span>
                      <div className="flex-1" />
                      <div className="w-24 bg-slate-200 rounded-full h-1.5">
                        <div className="bg-emerald-500 h-1.5 rounded-full" style={{ width: `${(completedCount / dd.items.length) * 100}%` }} />
                      </div>
                      <button
                        onClick={e => { e.stopPropagation(); setScreeningVendorId(dd.vendorId); setShowScreening(true); }}
                        className="text-xs border border-indigo-200 bg-indigo-50 text-indigo-700 rounded-lg px-2.5 py-1 hover:bg-indigo-100"
                        style={{ fontWeight: 500 }}
                      >
                        <Search size={11} className="inline mr-0.5" /> Screen
                      </button>
                      <button
                        onClick={e => { e.stopPropagation(); setHistoryVendorId(dd.vendorId); setShowHistory(true); }}
                        className="text-xs border border-slate-200 bg-white text-slate-600 rounded-lg px-2.5 py-1 hover:bg-slate-50"
                        style={{ fontWeight: 500 }}
                      >
                        <Eye size={11} className="inline mr-0.5" /> History
                      </button>
                    </div>
                    {isExpanded && (
                      <div className="px-5 pb-4 pl-14">
                        <div className="space-y-2">
                          {dd.items.map(item => (
                            <div key={item.id} className="flex items-center gap-3 text-sm">
                              {item.status === 'complete' ? (
                                <CheckCircle size={14} className="text-emerald-500 flex-shrink-0" />
                              ) : (
                                <Clock size={14} className="text-amber-500 flex-shrink-0" />
                              )}
                              <span className={item.status === 'complete' ? 'text-slate-600' : 'text-amber-700'}>{item.label}</span>
                              <span className={`text-xs px-1.5 py-0.5 rounded-full ${statusColors[item.status] ?? 'bg-slate-100 text-slate-600'}`} style={{ fontWeight: 500 }}>{item.status}</span>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>

          {/* Sanctions Screening Results */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="flex items-center gap-2 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
              <Globe size={14} className="text-indigo-600" />
              <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Recent Sanctions Screenings</span>
            </div>
            <div className="divide-y divide-slate-100">
              {sanctionsScreenings.map(s => {
                const vendor = getVendorById(s.vendorId);
                return (
                  <div key={s.id} className="flex items-center gap-4 px-5 py-3">
                    {s.result === 'clear' ? (
                      <CheckCircle size={16} className="text-emerald-500" />
                    ) : (
                      <AlertOctagon size={16} className="text-red-500" />
                    )}
                    <div className="flex-1">
                      <div className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{vendor?.name ?? s.vendorId}</div>
                      <div className="text-slate-400 text-xs">{formatDateTime(s.screenedAt)}</div>
                    </div>
                    <span className={`text-xs px-2 py-0.5 rounded-full ${s.result === 'clear' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`} style={{ fontWeight: 600 }}>
                      {s.result}
                    </span>
                    {s.matchedEntries.length > 0 && (
                      <span className="text-xs text-red-500">{s.matchedEntries.length} match{s.matchedEntries.length !== 1 ? 'es' : ''}</span>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* Side Rail: Selected Risk Detail */}
        <div className="w-72 flex-shrink-0 border-l border-slate-200 bg-white flex flex-col overflow-hidden">
          <div className="px-4 py-3 border-b border-slate-100 flex-shrink-0">
            <h3 className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>Risk Detail</h3>
          </div>

          {selectedRisk ? (
            <div className="flex-1 overflow-y-auto p-4 space-y-4">
              <div className={`rounded-xl border p-4 ${riskLevelConfig[selectedRisk.level].bg} ${riskLevelConfig[selectedRisk.level].border}`}>
                <div className="flex items-center gap-2 mb-2">
                  {(() => { const I = levelIcons[selectedRisk.level]; return <I size={14} className={riskLevelConfig[selectedRisk.level].text} />; })()}
                  <span className={`text-sm ${riskLevelConfig[selectedRisk.level].text}`} style={{ fontWeight: 600 }}>{selectedRisk.title}</span>
                </div>
                <p className="text-slate-600 text-xs leading-relaxed">{selectedRisk.description}</p>
              </div>

              <div className="space-y-2">
                <div className="flex justify-between text-xs"><span className="text-slate-500">Category</span><span className="text-slate-700 capitalize">{selectedRisk.category}</span></div>
                <div className="flex justify-between text-xs"><span className="text-slate-500">Level</span><span className={`capitalize ${riskLevelConfig[selectedRisk.level].text}`} style={{ fontWeight: 600 }}>{selectedRisk.level}</span></div>
                <div className="flex justify-between text-xs"><span className="text-slate-500">Status</span><span className="text-slate-700 capitalize">{selectedRisk.status}</span></div>
                {selectedRisk.vendorId && (
                  <div className="flex justify-between text-xs"><span className="text-slate-500">Vendor</span><span className="text-slate-700">{getVendorById(selectedRisk.vendorId)?.name}</span></div>
                )}
                <div className="flex justify-between text-xs"><span className="text-slate-500">Escalated</span><span className="text-slate-700">{selectedRisk.escalated ? 'Yes' : 'No'}</span></div>
              </div>

              <div className="flex gap-2 pt-2">
                {(selectedRisk.level === 'critical' || selectedRisk.level === 'high') && !selectedRisk.escalated && (
                  <button onClick={() => { setEscalationRisk(selectedRisk); setShowEscalation(true); }} className="flex-1 text-xs bg-orange-600 hover:bg-orange-700 text-white rounded-lg py-2" style={{ fontWeight: 500 }}>
                    Escalate
                  </button>
                )}
                {selectedRisk.level === 'medium' && (
                  <button onClick={() => { setExceptionRisk(selectedRisk); setShowException(true); }} className="flex-1 text-xs bg-slate-700 hover:bg-slate-800 text-white rounded-lg py-2" style={{ fontWeight: 500 }}>
                    Request Exception
                  </button>
                )}
              </div>
            </div>
          ) : (
            <div className="flex-1 flex items-center justify-center">
              <p className="text-slate-400 text-xs">Select a risk item to view details</p>
            </div>
          )}
        </div>
      </div>

      {/* SlideOver: Risk Escalation Required */}
      <SlideOver
        open={showEscalation}
        onOpenChange={setShowEscalation}
        title="Risk Escalation Required"
        description={escalationRisk ? escalationRisk.title : ''}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowEscalation(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowEscalation(false)} className="bg-orange-600 hover:bg-orange-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Submit Escalation</button>
          </div>
        }
      >
        <div className="space-y-5">
          {escalationRisk && (
            <div className={`rounded-xl border p-4 ${riskLevelConfig[escalationRisk.level].bg} ${riskLevelConfig[escalationRisk.level].border}`}>
              <div className={`text-sm ${riskLevelConfig[escalationRisk.level].text}`} style={{ fontWeight: 600 }}>{escalationRisk.title}</div>
              <p className="text-slate-600 text-xs mt-1">{escalationRisk.description}</p>
            </div>
          )}
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
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Supporting Evidence</label>
            <div className="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center">
              <p className="text-slate-400 text-sm">Drag files here or click to upload</p>
            </div>
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Policy Exception Request */}
      <SlideOver
        open={showException}
        onOpenChange={setShowException}
        title="Policy Exception Request"
        description={exceptionRisk ? exceptionRisk.title : ''}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowException(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowException(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Submit Request</button>
          </div>
        }
      >
        <div className="space-y-5">
          {exceptionRisk && (
            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
              <div className="text-amber-700 text-sm" style={{ fontWeight: 600 }}>{exceptionRisk.title}</div>
              <p className="text-amber-600 text-xs mt-1">{exceptionRisk.description}</p>
            </div>
          )}
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
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Supporting Evidence</label>
            <div className="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center">
              <p className="text-slate-400 text-sm">Drag files here or click to upload</p>
            </div>
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Sanctions Screening */}
      <SlideOver
        open={showScreening}
        onOpenChange={setShowScreening}
        title="Sanctions Screening"
        description={screeningVendor ? `Screen ${screeningVendor.name} against sanctions lists.` : ''}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowScreening(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            <button onClick={() => setShowScreening(false)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Search size={14} /> Run Screening
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          {screeningVendor && (
            <div className="bg-slate-50 rounded-xl border border-slate-200 p-4 space-y-2">
              <div className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>{screeningVendor.name}</div>
              <div className="flex justify-between text-xs"><span className="text-slate-500">Country</span><span className="text-slate-700">{screeningVendor.country}</span></div>
              <div className="flex justify-between text-xs"><span className="text-slate-500">Category</span><span className="text-slate-700">{screeningVendor.category}</span></div>
              <div className="flex justify-between text-xs"><span className="text-slate-500">Contact</span><span className="text-slate-700">{screeningVendor.contactEmail}</span></div>
              <div className="flex justify-between text-xs">
                <span className="text-slate-500">Current Status</span>
                <span className={`text-xs px-2 py-0.5 rounded-full ${screeningVendor.sanctionsStatus === 'clear' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`} style={{ fontWeight: 600 }}>
                  {screeningVendor.sanctionsStatus}
                </span>
              </div>
            </div>
          )}

          <div>
            <label className="text-slate-600 text-xs block mb-2" style={{ fontWeight: 500 }}>Screening Lists</label>
            <div className="space-y-2">
              {['OFAC SDN List', 'EU Sanctions List', 'UN Consolidated List', 'UK Sanctions List', 'Australia DFAT'].map(list => (
                <label key={list} className="flex items-center gap-2 text-sm text-slate-700">
                  <input type="checkbox" defaultChecked className="rounded text-indigo-600 focus:ring-indigo-500" />
                  {list}
                </label>
              ))}
            </div>
          </div>

          {screeningResults.length > 0 && (
            <div>
              <div className="text-slate-700 text-xs mb-2" style={{ fontWeight: 700 }}>PREVIOUS RESULTS</div>
              {screeningResults.map(s => (
                <div key={s.id} className={`rounded-xl border p-3 mb-2 ${s.result === 'clear' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'}`}>
                  <div className="flex items-center gap-2 mb-1">
                    {s.result === 'clear' ? <CheckCircle size={12} className="text-emerald-600" /> : <AlertOctagon size={12} className="text-red-600" />}
                    <span className={`text-xs ${s.result === 'clear' ? 'text-emerald-700' : 'text-red-700'}`} style={{ fontWeight: 600 }}>{s.result === 'clear' ? 'Clear' : 'Flagged'}</span>
                    <span className="text-slate-400 text-xs ml-auto">{formatDateTime(s.screenedAt)}</span>
                  </div>
                  {s.matchedEntries.length > 0 && (
                    <div className="mt-1 space-y-1">
                      {s.matchedEntries.map((entry, i) => (
                        <div key={i} className="text-red-600 text-xs bg-red-100 rounded px-2 py-1">{entry}</div>
                      ))}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </SlideOver>

      {/* SlideOver: View Screening History */}
      <SlideOver
        open={showHistory}
        onOpenChange={setShowHistory}
        title="Screening History"
        description={historyVendor ? `Past screening runs for ${historyVendor.name}.` : ''}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowHistory(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
          </div>
        }
      >
        <div className="space-y-4">
          {historyResults.length > 0 ? (
            historyResults.map(s => {
              const screener = s.screener;
              return (
                <div key={s.id} className={`rounded-xl border p-4 ${s.result === 'clear' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'}`}>
                  <div className="flex items-center gap-2 mb-2">
                    {s.result === 'clear' ? <CheckCircle size={14} className="text-emerald-600" /> : <AlertOctagon size={14} className="text-red-600" />}
                    <span className={`text-sm ${s.result === 'clear' ? 'text-emerald-700' : 'text-red-700'}`} style={{ fontWeight: 600 }}>
                      {s.result === 'clear' ? 'Clear' : 'Flagged'}
                    </span>
                  </div>
                  <div className="space-y-1 text-xs">
                    <div className="flex justify-between"><span className="text-slate-500">Screened At</span><span className="text-slate-700">{formatDateTime(s.screenedAt)}</span></div>
                    <div className="flex justify-between"><span className="text-slate-500">Screener</span><span className="text-slate-700">{screener}</span></div>
                  </div>
                  {s.matchedEntries.length > 0 && (
                    <div className="mt-2 space-y-1">
                      {s.matchedEntries.map((entry, i) => (
                        <div key={i} className="text-red-600 text-xs bg-red-100 rounded px-2 py-1">{entry}</div>
                      ))}
                    </div>
                  )}
                </div>
              );
            })
          ) : (
            <div className="text-center py-8">
              <Globe size={24} className="mx-auto mb-2 text-slate-300" />
              <p className="text-slate-400 text-sm">No screening history for this vendor.</p>
            </div>
          )}
        </div>
      </SlideOver>
    </div>
  );
}
