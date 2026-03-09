import { useState } from 'react';
import { AlertTriangle, CheckCircle, XCircle, ChevronRight, Zap, RefreshCw, Edit3, AlertCircle, X, Search } from 'lucide-react';

type MappingState = 'unmapped' | 'partial' | 'full';

interface SourceLine {
  id: string;
  raw: string;
  vendor: string;
  qty: string;
  uom: string;
  unitPrice: string;
  currency: string;
  state: MappingState;
  conflicts?: string[];
  normalizedId?: string;
}

interface NormalizedItem {
  id: string;
  description: string;
  stdUOM: string;
  stdCurrency: string;
  mappedCount: number;
  lines: string[];
}

const sourceLines: SourceLine[] = [
  { id: 'sl1', vendor: 'TechCorp', raw: 'Dell PE R750 10Core Server', qty: '10', uom: 'Each', unitPrice: '8200.00', currency: 'USD', state: 'full', normalizedId: 'ni1' },
  { id: 'sl2', vendor: 'GlobalSupply', raw: 'HP SERVER DL380 GEN10', qty: '5', uom: 'Unit', unitPrice: '9100.00', currency: 'USD', state: 'partial', conflicts: ['UOM mismatch: "Unit" vs "EA"'], normalizedId: 'ni2' },
  { id: 'sl3', vendor: 'FastParts', raw: '32GB RAM ECC (Box/8)', qty: '10', uom: 'Box', unitPrice: '3280.00', currency: 'GBP', state: 'partial', conflicts: ['Currency conversion required: GBP→USD', 'UOM: "Box/8" needs quantity split'], normalizedId: 'ni3' },
  { id: 'sl4', vendor: 'TechCorp', raw: 'Cisco Cat 9300-48P', qty: '8', uom: 'EA', unitPrice: '4800.00', currency: 'USD', state: 'full', normalizedId: 'ni4' },
  { id: 'sl5', vendor: 'GlobalSupply', raw: 'FORTINET FORTIGATE 100F', qty: '2', uom: 'Pcs', unitPrice: '5900.00', currency: 'EUR', state: 'partial', conflicts: ['Currency conversion required: EUR→USD'] },
  { id: 'sl6', vendor: 'FastParts', raw: 'VMware vSphere Ent+', qty: '10', uom: 'License', unitPrice: '3400.00', currency: 'USD', state: 'unmapped' },
  { id: 'sl7', vendor: 'PrimeSource', raw: 'MS SQL Server Standard 2022', qty: '5', uom: 'Lic', unitPrice: '1720.00', currency: 'USD', state: 'partial', conflicts: ['UOM: "Lic" → "License"'] },
  { id: 'sl8', vendor: 'PrimeSource', raw: 'Cat6A Patch Cable 1m RJ45', qty: '200', uom: 'PC', unitPrice: '13.00', currency: 'USD', state: 'unmapped' },
];

const normalizedItems: NormalizedItem[] = [
  { id: 'ni1', description: 'Dell PowerEdge R750 Server', stdUOM: 'EA', stdCurrency: 'USD', mappedCount: 1, lines: ['sl1'] },
  { id: 'ni2', description: 'HPE ProLiant DL380 Gen10', stdUOM: 'EA', stdCurrency: 'USD', mappedCount: 1, lines: ['sl2'] },
  { id: 'ni3', description: '32GB DDR4 ECC RDIMM', stdUOM: 'EA', stdCurrency: 'USD', mappedCount: 1, lines: ['sl3'] },
  { id: 'ni4', description: 'Cisco Catalyst 9300 48-Port Switch', stdUOM: 'EA', stdCurrency: 'USD', mappedCount: 1, lines: ['sl4'] },
];

const conflictQueue = sourceLines.filter(l => l.conflicts && l.conflicts.length > 0);

function StateBadge({ state }: { state: MappingState }) {
  if (state === 'full') return <span className="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-xs px-2 py-0.5 rounded-full border border-emerald-200" style={{ fontWeight: 600 }}><CheckCircle size={10} /> Mapped</span>;
  if (state === 'partial') return <span className="inline-flex items-center gap-1 bg-amber-50 text-amber-700 text-xs px-2 py-0.5 rounded-full border border-amber-200" style={{ fontWeight: 600 }}><AlertTriangle size={10} /> Partial</span>;
  return <span className="inline-flex items-center gap-1 bg-red-50 text-red-700 text-xs px-2 py-0.5 rounded-full border border-red-200" style={{ fontWeight: 600 }}><XCircle size={10} /> Unmapped</span>;
}

export function QuoteNormalizationWorkspace() {
  const [selected, setSelected] = useState<string | null>('sl3');
  const [conflictModal, setConflictModal] = useState<SourceLine | null>(null);
  const [overrideModal, setOverrideModal] = useState<SourceLine | null>(null);
  const [activeTab, setActiveTab] = useState<'source' | 'conflicts'>('source');
  const [overrideValue, setOverrideValue] = useState('');

  const full = sourceLines.filter(l => l.state === 'full').length;
  const partial = sourceLines.filter(l => l.state === 'partial').length;
  const unmapped = sourceLines.filter(l => l.state === 'unmapped').length;
  const selectedLine = sourceLines.find(l => l.id === selected);

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center justify-between">
          <div>
            <div className="text-xs text-slate-500 mb-1">RFQ-2401 · Server Infrastructure Components</div>
            <h1 className="text-slate-900">Quote Normalization Workspace</h1>
          </div>
          <div className="flex items-center gap-2">
            <div className="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs">
              <span className="flex items-center gap-1 text-emerald-600"><CheckCircle size={12} /><b>{full}</b> mapped</span>
              <span className="w-px h-4 bg-slate-200" />
              <span className="flex items-center gap-1 text-amber-600"><AlertTriangle size={12} /><b>{partial}</b> partial</span>
              <span className="w-px h-4 bg-slate-200" />
              <span className="flex items-center gap-1 text-red-600"><XCircle size={12} /><b>{unmapped}</b> unmapped</span>
            </div>
            <button className="flex items-center gap-2 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 bg-white">
              <RefreshCw size={14} />
              Auto-Map
            </button>
            <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Zap size={14} />
              Finalize Normalization
            </button>
          </div>
        </div>
      </div>

      {/* Workspace */}
      <div className="flex-1 flex overflow-hidden">
        {/* LEFT: Source Lines */}
        <div className="w-[420px] flex-shrink-0 flex flex-col border-r border-slate-200 bg-white">
          {/* Tab bar */}
          <div className="flex border-b border-slate-200 flex-shrink-0">
            <button
              onClick={() => setActiveTab('source')}
              className={`flex-1 py-2.5 text-xs transition-colors ${activeTab === 'source' ? 'border-b-2 border-indigo-600 text-indigo-600 bg-indigo-50/40' : 'text-slate-500 hover:text-slate-700'}`}
              style={{ fontWeight: activeTab === 'source' ? 600 : 400 }}
            >
              Source Lines ({sourceLines.length})
            </button>
            <button
              onClick={() => setActiveTab('conflicts')}
              className={`flex-1 py-2.5 text-xs transition-colors relative ${activeTab === 'conflicts' ? 'border-b-2 border-amber-500 text-amber-600 bg-amber-50/40' : 'text-slate-500 hover:text-slate-700'}`}
              style={{ fontWeight: activeTab === 'conflicts' ? 600 : 400 }}
            >
              Conflict Queue
              {conflictQueue.length > 0 && (
                <span className="absolute top-1.5 right-3 bg-amber-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" style={{ fontSize: 10 }}>{conflictQueue.length}</span>
              )}
            </button>
          </div>

          {/* Search */}
          <div className="px-3 py-2 border-b border-slate-100 flex-shrink-0">
            <div className="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
              <Search size={12} className="text-slate-400" />
              <input className="bg-transparent flex-1 text-xs text-slate-700 outline-none placeholder:text-slate-400" placeholder="Search lines..." />
            </div>
          </div>

          {/* Lines */}
          <div className="flex-1 overflow-y-auto">
            {(activeTab === 'source' ? sourceLines : conflictQueue).map((line) => (
              <div
                key={line.id}
                onClick={() => setSelected(line.id)}
                className={`px-4 py-3 border-b border-slate-100 cursor-pointer transition-colors ${selected === line.id ? 'bg-indigo-50 border-l-2 border-l-indigo-500' : 'hover:bg-slate-50'}`}
              >
                <div className="flex items-start justify-between gap-2 mb-1.5">
                  <span className="text-slate-800 text-xs" style={{ fontWeight: 500 }}>{line.raw}</span>
                  <StateBadge state={line.state} />
                </div>
                <div className="flex items-center gap-3 text-xs text-slate-500">
                  <span className="bg-slate-100 px-1.5 py-0.5 rounded text-slate-600" style={{ fontWeight: 500 }}>{line.vendor}</span>
                  <span>{line.qty} {line.uom}</span>
                  <span className="text-slate-700" style={{ fontWeight: 500 }}>{line.currency} {line.unitPrice}/unit</span>
                </div>
                {line.conflicts && line.conflicts.length > 0 && (
                  <div className="mt-1.5 space-y-0.5">
                    {line.conflicts.map((c, i) => (
                      <div key={i} className="flex items-center gap-1 text-amber-600 text-xs">
                        <AlertCircle size={10} />
                        <span>{c}</span>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* RIGHT: Mapping Grid */}
        <div className="flex-1 flex flex-col overflow-hidden">
          {/* Mapping Editor */}
          {selectedLine ? (
            <div className="flex-1 overflow-auto p-4 space-y-4">
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                  <div>
                    <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Mapping Editor</span>
                    <span className="ml-2"><StateBadge state={selectedLine.state} /></span>
                  </div>
                  <div className="flex gap-2">
                    {selectedLine.conflicts && (
                      <button onClick={() => setConflictModal(selectedLine)} className="flex items-center gap-1.5 text-xs bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-3 py-1.5 hover:bg-amber-100">
                        <AlertTriangle size={12} />
                        Resolve Conflicts
                      </button>
                    )}
                    <button onClick={() => setOverrideModal(selectedLine)} className="flex items-center gap-1.5 text-xs bg-slate-50 border border-slate-200 text-slate-600 rounded-lg px-3 py-1.5 hover:bg-slate-100">
                      <Edit3 size={12} />
                      Manual Override
                    </button>
                  </div>
                </div>
                <div className="p-5">
                  <div className="grid grid-cols-2 gap-6">
                    {/* Source */}
                    <div>
                      <div className="text-xs text-slate-500 mb-3" style={{ fontWeight: 700 }}>SOURCE (RAW)</div>
                      <div className="space-y-3">
                        {[
                          { label: 'Description', value: selectedLine.raw },
                          { label: 'Quantity', value: selectedLine.qty },
                          { label: 'UOM', value: selectedLine.uom },
                          { label: 'Unit Price', value: selectedLine.unitPrice },
                          { label: 'Currency', value: selectedLine.currency },
                          { label: 'Vendor', value: selectedLine.vendor },
                        ].map(f => (
                          <div key={f.label} className="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
                            <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>{f.label}</span>
                            <span className="text-slate-800 text-xs" style={{ fontWeight: 500 }}>{f.value}</span>
                          </div>
                        ))}
                      </div>
                    </div>

                    {/* Normalized */}
                    <div>
                      <div className="text-xs text-slate-500 mb-3" style={{ fontWeight: 700 }}>NORMALIZED (TARGET)</div>
                      <div className="space-y-3">
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between border border-indigo-100">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Description</span>
                          <span className="text-indigo-700 text-xs" style={{ fontWeight: 500 }}>
                            {selectedLine.normalizedId ? normalizedItems.find(n => n.id === selectedLine.normalizedId)?.description || '—' : '—'}
                          </span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Std. Qty</span>
                          <span className={`text-xs ${selectedLine.uom === 'Box' ? 'text-amber-700' : 'text-slate-800'}`} style={{ fontWeight: 500 }}>
                            {selectedLine.uom === 'Box' ? `${parseInt(selectedLine.qty) * 8} EA (converted)` : `${selectedLine.qty} EA`}
                          </span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Std. UOM</span>
                          <span className="text-slate-800 text-xs" style={{ fontWeight: 500 }}>EA</span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Std. Price</span>
                          <span className={`text-xs ${selectedLine.currency !== 'USD' ? 'text-amber-700' : 'text-slate-800'}`} style={{ fontWeight: 500 }}>
                            {selectedLine.currency !== 'USD' ? `USD ${(parseFloat(selectedLine.unitPrice) * 1.27).toFixed(2)} (est.)` : `USD ${selectedLine.unitPrice}`}
                          </span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Std. Currency</span>
                          <span className="text-slate-800 text-xs" style={{ fontWeight: 500 }}>USD</span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Mapping Confidence</span>
                          <span className={`text-xs px-2 py-0.5 rounded-full ${selectedLine.state === 'full' ? 'bg-emerald-50 text-emerald-700' : selectedLine.state === 'partial' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'}`} style={{ fontWeight: 600 }}>
                            {selectedLine.state === 'full' ? '98%' : selectedLine.state === 'partial' ? '61%' : '—'}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Conflicts */}
                  {selectedLine.conflicts && selectedLine.conflicts.length > 0 && (
                    <div className="mt-5 bg-amber-50 border border-amber-200 rounded-xl p-4">
                      <div className="flex items-center gap-2 mb-3">
                        <AlertTriangle size={14} className="text-amber-600" />
                        <span className="text-amber-700 text-sm" style={{ fontWeight: 600 }}>Conflicts Detected</span>
                      </div>
                      <div className="space-y-2">
                        {selectedLine.conflicts.map((c, i) => (
                          <div key={i} className="flex items-start gap-2">
                            <AlertCircle size={12} className="text-amber-600 mt-0.5 flex-shrink-0" />
                            <span className="text-amber-700 text-xs">{c}</span>
                          </div>
                        ))}
                      </div>
                      <button onClick={() => setConflictModal(selectedLine)} className="mt-3 bg-amber-600 hover:bg-amber-700 text-white text-xs px-3 py-1.5 rounded-lg" style={{ fontWeight: 500 }}>
                        Resolve All Conflicts
                      </button>
                    </div>
                  )}
                </div>
              </div>

              {/* Normalized Items Grid */}
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div className="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                  <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Normalized Catalog Items</span>
                  <button className="text-indigo-600 text-xs hover:underline" style={{ fontWeight: 500 }}>+ Add Item</button>
                </div>
                <div className="divide-y divide-slate-100">
                  {normalizedItems.map((item) => (
                    <div key={item.id} className={`flex items-center gap-4 px-5 py-3 hover:bg-slate-50 cursor-pointer ${selectedLine.normalizedId === item.id ? 'bg-indigo-50/50 border-l-2 border-l-indigo-500' : ''}`}>
                      <div className="flex-1">
                        <div className="text-slate-800 text-sm">{item.description}</div>
                        <div className="text-slate-500 text-xs mt-0.5">Std: {item.stdUOM} · {item.stdCurrency}</div>
                      </div>
                      <span className="text-slate-500 text-xs">{item.mappedCount} line{item.mappedCount !== 1 ? 's' : ''}</span>
                      {selectedLine.normalizedId === item.id && <CheckCircle size={14} className="text-indigo-600" />}
                    </div>
                  ))}
                </div>
              </div>
            </div>
          ) : (
            <div className="flex-1 flex items-center justify-center text-slate-400">
              <div className="text-center">
                <ArrowLeftRight size={32} className="mx-auto mb-3 opacity-40" />
                <p className="text-sm">Select a source line to begin mapping</p>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Conflict Resolution Modal */}
      {conflictModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <AlertTriangle size={18} className="text-amber-500" />
                <h2 className="text-slate-900 text-sm">Resolve Conflicts</h2>
              </div>
              <button onClick={() => setConflictModal(null)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6 space-y-4">
              <div className="bg-slate-50 rounded-lg p-3 text-xs text-slate-700">
                <span style={{ fontWeight: 600 }}>Line:</span> {conflictModal.raw} ({conflictModal.vendor})
              </div>
              {conflictModal.conflicts?.map((conflict, i) => (
                <div key={i} className="border border-slate-200 rounded-xl p-4">
                  <div className="flex items-center gap-2 mb-3">
                    <AlertCircle size={13} className="text-amber-500" />
                    <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>{conflict}</span>
                  </div>
                  {conflict.includes('UOM') && (
                    <div className="flex items-center gap-3">
                      <div className="flex-1">
                        <label className="text-slate-500 text-xs block mb-1">Source UOM</label>
                        <input className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-slate-50" value={conflictModal.uom} readOnly />
                      </div>
                      <ChevronRight size={14} className="text-slate-400 mt-4" />
                      <div className="flex-1">
                        <label className="text-slate-500 text-xs block mb-1">Map to Standard UOM</label>
                        <select className="w-full border border-indigo-300 rounded-lg px-3 py-2 text-xs bg-white text-slate-800">
                          <option>EA (Each)</option>
                          <option>Unit</option>
                          <option>License</option>
                        </select>
                      </div>
                    </div>
                  )}
                  {conflict.includes('Currency') && (
                    <div className="flex items-center gap-3">
                      <div className="flex-1">
                        <label className="text-slate-500 text-xs block mb-1">Source Currency</label>
                        <input className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-slate-50" value={conflictModal.currency} readOnly />
                      </div>
                      <ChevronRight size={14} className="text-slate-400 mt-4" />
                      <div className="flex-1">
                        <label className="text-slate-500 text-xs block mb-1">Convert to (Rate: 1 GBP = 1.27 USD)</label>
                        <select className="w-full border border-indigo-300 rounded-lg px-3 py-2 text-xs bg-white text-slate-800">
                          <option>USD (apply live rate)</option>
                        </select>
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setConflictModal(null)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setConflictModal(null)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply Resolutions</button>
            </div>
          </div>
        </div>
      )}

      {/* Override Modal */}
      {overrideModal && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <Edit3 size={16} className="text-slate-600" />
                <h2 className="text-slate-900 text-sm">Manual Override</h2>
              </div>
              <button onClick={() => setOverrideModal(null)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6 space-y-4">
              <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700 flex items-start gap-2">
                <AlertTriangle size={13} className="mt-0.5 flex-shrink-0" />
                <span>Manual overrides bypass AI normalization and are logged for audit purposes.</span>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Override Reason</label>
                <textarea className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 resize-none" rows={3} placeholder="Describe why this manual override is necessary..." value={overrideValue} onChange={e => setOverrideValue(e.target.value)} />
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Override Value (USD)</label>
                <input className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800" placeholder="Enter normalized unit price..." />
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setOverrideModal(null)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setOverrideModal(null)} className="bg-slate-900 hover:bg-slate-800 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply Override</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function ArrowLeftRight({ size, className }: { size: number; className?: string }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
      <path d="M8 3L4 7l4 4M4 7h16M16 21l4-4-4-4m4 4H4" />
    </svg>
  );
}
