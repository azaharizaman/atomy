import { useState } from 'react';
import {
  MessageSquare, Plus, Send, Check, X, ChevronRight, TrendingDown,
  TrendingUp, Clock, CheckCircle, AlertTriangle, ArrowRight, Minus,
  Users, Gavel, Flag
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import {
  negotiationRounds, vendors, rfqs, formatCurrency, formatDateTime, getVendorById,
  rfqLineItems,
} from '../data/mockData';

const rfq = rfqs.find(r => r.id === 'RFQ-2401')!;

type WorkspaceState = 'no-negotiation' | 'active' | 'final';

export function NegotiationWorkspace() {
  const [state, setState] = useState<WorkspaceState>('active');
  const [selectedRoundId, setSelectedRoundId] = useState(negotiationRounds[negotiationRounds.length - 1]?.id ?? '');

  const [showLaunch, setShowLaunch] = useState(false);
  const [showCounter, setShowCounter] = useState(false);
  const [showBafo, setShowBafo] = useState(false);
  const [showClose, setShowClose] = useState(false);

  const [selectedVendors, setSelectedVendors] = useState<string[]>([]);
  const lineItems = rfqLineItems.filter(li => li.rfqId === rfq.id);

  const [counterPrices, setCounterPrices] = useState<Record<string, number>>(
    Object.fromEntries(lineItems.map(li => [li.id, Math.round(li.estimatedUnitPrice * li.quantity * 0.96)]))
  );

  const selectedRound = negotiationRounds.find(r => r.id === selectedRoundId);
  const activeRound = negotiationRounds.find(r => r.status === 'active');
  const currentGap = activeRound?.offers.length
    ? Math.abs(activeRound.offers[0].counterTotal - activeRound.offers[0].originalTotal)
    : 0;

  const toggleVendor = (vid: string) => {
    setSelectedVendors(prev => prev.includes(vid) ? prev.filter(v => v !== vid) : [...prev, vid]);
  };

  const counterTotal = Object.values(counterPrices).reduce((s, v) => s + v, 0);
  const currentTotal = lineItems.reduce((s, li) => s + li.estimatedUnitPrice * li.quantity, 0);
  const counterDelta = counterTotal - currentTotal;
  const counterPct = currentTotal > 0 ? ((counterDelta / currentTotal) * 100).toFixed(1) : '0';

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
              <span>{rfq.id}</span><span>·</span><span>{rfq.title}</span>
            </div>
            <h1 className="text-slate-900">Negotiation Workspace</h1>
          </div>
          <div className="flex items-center gap-2">
            {state === 'no-negotiation' && (
              <button onClick={() => { setState('active'); setShowLaunch(true); }} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
                <Plus size={14} /> Launch Negotiation
              </button>
            )}
            {state === 'active' && (
              <>
                <button onClick={() => setShowLaunch(true)} className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                  <Plus size={14} /> New Round
                </button>
                <button onClick={() => setShowBafo(true)} className="flex items-center gap-2 border border-amber-300 bg-amber-50 text-amber-700 rounded-lg px-3 py-2 text-sm hover:bg-amber-100" style={{ fontWeight: 500 }}>
                  <Flag size={14} /> Request BAFO
                </button>
                <button onClick={() => setShowClose(true)} className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
                  <CheckCircle size={14} /> Close Negotiation
                </button>
              </>
            )}
            {state === 'final' && (
              <div className="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2">
                <CheckCircle size={15} className="text-emerald-600" />
                <span className="text-emerald-700 text-sm" style={{ fontWeight: 600 }}>Negotiation Closed</span>
              </div>
            )}
          </div>
        </div>

        {state === 'no-negotiation' && (
          <div className="mt-3 bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-center gap-3">
            <MessageSquare size={16} className="text-slate-400" />
            <span className="text-slate-500 text-sm">No negotiation started yet. Launch a negotiation to begin multi-round offers.</span>
          </div>
        )}
        {state === 'active' && activeRound && (
          <div className="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-center gap-3">
            <Clock size={14} className="text-amber-600" />
            <span className="text-amber-700 text-sm" style={{ fontWeight: 500 }}>Round {activeRound.roundNumber} in progress</span>
            <span className="text-amber-600 text-sm">· Deadline: {formatDateTime(activeRound.deadline)}</span>
            <span className="ml-auto text-amber-700 text-xs" style={{ fontWeight: 600 }}>
              {activeRound.offers.length} of {activeRound.vendors.length} offers received · Gap: {formatCurrency(currentGap)}
            </span>
          </div>
        )}
        {state === 'final' && (
          <div className="mt-3 bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center gap-3">
            <CheckCircle size={14} className="text-emerald-600" />
            <span className="text-emerald-700 text-sm" style={{ fontWeight: 500 }}>Negotiation closed</span>
            <span className="text-emerald-600 text-sm">· {negotiationRounds.length} rounds completed</span>
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
            <p className="text-slate-500 text-sm mb-5">Begin a structured multi-round negotiation. Track offers, concessions, and deltas across all rounds.</p>
            <button onClick={() => { setState('active'); setShowLaunch(true); }} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-6 py-3 text-sm" style={{ fontWeight: 500 }}>
              Launch Negotiation
            </button>
          </div>
        </div>
      ) : (
        <div className="flex-1 flex overflow-hidden">
          {/* Left: Round Timeline */}
          <div className="w-80 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col">
            <div className="px-4 py-3 border-b border-slate-100 flex-shrink-0">
              <div className="flex items-center justify-between">
                <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Round History</span>
                <span className="text-slate-400 text-xs">{negotiationRounds.length} rounds</span>
              </div>
            </div>

            <div className="flex-1 overflow-y-auto p-4">
              <div className="relative">
                <div className="absolute left-3.5 top-4 bottom-4 w-px bg-slate-200" />

                {negotiationRounds.map((round) => {
                  const isActive = round.status === 'active';
                  const isClosed = round.status === 'closed';
                  const isSelected = round.id === selectedRoundId;
                  return (
                    <div key={round.id} className="relative pl-10 mb-4 last:mb-0" onClick={() => setSelectedRoundId(round.id)}>
                      <div className={`absolute left-0 w-7 h-7 rounded-full border-2 flex items-center justify-center cursor-pointer transition-all ${isActive ? 'bg-amber-400 border-amber-500' : isClosed ? 'bg-emerald-500 border-emerald-600' : 'bg-slate-200 border-slate-300'}`}>
                        {isClosed ? <Check size={12} className="text-white" /> : <span className="text-white text-xs" style={{ fontWeight: 700 }}>{round.roundNumber}</span>}
                      </div>

                      <div className={`rounded-xl border p-3 cursor-pointer transition-all ${isSelected ? 'border-indigo-200 bg-indigo-50' : 'border-slate-200 hover:border-slate-300 bg-white'}`}>
                        <div className="flex items-center justify-between mb-1">
                          <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>Round {round.roundNumber}</span>
                          <span className={`text-xs px-1.5 py-0.5 rounded-full ${isActive ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}`} style={{ fontWeight: 500 }}>
                            {isActive ? 'Active' : 'Closed'}
                          </span>
                        </div>
                        <div className="text-slate-400 text-xs mb-1.5">{formatDateTime(round.startedAt)}</div>
                        <div className="text-slate-500 text-xs mb-2">{round.scope}</div>

                        <div className="flex items-center gap-1.5 mb-2">
                          <Users size={10} className="text-slate-400" />
                          {round.vendors.map(vid => {
                            const v = getVendorById(vid);
                            return <span key={vid} className="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">{v?.name.split(' ')[0] ?? vid}</span>;
                          })}
                        </div>

                        <div className="text-slate-400 text-xs mb-1" style={{ fontWeight: 500 }}>Deadline: {formatDateTime(round.deadline)}</div>

                        {round.offers.map(offer => {
                          const v = getVendorById(offer.vendorId);
                          return (
                            <div key={offer.vendorId} className="border-t border-slate-100 pt-1.5 mt-1.5 space-y-0.5">
                              <div className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>{v?.name ?? offer.vendorId}</div>
                              <div className="flex justify-between text-xs">
                                <span className="text-slate-400">Original</span>
                                <span className="text-slate-700" style={{ fontWeight: 500 }}>{formatCurrency(offer.originalTotal)}</span>
                              </div>
                              <div className="flex justify-between text-xs">
                                <span className="text-slate-400">Counter</span>
                                <span className="text-indigo-700" style={{ fontWeight: 500 }}>{formatCurrency(offer.counterTotal)}</span>
                              </div>
                              <div className="flex justify-between text-xs">
                                <span className="text-slate-400">Delta</span>
                                <span className={`${offer.deltaPercent < 0 ? 'text-emerald-600' : 'text-red-500'}`} style={{ fontWeight: 600 }}>
                                  {offer.deltaPercent < 0 ? '↓' : '↑'} {Math.abs(offer.deltaPercent)}%
                                </span>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Right: Active Round Detail + Counter-Offer Panel */}
          <div className="flex-1 flex flex-col overflow-hidden">
            {selectedRound && (
              <div className="flex-1 overflow-auto p-5 space-y-5">
                {/* Round Info Cards */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <MessageSquare size={14} className="text-indigo-500" />
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Round {selectedRound.roundNumber} Details</span>
                    </div>
                    <div className="space-y-2 text-sm">
                      <div className="flex justify-between"><span className="text-slate-500">Scope</span><span className="text-slate-700">{selectedRound.scope}</span></div>
                      <div className="flex justify-between"><span className="text-slate-500">Started</span><span className="text-slate-700">{formatDateTime(selectedRound.startedAt)}</span></div>
                      <div className="flex justify-between"><span className="text-slate-500">Deadline</span><span className="text-slate-700">{formatDateTime(selectedRound.deadline)}</span></div>
                      <div className="flex justify-between"><span className="text-slate-500">Vendors</span><span className="text-slate-700">{selectedRound.vendors.length}</span></div>
                      <div className="flex justify-between"><span className="text-slate-500">Offers Received</span><span className="text-slate-700">{selectedRound.offers.length}</span></div>
                    </div>
                  </div>

                  <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <TrendingDown size={14} className="text-emerald-500" />
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Concession Summary</span>
                    </div>
                    {selectedRound.offers.length > 0 ? (
                      <div className="space-y-3">
                        {selectedRound.offers.map(offer => {
                          const v = getVendorById(offer.vendorId);
                          const saved = offer.originalTotal - offer.counterTotal;
                          return (
                            <div key={offer.vendorId} className="bg-slate-50 rounded-lg p-3">
                              <div className="text-slate-700 text-xs mb-1" style={{ fontWeight: 600 }}>{v?.name}</div>
                              <div className="flex justify-between text-xs mb-0.5">
                                <span className="text-slate-500">Saving</span>
                                <span className="text-emerald-600" style={{ fontWeight: 600 }}>{formatCurrency(saved)} ({Math.abs(offer.deltaPercent)}%)</span>
                              </div>
                              <div className="text-slate-400 text-xs">{offer.terms}</div>
                            </div>
                          );
                        })}
                      </div>
                    ) : (
                      <p className="text-slate-400 text-sm">No offers received yet for this round.</p>
                    )}
                  </div>
                </div>

                {/* Offer Detail Table */}
                <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                  <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                    <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Offers This Round</span>
                    {state === 'active' && selectedRound.status === 'active' && (
                      <button onClick={() => setShowCounter(true)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-xs" style={{ fontWeight: 500 }}>
                        <Send size={12} /> Submit Counter-Offer
                      </button>
                    )}
                  </div>
                  {selectedRound.offers.length > 0 ? (
                    <table className="w-full">
                      <thead>
                        <tr className="bg-slate-50 border-b border-slate-100">
                          {['Vendor', 'Original', 'Counter', 'Delta', 'Terms', 'Submitted'].map(h => (
                            <th key={h} className="text-left px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>{h}</th>
                          ))}
                        </tr>
                      </thead>
                      <tbody>
                        {selectedRound.offers.map(offer => {
                          const v = getVendorById(offer.vendorId);
                          return (
                            <tr key={offer.vendorId} className="border-b border-slate-50 hover:bg-slate-50/50">
                              <td className="px-5 py-3 text-slate-800 text-sm" style={{ fontWeight: 500 }}>{v?.name}</td>
                              <td className="px-5 py-3 text-slate-600 text-sm">{formatCurrency(offer.originalTotal)}</td>
                              <td className="px-5 py-3 text-indigo-700 text-sm" style={{ fontWeight: 600 }}>{formatCurrency(offer.counterTotal)}</td>
                              <td className="px-5 py-3">
                                <span className={`text-sm ${offer.deltaPercent < 0 ? 'text-emerald-600' : 'text-red-500'}`} style={{ fontWeight: 600 }}>
                                  {offer.deltaPercent < 0 ? '↓' : '↑'} {Math.abs(offer.deltaPercent)}%
                                </span>
                              </td>
                              <td className="px-5 py-3 text-slate-600 text-xs">{offer.terms}</td>
                              <td className="px-5 py-3 text-slate-400 text-xs">{formatDateTime(offer.submittedAt)}</td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  ) : (
                    <div className="px-5 py-8 text-center text-slate-400 text-sm">Awaiting vendor responses</div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* SlideOver: Launch Negotiation Round */}
      <SlideOver
        open={showLaunch}
        onOpenChange={setShowLaunch}
        title="Launch Negotiation Round"
        description="Select vendors, define scope, and set a response deadline."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowLaunch(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowLaunch(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Launch Round</button>
          </div>
        }
      >
        <div className="space-y-5">
          <div>
            <label className="text-slate-600 text-xs block mb-2" style={{ fontWeight: 500 }}>Select Vendors</label>
            <div className="space-y-2">
              {vendors.slice(0, 5).map(v => (
                <label key={v.id} className={`flex items-center gap-3 px-3 py-2.5 rounded-xl border cursor-pointer transition-colors ${selectedVendors.includes(v.id) ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 hover:border-slate-300'}`}>
                  <input type="checkbox" checked={selectedVendors.includes(v.id)} onChange={() => toggleVendor(v.id)} className="rounded text-indigo-600 focus:ring-indigo-500" />
                  <div className="flex-1">
                    <div className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{v.name}</div>
                    <div className="text-slate-400 text-xs">{v.country} · {v.category}</div>
                  </div>
                  <span className="text-xs text-slate-400">Score: {v.overallScore}</span>
                </label>
              ))}
            </div>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Negotiation Scope</label>
            <textarea rows={3} placeholder="Define what is being negotiated in this round..." className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm resize-none focus:border-indigo-400 outline-none" />
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Response Deadline</label>
            <input type="datetime-local" defaultValue="2026-03-12T17:00" className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Submit Counter Offer */}
      <SlideOver
        open={showCounter}
        onOpenChange={setShowCounter}
        title="Submit Counter-Offer"
        description="Set target prices per line item and send counter to vendors."
        width="lg"
        footer={
          <div className="flex items-center justify-between">
            <div className="text-sm">
              <span className="text-slate-500">Counter Total: </span>
              <span className="text-indigo-700" style={{ fontWeight: 700 }}>{formatCurrency(counterTotal)}</span>
              <span className={`ml-3 text-xs ${counterDelta < 0 ? 'text-emerald-600' : 'text-red-500'}`} style={{ fontWeight: 600 }}>
                {counterDelta < 0 ? `−${formatCurrency(Math.abs(counterDelta))} (${Math.abs(parseFloat(counterPct))}%)` : '—'}
              </span>
            </div>
            <div className="flex items-center gap-3">
              <button onClick={() => setShowCounter(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setShowCounter(false)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
                <Send size={14} /> Send Counter-Offer
              </button>
            </div>
          </div>
        }
      >
        <div className="space-y-4">
          {lineItems.map(item => {
            const currentValue = item.estimatedUnitPrice * item.quantity;
            const target = counterPrices[item.id] ?? currentValue;
            const delta = target - currentValue;
            const pct = currentValue > 0 ? ((delta / currentValue) * 100).toFixed(1) : '0';
            return (
              <div key={item.id} className="flex items-center gap-4 border border-slate-200 rounded-xl p-3 hover:bg-slate-50/50">
                <div className="flex-1 min-w-0">
                  <div className="text-slate-700 text-sm">{item.description}</div>
                  <div className="text-slate-400 text-xs mt-0.5">{item.quantity} × {formatCurrency(item.estimatedUnitPrice)}</div>
                </div>
                <div className="text-center w-24">
                  <div className="text-slate-500 text-xs mb-0.5">Current</div>
                  <div className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{formatCurrency(currentValue)}</div>
                </div>
                <ArrowRight size={14} className="text-slate-300 flex-shrink-0" />
                <div className="w-32">
                  <div className="text-slate-500 text-xs mb-1">Target</div>
                  <input
                    type="number"
                    value={target}
                    onChange={e => setCounterPrices(p => ({ ...p, [item.id]: parseInt(e.target.value) || 0 }))}
                    className="w-full border border-indigo-200 rounded-lg px-3 py-1.5 text-xs text-indigo-700 text-right focus:border-indigo-400 outline-none bg-indigo-50"
                  />
                </div>
                <div className="text-center w-20">
                  <div className={`text-sm ${delta < 0 ? 'text-emerald-600' : delta > 0 ? 'text-red-500' : 'text-slate-400'}`} style={{ fontWeight: 600 }}>
                    {delta < 0 ? `−${formatCurrency(Math.abs(delta))}` : delta === 0 ? '—' : `+${formatCurrency(delta)}`}
                  </div>
                  {delta !== 0 && <div className={`text-xs ${delta < 0 ? 'text-emerald-500' : 'text-red-400'}`}>{pct}%</div>}
                </div>
              </div>
            );
          })}

          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Message to Vendors</label>
            <textarea rows={3} defaultValue="Please review our revised counter-offer and confirm your acceptance or provide a revised position." className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm resize-none focus:border-indigo-400 outline-none" />
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Request BAFO */}
      <SlideOver
        open={showBafo}
        onOpenChange={setShowBafo}
        title="Request Best & Final Offer (BAFO)"
        description="Request final pricing from shortlisted vendors before closing negotiation."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowBafo(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowBafo(false)} className="flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Flag size={14} /> Send BAFO Request
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-1">
              <AlertTriangle size={14} className="text-amber-600" />
              <span className="text-amber-700 text-sm" style={{ fontWeight: 600 }}>BAFO is a final round</span>
            </div>
            <p className="text-amber-600 text-xs">This signals to vendors that no further negotiations will take place. Their response will be treated as the final offer.</p>
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-2" style={{ fontWeight: 500 }}>Select Vendors for BAFO</label>
            <div className="space-y-2">
              {negotiationRounds[negotiationRounds.length - 1]?.vendors.map(vid => {
                const v = getVendorById(vid);
                if (!v) return null;
                return (
                  <label key={vid} className="flex items-center gap-3 px-3 py-2.5 rounded-xl border border-slate-200 hover:border-slate-300 cursor-pointer">
                    <input type="checkbox" defaultChecked className="rounded text-amber-600 focus:ring-amber-500" />
                    <div>
                      <div className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{v.name}</div>
                      <div className="text-slate-400 text-xs">{v.country}</div>
                    </div>
                  </label>
                );
              })}
            </div>
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>BAFO Deadline</label>
            <input type="datetime-local" defaultValue="2026-03-14T17:00" className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Additional Instructions</label>
            <textarea rows={3} placeholder="Specify any requirements or focus areas for the final offer..." className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm resize-none focus:border-indigo-400 outline-none" />
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Close Negotiation */}
      <SlideOver
        open={showClose}
        onOpenChange={setShowClose}
        title="Close Negotiation"
        description="Finalize the negotiation and lock all rounds."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowClose(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => { setShowClose(false); setState('final'); }} className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <CheckCircle size={14} /> Confirm & Close
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-2">
              <CheckCircle size={16} className="text-emerald-600" />
              <span className="text-emerald-700 text-sm" style={{ fontWeight: 600 }}>Confirm Closure</span>
            </div>
            <p className="text-emerald-600 text-sm">Closing the negotiation will lock all rounds and prevent further counter-offers. This action is logged in the Decision Trail.</p>
          </div>

          <div className="bg-slate-50 rounded-xl border border-slate-200 p-4 space-y-2">
            <div className="text-slate-600 text-xs" style={{ fontWeight: 700 }}>NEGOTIATION SUMMARY</div>
            <div className="flex justify-between text-sm"><span className="text-slate-500">Rounds</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{negotiationRounds.length}</span></div>
            <div className="flex justify-between text-sm"><span className="text-slate-500">Vendors</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{new Set(negotiationRounds.flatMap(r => r.vendors)).size}</span></div>
            <div className="flex justify-between text-sm"><span className="text-slate-500">Total Offers</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{negotiationRounds.reduce((s, r) => s + r.offers.length, 0)}</span></div>
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Closure Notes (optional)</label>
            <textarea rows={3} placeholder="Add any final notes about the negotiation outcome..." className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm resize-none focus:border-indigo-400 outline-none" />
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
