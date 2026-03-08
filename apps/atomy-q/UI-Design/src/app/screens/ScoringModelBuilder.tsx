import { useState } from 'react';
import { Plus, Trash2, AlertTriangle, CheckCircle, ChevronDown, Save, History, X, GripVertical, Info } from 'lucide-react';

interface Criterion {
  id: string;
  name: string;
  category: string;
  weight: number;
  description: string;
  enabled: boolean;
}

const initialCriteria: Criterion[] = [
  { id: 'c1', name: 'Price Competitiveness', category: 'Commercial', weight: 35, description: 'Total landed cost vs. market benchmark', enabled: true },
  { id: 'c2', name: 'Delivery Performance', category: 'Operational', weight: 20, description: 'Lead time and on-time delivery history', enabled: true },
  { id: 'c3', name: 'Quality & Compliance', category: 'Quality', weight: 20, description: 'Certifications, defect rates, audit scores', enabled: true },
  { id: 'c4', name: 'Vendor Financial Health', category: 'Risk', weight: 10, description: 'Credit score, revenue stability, insurance', enabled: true },
  { id: 'c5', name: 'Sustainability Score', category: 'ESG', weight: 10, description: 'Carbon footprint, ethical sourcing rating', enabled: true },
  { id: 'c6', name: 'Innovation Capability', category: 'Strategic', weight: 5, description: 'R&D investment, product roadmap alignment', enabled: true },
];

interface Rule {
  id: string;
  type: 'constraint' | 'policy';
  label: string;
  condition: string;
  action: string;
  active: boolean;
}

const rules: Rule[] = [
  { id: 'r1', type: 'constraint', label: 'Max Unit Price', condition: 'Unit Price > $10,000', action: 'Disqualify vendor', active: true },
  { id: 'r2', type: 'policy', label: 'Preferred Vendor Bonus', condition: 'Vendor in Preferred List', action: '+5 score bonus', active: true },
  { id: 'r3', type: 'constraint', label: 'Min Sustainability', condition: 'ESG Score < 40', action: 'Flag for review', active: true },
  { id: 'r4', type: 'policy', label: 'Local Vendor Priority', condition: 'Vendor country = AU', action: '+3 score bonus', active: false },
];

const versions = [
  { id: 'v3', label: 'v3.0 (Current)', date: 'Mar 6, 2026', author: 'Alex Kumar', active: true },
  { id: 'v2', label: 'v2.1', date: 'Feb 12, 2026', author: 'Sam Chen', active: false },
  { id: 'v1', label: 'v1.0', date: 'Jan 5, 2026', author: 'Alex Kumar', active: false },
];

const previewVendors = [
  { name: 'PrimeSource Co.', scores: { c1: 92, c2: 88, c3: 95, c4: 87, c5: 78, c6: 72 } },
  { name: 'TechCorp Solutions', scores: { c1: 84, c2: 91, c3: 88, c4: 79, c5: 82, c6: 88 } },
  { name: 'FastParts Ltd.', scores: { c1: 88, c2: 76, c3: 74, c4: 81, c5: 65, c6: 60 } },
];

function getWeightedScore(vendor: typeof previewVendors[0], criteria: Criterion[]) {
  return criteria.reduce((total, c) => {
    if (!c.enabled) return total;
    const raw = vendor.scores[c.id as keyof typeof vendor.scores] || 0;
    return total + (raw * c.weight / 100);
  }, 0);
}

export function ScoringModelBuilder() {
  const [criteria, setCriteria] = useState<Criterion[]>(initialCriteria);
  const [showWeightModal, setShowWeightModal] = useState(false);
  const [showVersionModal, setShowVersionModal] = useState(false);
  const [showViolation, setShowViolation] = useState(false);

  const totalWeight = criteria.filter(c => c.enabled).reduce((s, c) => s + c.weight, 0);
  const isViolation = totalWeight !== 100;

  const updateWeight = (id: string, val: number) => {
    setCriteria(prev => prev.map(c => c.id === id ? { ...c, weight: val } : c));
  };

  const toggleCriterion = (id: string) => {
    setCriteria(prev => prev.map(c => c.id === id ? { ...c, enabled: !c.enabled } : c));
  };

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
            <button onClick={() => setShowVersionModal(true)} className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <History size={14} />
              v3.0 (Current)
              <ChevronDown size={12} />
            </button>
            {isViolation && (
              <button onClick={() => setShowViolation(true)} className="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 rounded-lg px-3 py-2 text-sm hover:bg-red-100">
                <AlertTriangle size={14} />
                Policy Violation
              </button>
            )}
            <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Save size={14} />
              Save Model
            </button>
          </div>
        </div>

        {/* Weight Total */}
        <div className={`mt-4 flex items-center gap-3 px-4 py-2.5 rounded-xl border ${isViolation ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200'}`}>
          {isViolation ? <AlertTriangle size={15} className="text-red-500" /> : <CheckCircle size={15} className="text-emerald-500" />}
          <span className={`text-sm ${isViolation ? 'text-red-700' : 'text-emerald-700'}`} style={{ fontWeight: 600 }}>
            Total Weight: {totalWeight}%
          </span>
          <span className={`text-xs ${isViolation ? 'text-red-600' : 'text-emerald-600'}`}>
            {isViolation ? `(must equal 100% — ${totalWeight > 100 ? `${totalWeight - 100}% over` : `${100 - totalWeight}% under`})` : 'Weights are balanced'}
          </span>
          {isViolation && (
            <button onClick={() => setShowWeightModal(true)} className="ml-auto text-xs bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700" style={{ fontWeight: 500 }}>
              Auto-Balance Weights
            </button>
          )}
          <div className="ml-auto w-48 h-2 bg-white rounded-full overflow-hidden border border-slate-200">
            <div className={`h-full rounded-full transition-all ${isViolation ? (totalWeight > 100 ? 'bg-red-500' : 'bg-amber-500') : 'bg-emerald-500'}`} style={{ width: `${Math.min(totalWeight, 100)}%` }} />
          </div>
        </div>
      </div>

      <div className="flex-1 overflow-hidden flex">
        {/* Left: Criteria + Rules */}
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
              {criteria.map((c) => (
                <div key={c.id} className={`flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 last:border-0 transition-colors ${!c.enabled ? 'opacity-50 bg-slate-50' : 'hover:bg-slate-50/50'}`}>
                  <GripVertical size={14} className="text-slate-300 cursor-grab flex-shrink-0" />

                  {/* Toggle */}
                  <button
                    onClick={() => toggleCriterion(c.id)}
                    className={`w-8 h-4 rounded-full transition-colors flex-shrink-0 relative ${c.enabled ? 'bg-indigo-600' : 'bg-slate-200'}`}
                  >
                    <span className={`absolute top-0.5 w-3 h-3 bg-white rounded-full shadow transition-all ${c.enabled ? 'left-4.5' : 'left-0.5'}`} />
                  </button>

                  {/* Info */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{c.name}</span>
                      <span className="text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>{c.category}</span>
                    </div>
                    <span className="text-slate-400 text-xs">{c.description}</span>
                  </div>

                  {/* Slider */}
                  <div className="flex items-center gap-3 w-64">
                    <input
                      type="range"
                      min={0} max={50} step={1}
                      value={c.weight}
                      disabled={!c.enabled}
                      onChange={e => updateWeight(c.id, parseInt(e.target.value))}
                      className="flex-1 accent-indigo-600"
                    />
                    <div className="w-14 text-center">
                      <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{c.weight}%</span>
                    </div>
                  </div>

                  <button className="text-slate-300 hover:text-red-400 flex-shrink-0">
                    <Trash2 size={14} />
                  </button>
                </div>
              ))}
            </div>
          </div>

          {/* Constraint & Policy Rules */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
              <h3 className="text-slate-900">Constraint & Policy Rules</h3>
              <button className="flex items-center gap-1.5 text-indigo-600 text-xs hover:text-indigo-700" style={{ fontWeight: 500 }}>
                <Plus size={13} />
                Add Rule
              </button>
            </div>
            <div className="divide-y divide-slate-100">
              {rules.map((rule) => (
                <div key={rule.id} className={`flex items-center gap-3 px-5 py-3 ${!rule.active ? 'opacity-50' : ''}`}>
                  <span className={`text-xs px-2 py-0.5 rounded-full border flex-shrink-0 ${rule.type === 'constraint' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'}`} style={{ fontWeight: 600 }}>
                    {rule.type === 'constraint' ? 'CONSTRAINT' : 'POLICY'}
                  </span>
                  <div className="flex-1">
                    <span className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{rule.label}</span>
                    <div className="flex items-center gap-2 mt-0.5">
                      <span className="text-slate-400 text-xs">IF {rule.condition}</span>
                      <span className="text-slate-400 text-xs">→</span>
                      <span className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>{rule.action}</span>
                    </div>
                  </div>
                  <button className={`w-8 h-4 rounded-full transition-colors flex-shrink-0 relative ${rule.active ? 'bg-indigo-600' : 'bg-slate-200'}`}>
                    <span className={`absolute top-0.5 w-3 h-3 bg-white rounded-full shadow ${rule.active ? 'left-4.5' : 'left-0.5'}`} />
                  </button>
                </div>
              ))}
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
            <p className="text-slate-400 text-xs mt-0.5">RFQ-2401 · 4 vendors</p>
          </div>

          <div className="p-4 space-y-3">
            {previewVendors.map((vendor, i) => {
              const score = getWeightedScore(vendor, criteria);
              const isWinner = i === 0;
              return (
                <div key={vendor.name} className={`rounded-xl border p-4 ${isWinner ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200'}`}>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>{vendor.name}</span>
                    <span className={`text-lg ${isWinner ? 'text-emerald-700' : 'text-slate-800'}`} style={{ fontWeight: 800 }}>{score.toFixed(1)}</span>
                  </div>
                  <div className="h-2 bg-slate-100 rounded-full overflow-hidden mb-3">
                    <div className={`h-full rounded-full ${isWinner ? 'bg-emerald-500' : 'bg-indigo-400'}`} style={{ width: `${score}%` }} />
                  </div>
                  <div className="space-y-1.5">
                    {criteria.filter(c => c.enabled).map(c => {
                      const raw = vendor.scores[c.id as keyof typeof vendor.scores] || 0;
                      const contribution = (raw * c.weight / 100).toFixed(1);
                      return (
                        <div key={c.id} className="flex items-center justify-between">
                          <span className="text-slate-500 text-xs">{c.name}</span>
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

      {/* Policy Violation Modal */}
      {showViolation && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center gap-3 px-6 py-5 bg-red-50 rounded-t-2xl border-b border-red-200">
              <div className="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <AlertTriangle size={20} className="text-red-600" />
              </div>
              <div>
                <h2 className="text-red-900">Policy Violation</h2>
                <p className="text-red-600 text-xs">Scoring model cannot be saved with invalid weights</p>
              </div>
            </div>
            <div className="p-6">
              <p className="text-slate-600 text-sm">Total criteria weights sum to <strong>{totalWeight}%</strong> but must equal exactly <strong>100%</strong>.</p>
              <div className="mt-4 bg-slate-50 rounded-xl p-4 space-y-2">
                {criteria.filter(c => c.enabled).map(c => (
                  <div key={c.id} className="flex justify-between items-center text-xs">
                    <span className="text-slate-600">{c.name}</span>
                    <span className="text-slate-800" style={{ fontWeight: 600 }}>{c.weight}%</span>
                  </div>
                ))}
                <div className="border-t border-slate-200 pt-2 flex justify-between items-center">
                  <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>Total</span>
                  <span className="text-red-600 text-sm" style={{ fontWeight: 700 }}>{totalWeight}%</span>
                </div>
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setShowViolation(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Dismiss</button>
              <button onClick={() => { setShowViolation(false); setShowWeightModal(true); }} className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Auto-Balance</button>
            </div>
          </div>
        </div>
      )}

      {/* Weight Adjustment Modal */}
      {showWeightModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-slate-900 text-sm">Auto-Balance Weights</h2>
              <button onClick={() => setShowWeightModal(false)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6">
              <p className="text-slate-600 text-sm mb-4">Automatically distribute weights to sum to 100% while preserving relative proportions.</p>
              <div className="space-y-2">
                {criteria.filter(c => c.enabled).map(c => {
                  const normalized = Math.round(c.weight / totalWeight * 100);
                  return (
                    <div key={c.id} className="flex justify-between items-center">
                      <span className="text-slate-600 text-xs">{c.name}</span>
                      <div className="flex items-center gap-2">
                        <span className="text-slate-400 text-xs">{c.weight}%</span>
                        <span className="text-slate-400 text-xs">→</span>
                        <span className="text-indigo-600 text-xs" style={{ fontWeight: 600 }}>{normalized}%</span>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setShowWeightModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => {
                const total = criteria.filter(c => c.enabled).reduce((s, c) => s + c.weight, 0);
                setCriteria(prev => prev.map(c => c.enabled ? { ...c, weight: Math.round(c.weight / total * 100) } : c));
                setShowWeightModal(false);
              }} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply</button>
            </div>
          </div>
        </div>
      )}

      {/* Version History Modal */}
      {showVersionModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-slate-900 text-sm">Version History</h2>
              <button onClick={() => setShowVersionModal(false)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="divide-y divide-slate-100">
              {versions.map((v) => (
                <div key={v.id} className={`flex items-center justify-between px-6 py-4 ${v.active ? 'bg-indigo-50' : 'hover:bg-slate-50'}`}>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{v.label}</span>
                      {v.active && <span className="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>Active</span>}
                    </div>
                    <span className="text-slate-500 text-xs">{v.date} · {v.author}</span>
                  </div>
                  {!v.active && <button className="text-indigo-600 text-xs hover:underline" style={{ fontWeight: 500 }}>Restore</button>}
                </div>
              ))}
            </div>
            <div className="px-6 py-4 border-t border-slate-100 flex justify-end">
              <button onClick={() => setShowVersionModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
