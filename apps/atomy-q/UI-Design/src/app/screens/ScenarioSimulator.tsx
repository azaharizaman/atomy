import { useState } from 'react';
import { Plus, Save, GitCompare, TrendingDown, X, AlertTriangle, Sliders, Check } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';

interface Scenario {
  id: string;
  name: string;
  description: string;
  isBaseline: boolean;
  unsaved?: boolean;
  assumptions: {
    volumeDiscount: number;
    paymentTermDiscount: number;
    bulkOrderQtyMultiplier: number;
    freightWaived: boolean;
  };
  vendors: {
    name: string;
    baseTotal: number;
  }[];
}

const baseVendors = [
  { name: 'PrimeSource Co.', baseTotal: 189200 },
  { name: 'TechCorp Solutions', baseTotal: 198400 },
  { name: 'FastParts Ltd.', baseTotal: 202600 },
  { name: 'GlobalSupply Inc.', baseTotal: 195800 },
];

function computeTotal(vendor: { baseTotal: number }, assumptions: Scenario['assumptions']) {
  let t = vendor.baseTotal;
  t = t * (1 - assumptions.volumeDiscount / 100);
  t = t * (1 - assumptions.paymentTermDiscount / 100);
  t = t * assumptions.bulkOrderQtyMultiplier;
  if (assumptions.freightWaived) t = t - 2800;
  return Math.round(t);
}

const initialScenarios: Scenario[] = [
  {
    id: 's1', name: 'Baseline', description: 'No modifications — as quoted', isBaseline: true,
    assumptions: { volumeDiscount: 0, paymentTermDiscount: 0, bulkOrderQtyMultiplier: 1, freightWaived: false },
    vendors: baseVendors,
  },
  {
    id: 's2', name: '10% Volume Discount', description: 'Apply 10% discount for volume commitment', isBaseline: false,
    assumptions: { volumeDiscount: 10, paymentTermDiscount: 0, bulkOrderQtyMultiplier: 1, freightWaived: false },
    vendors: baseVendors,
  },
  {
    id: 's3', name: 'Early Payment + Freight Waived', description: 'Net 15 terms + freight waived', isBaseline: false,
    assumptions: { volumeDiscount: 0, paymentTermDiscount: 3, bulkOrderQtyMultiplier: 1, freightWaived: true },
    vendors: baseVendors,
  },
];

export function ScenarioSimulator() {
  const [scenarios, setScenarios] = useState<Scenario[]>(initialScenarios);
  const [selectedIds, setSelectedIds] = useState<string[]>(['s1', 's2']);
  const [activeScenario, setActiveScenario] = useState<string>('s2');
  const [showSaveModal, setShowSaveModal] = useState(false);
  const [showCompareModal, setShowCompareModal] = useState(false);
  const [newScenarioName, setNewScenarioName] = useState('');

  const selected = scenarios.filter(s => selectedIds.includes(s.id));
  const active = scenarios.find(s => s.id === activeScenario);

  const chartData = baseVendors.map(v => {
    const row: Record<string, string | number> = { vendor: v.name.split(' ')[0] };
    selected.forEach(s => {
      row[s.name] = computeTotal(v, s.assumptions);
    });
    return row;
  });

  const updateAssumption = (key: keyof Scenario['assumptions'], val: number | boolean) => {
    setScenarios(prev => prev.map(s =>
      s.id === activeScenario ? {
        ...s,
        assumptions: { ...s.assumptions, [key]: val },
        unsaved: true,
      } : s
    ));
  };

  const colors = ['#6366F1', '#10B981', '#F59E0B', '#EF4444'];

  return (
    <div className="flex h-full bg-slate-50">
      {/* Left: Scenario List */}
      <div className="w-72 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col">
        <div className="px-4 py-4 border-b border-slate-100">
          <h2 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Scenarios</h2>
          <p className="text-slate-500 text-xs mt-0.5">RFQ-2401 · What-if modeling</p>
        </div>

        <div className="flex-1 overflow-y-auto p-3 space-y-2">
          {scenarios.map((s) => {
            const isActive = s.id === activeScenario;
            const isSelected = selectedIds.includes(s.id);
            const bestTotal = Math.min(...baseVendors.map(v => computeTotal(v, s.assumptions)));
            const baseline = scenarios[0];
            const baseBest = Math.min(...baseVendors.map(v => computeTotal(v, baseline.assumptions)));
            const savings = baseBest - bestTotal;

            return (
              <div
                key={s.id}
                onClick={() => setActiveScenario(s.id)}
                className={`rounded-xl border p-3 cursor-pointer transition-all ${isActive ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'}`}
              >
                <div className="flex items-start gap-2 mb-2">
                  <input
                    type="checkbox"
                    checked={isSelected}
                    onChange={e => {
                      e.stopPropagation();
                      setSelectedIds(prev => e.target.checked ? [...prev, s.id] : prev.filter(id => id !== s.id));
                    }}
                    className="mt-0.5 accent-indigo-600"
                    onClick={e => e.stopPropagation()}
                  />
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-1.5 flex-wrap">
                      <span className="text-slate-800 text-xs" style={{ fontWeight: 600 }}>{s.name}</span>
                      {s.isBaseline && <span className="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>Baseline</span>}
                      {s.unsaved && <span className="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>Unsaved</span>}
                    </div>
                    <p className="text-slate-500 text-xs mt-0.5 leading-relaxed">{s.description}</p>
                  </div>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-slate-600 text-xs">Best: <b>${bestTotal.toLocaleString()}</b></span>
                  {savings > 0 ? (
                    <span className="text-emerald-600 text-xs" style={{ fontWeight: 600 }}>-${savings.toLocaleString()}</span>
                  ) : savings < 0 ? (
                    <span className="text-red-500 text-xs" style={{ fontWeight: 600 }}>+${Math.abs(savings).toLocaleString()}</span>
                  ) : <span className="text-slate-400 text-xs">—</span>}
                </div>
              </div>
            );
          })}
        </div>

        <div className="p-3 border-t border-slate-100 space-y-2">
          <button
            onClick={() => {
              const newS: Scenario = {
                id: `s${Date.now()}`, name: 'New Scenario', description: 'Custom assumptions', isBaseline: false, unsaved: true,
                assumptions: { volumeDiscount: 5, paymentTermDiscount: 2, bulkOrderQtyMultiplier: 1, freightWaived: false },
                vendors: baseVendors,
              };
              setScenarios(p => [...p, newS]);
              setActiveScenario(newS.id);
            }}
            className="w-full flex items-center gap-2 border border-dashed border-slate-300 rounded-xl px-3 py-2.5 text-sm text-slate-500 hover:border-indigo-400 hover:text-indigo-600 justify-center"
          >
            <Plus size={14} />
            New Scenario
          </button>
          <button onClick={() => setShowCompareModal(true)} className="w-full flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-3 py-2.5 text-sm justify-center" style={{ fontWeight: 500 }}>
            <GitCompare size={14} />
            Compare Selected
          </button>
        </div>
      </div>

      {/* Right: Canvas */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Top bar */}
        <div className="bg-white border-b border-slate-200 px-5 py-3 flex items-center justify-between flex-shrink-0">
          <div>
            <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{active?.name}</span>
            {active?.unsaved && <span className="ml-2 text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded" style={{ fontWeight: 500 }}>Unsaved Changes</span>}
          </div>
          <div className="flex items-center gap-2">
            {active?.unsaved && (
              <button onClick={() => setShowSaveModal(true)} className="flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3 py-1.5 rounded-lg" style={{ fontWeight: 500 }}>
                <Save size={13} />
                Save Scenario
              </button>
            )}
          </div>
        </div>

        <div className="flex-1 overflow-auto p-5 space-y-5">
          {/* Assumption Controls */}
          {active && (
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
              <div className="flex items-center gap-2 mb-4">
                <Sliders size={15} className="text-indigo-600" />
                <h3 className="text-slate-900">Assumption Controls</h3>
                {active.isBaseline && <span className="text-xs text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded" style={{ fontWeight: 500 }}>Baseline cannot be edited</span>}
              </div>
              <div className="grid grid-cols-2 gap-5">
                <div>
                  <div className="flex justify-between mb-2">
                    <label className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>Volume Discount</label>
                    <span className="text-indigo-600 text-xs" style={{ fontWeight: 600 }}>{active.assumptions.volumeDiscount}%</span>
                  </div>
                  <input type="range" min={0} max={25} step={0.5} value={active.assumptions.volumeDiscount}
                    disabled={active.isBaseline}
                    onChange={e => updateAssumption('volumeDiscount', parseFloat(e.target.value))}
                    className="w-full accent-indigo-600" />
                  <div className="flex justify-between text-xs text-slate-400 mt-1"><span>0%</span><span>25%</span></div>
                </div>
                <div>
                  <div className="flex justify-between mb-2">
                    <label className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>Early Payment Discount</label>
                    <span className="text-indigo-600 text-xs" style={{ fontWeight: 600 }}>{active.assumptions.paymentTermDiscount}%</span>
                  </div>
                  <input type="range" min={0} max={10} step={0.5} value={active.assumptions.paymentTermDiscount}
                    disabled={active.isBaseline}
                    onChange={e => updateAssumption('paymentTermDiscount', parseFloat(e.target.value))}
                    className="w-full accent-indigo-600" />
                  <div className="flex justify-between text-xs text-slate-400 mt-1"><span>0%</span><span>10%</span></div>
                </div>
                <div>
                  <div className="flex justify-between mb-2">
                    <label className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>Quantity Multiplier</label>
                    <span className="text-indigo-600 text-xs" style={{ fontWeight: 600 }}>{active.assumptions.bulkOrderQtyMultiplier}×</span>
                  </div>
                  <input type="range" min={0.5} max={3} step={0.1} value={active.assumptions.bulkOrderQtyMultiplier}
                    disabled={active.isBaseline}
                    onChange={e => updateAssumption('bulkOrderQtyMultiplier', parseFloat(e.target.value))}
                    className="w-full accent-indigo-600" />
                  <div className="flex justify-between text-xs text-slate-400 mt-1"><span>0.5×</span><span>3×</span></div>
                </div>
                <div className="flex items-center gap-3">
                  <button
                    disabled={active.isBaseline}
                    onClick={() => updateAssumption('freightWaived', !active.assumptions.freightWaived)}
                    className={`w-10 h-5 rounded-full transition-colors flex-shrink-0 relative ${active.assumptions.freightWaived ? 'bg-indigo-600' : 'bg-slate-200'} ${active.isBaseline ? 'opacity-40 cursor-not-allowed' : ''}`}
                  >
                    <span className={`absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all ${active.assumptions.freightWaived ? 'left-5.5' : 'left-0.5'}`} />
                  </button>
                  <div>
                    <div className="text-slate-700 text-xs" style={{ fontWeight: 500 }}>Freight Waived</div>
                    <div className="text-slate-400 text-xs">-$2,800 per vendor</div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Comparison Chart */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-slate-900">Outcome Comparison</h3>
              <span className="text-xs text-slate-500">{selected.length} scenarios selected</span>
            </div>
            <ResponsiveContainer width="100%" height={260}>
              <BarChart data={chartData} barGap={4}>
                <CartesianGrid strokeDasharray="3 3" stroke="#F1F5F9" vertical={false} />
                <XAxis dataKey="vendor" tick={{ fontSize: 11, fill: '#94A3B8' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 11, fill: '#94A3B8' }} axisLine={false} tickLine={false} tickFormatter={v => `$${(v / 1000).toFixed(0)}k`} />
                <Tooltip formatter={(v: number) => [`$${v.toLocaleString()}`, '']} contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #E2E8F0' }} />
                <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 11 }} />
                {selected.map((s, i) => (
                  <Bar key={s.id} dataKey={s.name} fill={colors[i % colors.length]} radius={[3, 3, 0, 0]} maxBarSize={50} />
                ))}
              </BarChart>
            </ResponsiveContainer>
          </div>

          {/* Delta Table */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="px-5 py-3 border-b border-slate-100">
              <h3 className="text-slate-900">Savings Summary</h3>
            </div>
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50">
                  <th className="text-left px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>VENDOR</th>
                  {selected.map(s => (
                    <th key={s.id} className="text-center px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>{s.name}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {baseVendors.map((v) => (
                  <tr key={v.name} className="border-b border-slate-50 hover:bg-slate-50">
                    <td className="px-5 py-3 text-slate-700 text-sm">{v.name}</td>
                    {selected.map(s => {
                      const total = computeTotal(v, s.assumptions);
                      const base = computeTotal(v, scenarios[0].assumptions);
                      const delta = total - base;
                      return (
                        <td key={s.id} className="px-4 py-3 text-center">
                          <div className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>${total.toLocaleString()}</div>
                          {!s.isBaseline && (
                            <div className={`text-xs ${delta < 0 ? 'text-emerald-600' : delta > 0 ? 'text-red-500' : 'text-slate-400'}`} style={{ fontWeight: 500 }}>
                              {delta < 0 ? `−$${Math.abs(delta).toLocaleString()}` : delta > 0 ? `+$${delta.toLocaleString()}` : '—'}
                            </div>
                          )}
                        </td>
                      );
                    })}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Save Modal */}
      {showSaveModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-slate-900 text-sm">Save Scenario</h2>
              <button onClick={() => setShowSaveModal(false)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Scenario Name</label>
                <input value={newScenarioName || active?.name || ''} onChange={e => setNewScenarioName(e.target.value)} className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Notes (optional)</label>
                <textarea rows={3} className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none" placeholder="Describe this scenario..." />
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setShowSaveModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => { setScenarios(p => p.map(s => s.id === activeScenario ? { ...s, unsaved: false } : s)); setShowSaveModal(false); }} className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Save</button>
            </div>
          </div>
        </div>
      )}

      {/* Compare Modal */}
      {showCompareModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-slate-900 text-sm">Comparison Report</h2>
              <button onClick={() => setShowCompareModal(false)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6">
              <p className="text-slate-600 text-sm mb-4">Comparing {selected.length} scenarios across {baseVendors.length} vendors.</p>
              <div className="space-y-2">
                {selected.map((s, i) => {
                  const best = Math.min(...baseVendors.map(v => computeTotal(v, s.assumptions)));
                  const baseBest = Math.min(...baseVendors.map(v => computeTotal(v, scenarios[0].assumptions)));
                  const savings = baseBest - best;
                  return (
                    <div key={s.id} className="flex items-center justify-between px-4 py-3 bg-slate-50 rounded-xl border border-slate-200">
                      <div className="flex items-center gap-2">
                        <div className="w-3 h-3 rounded-full" style={{ background: colors[i % colors.length] }} />
                        <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{s.name}</span>
                      </div>
                      <div className="text-right">
                        <div className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>${best.toLocaleString()}</div>
                        {savings > 0 && <div className="text-emerald-600 text-xs">Save ${savings.toLocaleString()}</div>}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setShowCompareModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
              <button className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Export Report</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
