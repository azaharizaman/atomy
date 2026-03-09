import { useState } from 'react';
import { Plus, Save, GitCompare, Sliders, X } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';
import { scenarios as mockScenarios, vendors, formatCurrency, getVendorById } from '../data/mockData';
import { SlideOver } from '../components/SlideOver';

type LocalScenario = {
  id: string;
  name: string;
  isBaseline: boolean;
  unsaved?: boolean;
  assumptions: {
    volumeMultiplier: number;
    paymentTermsDays: number;
    freightCostPct: number;
    fxRateAdjust: number;
  };
  vendorRankings: { vendorId: string; total: number; score: number }[];
};

function applyAssumptions(rankings: LocalScenario['vendorRankings'], assumptions: LocalScenario['assumptions']): LocalScenario['vendorRankings'] {
  return rankings.map(r => {
    let t = r.total;
    t = t * assumptions.volumeMultiplier;
    if (assumptions.paymentTermsDays < 30) {
      t = t * (1 - 0.02);
    } else if (assumptions.paymentTermsDays > 30) {
      t = t * (1 + 0.01);
    }
    t = t * (1 + assumptions.freightCostPct / 100);
    t = t * (1 + assumptions.fxRateAdjust / 100);
    return { ...r, total: Math.round(t) };
  });
}

export function ScenarioSimulator() {
  const [scenarioList, setScenarioList] = useState<LocalScenario[]>(
    mockScenarios.map(s => ({
      id: s.id,
      name: s.name,
      isBaseline: s.isBaseline,
      assumptions: { ...s.assumptions },
      vendorRankings: s.vendorRankings.map(r => ({ ...r })),
    }))
  );
  const [selectedIds, setSelectedIds] = useState<string[]>([mockScenarios[0].id, mockScenarios[1].id]);
  const [activeScenario, setActiveScenario] = useState<string>(mockScenarios[1].id);
  const [showSaveModal, setShowSaveModal] = useState(false);
  const [showDiffModal, setShowDiffModal] = useState(false);
  const [newScenarioName, setNewScenarioName] = useState('');

  const selected = scenarioList.filter(s => selectedIds.includes(s.id));
  const active = scenarioList.find(s => s.id === activeScenario);
  const baseline = scenarioList.find(s => s.isBaseline)!;

  const participatingVendorIds = baseline.vendorRankings.map(r => r.vendorId);
  const participatingVendors = participatingVendorIds.map(id => getVendorById(id)!);

  const chartData = participatingVendors.map(v => {
    const row: Record<string, string | number> = { vendor: v.name.split(' ')[0] };
    selected.forEach(s => {
      const ranking = s.vendorRankings.find(r => r.vendorId === v.id);
      row[s.name] = ranking?.total ?? 0;
    });
    return row;
  });

  const updateAssumption = (key: keyof LocalScenario['assumptions'], val: number) => {
    setScenarioList(prev => prev.map(s =>
      s.id === activeScenario ? {
        ...s,
        assumptions: { ...s.assumptions, [key]: val },
        unsaved: true,
      } : s
    ));
  };

  const colors = ['#6366F1', '#10B981', '#F59E0B', '#EF4444'];

  const diffScenarios = selected.length >= 2 ? [selected[0], selected[1]] : [];

  return (
    <div className="flex h-full bg-slate-50">
      {/* Left: Scenario List */}
      <div className="w-72 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col">
        <div className="px-4 py-4 border-b border-slate-100">
          <h2 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Scenarios</h2>
          <p className="text-slate-500 text-xs mt-0.5">{mockScenarios[0]?.rfqId ?? 'RFQ'} · What-if modeling</p>
        </div>

        <div className="flex-1 overflow-y-auto p-3 space-y-2">
          {scenarioList.map(s => {
            const isActive = s.id === activeScenario;
            const isSelected = selectedIds.includes(s.id);
            const bestTotal = Math.min(...s.vendorRankings.map(r => r.total));
            const baseBest = Math.min(...baseline.vendorRankings.map(r => r.total));
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
                    <p className="text-slate-500 text-xs mt-0.5 leading-relaxed">
                      Vol: {s.assumptions.volumeMultiplier}× · Terms: {s.assumptions.paymentTermsDays}d · FX: {s.assumptions.fxRateAdjust > 0 ? '+' : ''}{s.assumptions.fxRateAdjust}%
                    </p>
                  </div>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-slate-600 text-xs">Best: <b>{formatCurrency(bestTotal)}</b></span>
                  {savings > 0 ? (
                    <span className="text-emerald-600 text-xs" style={{ fontWeight: 600 }}>-{formatCurrency(savings)}</span>
                  ) : savings < 0 ? (
                    <span className="text-red-500 text-xs" style={{ fontWeight: 600 }}>+{formatCurrency(Math.abs(savings))}</span>
                  ) : <span className="text-slate-400 text-xs">—</span>}
                </div>
              </div>
            );
          })}
        </div>

        <div className="p-3 border-t border-slate-100 space-y-2">
          <button
            onClick={() => {
              const newS: LocalScenario = {
                id: `sc-${Date.now()}`, name: 'New Scenario', isBaseline: false, unsaved: true,
                assumptions: { volumeMultiplier: 1.0, paymentTermsDays: 30, freightCostPct: 0, fxRateAdjust: 0 },
                vendorRankings: baseline.vendorRankings.map(r => ({ ...r })),
              };
              setScenarioList(p => [...p, newS]);
              setActiveScenario(newS.id);
            }}
            className="w-full flex items-center gap-2 border border-dashed border-slate-300 rounded-xl px-3 py-2.5 text-sm text-slate-500 hover:border-indigo-400 hover:text-indigo-600 justify-center"
          >
            <Plus size={14} />
            New Scenario
          </button>
          <button
            onClick={() => setShowDiffModal(true)}
            disabled={selected.length < 2}
            className={`w-full flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm justify-center ${selected.length < 2 ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 text-white'}`}
            style={{ fontWeight: 500 }}
          >
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
                    <label className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>Volume Multiplier</label>
                    <span className="text-indigo-600 text-xs" style={{ fontWeight: 600 }}>{active.assumptions.volumeMultiplier}×</span>
                  </div>
                  <input type="range" min={0.5} max={3} step={0.1} value={active.assumptions.volumeMultiplier}
                    disabled={active.isBaseline}
                    onChange={e => updateAssumption('volumeMultiplier', parseFloat(e.target.value))}
                    className="w-full accent-indigo-600" />
                  <div className="flex justify-between text-xs text-slate-400 mt-1"><span>0.5×</span><span>3×</span></div>
                </div>
                <div>
                  <div className="flex justify-between mb-2">
                    <label className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>Payment Terms (days)</label>
                    <span className="text-indigo-600 text-xs" style={{ fontWeight: 600 }}>Net {active.assumptions.paymentTermsDays}</span>
                  </div>
                  <input type="range" min={7} max={90} step={1} value={active.assumptions.paymentTermsDays}
                    disabled={active.isBaseline}
                    onChange={e => updateAssumption('paymentTermsDays', parseInt(e.target.value))}
                    className="w-full accent-indigo-600" />
                  <div className="flex justify-between text-xs text-slate-400 mt-1"><span>Net 7</span><span>Net 90</span></div>
                </div>
                <div>
                  <div className="flex justify-between mb-2">
                    <label className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>Freight Cost Adjustment</label>
                    <span className="text-indigo-600 text-xs" style={{ fontWeight: 600 }}>{active.assumptions.freightCostPct > 0 ? '+' : ''}{active.assumptions.freightCostPct}%</span>
                  </div>
                  <input type="range" min={-10} max={20} step={0.5} value={active.assumptions.freightCostPct}
                    disabled={active.isBaseline}
                    onChange={e => updateAssumption('freightCostPct', parseFloat(e.target.value))}
                    className="w-full accent-indigo-600" />
                  <div className="flex justify-between text-xs text-slate-400 mt-1"><span>-10%</span><span>+20%</span></div>
                </div>
                <div>
                  <div className="flex justify-between mb-2">
                    <label className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>FX Rate Adjustment</label>
                    <span className="text-indigo-600 text-xs" style={{ fontWeight: 600 }}>{active.assumptions.fxRateAdjust > 0 ? '+' : ''}{active.assumptions.fxRateAdjust}%</span>
                  </div>
                  <input type="range" min={-10} max={15} step={0.5} value={active.assumptions.fxRateAdjust}
                    disabled={active.isBaseline}
                    onChange={e => updateAssumption('fxRateAdjust', parseFloat(e.target.value))}
                    className="w-full accent-indigo-600" />
                  <div className="flex justify-between text-xs text-slate-400 mt-1"><span>-10%</span><span>+15%</span></div>
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
                {participatingVendors.map(v => (
                  <tr key={v.id} className="border-b border-slate-50 hover:bg-slate-50">
                    <td className="px-5 py-3 text-slate-700 text-sm">{v.name}</td>
                    {selected.map(s => {
                      const ranking = s.vendorRankings.find(r => r.vendorId === v.id);
                      const total = ranking?.total ?? 0;
                      const baseRanking = baseline.vendorRankings.find(r => r.vendorId === v.id);
                      const baseTotal = baseRanking?.total ?? 0;
                      const delta = total - baseTotal;
                      return (
                        <td key={s.id} className="px-4 py-3 text-center">
                          <div className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{formatCurrency(total)}</div>
                          {!s.isBaseline && (
                            <div className={`text-xs ${delta < 0 ? 'text-emerald-600' : delta > 0 ? 'text-red-500' : 'text-slate-400'}`} style={{ fontWeight: 500 }}>
                              {delta < 0 ? `−${formatCurrency(Math.abs(delta))}` : delta > 0 ? `+${formatCurrency(delta)}` : '—'}
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

      {/* SlideOver: Save Scenario */}
      <SlideOver
        open={showSaveModal}
        onOpenChange={setShowSaveModal}
        title="Save Scenario"
        description="Save the current assumptions as a named scenario."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowSaveModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => {
              setScenarioList(p => p.map(s =>
                s.id === activeScenario ? { ...s, name: newScenarioName || s.name, unsaved: false } : s
              ));
              setShowSaveModal(false);
              setNewScenarioName('');
            }} className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><Save size={14} /> Save</span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Scenario Name</label>
            <input
              value={newScenarioName || active?.name || ''}
              onChange={e => setNewScenarioName(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none"
              placeholder="e.g. Volume +20% with freight waiver"
            />
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Notes (optional)</label>
            <textarea rows={3} className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none" placeholder="Describe assumptions rationale..." />
          </div>
          {active && (
            <div className="bg-slate-50 rounded-xl border border-slate-200 p-3 space-y-1.5">
              <span className="text-xs text-slate-500" style={{ fontWeight: 600 }}>Assumptions Summary</span>
              <div className="flex justify-between text-xs">
                <span className="text-slate-500">Volume Multiplier</span>
                <span className="text-slate-700" style={{ fontWeight: 500 }}>{active.assumptions.volumeMultiplier}×</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-slate-500">Payment Terms</span>
                <span className="text-slate-700" style={{ fontWeight: 500 }}>Net {active.assumptions.paymentTermsDays}</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-slate-500">Freight Cost</span>
                <span className="text-slate-700" style={{ fontWeight: 500 }}>{active.assumptions.freightCostPct > 0 ? '+' : ''}{active.assumptions.freightCostPct}%</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-slate-500">FX Adjustment</span>
                <span className="text-slate-700" style={{ fontWeight: 500 }}>{active.assumptions.fxRateAdjust > 0 ? '+' : ''}{active.assumptions.fxRateAdjust}%</span>
              </div>
            </div>
          )}
        </div>
      </SlideOver>

      {/* SlideOver: Scenario Diff */}
      <SlideOver
        open={showDiffModal}
        onOpenChange={setShowDiffModal}
        title="Scenario Comparison"
        description={diffScenarios.length >= 2 ? `${diffScenarios[0].name} vs ${diffScenarios[1].name}` : 'Select at least 2 scenarios'}
        width="lg"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowDiffModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            <button className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Export Report</button>
          </div>
        }
      >
        {diffScenarios.length >= 2 && (
          <div className="space-y-5">
            {/* Assumptions Diff */}
            <div className="space-y-3">
              <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Assumptions</h4>
              <table className="w-full">
                <thead>
                  <tr className="border-b border-slate-200">
                    <th className="text-left px-3 py-2 text-xs text-slate-500" style={{ fontWeight: 600 }}>Parameter</th>
                    <th className="text-center px-3 py-2 text-xs" style={{ fontWeight: 600 }}>
                      <div className="flex items-center justify-center gap-2">
                        <div className="w-2.5 h-2.5 rounded-full" style={{ background: colors[0] }} />
                        <span className="text-slate-700">{diffScenarios[0].name}</span>
                      </div>
                    </th>
                    <th className="text-center px-3 py-2 text-xs" style={{ fontWeight: 600 }}>
                      <div className="flex items-center justify-center gap-2">
                        <div className="w-2.5 h-2.5 rounded-full" style={{ background: colors[1] }} />
                        <span className="text-slate-700">{diffScenarios[1].name}</span>
                      </div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {([
                    { label: 'Volume Multiplier', key: 'volumeMultiplier' as const, fmt: (v: number) => `${v}×` },
                    { label: 'Payment Terms', key: 'paymentTermsDays' as const, fmt: (v: number) => `Net ${v}` },
                    { label: 'Freight Cost', key: 'freightCostPct' as const, fmt: (v: number) => `${v > 0 ? '+' : ''}${v}%` },
                    { label: 'FX Adjustment', key: 'fxRateAdjust' as const, fmt: (v: number) => `${v > 0 ? '+' : ''}${v}%` },
                  ]).map(row => {
                    const v1 = diffScenarios[0].assumptions[row.key];
                    const v2 = diffScenarios[1].assumptions[row.key];
                    const diff = v1 !== v2;
                    return (
                      <tr key={row.label} className={`border-b border-slate-100 ${diff ? 'bg-amber-50/50' : ''}`}>
                        <td className="px-3 py-2.5 text-slate-600 text-xs" style={{ fontWeight: 500 }}>{row.label}</td>
                        <td className="px-3 py-2.5 text-center text-slate-700 text-xs">{row.fmt(v1)}</td>
                        <td className={`px-3 py-2.5 text-center text-xs ${diff ? 'text-indigo-600' : 'text-slate-700'}`} style={{ fontWeight: diff ? 600 : 400 }}>{row.fmt(v2)}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>

            {/* Vendor Results Diff */}
            <div className="space-y-3">
              <h4 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>Vendor Totals</h4>
              <div className="space-y-2">
                {participatingVendors.map(v => {
                  const r1 = diffScenarios[0].vendorRankings.find(r => r.vendorId === v.id);
                  const r2 = diffScenarios[1].vendorRankings.find(r => r.vendorId === v.id);
                  const t1 = r1?.total ?? 0;
                  const t2 = r2?.total ?? 0;
                  const delta = t2 - t1;
                  return (
                    <div key={v.id} className="bg-slate-50 rounded-xl border border-slate-200 p-3">
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{v.name}</span>
                        <span className={`text-xs ${delta < 0 ? 'text-emerald-600' : delta > 0 ? 'text-red-500' : 'text-slate-400'}`} style={{ fontWeight: 600 }}>
                          {delta < 0 ? `−${formatCurrency(Math.abs(delta))}` : delta > 0 ? `+${formatCurrency(delta)}` : 'No change'}
                        </span>
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div className="text-center">
                          <div className="text-xs text-slate-400 mb-0.5">{diffScenarios[0].name}</div>
                          <div className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{formatCurrency(t1)}</div>
                        </div>
                        <div className="text-center">
                          <div className="text-xs text-slate-400 mb-0.5">{diffScenarios[1].name}</div>
                          <div className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{formatCurrency(t2)}</div>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Summary */}
            <div className="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
              <h4 className="text-indigo-900 text-sm mb-2" style={{ fontWeight: 600 }}>Comparison Summary</h4>
              {(() => {
                const best1 = Math.min(...diffScenarios[0].vendorRankings.map(r => r.total));
                const best2 = Math.min(...diffScenarios[1].vendorRankings.map(r => r.total));
                const delta = best2 - best1;
                return (
                  <div className="space-y-1.5">
                    <div className="flex justify-between text-xs">
                      <span className="text-indigo-700">{diffScenarios[0].name} best total</span>
                      <span className="text-indigo-800" style={{ fontWeight: 600 }}>{formatCurrency(best1)}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                      <span className="text-indigo-700">{diffScenarios[1].name} best total</span>
                      <span className="text-indigo-800" style={{ fontWeight: 600 }}>{formatCurrency(best2)}</span>
                    </div>
                    <div className="border-t border-indigo-200 pt-1.5 flex justify-between text-xs">
                      <span className="text-indigo-700" style={{ fontWeight: 600 }}>Delta</span>
                      <span className={`${delta <= 0 ? 'text-emerald-700' : 'text-red-600'}`} style={{ fontWeight: 700 }}>
                        {delta <= 0 ? `−${formatCurrency(Math.abs(delta))}` : `+${formatCurrency(delta)}`}
                      </span>
                    </div>
                  </div>
                );
              })()}
            </div>
          </div>
        )}
      </SlideOver>
    </div>
  );
}
