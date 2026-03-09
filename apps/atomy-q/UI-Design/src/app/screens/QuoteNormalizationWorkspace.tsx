import { useState, useMemo } from 'react';
import {
  AlertTriangle, CheckCircle, XCircle, ChevronRight, Zap, RefreshCw,
  Edit3, AlertCircle, Search, Lock, Unlock, ArrowLeftRight, Layers,
  RotateCcw, CheckSquare, Square,
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import { RichTooltip, TooltipBadge } from '../components/RichTooltip';
import {
  sourceLines as mockSourceLines,
  normalizedItems as mockNormalizedItems,
  conflicts as mockConflicts,
  rfqLineItems,
  statusColors,
  formatCurrency,
  type NormalizationStatus,
} from '../data/mockData';

type MappingStatus = 'mapped' | 'conflict' | 'unmapped';

function StateBadge({ status }: { status: MappingStatus }) {
  const map: Record<MappingStatus, { label: string; icon: typeof CheckCircle; colors: string }> = {
    mapped: { label: 'Mapped', icon: CheckCircle, colors: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    conflict: { label: 'Conflict', icon: AlertTriangle, colors: 'bg-amber-50 text-amber-700 border-amber-200' },
    unmapped: { label: 'Unmapped', icon: XCircle, colors: 'bg-red-50 text-red-700 border-red-200' },
  };
  const { label, icon: Icon, colors } = map[status];
  return (
    <span className={`inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border ${colors}`} style={{ fontWeight: 600 }}>
      <Icon size={10} />
      {label}
    </span>
  );
}

function ConfidencePill({ score }: { score: number }) {
  const color = score > 90 ? 'bg-emerald-50 text-emerald-700' : score >= 75 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700';
  return (
    <span className={`text-xs px-2 py-0.5 rounded-full ${color}`} style={{ fontWeight: 600 }}>{score}%</span>
  );
}

function InlineConfidenceBar({ score }: { score: number }) {
  const color = score > 90 ? 'bg-emerald-500' : score >= 75 ? 'bg-amber-500' : 'bg-red-500';
  return (
    <div className="flex items-center gap-2 mt-1">
      <div className="flex-1 h-1 bg-slate-100 rounded-full overflow-hidden">
        <div className={`h-full rounded-full ${color}`} style={{ width: `${score}%` }} />
      </div>
      <ConfidencePill score={score} />
    </div>
  );
}

export function QuoteNormalizationWorkspace() {
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set(['sl-001']));
  const [activeId, setActiveId] = useState<string | null>('sl-001');
  const [activeTab, setActiveTab] = useState<'source' | 'conflicts'>('source');
  const [searchQuery, setSearchQuery] = useState('');
  const [isLocked, setIsLocked] = useState(false);

  const [showConflictResolve, setShowConflictResolve] = useState(false);
  const [showOverride, setShowOverride] = useState(false);
  const [showBulkMapping, setShowBulkMapping] = useState(false);
  const [showRevertOverride, setShowRevertOverride] = useState(false);
  const [showLockConfirm, setShowLockConfirm] = useState(false);
  const [showUnlockConfirm, setShowUnlockConfirm] = useState(false);

  const [conflictLine, setConflictLine] = useState<(typeof mockSourceLines)[0] | null>(null);
  const [overrideReason, setOverrideReason] = useState('');

  const conflictQueue = useMemo(() =>
    mockSourceLines.filter(l => l.mappingStatus === 'conflict'),
  []);

  const mapped = mockSourceLines.filter(l => l.mappingStatus === 'mapped').length;
  const conflictCount = conflictQueue.length;
  const unmapped = mockSourceLines.filter(l => l.mappingStatus === 'unmapped').length;

  const displayLines = activeTab === 'source' ? mockSourceLines : conflictQueue;
  const filteredLines = searchQuery
    ? displayLines.filter(l => l.description.toLowerCase().includes(searchQuery.toLowerCase()))
    : displayLines;

  const activeLine = mockSourceLines.find(l => l.id === activeId);
  const activeNorm = activeLine ? mockNormalizedItems.find(n => n.sourceLineId === activeLine.id) : null;
  const activeConflict = activeLine ? mockConflicts.find(c => c.sourceLineId === activeLine.id) : null;
  const activeMappedRfqItem = activeLine?.mappedTo ? rfqLineItems.find(li => li.id === activeLine.mappedTo) : null;

  const allSelected = filteredLines.length > 0 && filteredLines.every(l => selectedIds.has(l.id));

  function toggleSelect(id: string) {
    setSelectedIds(prev => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id); else next.add(id);
      return next;
    });
  }

  function toggleSelectAll() {
    if (allSelected) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(filteredLines.map(l => l.id)));
    }
  }

  function openConflict(line: (typeof mockSourceLines)[0]) {
    setConflictLine(line);
    setShowConflictResolve(true);
  }

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Lock Banner */}
      {isLocked && (
        <div className="bg-indigo-600 text-white px-6 py-2 flex items-center justify-between flex-shrink-0">
          <div className="flex items-center gap-2 text-sm">
            <Lock size={14} />
            <span style={{ fontWeight: 600 }}>Normalization Locked</span>
            <span className="text-indigo-200 text-xs ml-2">Data is frozen for comparison run. Unlock to edit.</span>
          </div>
          <button onClick={() => setShowUnlockConfirm(true)} className="flex items-center gap-1.5 bg-white/20 hover:bg-white/30 rounded-lg px-3 py-1 text-xs" style={{ fontWeight: 500 }}>
            <Unlock size={12} />
            Unlock
          </button>
        </div>
      )}

      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center justify-between">
          <div>
            <div className="text-xs text-slate-500 mb-1">RFQ-2401 · Server Infrastructure Components</div>
            <h1 className="text-slate-900" style={{ fontWeight: 600 }}>Quote Normalization Workspace</h1>
          </div>
          <div className="flex items-center gap-2">
            <div className="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs">
              <span className="flex items-center gap-1 text-emerald-600"><CheckCircle size={12} /> <b>{mapped}</b> mapped</span>
              <span className="w-px h-4 bg-slate-200" />
              <span className="flex items-center gap-1 text-amber-600"><AlertTriangle size={12} /> <b>{conflictCount}</b> conflicts</span>
              <span className="w-px h-4 bg-slate-200" />
              <span className="flex items-center gap-1 text-red-600"><XCircle size={12} /> <b>{unmapped}</b> unmapped</span>
            </div>
            {selectedIds.size > 1 && (
              <button onClick={() => setShowBulkMapping(true)} className="flex items-center gap-1.5 border border-indigo-200 bg-indigo-50 text-indigo-700 rounded-lg px-3 py-2 text-sm hover:bg-indigo-100" style={{ fontWeight: 500 }}>
                <Layers size={14} />
                Bulk Map ({selectedIds.size})
              </button>
            )}
            <button className="flex items-center gap-2 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 bg-white">
              <RefreshCw size={14} />
              Auto-Map
            </button>
            {!isLocked ? (
              <button onClick={() => setShowLockConfirm(true)} className="flex items-center gap-2 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 bg-white">
                <Lock size={14} />
                Lock
              </button>
            ) : null}
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
        <div className="w-[440px] flex-shrink-0 flex flex-col border-r border-slate-200 bg-white">
          {/* Tab bar */}
          <div className="flex border-b border-slate-200 flex-shrink-0">
            <button
              onClick={() => setActiveTab('source')}
              className={`flex-1 py-2.5 text-xs transition-colors ${activeTab === 'source' ? 'border-b-2 border-indigo-600 text-indigo-600 bg-indigo-50/40' : 'text-slate-500 hover:text-slate-700'}`}
              style={{ fontWeight: activeTab === 'source' ? 600 : 400 }}
            >
              Source Lines ({mockSourceLines.length})
            </button>
            <button
              onClick={() => setActiveTab('conflicts')}
              className={`flex-1 py-2.5 text-xs transition-colors relative ${activeTab === 'conflicts' ? 'border-b-2 border-amber-500 text-amber-600 bg-amber-50/40' : 'text-slate-500 hover:text-slate-700'}`}
              style={{ fontWeight: activeTab === 'conflicts' ? 600 : 400 }}
            >
              Conflict Queue
              {conflictCount > 0 && (
                <span className="absolute top-1.5 right-3 bg-amber-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" style={{ fontSize: 10 }}>{conflictCount}</span>
              )}
            </button>
          </div>

          {/* Search + Select All */}
          <div className="px-3 py-2 border-b border-slate-100 flex-shrink-0 flex items-center gap-2">
            <button onClick={toggleSelectAll} className="text-slate-400 hover:text-indigo-600 flex-shrink-0">
              {allSelected ? <CheckSquare size={14} className="text-indigo-600" /> : <Square size={14} />}
            </button>
            <div className="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 flex-1">
              <Search size={12} className="text-slate-400" />
              <input
                className="bg-transparent flex-1 text-xs text-slate-700 outline-none placeholder:text-slate-400"
                placeholder="Search lines..."
                value={searchQuery}
                onChange={e => setSearchQuery(e.target.value)}
              />
            </div>
          </div>

          {/* Lines */}
          <div className="flex-1 overflow-y-auto">
            {filteredLines.map(line => (
              <div
                key={line.id}
                className={`flex items-start gap-2 px-3 py-3 border-b border-slate-100 cursor-pointer transition-colors ${activeId === line.id ? 'bg-indigo-50 border-l-2 border-l-indigo-500' : 'hover:bg-slate-50'}`}
              >
                <button
                  onClick={e => { e.stopPropagation(); toggleSelect(line.id); }}
                  className="mt-0.5 flex-shrink-0 text-slate-400 hover:text-indigo-600"
                >
                  {selectedIds.has(line.id) ? <CheckSquare size={14} className="text-indigo-600" /> : <Square size={14} />}
                </button>
                <div className="flex-1 min-w-0" onClick={() => setActiveId(line.id)}>
                  <div className="flex items-start justify-between gap-2 mb-1">
                    <RichTooltip
                      trigger={
                        <span className="text-slate-800 text-xs truncate cursor-default hover:text-indigo-600" style={{ fontWeight: 500 }}>{line.description}</span>
                      }
                      side="right"
                    >
                      <div className="text-sm text-slate-900 mb-2" style={{ fontWeight: 600 }}>{line.description}</div>
                      <TooltipBadge label="Quantity" value={line.quantity} />
                      <TooltipBadge label="UOM" value={line.uom} />
                      <TooltipBadge label="Unit Price" value={formatCurrency(line.unitPrice, line.currency)} />
                      <TooltipBadge label="Currency" value={line.currency} />
                      <TooltipBadge label="Confidence" value={`${line.confidence}%`} color={line.confidence > 90 ? 'text-emerald-600' : line.confidence >= 75 ? 'text-amber-600' : 'text-red-600'} />
                    </RichTooltip>
                    <StateBadge status={line.mappingStatus} />
                  </div>
                  <div className="flex items-center gap-3 text-xs text-slate-500">
                    <span>{line.quantity} {line.uom}</span>
                    <span className="text-slate-700" style={{ fontWeight: 500 }}>{line.currency} {formatCurrency(line.unitPrice, line.currency)}</span>
                  </div>
                  <InlineConfidenceBar score={line.confidence} />
                  {line.mappingStatus === 'conflict' && (
                    <div className="mt-1.5">
                      {mockConflicts.filter(c => c.sourceLineId === line.id).map(c => (
                        <div key={c.id} className="flex items-center gap-1 text-amber-600 text-xs">
                          <AlertCircle size={10} />
                          <span className="truncate">{c.description}</span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* RIGHT: Mapping Editor */}
        <div className="flex-1 flex flex-col overflow-hidden">
          {activeLine ? (
            <div className="flex-1 overflow-auto p-4 space-y-4">
              {/* Mapping Editor Card */}
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                  <div className="flex items-center gap-2">
                    <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Mapping Editor</span>
                    <StateBadge status={activeLine.mappingStatus} />
                    <ConfidencePill score={activeLine.confidence} />
                  </div>
                  <div className="flex gap-2">
                    {activeConflict && (
                      <button onClick={() => openConflict(activeLine)} className="flex items-center gap-1.5 text-xs bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-3 py-1.5 hover:bg-amber-100">
                        <AlertTriangle size={12} />
                        Resolve Conflict
                      </button>
                    )}
                    <button onClick={() => setShowOverride(true)} className="flex items-center gap-1.5 text-xs bg-slate-50 border border-slate-200 text-slate-600 rounded-lg px-3 py-1.5 hover:bg-slate-100">
                      <Edit3 size={12} />
                      Manual Override
                    </button>
                    {activeNorm && (
                      <button onClick={() => setShowRevertOverride(true)} className="flex items-center gap-1.5 text-xs bg-slate-50 border border-slate-200 text-slate-600 rounded-lg px-3 py-1.5 hover:bg-slate-100">
                        <RotateCcw size={12} />
                        Revert
                      </button>
                    )}
                  </div>
                </div>
                <div className="p-5">
                  <div className="grid grid-cols-2 gap-6">
                    {/* Source (Raw) */}
                    <div>
                      <div className="text-xs text-slate-500 mb-3" style={{ fontWeight: 700 }}>SOURCE (RAW)</div>
                      <div className="space-y-3">
                        {[
                          { label: 'Description', value: activeLine.description },
                          { label: 'Quantity', value: String(activeLine.quantity) },
                          { label: 'UOM', value: activeLine.uom },
                          { label: 'Unit Price', value: formatCurrency(activeLine.unitPrice, activeLine.currency) },
                          { label: 'Currency', value: activeLine.currency },
                        ].map(f => (
                          <div key={f.label} className="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
                            <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>{f.label}</span>
                            <span className="text-slate-800 text-xs" style={{ fontWeight: 500 }}>{f.value}</span>
                          </div>
                        ))}
                      </div>
                    </div>

                    {/* Normalized (Target) */}
                    <div>
                      <div className="text-xs text-slate-500 mb-3" style={{ fontWeight: 700 }}>NORMALIZED (TARGET)</div>
                      <div className="space-y-3">
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between border border-indigo-100">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Description</span>
                          <span className="text-indigo-700 text-xs" style={{ fontWeight: 500 }}>
                            {activeNorm?.normalizedDescription ?? '—'}
                          </span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Taxonomy</span>
                          <span className="text-slate-800 text-xs" style={{ fontWeight: 500 }}>
                            {activeNorm?.taxonomyCode ?? '—'}
                          </span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Std. Qty</span>
                          <span className="text-slate-800 text-xs" style={{ fontWeight: 500 }}>
                            {activeNorm ? `${activeNorm.normalizedQty} ${activeNorm.normalizedUom}` : '—'}
                          </span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Std. UOM</span>
                          <span className={`text-xs ${activeNorm && activeNorm.normalizedUom !== activeLine.uom ? 'text-amber-700' : 'text-slate-800'}`} style={{ fontWeight: 500 }}>
                            {activeNorm?.normalizedUom ?? '—'}
                            {activeNorm && activeNorm.normalizedUom !== activeLine.uom && (
                              <span className="text-amber-500 ml-1">({activeLine.uom} → {activeNorm.normalizedUom})</span>
                            )}
                          </span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Std. Price</span>
                          <span className={`text-xs ${activeNorm?.conversionApplied ? 'text-amber-700' : 'text-slate-800'}`} style={{ fontWeight: 500 }}>
                            {activeNorm ? `${activeNorm.normalizedCurrency} ${formatCurrency(activeNorm.normalizedUnitPrice, activeNorm.normalizedCurrency)}` : '—'}
                            {activeNorm?.conversionApplied && <span className="text-amber-500 ml-1">(converted)</span>}
                          </span>
                        </div>
                        <div className="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-between">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>Confidence</span>
                          <ConfidencePill score={activeLine.confidence} />
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Map to RFQ Line Item */}
                  <div className="mt-5 bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div className="text-xs text-blue-700 mb-2" style={{ fontWeight: 600 }}>Map to RFQ Line Item</div>
                    <select className="w-full border border-blue-300 rounded-lg px-3 py-2 text-xs bg-white text-slate-800">
                      <option value="">— Select RFQ line item —</option>
                      {rfqLineItems.map(li => (
                        <option key={li.id} value={li.id} selected={activeLine.mappedTo === li.id}>
                          {li.description} ({li.quantity} {li.uom} @ {formatCurrency(li.estimatedUnitPrice)})
                        </option>
                      ))}
                    </select>
                    {activeMappedRfqItem && (
                      <div className="mt-2 flex items-center gap-2 text-xs text-blue-600">
                        <CheckCircle size={12} />
                        Currently mapped to: <span style={{ fontWeight: 600 }}>{activeMappedRfqItem.description}</span>
                      </div>
                    )}
                  </div>

                  {/* Active Conflict */}
                  {activeConflict && (
                    <div className="mt-5 bg-amber-50 border border-amber-200 rounded-xl p-4">
                      <div className="flex items-center gap-2 mb-3">
                        <AlertTriangle size={14} className="text-amber-600" />
                        <span className="text-amber-700 text-sm" style={{ fontWeight: 600 }}>Conflict Detected</span>
                      </div>
                      <div className="flex items-start gap-2 mb-2">
                        <AlertCircle size={12} className="text-amber-600 mt-0.5 flex-shrink-0" />
                        <span className="text-amber-700 text-xs">{activeConflict.description}</span>
                      </div>
                      <div className="text-xs text-amber-600 mb-3">
                        <span style={{ fontWeight: 500 }}>Suggested:</span> {activeConflict.suggestedResolution}
                      </div>
                      <button onClick={() => openConflict(activeLine)} className="bg-amber-600 hover:bg-amber-700 text-white text-xs px-3 py-1.5 rounded-lg" style={{ fontWeight: 500 }}>
                        Resolve Conflict
                      </button>
                    </div>
                  )}
                </div>
              </div>

              {/* Normalized Items Grid */}
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div className="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                  <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Normalized Catalog Items</span>
                  <span className="text-slate-400 text-xs">{mockNormalizedItems.length} items</span>
                </div>
                <div className="divide-y divide-slate-100">
                  {mockNormalizedItems.map(item => (
                    <div key={item.id} className={`flex items-center gap-4 px-5 py-3 hover:bg-slate-50 cursor-pointer ${activeLine.id === item.sourceLineId ? 'bg-indigo-50/50 border-l-2 border-l-indigo-500' : ''}`}>
                      <div className="flex-1">
                        <div className="text-slate-800 text-sm">{item.normalizedDescription}</div>
                        <div className="text-slate-500 text-xs mt-0.5">
                          {item.taxonomyCode} · {item.normalizedUom} · {item.normalizedCurrency}
                          {item.conversionApplied && (
                            <span className="ml-1.5 bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded text-xs" style={{ fontWeight: 500 }}>converted</span>
                          )}
                        </div>
                      </div>
                      <span className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>{formatCurrency(item.normalizedUnitPrice, item.normalizedCurrency)}</span>
                      {activeLine.id === item.sourceLineId && <CheckCircle size={14} className="text-indigo-600" />}
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

      {/* ── SlideOver: Resolve Line Mapping Conflict ──────────── */}
      <SlideOver
        open={showConflictResolve}
        onOpenChange={setShowConflictResolve}
        title="Resolve Line Mapping Conflict"
        description={conflictLine ? `Conflict on: ${conflictLine.description}` : undefined}
        width="lg"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowConflictResolve(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowConflictResolve(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply Resolution</button>
          </div>
        }
      >
        {conflictLine && (
          <div className="space-y-4">
            <div className="bg-slate-50 rounded-lg p-3 text-xs text-slate-700">
              <span style={{ fontWeight: 600 }}>Source Line:</span> {conflictLine.description} — {conflictLine.quantity} {conflictLine.uom} @ {conflictLine.currency} {formatCurrency(conflictLine.unitPrice, conflictLine.currency)}
            </div>
            {mockConflicts.filter(c => c.sourceLineId === conflictLine.id).map(conflict => (
              <div key={conflict.id} className="border border-slate-200 rounded-xl p-4">
                <div className="flex items-center gap-2 mb-3">
                  <AlertCircle size={13} className="text-amber-500" />
                  <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>{conflict.description}</span>
                  <span className={`text-xs px-2 py-0.5 rounded-full ${conflict.type === 'uom_mismatch' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'}`} style={{ fontWeight: 500 }}>
                    {conflict.type.replace('_', ' ')}
                  </span>
                </div>
                <div className="bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-xs text-emerald-700 flex items-start gap-2 mb-3">
                  <Zap size={12} className="mt-0.5 flex-shrink-0" />
                  <span><span style={{ fontWeight: 600 }}>Suggested:</span> {conflict.suggestedResolution}</span>
                </div>
                {conflict.type === 'uom_mismatch' && (
                  <div className="flex items-center gap-3">
                    <div className="flex-1">
                      <label className="text-slate-500 text-xs block mb-1">Source UOM</label>
                      <input className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-slate-50" value={conflictLine.uom} readOnly />
                    </div>
                    <ChevronRight size={14} className="text-slate-400 mt-4" />
                    <div className="flex-1">
                      <label className="text-slate-500 text-xs block mb-1">Map to Standard UOM</label>
                      <select className="w-full border border-indigo-300 rounded-lg px-3 py-2 text-xs bg-white text-slate-800">
                        <option>EA (Each)</option>
                        <option>Unit</option>
                        <option>PCS (Pieces)</option>
                        <option>License</option>
                      </select>
                    </div>
                  </div>
                )}
                {conflict.type === 'no_match' && (
                  <div>
                    <label className="text-slate-500 text-xs block mb-1">Map to RFQ Line Item</label>
                    <select className="w-full border border-indigo-300 rounded-lg px-3 py-2 text-xs bg-white text-slate-800">
                      <option value="">— Select —</option>
                      {rfqLineItems.map(li => (
                        <option key={li.id} value={li.id}>{li.description} ({li.quantity} {li.uom})</option>
                      ))}
                    </select>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </SlideOver>

      {/* ── SlideOver: Normalization Override ─────────────────── */}
      <SlideOver
        open={showOverride}
        onOpenChange={setShowOverride}
        title="Normalization Override"
        description="Manually set normalized values for this line."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowOverride(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowOverride(false)} className="bg-slate-900 hover:bg-slate-800 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply Override</button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700 flex items-start gap-2">
            <AlertTriangle size={13} className="mt-0.5 flex-shrink-0" />
            <span>Manual overrides bypass AI normalization and are logged for audit purposes.</span>
          </div>
          {activeLine && (
            <div className="bg-slate-50 rounded-lg p-3 text-xs text-slate-700">
              <span style={{ fontWeight: 600 }}>Source:</span> {activeLine.description} — {activeLine.quantity} {activeLine.uom}
            </div>
          )}
          <div>
            <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Normalized Description</label>
            <input className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800" placeholder="Enter normalized description..." defaultValue={activeNorm?.normalizedDescription ?? ''} />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Quantity</label>
              <input className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800" placeholder="Qty" defaultValue={activeNorm?.normalizedQty ?? ''} />
            </div>
            <div>
              <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>UOM</label>
              <select className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-white text-slate-800">
                <option>EA</option><option>Unit</option><option>PCS</option><option>License</option><option>Box</option>
              </select>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Unit Price (AUD)</label>
              <input className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800" placeholder="0.00" defaultValue={activeNorm?.normalizedUnitPrice ?? ''} />
            </div>
            <div>
              <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Currency</label>
              <select className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-white text-slate-800">
                <option>AUD</option><option>USD</option><option>GBP</option><option>EUR</option>
              </select>
            </div>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Override Reason</label>
            <textarea className="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 resize-none" rows={3} placeholder="Describe why this manual override is necessary..." value={overrideReason} onChange={e => setOverrideReason(e.target.value)} />
          </div>
        </div>
      </SlideOver>

      {/* ── SlideOver: Bulk Mapping ──────────────────────────── */}
      <SlideOver
        open={showBulkMapping}
        onOpenChange={setShowBulkMapping}
        title="Bulk Mapping"
        description={`Apply mapping to ${selectedIds.size} selected source lines.`}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowBulkMapping(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowBulkMapping(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply to {selectedIds.size} Lines</button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-indigo-50 border border-indigo-200 rounded-lg p-3 text-xs text-indigo-700">
            <span style={{ fontWeight: 600 }}>{selectedIds.size} lines selected</span> — the settings below will be applied to all selected source lines.
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Taxonomy Code</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option value="">— Select taxonomy code —</option>
              <option>UNSPSC-43211501 (Servers)</option>
              <option>UNSPSC-43201803 (Memory)</option>
              <option>UNSPSC-43201811 (Storage)</option>
              <option>UNSPSC-43222604 (Networking)</option>
            </select>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Target UOM</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option>EA (Each)</option>
              <option>Unit</option>
              <option>PCS (Pieces)</option>
              <option>License</option>
            </select>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Target Currency</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option>AUD</option>
              <option>USD</option>
              <option>GBP</option>
              <option>EUR</option>
            </select>
          </div>
          <div className="border border-slate-200 rounded-lg divide-y divide-slate-100 max-h-48 overflow-y-auto">
            {mockSourceLines.filter(l => selectedIds.has(l.id)).map(l => (
              <div key={l.id} className="flex items-center justify-between px-3 py-2 text-xs">
                <span className="text-slate-700 truncate" style={{ fontWeight: 500 }}>{l.description}</span>
                <StateBadge status={l.mappingStatus} />
              </div>
            ))}
          </div>
        </div>
      </SlideOver>

      {/* ── SlideOver: Revert Override ───────────────────────── */}
      <SlideOver
        open={showRevertOverride}
        onOpenChange={setShowRevertOverride}
        title="Revert Override"
        description="Restore AI-generated normalization values."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowRevertOverride(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowRevertOverride(false)} className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Revert Override</button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
            <RotateCcw size={18} className="text-amber-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="text-amber-800 text-sm" style={{ fontWeight: 600 }}>Revert to AI values?</p>
              <p className="text-amber-600 text-xs mt-1">This will discard any manual overrides and restore the AI-generated normalization for this line item.</p>
            </div>
          </div>
          {activeLine && activeNorm && (
            <div className="bg-slate-50 rounded-lg p-3 space-y-1.5 text-xs">
              <div className="flex justify-between"><span className="text-slate-500">Line</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{activeLine.description}</span></div>
              <div className="flex justify-between"><span className="text-slate-500">AI Description</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{activeNorm.normalizedDescription}</span></div>
              <div className="flex justify-between"><span className="text-slate-500">AI Price</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{formatCurrency(activeNorm.normalizedUnitPrice, activeNorm.normalizedCurrency)}</span></div>
            </div>
          )}
        </div>
      </SlideOver>

      {/* ── SlideOver: Lock Normalization ────────────────────── */}
      <SlideOver
        open={showLockConfirm}
        onOpenChange={setShowLockConfirm}
        title="Lock Normalization"
        description="Freeze normalization data for a comparison run."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowLockConfirm(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => { setIsLocked(true); setShowLockConfirm(false); }} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><Lock size={14} /> Lock Normalization</span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
            <Lock size={18} className="text-blue-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="text-blue-800 text-sm" style={{ fontWeight: 600 }}>Locking prevents edits</p>
              <p className="text-blue-600 text-xs mt-1">Once locked, normalization data cannot be edited until explicitly unlocked. This ensures comparison runs use a consistent data snapshot.</p>
            </div>
          </div>
          <div className="bg-slate-50 rounded-lg p-3 space-y-1.5 text-xs">
            <div className="flex justify-between"><span className="text-slate-500">Total Lines</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{mockSourceLines.length}</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Mapped</span><span className="text-emerald-600" style={{ fontWeight: 500 }}>{mapped}</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Conflicts</span><span className="text-amber-600" style={{ fontWeight: 500 }}>{conflictCount}</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Unmapped</span><span className="text-red-600" style={{ fontWeight: 500 }}>{unmapped}</span></div>
          </div>
          {(conflictCount > 0 || unmapped > 0) && (
            <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700 flex items-start gap-2">
              <AlertTriangle size={13} className="mt-0.5 flex-shrink-0" />
              <span>There are unresolved conflicts or unmapped lines. Locking now will exclude them from the comparison run.</span>
            </div>
          )}
          <div>
            <label className="text-slate-600 text-xs block mb-1" style={{ fontWeight: 500 }}>Associated Comparison Run</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option>New Comparison Run (Preview)</option>
              <option>New Comparison Run (Final)</option>
            </select>
          </div>
        </div>
      </SlideOver>

      {/* ── SlideOver: Unlock Normalization ──────────────────── */}
      <SlideOver
        open={showUnlockConfirm}
        onOpenChange={setShowUnlockConfirm}
        title="Unlock Normalization"
        description="Allow edits to normalization data again."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowUnlockConfirm(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => { setIsLocked(false); setShowUnlockConfirm(false); }} className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><Unlock size={14} /> Unlock</span>
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <Unlock size={18} className="text-red-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="text-red-800 text-sm" style={{ fontWeight: 600 }}>Unlock normalization?</p>
              <p className="text-red-600 text-xs mt-1">Unlocking will mark any active comparison runs as stale. They will need to be regenerated after edits are complete.</p>
            </div>
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
