import { useState } from 'react';
import {
  Plus, Trash2, AlertTriangle, CheckCircle, ChevronDown, Save,
  History, GripVertical, Send, Layers, BookOpen,
} from 'lucide-react';
import {
  scoringModels, vendorScores, scoringPolicies,
  statusColors, formatDate, rfqs,
} from '../data/mockData';
import { SlideOver } from '../components/SlideOver';

type Criterion = {
  dimension: string;
  weight: number;
  mandatory: boolean;
};

export function ScoringModelBuilder() {
  const [selectedModelId, setSelectedModelId] = useState(scoringModels[0].id);
  const selectedModel = scoringModels.find(m => m.id === selectedModelId)!;
  const [criteria, setCriteria] = useState<Criterion[]>(selectedModel.criteria.map(c => ({ ...c })));
  const [showWeightSlider, setShowWeightSlider] = useState(false);
  const [showViolation, setShowViolation] = useState(false);
  const [showPublish, setShowPublish] = useState(false);
  const [showAssignment, setShowAssignment] = useState(false);
  const [showVersionHistory, setShowVersionHistory] = useState(false);

  const totalWeight = criteria.reduce((s, c) => s + c.weight, 0);
  const isViolation = totalWeight !== 100;

  const activePolicy = scoringPolicies.find(p =>
    p.assignedCategories.some(cat => selectedModel.assignedCategories.includes(cat))
  );

  const policyBreaches = activePolicy
    ? activePolicy.dimensions.filter(pd => {
        const c = criteria.find(cr => cr.dimension === pd.name);
        return c && (c.weight < pd.weightMin || c.weight > pd.weightMax);
      })
    : [];

  const updateWeight = (dim: string, val: number) => {
    setCriteria(prev => prev.map(c => c.dimension === dim ? { ...c, weight: val } : c));
  };

  const selectModel = (id: string) => {
    setSelectedModelId(id);
    const m = scoringModels.find(mdl => mdl.id === id)!;
    setCriteria(m.criteria.map(c => ({ ...c })));
  };

  const getWeightedScore = (vendorId: string) => {
    const vs = vendorScores.find(v => v.vendorId === vendorId);
    if (!vs) return 0;
    const dimMap: Record<string, number> = {
      'Price/LCC': vs.price,
      'Delivery': vs.delivery,
      'Quality': vs.quality,
      'Risk': vs.risk,
      'Sustainability': vs.sustainability,
    };
    return criteria.reduce((total, c) => total + ((dimMap[c.dimension] ?? 0) * c.weight / 100), 0);
  };

  const rankedVendors = [...vendorScores]
    .map(v => ({ ...v, weighted: getWeightedScore(v.vendorId) }))
    .sort((a, b) => b.weighted - a.weighted);

  const affectedRfqs = rfqs.filter(r =>
    selectedModel.assignedCategories.includes(r.category)
  );

  const allCategories = ['IT Hardware', 'Network Equipment', 'General Supplies', 'Software', 'Professional Services', 'Furniture', 'Facilities', 'IT Infrastructure'];
  const [assignedCats, setAssignedCats] = useState<string[]>(selectedModel.assignedCategories);

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-slate-900">Scoring Model Builder</h1>
            <p className="text-slate-500 text-xs mt-0.5">Configure evaluation criteria, weights and governance rules</p>
          </div>
          <div className="flex items-center gap-2">
            <select
              value={selectedModelId}
              onChange={e => selectModel(e.target.value)}
              className="border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-600 bg-white"
            >
              {scoringModels.map(m => (
                <option key={m.id} value={m.id}>{m.name} (v{m.version})</option>
              ))}
            </select>
            <button onClick={() => setShowVersionHistory(true)} className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <History size={14} />
              v{selectedModel.version}
            </button>
            <button onClick={() => setShowAssignment(true)} className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <Layers size={14} />
              Categories
            </button>
            {(isViolation || policyBreaches.length > 0) && (
              <button onClick={() => setShowViolation(true)} className="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 rounded-lg px-3 py-2 text-sm hover:bg-red-100">
                <AlertTriangle size={14} />
                {policyBreaches.length > 0 ? 'Policy Breach' : 'Weight Error'}
              </button>
            )}
            <button
              onClick={() => setShowPublish(true)}
              className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm"
              style={{ fontWeight: 500 }}
            >
              <Send size={14} />
              Publish
            </button>
          </div>
        </div>

        {/* Weight Total Bar */}
        <div className={`mt-4 flex items-center gap-3 px-4 py-2.5 rounded-xl border ${isViolation ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200'}`}>
          {isViolation ? <AlertTriangle size={15} className="text-red-500" /> : <CheckCircle size={15} className="text-emerald-500" />}
          <span className={`text-sm ${isViolation ? 'text-red-700' : 'text-emerald-700'}`} style={{ fontWeight: 600 }}>
            Total Weight: {totalWeight}%
          </span>
          <span className={`text-xs ${isViolation ? 'text-red-600' : 'text-emerald-600'}`}>
            {isViolation ? `(must equal 100% — ${totalWeight > 100 ? `${totalWeight - 100}% over` : `${100 - totalWeight}% under`})` : 'Weights are balanced'}
          </span>
          {isViolation && (
            <button onClick={() => setShowWeightSlider(true)} className="ml-auto text-xs bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700" style={{ fontWeight: 500 }}>
              Auto-Balance
            </button>
          )}
          <div className="ml-auto w-48 h-2 bg-white rounded-full overflow-hidden border border-slate-200">
            <div className={`h-full rounded-full transition-all ${isViolation ? (totalWeight > 100 ? 'bg-red-500' : 'bg-amber-500') : 'bg-emerald-500'}`} style={{ width: `${Math.min(totalWeight, 100)}%` }} />
          </div>
        </div>
      </div>

      <div className="flex-1 overflow-hidden flex">
        {/* Left: Criteria + Policy */}
        <div className="flex-1 overflow-auto p-5 space-y-4">
          {/* Criteria Editor */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
              <h3 className="text-slate-900">Evaluation Criteria</h3>
              <button className="flex items-center gap-1.5 text-indigo-600 text-xs hover:text-indigo-700" style={{ fontWeight: 500 }}>
                <Plus size={13} />
                Add Criterion
              </button>
            </div>
            <div>
              {criteria.map(c => {
                const policyDim = activePolicy?.dimensions.find(d => d.name === c.dimension);
                const breached = policyDim && (c.weight < policyDim.weightMin || c.weight > policyDim.weightMax);
                return (
                  <div key={c.dimension} className={`flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 last:border-0 transition-colors hover:bg-slate-50/50`}>
                    <GripVertical size={14} className="text-slate-300 cursor-grab flex-shrink-0" />
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <span className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{c.dimension}</span>
                        {c.mandatory && <span className="text-xs bg-red-50 text-red-600 px-1.5 py-0.5 rounded border border-red-200" style={{ fontWeight: 500 }}>Required</span>}
                        {breached && <span className="text-xs bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded border border-amber-200" style={{ fontWeight: 500 }}>Out of policy</span>}
                      </div>
                      {policyDim && (
                        <span className="text-slate-400 text-xs">Policy range: {policyDim.weightMin}% – {policyDim.weightMax}%</span>
                      )}
                    </div>
                    <div className="flex items-center gap-3 w-64">
                      <input
                        type="range" min={0} max={50} step={1}
                        value={c.weight}
                        onChange={e => updateWeight(c.dimension, parseInt(e.target.value))}
                        className="flex-1 accent-indigo-600"
                      />
                      <div className="w-14 text-center">
                        <span className={`text-sm ${breached ? 'text-red-600' : 'text-slate-800'}`} style={{ fontWeight: 600 }}>{c.weight}%</span>
                      </div>
                    </div>
                    <button className="text-slate-300 hover:text-red-400 flex-shrink-0">
                      <Trash2 size={14} />
                    </button>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Version Notes */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
              <h3 className="text-slate-900">Version Notes</h3>
              <span className="text-xs text-slate-400">v{selectedModel.version} · Updated {formatDate(selectedModel.updatedAt)}</span>
            </div>
            <div className="p-5">
              <textarea
                rows={3}
                placeholder="Describe what changed in this version..."
                className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none"
                defaultValue={`v${selectedModel.version} — standard weighting for ${selectedModel.assignedCategories.join(', ')}`}
              />
            </div>
          </div>

          {/* RFQ/Category Assignment */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
              <h3 className="text-slate-900">Category Assignments</h3>
              <button onClick={() => setShowAssignment(true)} className="flex items-center gap-1.5 text-indigo-600 text-xs hover:text-indigo-700" style={{ fontWeight: 500 }}>
                <Layers size={13} />
                Manage
              </button>
            </div>
            <div className="p-5">
              {selectedModel.assignedCategories.length > 0 ? (
                <div className="flex flex-wrap gap-2">
                  {selectedModel.assignedCategories.map(cat => (
                    <span key={cat} className="text-xs bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg border border-indigo-200" style={{ fontWeight: 500 }}>{cat}</span>
                  ))}
                </div>
              ) : (
                <p className="text-slate-400 text-sm">No categories assigned yet.</p>
              )}
              {affectedRfqs.length > 0 && (
                <div className="mt-3 pt-3 border-t border-slate-100">
                  <span className="text-xs text-slate-500" style={{ fontWeight: 500 }}>Affects {affectedRfqs.length} RFQ(s):</span>
                  <div className="mt-1 space-y-1">
                    {affectedRfqs.slice(0, 3).map(r => (
                      <div key={r.id} className="flex items-center gap-2 text-xs text-slate-600">
                        <span className={`px-1.5 py-0.5 rounded ${statusColors[r.status]}`} style={{ fontWeight: 500 }}>{r.status}</span>
                        <span>{r.id} — {r.title}</span>
                      </div>
                    ))}
                    {affectedRfqs.length > 3 && <span className="text-xs text-slate-400">+{affectedRfqs.length - 3} more</span>}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Right: Live Preview */}
        <div className="w-80 flex-shrink-0 border-l border-slate-200 bg-white overflow-auto">
          <div className="px-4 py-3 border-b border-slate-100 sticky top-0 bg-white z-10">
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" />
              <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Live Score Preview</span>
            </div>
            <p className="text-slate-400 text-xs mt-0.5">{selectedModel.name} · {rankedVendors.length} vendors</p>
          </div>
          <div className="p-4 space-y-3">
            {rankedVendors.map((vendor, i) => {
              const isWinner = i === 0;
              const dimMap: Record<string, number> = {
                'Price/LCC': vendor.price,
                'Delivery': vendor.delivery,
                'Quality': vendor.quality,
                'Risk': vendor.risk,
                'Sustainability': vendor.sustainability,
              };
              return (
                <div key={vendor.vendorId} className={`rounded-xl border p-4 ${isWinner ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200'}`}>
                  <div className="flex items-center justify-between mb-2">
                    <div className="flex items-center gap-2">
                      <span className="text-slate-500 text-xs" style={{ fontWeight: 600 }}>#{i + 1}</span>
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>{vendor.name}</span>
                    </div>
                    <span className={`text-lg ${isWinner ? 'text-emerald-700' : 'text-slate-800'}`} style={{ fontWeight: 800 }}>{vendor.weighted.toFixed(1)}</span>
                  </div>
                  <div className="h-2 bg-slate-100 rounded-full overflow-hidden mb-3">
                    <div className={`h-full rounded-full ${isWinner ? 'bg-emerald-500' : 'bg-indigo-400'}`} style={{ width: `${vendor.weighted}%` }} />
                  </div>
                  <div className="space-y-1.5">
                    {criteria.map(c => {
                      const raw = dimMap[c.dimension] ?? 0;
                      const contribution = (raw * c.weight / 100).toFixed(1);
                      return (
                        <div key={c.dimension} className="flex items-center justify-between">
                          <span className="text-slate-500 text-xs">{c.dimension}</span>
                          <div className="flex items-center gap-2">
                            <span className="text-slate-400 text-xs">{raw}/100</span>
                            <span className="text-slate-700 text-xs" style={{ fontWeight: 500 }}>+{contribution}</span>
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

      {/* SlideOver: Adjust Scoring Weights */}
      <SlideOver
        open={showWeightSlider}
        onOpenChange={setShowWeightSlider}
        title="Adjust Scoring Weights"
        description="Auto-balance weights to total 100% while preserving relative proportions."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowWeightSlider(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => {
              const total = criteria.reduce((s, c) => s + c.weight, 0);
              if (total > 0) setCriteria(prev => prev.map(c => ({ ...c, weight: Math.round(c.weight / total * 100) })));
              setShowWeightSlider(false);
            }} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply</button>
          </div>
        }
      >
        <div className="space-y-4">
          <p className="text-slate-600 text-sm">Automatically distribute weights to sum to 100% while preserving relative proportions.</p>
          <div className="space-y-3">
            {criteria.map(c => {
              const normalized = totalWeight > 0 ? Math.round(c.weight / totalWeight * 100) : 0;
              return (
                <div key={c.dimension} className="space-y-1">
                  <div className="flex items-center justify-between">
                    <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{c.dimension}</span>
                    <div className="flex items-center gap-2 text-xs">
                      <span className="text-slate-400">{c.weight}%</span>
                      <span className="text-slate-400">→</span>
                      <span className="text-indigo-600" style={{ fontWeight: 600 }}>{normalized}%</span>
                    </div>
                  </div>
                  <input
                    type="range" min={0} max={50} step={1}
                    value={c.weight}
                    onChange={e => updateWeight(c.dimension, parseInt(e.target.value))}
                    className="w-full accent-indigo-600"
                  />
                </div>
              );
            })}
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Policy Violation Warning */}
      <SlideOver
        open={showViolation}
        onOpenChange={setShowViolation}
        title="Policy Violation Warning"
        description="Current weights breach governance policy rules."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowViolation(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Dismiss</button>
            <button onClick={() => { setShowViolation(false); setShowWeightSlider(true); }} className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Auto-Balance</button>
          </div>
        }
      >
        <div className="space-y-5">
          {isViolation && (
            <div className="bg-red-50 border border-red-200 rounded-xl p-4">
              <div className="flex items-start gap-3">
                <AlertTriangle size={18} className="text-red-600 flex-shrink-0 mt-0.5" />
                <div>
                  <p className="text-red-800 text-sm" style={{ fontWeight: 600 }}>Weight Total: {totalWeight}%</p>
                  <p className="text-red-600 text-xs mt-1">Total criteria weights must equal exactly 100%.</p>
                </div>
              </div>
            </div>
          )}

          {policyBreaches.length > 0 && activePolicy && (
            <div className="space-y-3">
              <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Policy: {activePolicy.name}</h4>
              <div className="space-y-2">
                {policyBreaches.map(pd => {
                  const c = criteria.find(cr => cr.dimension === pd.name);
                  return (
                    <div key={pd.name} className="bg-amber-50 border border-amber-200 rounded-lg p-3">
                      <div className="flex items-center justify-between">
                        <span className="text-amber-800 text-sm" style={{ fontWeight: 500 }}>{pd.name}</span>
                        <span className="text-red-600 text-xs" style={{ fontWeight: 600 }}>{c?.weight}%</span>
                      </div>
                      <p className="text-amber-700 text-xs mt-1">Allowed range: {pd.weightMin}% – {pd.weightMax}%</p>
                    </div>
                  );
                })}
              </div>
              <div className="bg-slate-50 rounded-xl border border-slate-200 p-3 space-y-1">
                <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>Policy Rules:</span>
                {activePolicy.rules.map((rule, i) => (
                  <p key={i} className="text-slate-500 text-xs">• {rule}</p>
                ))}
              </div>
            </div>
          )}

          <div className="space-y-2">
            <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Current Weights</h4>
            {criteria.map(c => (
              <div key={c.dimension} className="flex justify-between items-center text-xs px-3 py-2 bg-slate-50 rounded-lg">
                <span className="text-slate-600">{c.dimension}</span>
                <span className="text-slate-800" style={{ fontWeight: 600 }}>{c.weight}%</span>
              </div>
            ))}
            <div className="border-t border-slate-200 pt-2 flex justify-between items-center px-3">
              <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>Total</span>
              <span className={`text-sm ${isViolation ? 'text-red-600' : 'text-emerald-600'}`} style={{ fontWeight: 700 }}>{totalWeight}%</span>
            </div>
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Confirm Publish */}
      <SlideOver
        open={showPublish}
        onOpenChange={setShowPublish}
        title="Confirm Publish"
        description={`Publish ${selectedModel.name} v${selectedModel.version + 1} to production.`}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowPublish(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button
              onClick={() => setShowPublish(false)}
              disabled={isViolation || policyBreaches.length > 0}
              className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm ${isViolation || policyBreaches.length > 0 ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 text-white'}`}
              style={{ fontWeight: 500 }}
            >
              <Send size={14} />
              Publish
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="bg-slate-50 rounded-xl border border-slate-200 p-4 space-y-2">
            <div className="flex justify-between text-xs">
              <span className="text-slate-500" style={{ fontWeight: 500 }}>Model</span>
              <span className="text-slate-800" style={{ fontWeight: 600 }}>{selectedModel.name}</span>
            </div>
            <div className="flex justify-between text-xs">
              <span className="text-slate-500" style={{ fontWeight: 500 }}>New Version</span>
              <span className="text-slate-800" style={{ fontWeight: 600 }}>v{selectedModel.version + 1}</span>
            </div>
            <div className="flex justify-between text-xs">
              <span className="text-slate-500" style={{ fontWeight: 500 }}>Weight Total</span>
              <span className={`${isViolation ? 'text-red-600' : 'text-emerald-600'}`} style={{ fontWeight: 600 }}>{totalWeight}%</span>
            </div>
          </div>

          {affectedRfqs.length > 0 && (
            <div className="space-y-3">
              <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Affected RFQs ({affectedRfqs.length})</h4>
              <div className="space-y-2">
                {affectedRfqs.map(r => (
                  <div key={r.id} className="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{r.id}</span>
                      <p className="text-slate-500 text-xs">{r.title}</p>
                    </div>
                    <span className={`text-xs px-1.5 py-0.5 rounded ${statusColors[r.status]}`} style={{ fontWeight: 500 }}>{r.status}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {(isViolation || policyBreaches.length > 0) && (
            <div className="bg-red-50 border border-red-200 rounded-xl p-4">
              <div className="flex items-start gap-3">
                <AlertTriangle size={18} className="text-red-600 flex-shrink-0 mt-0.5" />
                <div>
                  <p className="text-red-800 text-sm" style={{ fontWeight: 600 }}>Cannot publish</p>
                  <p className="text-red-600 text-xs mt-1">Resolve weight total and policy violations before publishing.</p>
                </div>
              </div>
            </div>
          )}
        </div>
      </SlideOver>

      {/* SlideOver: Model Assignment */}
      <SlideOver
        open={showAssignment}
        onOpenChange={setShowAssignment}
        title="Model Assignment"
        description="Assign this scoring model to procurement categories."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowAssignment(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowAssignment(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Save Assignment</button>
          </div>
        }
      >
        <div className="space-y-4">
          <p className="text-slate-600 text-sm">Select which procurement categories should use <strong>{selectedModel.name}</strong> for scoring.</p>
          <div className="space-y-2">
            {allCategories.map(cat => {
              const checked = assignedCats.includes(cat);
              const otherModel = scoringModels.find(m => m.id !== selectedModelId && m.assignedCategories.includes(cat));
              return (
                <label key={cat} className={`flex items-center justify-between p-3 rounded-lg border cursor-pointer transition-colors ${checked ? 'border-indigo-200 bg-indigo-50' : 'border-slate-200 hover:bg-slate-50'}`}>
                  <div className="flex items-center gap-3">
                    <input
                      type="checkbox"
                      checked={checked}
                      onChange={e => setAssignedCats(prev => e.target.checked ? [...prev, cat] : prev.filter(c => c !== cat))}
                      className="accent-indigo-600"
                    />
                    <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{cat}</span>
                  </div>
                  {otherModel && (
                    <span className="text-xs text-slate-400">Currently: {otherModel.name}</span>
                  )}
                </label>
              );
            })}
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: View Version History */}
      <SlideOver
        open={showVersionHistory}
        onOpenChange={setShowVersionHistory}
        title="Version History"
        description={`${selectedModel.name} — all published versions.`}
        width="md"
      >
        <div className="space-y-3">
          {Array.from({ length: selectedModel.version }, (_, i) => selectedModel.version - i).map(ver => {
            const isCurrent = ver === selectedModel.version;
            return (
              <div key={ver} className={`rounded-xl border p-4 ${isCurrent ? 'border-indigo-200 bg-indigo-50' : 'border-slate-200'}`}>
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>v{ver}.0</span>
                    {isCurrent && <span className="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>Current</span>}
                  </div>
                  {!isCurrent && <button className="text-indigo-600 text-xs hover:underline" style={{ fontWeight: 500 }}>Restore</button>}
                </div>
                <p className="text-slate-500 text-xs">
                  {isCurrent ? `Updated ${formatDate(selectedModel.updatedAt)}` : `Created ${formatDate(selectedModel.createdAt)}`}
                </p>
                {isCurrent && (
                  <div className="mt-2 flex flex-wrap gap-1.5">
                    {criteria.map(c => (
                      <span key={c.dimension} className="text-xs bg-white border border-slate-200 text-slate-600 px-2 py-0.5 rounded">{c.dimension}: {c.weight}%</span>
                    ))}
                  </div>
                )}
                {!isCurrent && (
                  <div className="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-2 text-xs text-amber-700">
                    <BookOpen size={11} className="inline mr-1" />
                    Diff: Weight adjustments applied vs previous version
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </SlideOver>
    </div>
  );
}
