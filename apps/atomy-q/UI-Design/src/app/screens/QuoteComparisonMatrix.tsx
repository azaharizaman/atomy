import { useState } from 'react';
import React from 'react';
import {
  Download, Filter, ChevronDown, ChevronRight,
  AlertTriangle, Info, Star, ArrowUpDown, Play, Lock, Unlock,
  Eye, Zap, FileText, Settings2,
} from 'lucide-react';
import {
  comparisonMatrix, comparisonRuns, vendors, vendorScores,
  scoringModels, formatCurrency, getVendorById, statusColors,
} from '../data/mockData';
import { SlideOver } from '../components/SlideOver';

const run = comparisonRuns.find(r => r.id === comparisonMatrix.runId)!;
const matrixVendorIds = Object.keys(comparisonMatrix.vendorTotals);
const matrixVendors = matrixVendorIds.map(id => getVendorById(id)!);

function getBestVendorForPrices(prices: Record<string, number>) {
  const entries = Object.entries(prices);
  if (!entries.length) return null;
  return entries.reduce((a, b) => (a[1] < b[1] ? a : b))[0];
}

function getDeltaPct(price: number, best: number) {
  if (best === 0) return 0;
  return ((price - best) / best) * 100;
}

export function QuoteComparisonMatrix() {
  const [collapsed, setCollapsed] = useState<Record<string, boolean>>({});
  const [highlightBest, setHighlightBest] = useState(true);
  const [selectedScoringModel, setSelectedScoringModel] = useState(run.scoringModelId);

  const [showRunEngine, setShowRunEngine] = useState(false);
  const [showLineDetail, setShowLineDetail] = useState<string | null>(null);
  const [showScoringModelPicker, setShowScoringModelPicker] = useState(false);
  const [showUnlockConfirm, setShowUnlockConfirm] = useState(false);

  const toggleCat = (name: string) => setCollapsed(p => ({ ...p, [name]: !p[name] }));

  const totals = comparisonMatrix.vendorTotals;
  const bestTotal = Math.min(...Object.values(totals));
  const isLocked = run.status === 'locked';
  const currentModel = scoringModels.find(m => m.id === selectedScoringModel);

  const lineDetailItem = showLineDetail
    ? comparisonMatrix.categories.flatMap(c => c.items).find(i => i.lineItemId === showLineDetail)
    : null;

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Page Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
              <span>{comparisonMatrix.rfqId}</span>
              <span>·</span>
              <span className={`inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs ${run.mode === 'final' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}`} style={{ fontWeight: 600 }}>
                {run.mode === 'final' ? <Lock size={10} /> : <Eye size={10} />}
                {run.mode === 'final' ? 'Final Run' : 'Preview'}
              </span>
              {isLocked && (
                <span className={`inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs ${statusColors['locked']}`} style={{ fontWeight: 500 }}>
                  <Lock size={10} /> Locked
                </span>
              )}
            </div>
            <h1 className="text-slate-900">Quote Comparison Matrix</h1>
            <p className="text-slate-500 text-xs mt-0.5">
              {matrixVendors.length} vendors · {comparisonMatrix.categories.reduce((s, c) => s + c.items.length, 0)} line items · {comparisonMatrix.categories.length} categories · Model: {currentModel?.name}
            </p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowScoringModelPicker(true)}
              className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"
            >
              <Settings2 size={14} />
              {currentModel?.name ?? 'Select Model'}
              <ChevronDown size={12} />
            </button>
            {isLocked ? (
              <button
                onClick={() => setShowUnlockConfirm(true)}
                className="flex items-center gap-2 border border-amber-200 bg-amber-50 text-amber-700 rounded-lg px-3 py-2 text-sm hover:bg-amber-100"
              >
                <Unlock size={14} />
                Unlock
              </button>
            ) : (
              <button
                onClick={() => setShowRunEngine(true)}
                className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"
              >
                <Play size={14} />
                Run Engine
              </button>
            )}
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <Filter size={14} />
              Filter
            </button>
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <Download size={14} />
              Export
            </button>
            <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Star size={14} />
              Score & Award
            </button>
          </div>
        </div>

        {/* Vendor Summary Cards */}
        <div className={`grid gap-3 mt-4`} style={{ gridTemplateColumns: `repeat(${matrixVendors.length}, 1fr)` }}>
          {matrixVendors.map(v => {
            const total = totals[v.id];
            const isLowest = total === bestTotal;
            const delta = getDeltaPct(total, bestTotal);
            const score = vendorScores.find(s => s.vendorId === v.id);
            return (
              <div key={v.id} className={`rounded-lg border px-3 py-2.5 ${isLowest ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white'}`}>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-xs text-slate-700" style={{ fontWeight: 600 }}>{v.name}</span>
                  {isLowest && <span className="text-xs bg-emerald-600 text-white px-1.5 py-0.5 rounded" style={{ fontWeight: 600 }}>BEST</span>}
                  {score?.recommended && !isLowest && <span className="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>★ Recommended</span>}
                </div>
                <div className={`text-lg ${isLowest ? 'text-emerald-700' : 'text-slate-900'}`} style={{ fontWeight: 700 }}>
                  {formatCurrency(total)}
                </div>
                <div className="flex items-center gap-2 mt-0.5">
                  <span className="text-xs text-slate-500">Score: {score?.overall ?? '—'}/100</span>
                  {delta > 0 && <span className="text-xs text-amber-600">+{delta.toFixed(1)}%</span>}
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Matrix */}
      <div className="flex-1 overflow-auto">
        <table className="w-full border-collapse" style={{ minWidth: '900px' }}>
          <thead className="sticky top-0 z-20 bg-white border-b-2 border-slate-200">
            <tr>
              <th className="sticky left-0 z-30 bg-white border-r border-slate-200 text-left px-4 py-3 w-72">
                <div className="flex items-center gap-1 text-slate-500 text-xs" style={{ fontWeight: 600 }}>
                  <span>LINE ITEM</span>
                  <ArrowUpDown size={11} className="cursor-pointer" />
                </div>
              </th>
              {matrixVendors.map(v => {
                const total = totals[v.id];
                const isLowest = total === bestTotal;
                return (
                  <th key={v.id} className={`border-r border-slate-200 px-4 py-3 text-center text-xs ${isLowest ? 'bg-emerald-50' : 'bg-white'}`} style={{ minWidth: 180 }}>
                    <div className={`${isLowest ? 'text-emerald-700' : 'text-slate-700'}`} style={{ fontWeight: 600 }}>{v.name}</div>
                    <div className="text-slate-400 text-xs mt-0.5" style={{ fontWeight: 400 }}>Total Price</div>
                  </th>
                );
              })}
            </tr>
          </thead>
          <tbody>
            {comparisonMatrix.categories.map(cat => {
              const isColl = collapsed[cat.name];
              const catTotals = matrixVendorIds.reduce<Record<string, number>>((acc, vid) => {
                acc[vid] = cat.items.reduce((s, item) => s + (item.prices[vid] ?? 0), 0);
                return acc;
              }, {});
              return (
                <React.Fragment key={cat.name}>
                  <tr
                    className="bg-slate-100 border-y border-slate-200 cursor-pointer hover:bg-slate-200/60"
                    onClick={() => toggleCat(cat.name)}
                  >
                    <td className="sticky left-0 z-10 bg-slate-100 border-r border-slate-200 px-4 py-2">
                      <div className="flex items-center gap-2">
                        {isColl ? <ChevronRight size={14} className="text-slate-500" /> : <ChevronDown size={14} className="text-slate-500" />}
                        <span className="text-slate-700 text-xs" style={{ fontWeight: 700 }}>{cat.name}</span>
                        <span className="text-slate-400 text-xs">({cat.items.length} items)</span>
                      </div>
                    </td>
                    {matrixVendors.map(v => (
                      <td key={v.id} className="bg-slate-100 border-r border-slate-200 px-4 py-2 text-center">
                        <span className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>{formatCurrency(catTotals[v.id])}</span>
                      </td>
                    ))}
                  </tr>

                  {!isColl && cat.items.map(item => {
                    const bestVid = getBestVendorForPrices(item.prices);
                    const bestPrice = bestVid ? item.prices[bestVid] : 0;
                    return (
                      <tr key={item.lineItemId} className="border-b border-slate-100 hover:bg-blue-50/30 transition-colors">
                        <td className="sticky left-0 z-10 bg-white border-r border-slate-100 px-4 py-3 hover:bg-blue-50/30">
                          <button
                            onClick={() => setShowLineDetail(item.lineItemId)}
                            className="flex items-center gap-2 text-slate-800 text-sm hover:text-indigo-600 group"
                          >
                            <span>{item.description}</span>
                            <Info size={12} className="text-slate-300 group-hover:text-indigo-400" />
                          </button>
                        </td>
                        {matrixVendors.map(v => {
                          const price = item.prices[v.id];
                          const isBest = v.id === bestVid;
                          const delta = price != null && bestPrice ? getDeltaPct(price, bestPrice) : null;
                          return (
                            <td key={v.id} className={`border-r border-slate-100 px-4 py-3 text-center ${isBest && highlightBest ? 'bg-emerald-50' : ''}`}>
                              {price == null ? (
                                <div className="flex flex-col items-center gap-1">
                                  <span className="text-red-400 text-xs" style={{ fontWeight: 500 }}>—</span>
                                  <span className="text-red-400 text-xs bg-red-50 px-1.5 py-0.5 rounded border border-red-200" style={{ fontWeight: 500 }}>Missing</span>
                                </div>
                              ) : (
                                <div className="flex flex-col items-center gap-0.5">
                                  <span className={`text-sm ${isBest ? 'text-emerald-700' : 'text-slate-800'}`} style={{ fontWeight: isBest ? 700 : 500 }}>
                                    {formatCurrency(price)}
                                  </span>
                                  {isBest ? (
                                    <span className="text-emerald-600 text-xs" style={{ fontWeight: 600 }}>✓ Best</span>
                                  ) : delta != null && delta > 0 ? (
                                    <span className="text-amber-600 text-xs" style={{ fontWeight: 500 }}>+{delta.toFixed(1)}%</span>
                                  ) : null}
                                </div>
                              )}
                            </td>
                          );
                        })}
                      </tr>
                    );
                  })}
                </React.Fragment>
              );
            })}

            {/* Summary Row */}
            <tr className="border-t-2 border-slate-300 bg-slate-900 sticky bottom-0 z-10">
              <td className="sticky left-0 z-10 bg-slate-900 border-r border-slate-700 px-4 py-3">
                <span className="text-white text-sm" style={{ fontWeight: 700 }}>GRAND TOTAL</span>
              </td>
              {matrixVendors.map(v => {
                const total = totals[v.id];
                const isLowest = total === bestTotal;
                const delta = getDeltaPct(total, bestTotal);
                return (
                  <td key={v.id} className="border-r border-slate-700 px-4 py-3 text-center">
                    <div className={`text-sm ${isLowest ? 'text-emerald-400' : 'text-white'}`} style={{ fontWeight: 700 }}>
                      {formatCurrency(total)}
                    </div>
                    {isLowest ? (
                      <div className="text-emerald-400 text-xs mt-0.5" style={{ fontWeight: 600 }}>LOWEST</div>
                    ) : (
                      <div className="text-amber-400 text-xs mt-0.5" style={{ fontWeight: 500 }}>+{delta.toFixed(1)}%</div>
                    )}
                  </td>
                );
              })}
            </tr>
          </tbody>
        </table>

        {/* Terms Comparison */}
        <div className="bg-white border-t-2 border-slate-200 mt-0">
          <div className="px-4 py-3 border-b border-slate-100 bg-slate-50">
            <span className="text-slate-700 text-xs" style={{ fontWeight: 700 }}>COMMERCIAL TERMS COMPARISON</span>
          </div>
          <table className="w-full" style={{ minWidth: '900px' }}>
            <tbody>
              {comparisonMatrix.terms.map((term, i) => (
                <tr key={term.label} className={`border-b border-slate-100 ${i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'}`}>
                  <td className="sticky left-0 bg-inherit border-r border-slate-100 px-4 py-2.5 w-72">
                    <span className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>{term.label}</span>
                  </td>
                  {matrixVendors.map(v => (
                    <td key={v.id} className="border-r border-slate-100 px-4 py-2.5 text-center">
                      <span className="text-slate-700 text-xs">{term.values[v.id]}</span>
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* SlideOver: Run Comparison Engine */}
      <SlideOver
        open={showRunEngine}
        onOpenChange={setShowRunEngine}
        title="Run Comparison Engine"
        description="Generate a comparison using the selected scoring model."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowRunEngine(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Zap size={14} />
              Execute Run
            </button>
          </div>
        }
      >
        <div className="space-y-6">
          <div className="space-y-3">
            <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Run Mode</h4>
            <div className="grid grid-cols-2 gap-3">
              <div className="rounded-xl border-2 border-amber-200 bg-amber-50 p-4 cursor-pointer">
                <div className="flex items-center gap-2 mb-1">
                  <Eye size={16} className="text-amber-600" />
                  <span className="text-amber-800 text-sm" style={{ fontWeight: 600 }}>Preview Run</span>
                </div>
                <p className="text-amber-700 text-xs">Draft comparison — does not lock data. Results can be discarded.</p>
              </div>
              <div className="rounded-xl border border-slate-200 bg-white p-4 cursor-pointer hover:border-emerald-300">
                <div className="flex items-center gap-2 mb-1">
                  <Lock size={16} className="text-emerald-600" />
                  <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>Final Run</span>
                </div>
                <p className="text-slate-500 text-xs">Locks normalization data. Triggers approval workflow if required.</p>
              </div>
            </div>
          </div>

          <div className="space-y-3">
            <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Scoring Model</h4>
            <div className="bg-slate-50 rounded-xl border border-slate-200 p-3">
              <div className="flex items-center justify-between">
                <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{currentModel?.name}</span>
                <span className={`text-xs px-1.5 py-0.5 rounded ${statusColors[currentModel?.status ?? 'draft']}`} style={{ fontWeight: 500 }}>{currentModel?.status}</span>
              </div>
              <div className="flex flex-wrap gap-2 mt-2">
                {currentModel?.criteria.map(c => (
                  <span key={c.dimension} className="text-xs bg-white border border-slate-200 text-slate-600 px-2 py-0.5 rounded">{c.dimension}: {c.weight}%</span>
                ))}
              </div>
            </div>
          </div>

          <div className="space-y-3">
            <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Readiness Checks</h4>
            <div className="space-y-2">
              {[
                { ok: true, label: `${matrixVendors.length} vendor quotes accepted` },
                { ok: true, label: 'Normalization complete for all line items' },
                { ok: run.status !== 'stale', label: 'No stale data detected' },
                { ok: currentModel?.status === 'published', label: `Scoring model "${currentModel?.name}" is published` },
              ].map((check, i) => (
                <div key={i} className="flex items-center gap-2">
                  {check.ok ? (
                    <div className="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center"><span className="text-emerald-600 text-xs">✓</span></div>
                  ) : (
                    <div className="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center"><AlertTriangle size={12} className="text-red-500" /></div>
                  )}
                  <span className={`text-xs ${check.ok ? 'text-slate-600' : 'text-red-600'}`}>{check.label}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Line Detail Note */}
      <SlideOver
        open={!!showLineDetail}
        onOpenChange={open => { if (!open) setShowLineDetail(null); }}
        title="Line Item Detail"
        description={lineDetailItem?.description ?? ''}
        width="md"
      >
        {lineDetailItem && (
          <div className="space-y-5">
            <div className="space-y-3">
              <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Price per Vendor</h4>
              <div className="space-y-2">
                {matrixVendors.map(v => {
                  const price = lineDetailItem.prices[v.id];
                  const isBest = v.id === lineDetailItem.bestVendor;
                  return (
                    <div key={v.id} className={`flex items-center justify-between p-3 rounded-lg border ${isBest ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200'}`}>
                      <div className="flex items-center gap-2">
                        <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{v.name}</span>
                        {isBest && <span className="text-xs bg-emerald-600 text-white px-1.5 py-0.5 rounded" style={{ fontWeight: 600 }}>Best</span>}
                      </div>
                      <span className={`text-sm ${isBest ? 'text-emerald-700' : 'text-slate-800'}`} style={{ fontWeight: 600 }}>
                        {price != null ? formatCurrency(price) : '—'}
                      </span>
                    </div>
                  );
                })}
              </div>
            </div>

            <div className="space-y-3">
              <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Extraction Evidence</h4>
              <div className="bg-slate-50 rounded-xl border border-slate-200 p-4 text-xs text-slate-600 space-y-2">
                <div className="flex justify-between">
                  <span style={{ fontWeight: 500 }}>Source Document</span>
                  <span className="text-indigo-600 hover:underline cursor-pointer">View PDF</span>
                </div>
                <div className="flex justify-between">
                  <span style={{ fontWeight: 500 }}>AI Confidence</span>
                  <span>92%</span>
                </div>
                <div className="flex justify-between">
                  <span style={{ fontWeight: 500 }}>Original Description</span>
                  <span className="text-right max-w-[200px]">{lineDetailItem.description}</span>
                </div>
                <div className="flex justify-between">
                  <span style={{ fontWeight: 500 }}>Normalized Description</span>
                  <span className="text-right max-w-[200px]">{lineDetailItem.description}</span>
                </div>
              </div>
            </div>

            <div className="space-y-3">
              <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Notes</h4>
              <textarea
                rows={3}
                placeholder="Add a note about this line item..."
                className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none"
              />
            </div>
          </div>
        )}
      </SlideOver>

      {/* SlideOver: Select Scoring Model */}
      <SlideOver
        open={showScoringModelPicker}
        onOpenChange={setShowScoringModelPicker}
        title="Select Scoring Model"
        description="Choose which scoring model to apply to this comparison."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowScoringModelPicker(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowScoringModelPicker(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply Model</button>
          </div>
        }
      >
        <div className="space-y-3">
          {scoringModels.map(model => (
            <div
              key={model.id}
              onClick={() => setSelectedScoringModel(model.id)}
              className={`rounded-xl border p-4 cursor-pointer transition-all ${selectedScoringModel === model.id ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 hover:border-slate-300'}`}
            >
              <div className="flex items-center justify-between mb-1">
                <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{model.name}</span>
                <span className={`text-xs px-1.5 py-0.5 rounded ${statusColors[model.status]}`} style={{ fontWeight: 500 }}>{model.status}</span>
              </div>
              <div className="flex flex-wrap gap-1.5 mt-2">
                {model.criteria.map(c => (
                  <span key={c.dimension} className="text-xs bg-white border border-slate-200 text-slate-600 px-2 py-0.5 rounded">{c.dimension}: {c.weight}%</span>
                ))}
              </div>
              {model.assignedCategories.length > 0 && (
                <p className="text-slate-400 text-xs mt-2">Categories: {model.assignedCategories.join(', ')}</p>
              )}
            </div>
          ))}
        </div>
      </SlideOver>

      {/* SlideOver: Unlock Comparison */}
      <SlideOver
        open={showUnlockConfirm}
        onOpenChange={setShowUnlockConfirm}
        title="Unlock Comparison"
        description="This will revert the comparison to editable state."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowUnlockConfirm(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowUnlockConfirm(false)} className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><Unlock size={14} /> Unlock</span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div className="flex items-start gap-3">
              <AlertTriangle size={18} className="text-amber-600 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-amber-800 text-sm" style={{ fontWeight: 600 }}>Are you sure?</p>
                <p className="text-amber-700 text-xs mt-1">Unlocking this comparison will:</p>
                <ul className="text-amber-700 text-xs mt-1 space-y-1 list-disc list-inside">
                  <li>Allow edits to normalization data</li>
                  <li>Invalidate the current scoring results</li>
                  <li>Require a new comparison run before approval</li>
                  <li>Log this action in the decision trail</li>
                </ul>
              </div>
            </div>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Reason for unlocking</label>
            <textarea rows={3} placeholder="Provide a reason..." className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none" />
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
