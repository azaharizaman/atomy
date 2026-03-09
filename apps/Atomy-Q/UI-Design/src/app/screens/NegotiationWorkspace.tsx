import { useState } from 'react';
import {
  MessageSquare, Plus, Send, Check, X, ChevronRight, TrendingDown,
  TrendingUp, Clock, CheckCircle, AlertTriangle, ArrowRight, Minus
} from 'lucide-react';

interface Round {
  id: string;
  roundNum: number;
  date: string;
  status: 'completed' | 'active' | 'pending';
  ourOffer: number;
  vendorOffer: number;
  delta: number;
  notes: string;
  concessions: string[];
}

interface CounterOffer {
  lineId: string;
  description: string;
  currentPrice: number;
  targetPrice: number;
  justification: string;
}

const vendorName = 'PrimeSource Co.';
const rfqTitle = 'Server Infrastructure Components';

const rounds: Round[] = [
  {
    id: 'r1', roundNum: 1, date: 'Feb 28, 2026', status: 'completed',
    ourOffer: 170000, vendorOffer: 189200, delta: -19200,
    notes: 'Initial submission. Vendor at standard list price. We requested 10% volume discount.',
    concessions: ['Requested 10% volume discount', 'Requested Net 60 payment terms'],
  },
  {
    id: 'r2', roundNum: 2, date: 'Mar 3, 2026', status: 'completed',
    ourOffer: 175000, vendorOffer: 182000, delta: -7000,
    notes: 'Vendor reduced by $7,200. We increased our position slightly. Freight still to be negotiated.',
    concessions: ['Vendor offered 3.8% volume discount', 'We offered Net 45 payment terms'],
  },
  {
    id: 'r3', roundNum: 3, date: 'Mar 7, 2026', status: 'active',
    ourOffer: 178500, vendorOffer: 180000, delta: -1500,
    notes: 'Gap narrowing to $1,500. Vendor is considering freight waiver as final concession.',
    concessions: ['Freight waiver under discussion', 'Extended warranty (4yr vs 3yr) proposed'],
  },
];

const counterOffers: CounterOffer[] = [
  { lineId: 'li1', description: 'Dell PowerEdge R750 Server (×10)', currentPrice: 78000, targetPrice: 75000, justification: 'Benchmark pricing from comparable RFQ' },
  { lineId: 'li2', description: 'HPE ProLiant DL380 Gen10 (×5)', currentPrice: 46000, targetPrice: 44000, justification: 'Last purchase price + 3% escalation' },
  { lineId: 'li3', description: 'Cisco Catalyst 9300 48-Port (×8)', currentPrice: 37200, targetPrice: 36000, justification: 'MSRP discount achievable at volume' },
  { lineId: 'li4', description: 'Fortinet FortiGate 100F (×2)', currentPrice: 11900, targetPrice: 11500, justification: 'Competitive pricing from alternate vendor' },
];

type WorkspaceState = 'no-negotiation' | 'active' | 'final';

export function NegotiationWorkspace() {
  const [state, setState] = useState<WorkspaceState>('active');
  const [selectedRound, setSelectedRound] = useState<string>('r3');
  const [showLaunchModal, setShowLaunchModal] = useState(false);
  const [showSubmitModal, setShowSubmitModal] = useState(false);
  const [editTargets, setEditTargets] = useState<Record<string, number>>(
    Object.fromEntries(counterOffers.map(c => [c.lineId, c.targetPrice]))
  );

  const activeRound = rounds.find(r => r.id === selectedRound);
  const totalTarget = Object.values(editTargets).reduce((s, v) => s + v, 0);
  const totalCurrent = counterOffers.reduce((s, c) => s + c.currentPrice, 0);
  const totalDelta = totalTarget - totalCurrent;
  const totalPct = ((totalDelta / totalCurrent) * 100).toFixed(1);

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
              <span>RFQ-2401</span><span>·</span><span>{rfqTitle}</span><span>·</span>
              <span className="text-slate-700" style={{ fontWeight: 500 }}>{vendorName}</span>
            </div>
            <h1 className="text-slate-900">Negotiation Workspace</h1>
          </div>
          <div className="flex items-center gap-2">
            {state === 'no-negotiation' && (
              <button onClick={() => { setState('active'); setShowLaunchModal(true); }} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
                <Plus size={14} />
                Launch Negotiation
              </button>
            )}
            {state === 'active' && (
              <>
                <button onClick={() => setShowLaunchModal(true)} className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                  <Plus size={14} />
                  New Round
                </button>
                <button onClick={() => setState('final')} className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
                  <CheckCircle size={14} />
                  Finalize Agreement
                </button>
              </>
            )}
            {state === 'final' && (
              <div className="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2">
                <CheckCircle size={15} className="text-emerald-600" />
                <span className="text-emerald-700 text-sm" style={{ fontWeight: 600 }}>Agreement Finalized</span>
              </div>
            )}
          </div>
        </div>

        {/* Status Banner */}
        {state === 'no-negotiation' && (
          <div className="mt-3 bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-center gap-3">
            <MessageSquare size={16} className="text-slate-400" />
            <span className="text-slate-500 text-sm">No negotiation started yet. Launch a negotiation to begin multi-round offers.</span>
          </div>
        )}
        {state === 'active' && (
          <div className="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-center gap-3">
            <Clock size={14} className="text-amber-600" />
            <span className="text-amber-700 text-sm" style={{ fontWeight: 500 }}>Round 3 in progress</span>
            <span className="text-amber-600 text-sm">· Awaiting vendor response by Mar 10, 2026</span>
            <span className="ml-auto text-amber-700 text-xs" style={{ fontWeight: 600 }}>Gap: ${Math.abs(activeRound?.delta || 0).toLocaleString()}</span>
          </div>
        )}
        {state === 'final' && (
          <div className="mt-3 bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center gap-3">
            <CheckCircle size={14} className="text-emerald-600" />
            <span className="text-emerald-700 text-sm" style={{ fontWeight: 500 }}>Agreement reached: $179,000</span>
            <span className="text-emerald-600 text-sm">· $10,200 savings (5.4%) vs. initial quote</span>
          </div>
        )}
      </div>

      {state === 'no-negotiation' ? (
        <div className="flex-1 flex items-center justify-center">
          <div className="text-center max-w-sm">
            <div className="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mx-auto mb-4">
              <MessageSquare size={28} className="text-indigo-400" />
            </div>
            <h2 className="text-slate-700 mb-2">Start a Negotiation</h2>
            <p className="text-slate-500 text-sm mb-5">Begin a structured multi-round negotiation with {vendorName} for this RFQ. Track offers, concessions, and deltas across all rounds.</p>
            <button onClick={() => { setState('active'); setShowLaunchModal(true); }} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-6 py-3 text-sm" style={{ fontWeight: 500 }}>
              Launch Negotiation
            </button>
          </div>
        </div>
      ) : (
        <div className="flex-1 flex overflow-hidden">
          {/* Left: Round Timeline */}
          <div className="w-72 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col">
            <div className="px-4 py-3 border-b border-slate-100 flex-shrink-0">
              <div className="flex items-center justify-between">
                <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Round History</span>
                <span className="text-slate-400 text-xs">{rounds.length} rounds</span>
              </div>
            </div>

            {/* Timeline */}
            <div className="flex-1 overflow-y-auto p-4">
              <div className="relative">
                {/* Vertical line */}
                <div className="absolute left-3.5 top-4 bottom-4 w-px bg-slate-200" />

                {rounds.map((round, i) => {
                  const isActive = round.status === 'active';
                  const isSelected = round.id === selectedRound;
                  return (
                    <div key={round.id} className="relative pl-10 mb-4 last:mb-0" onClick={() => setSelectedRound(round.id)}>
                      {/* Dot */}
                      <div className={`absolute left-0 w-7 h-7 rounded-full border-2 flex items-center justify-center cursor-pointer transition-all ${isActive ? 'bg-amber-400 border-amber-500' : round.status === 'completed' ? 'bg-emerald-500 border-emerald-600' : 'bg-slate-200 border-slate-300'}`}>
                        {round.status === 'completed' ? <Check size={12} className="text-white" /> : <span className="text-white text-xs" style={{ fontWeight: 700 }}>{round.roundNum}</span>}
                      </div>

                      <div className={`rounded-xl border p-3 cursor-pointer transition-all ${isSelected ? 'border-indigo-200 bg-indigo-50' : 'border-slate-200 hover:border-slate-300 bg-white'}`}>
                        <div className="flex items-center justify-between mb-1">
                          <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>Round {round.roundNum}</span>
                          <span className={`text-xs px-1.5 py-0.5 rounded-full ${isActive ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}`} style={{ fontWeight: 500 }}>
                            {isActive ? 'Active' : 'Closed'}
                          </span>
                        </div>
                        <div className="text-slate-400 text-xs mb-2">{round.date}</div>
                        <div className="space-y-1">
                          <div className="flex justify-between text-xs">
                            <span className="text-slate-500">Our offer</span>
                            <span className="text-indigo-700" style={{ fontWeight: 500 }}>${round.ourOffer.toLocaleString()}</span>
                          </div>
                          <div className="flex justify-between text-xs">
                            <span className="text-slate-500">Vendor offer</span>
                            <span className="text-slate-700" style={{ fontWeight: 500 }}>${round.vendorOffer.toLocaleString()}</span>
                          </div>
                          <div className="flex justify-between text-xs border-t border-slate-100 pt-1 mt-1">
                            <span className="text-slate-500">Gap</span>
                            <span className={`${round.delta < 0 ? 'text-red-500' : 'text-emerald-600'}`} style={{ fontWeight: 600 }}>
                              ${Math.abs(round.delta).toLocaleString()}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Right: Active Round Detail */}
          <div className="flex-1 flex flex-col overflow-hidden">
            {activeRound && (
              <div className="flex-1 overflow-auto p-5 space-y-5">
                {/* Round Notes & Concessions */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <MessageSquare size={14} className="text-indigo-500" />
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Round {activeRound.roundNum} Notes</span>
                    </div>
                    <p className="text-slate-600 text-sm leading-relaxed">{activeRound.notes}</p>
                  </div>
                  <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <ArrowRight size={14} className="text-emerald-500" />
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Concessions This Round</span>
                    </div>
                    <div className="space-y-2">
                      {activeRound.concessions.map((c, i) => (
                        <div key={i} className="flex items-start gap-2">
                          <div className="w-4 h-4 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span className="text-slate-500 text-xs" style={{ fontSize: '9px', fontWeight: 700 }}>{i + 1}</span>
                          </div>
                          <span className="text-slate-600 text-xs">{c}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>

                {/* Counter-Offer Editor */}
                {state === 'active' && (
                  <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                      <div className="flex items-center gap-2">
                        <Edit3 size={14} className="text-indigo-600" />
                        <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Counter-Offer Editor</span>
                      </div>
                      <button onClick={() => setShowSubmitModal(true)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-xs" style={{ fontWeight: 500 }}>
                        <Send size={12} />
                        Submit Counter-Offer
                      </button>
                    </div>

                    <div>
                      {counterOffers.map((item) => {
                        const target = editTargets[item.lineId];
                        const delta = target - item.currentPrice;
                        const pct = ((delta / item.currentPrice) * 100).toFixed(1);
                        return (
                          <div key={item.lineId} className="flex items-center gap-4 px-5 py-3 border-b border-slate-50 hover:bg-slate-50/50">
                            <div className="flex-1 min-w-0">
                              <div className="text-slate-700 text-sm">{item.description}</div>
                              <div className="text-slate-400 text-xs mt-0.5">{item.justification}</div>
                            </div>
                            <div className="text-center w-28">
                              <div className="text-slate-500 text-xs mb-0.5">Current</div>
                              <div className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>${item.currentPrice.toLocaleString()}</div>
                            </div>
                            <ArrowRight size={14} className="text-slate-300 flex-shrink-0" />
                            <div className="text-center w-32">
                              <div className="text-slate-500 text-xs mb-1">Target Price</div>
                              <div className="relative">
                                <span className="absolute left-2.5 top-2 text-slate-400 text-xs">$</span>
                                <input
                                  type="number"
                                  value={target}
                                  onChange={e => setEditTargets(p => ({ ...p, [item.lineId]: parseInt(e.target.value) || 0 }))}
                                  className="w-full border border-indigo-200 rounded-lg pl-5 pr-2 py-1.5 text-xs text-indigo-700 text-right focus:border-indigo-400 outline-none bg-indigo-50"
                                />
                              </div>
                            </div>
                            <div className="text-center w-20">
                              <div className={`text-sm ${delta < 0 ? 'text-emerald-600' : 'text-slate-400'}`} style={{ fontWeight: 600 }}>
                                {delta < 0 ? `−$${Math.abs(delta).toLocaleString()}` : delta === 0 ? '—' : `+$${delta}`}
                              </div>
                              {delta !== 0 && <div className={`text-xs ${delta < 0 ? 'text-emerald-500' : 'text-red-500'}`}>{pct}%</div>}
                            </div>
                          </div>
                        );
                      })}
                    </div>

                    {/* Summary Row */}
                    <div className="flex items-center justify-end gap-6 px-5 py-3 bg-slate-900 rounded-b-xl">
                      <div className="text-center">
                        <div className="text-slate-400 text-xs">Current Total</div>
                        <div className="text-white text-sm" style={{ fontWeight: 600 }}>${totalCurrent.toLocaleString()}</div>
                      </div>
                      <ArrowRight size={14} className="text-slate-500" />
                      <div className="text-center">
                        <div className="text-slate-400 text-xs">Counter Total</div>
                        <div className="text-indigo-300 text-sm" style={{ fontWeight: 700 }}>${totalTarget.toLocaleString()}</div>
                      </div>
                      <div className="text-center">
                        <div className="text-slate-400 text-xs">Savings</div>
                        <div className="text-emerald-400 text-sm" style={{ fontWeight: 700 }}>
                          {totalDelta < 0 ? `−$${Math.abs(totalDelta).toLocaleString()} (${Math.abs(parseFloat(totalPct))}%)` : '—'}
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Launch Round Modal */}
      {showLaunchModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <Plus size={16} className="text-indigo-600" />
                <h2 className="text-slate-900 text-sm">Launch Negotiation Round</h2>
              </div>
              <button onClick={() => setShowLaunchModal(false)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Round Number</label>
                  <input defaultValue="4" className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
                </div>
                <div>
                  <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Response Deadline</label>
                  <input type="date" defaultValue="2026-03-12" className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
                </div>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Opening Position (Our Offer)</label>
                <div className="relative">
                  <span className="absolute left-3 top-2.5 text-slate-400">$</span>
                  <input defaultValue="179000" className="w-full border border-slate-200 rounded-lg pl-7 pr-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
                </div>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Round Notes</label>
                <textarea rows={3} className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm resize-none focus:border-indigo-400 outline-none" placeholder="Describe the objective for this round..." />
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setShowLaunchModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setShowLaunchModal(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Launch Round</button>
            </div>
          </div>
        </div>
      )}

      {/* Submit Counter-Offer Modal */}
      {showSubmitModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <Send size={16} className="text-indigo-600" />
                <h2 className="text-slate-900 text-sm">Submit Counter-Offer</h2>
              </div>
              <button onClick={() => setShowSubmitModal(false)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6 space-y-4">
              <div className="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-indigo-700 text-sm" style={{ fontWeight: 600 }}>Counter-Offer Summary</span>
                </div>
                <div className="space-y-1.5">
                  <div className="flex justify-between text-sm">
                    <span className="text-slate-600">Vendor's current offer</span>
                    <span className="text-slate-800" style={{ fontWeight: 500 }}>${totalCurrent.toLocaleString()}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-slate-600">Our counter-offer</span>
                    <span className="text-indigo-700" style={{ fontWeight: 700 }}>${totalTarget.toLocaleString()}</span>
                  </div>
                  <div className="flex justify-between text-sm border-t border-indigo-200 pt-2 mt-1">
                    <span className="text-slate-600">Requested saving</span>
                    <span className="text-emerald-700" style={{ fontWeight: 700 }}>−${Math.abs(totalDelta).toLocaleString()} ({Math.abs(parseFloat(totalPct))}%)</span>
                  </div>
                </div>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Message to Vendor</label>
                <textarea rows={4} defaultValue="Thank you for your revised submission. We are pleased to provide our counter-offer based on market benchmarking and volume commitments. Please confirm your acceptance or provide a revised position by March 12." className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm resize-none focus:border-indigo-400 outline-none" />
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setShowSubmitModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setShowSubmitModal(false)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
                <Send size={14} />
                Send Counter-Offer
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function Edit3({ size, className }: { size: number; className?: string }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
    </svg>
  );
}
