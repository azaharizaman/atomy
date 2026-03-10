import { useState } from 'react';
import {
  Shield, CheckCircle, XCircle, AlertTriangle, Filter, Download,
  Hash, User, Clock, ChevronRight, ChevronDown, Link2, Search,
  ShieldCheck, FileDown, Calendar, Activity
} from 'lucide-react';
import { Link } from 'react-router';
import { SlideOver } from '../components/SlideOver';
import {
  decisionTrailEntries, rfqs, getUserById, formatDateTime,
} from '../data/mockData';

const eventTypeConfig: Record<string, { label: string; color: string }> = {
  rfq_created: { label: 'RFQ Created', color: 'bg-blue-100 text-blue-700' },
  rfq_published: { label: 'RFQ Published', color: 'bg-indigo-100 text-indigo-700' },
  quote_accepted: { label: 'Quote Accepted', color: 'bg-emerald-100 text-emerald-700' },
  normalization_locked: { label: 'Normalization Locked', color: 'bg-purple-100 text-purple-700' },
  matrix_built: { label: 'Matrix Built', color: 'bg-indigo-100 text-indigo-700' },
  scoring_computed: { label: 'Scoring Computed', color: 'bg-purple-100 text-purple-700' },
  approval_required: { label: 'Approval Required', color: 'bg-amber-100 text-amber-700' },
  sanctions_flagged: { label: 'Sanctions Flagged', color: 'bg-red-100 text-red-700' },
};

const allEventTypes = Object.keys(eventTypeConfig);

export function DecisionTrail() {
  const [scopeFilter, setScopeFilter] = useState('all');
  const [eventTypeFilter, setEventTypeFilter] = useState('all');
  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const [showExportModal, setShowExportModal] = useState(false);
  const [expandedEntryId, setExpandedEntryId] = useState<string | null>(null);
  const [exportScope, setExportScope] = useState('current');
  const [exportFormat, setExportFormat] = useState('pdf');

  const filteredEntries = decisionTrailEntries.filter((entry) => {
    if (scopeFilter !== 'all' && entry.rfqId !== scopeFilter) return false;
    if (eventTypeFilter !== 'all' && entry.eventType !== eventTypeFilter) return false;
    return true;
  });

  const allVerified = filteredEntries.every((e) => e.verified);
  const verifiedCount = filteredEntries.filter((e) => e.verified).length;

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Page Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>Decision Trail</span>
        </div>

        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-3 mb-1">
              <h1 className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>Decision Trail</h1>
              <div className={`flex items-center gap-1.5 px-2.5 py-1 rounded-full border ${allVerified ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'}`}>
                {allVerified ? <ShieldCheck size={12} className="text-emerald-600" /> : <AlertTriangle size={12} className="text-red-600" />}
                <span className={`text-xs ${allVerified ? 'text-emerald-700' : 'text-red-700'}`} style={{ fontWeight: 600 }}>
                  {allVerified ? 'Chain Verified' : 'Integrity Warning'}
                </span>
              </div>
            </div>
            <p className="text-xs text-slate-500">
              Immutable event ledger for governance and audit compliance &middot; {filteredEntries.length} entries
            </p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowVerifyModal(true)}
              className="flex items-center gap-2 border border-emerald-300 bg-emerald-50 rounded-lg px-3 py-2 text-sm text-emerald-700 hover:bg-emerald-100 transition-colors"
              style={{ fontWeight: 500 }}
            >
              <ShieldCheck size={14} />
              Verify Integrity
            </button>
            <button
              onClick={() => setShowExportModal(true)}
              className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 transition-colors"
              style={{ fontWeight: 500 }}
            >
              <Download size={14} />
              Export Trail
            </button>
          </div>
        </div>
      </div>

      {/* Content Area */}
      <div className="flex-1 overflow-hidden flex">
        {/* Filter Sidebar */}
        <div className="w-64 bg-white border-r border-slate-200 p-4 flex-shrink-0 overflow-y-auto">
          <div className="flex items-center gap-2 mb-4">
            <Filter size={14} className="text-slate-500" />
            <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>Filters</span>
          </div>

          {/* Scope Filter */}
          <div className="mb-5">
            <label className="text-xs text-slate-500 block mb-1.5" style={{ fontWeight: 600 }}>SCOPE</label>
            <select
              value={scopeFilter}
              onChange={(e) => setScopeFilter(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:border-indigo-400 outline-none"
            >
              <option value="all">All RFQs</option>
              {rfqs.map((r) => (
                <option key={r.id} value={r.id}>{r.id} — {r.title}</option>
              ))}
            </select>
          </div>

          {/* Event Type Filter */}
          <div className="mb-5">
            <label className="text-xs text-slate-500 block mb-1.5" style={{ fontWeight: 600 }}>EVENT TYPE</label>
            <select
              value={eventTypeFilter}
              onChange={(e) => setEventTypeFilter(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:border-indigo-400 outline-none"
            >
              <option value="all">All Types</option>
              {allEventTypes.map((et) => (
                <option key={et} value={et}>{eventTypeConfig[et].label}</option>
              ))}
            </select>
          </div>

          {/* Quick Stats */}
          <div className="border-t border-slate-100 pt-4 mt-4">
            <div className="text-xs text-slate-500 mb-3" style={{ fontWeight: 600 }}>QUICK STATS</div>
            <div className="space-y-2">
              <div className="flex items-center justify-between text-xs">
                <span className="text-slate-500">Total Entries</span>
                <span className="text-slate-800" style={{ fontWeight: 600 }}>{filteredEntries.length}</span>
              </div>
              <div className="flex items-center justify-between text-xs">
                <span className="text-slate-500">Verified</span>
                <span className="text-emerald-700" style={{ fontWeight: 600 }}>{verifiedCount}</span>
              </div>
              <div className="flex items-center justify-between text-xs">
                <span className="text-slate-500">Failed</span>
                <span className={`${filteredEntries.length - verifiedCount > 0 ? 'text-red-700' : 'text-slate-400'}`} style={{ fontWeight: 600 }}>
                  {filteredEntries.length - verifiedCount}
                </span>
              </div>
            </div>
          </div>

          {/* Event Type Breakdown */}
          <div className="border-t border-slate-100 pt-4 mt-4">
            <div className="text-xs text-slate-500 mb-3" style={{ fontWeight: 600 }}>BY EVENT TYPE</div>
            <div className="space-y-1.5">
              {allEventTypes.map((et) => {
                const count = filteredEntries.filter((e) => e.eventType === et).length;
                if (count === 0) return null;
                const cfg = eventTypeConfig[et];
                return (
                  <div key={et} className="flex items-center justify-between text-xs">
                    <span className={`px-1.5 py-0.5 rounded ${cfg.color}`} style={{ fontWeight: 500 }}>{cfg.label}</span>
                    <span className="text-slate-600" style={{ fontWeight: 600 }}>{count}</span>
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* Timeline */}
        <div className="flex-1 overflow-y-auto p-6">
          {/* Integrity Banner */}
          <div className={`flex items-center gap-3 px-4 py-3 rounded-xl border mb-5 ${allVerified ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'}`}>
            {allVerified ? <CheckCircle size={15} className="text-emerald-600" /> : <AlertTriangle size={15} className="text-red-600" />}
            <span className={`text-sm ${allVerified ? 'text-emerald-700' : 'text-red-700'}`} style={{ fontWeight: 600 }}>
              {allVerified ? 'Hash Chain Integrity Verified' : 'Hash Chain Integrity Warning'}
            </span>
            <span className={`text-xs ${allVerified ? 'text-emerald-600' : 'text-red-600'}`}>
              {verifiedCount}/{filteredEntries.length} entries validated &middot; SHA-256
            </span>
            {allVerified && (
              <span className="ml-auto text-xs bg-emerald-600 text-white px-2 py-0.5 rounded" style={{ fontWeight: 600 }}>Verified</span>
            )}
          </div>

          {/* Timeline entries */}
          <div className="relative">
            {filteredEntries.map((entry, index) => {
              const cfg = eventTypeConfig[entry.eventType] || { label: entry.eventType, color: 'bg-slate-100 text-slate-600' };
              const user = entry.actor !== 'System' ? getUserById(entry.actor) : null;
              const actorName = user ? user.name : entry.actor;
              const isExpanded = expandedEntryId === entry.id;
              const isLast = index === filteredEntries.length - 1;

              return (
                <div key={entry.id} className="relative flex gap-4 pb-1">
                  {/* Vertical connector line */}
                  <div className="flex flex-col items-center w-8 flex-shrink-0">
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 border-2 z-10 ${
                      entry.verified ? 'bg-white border-emerald-400' : 'bg-white border-red-400'
                    }`}>
                      <span className="text-xs text-slate-600" style={{ fontWeight: 700 }}>{entry.sequence}</span>
                    </div>
                    {!isLast && (
                      <div className="w-0.5 flex-1 bg-slate-200 min-h-[24px]" />
                    )}
                  </div>

                  {/* Entry Card */}
                  <div className="flex-1 mb-3">
                    <div
                      className="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                      onClick={() => setExpandedEntryId(isExpanded ? null : entry.id)}
                    >
                      <div className="flex items-start gap-3 px-4 py-3">
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 flex-wrap mb-1">
                            <span className={`text-xs px-2 py-0.5 rounded ${cfg.color}`} style={{ fontWeight: 600 }}>{cfg.label}</span>
                            <span className="text-slate-300">|</span>
                            <span className="text-xs text-slate-500">{entry.rfqId}</span>
                          </div>
                          <p className="text-sm text-slate-800 mb-1.5" style={{ fontWeight: 500 }}>{entry.description}</p>
                          <div className="flex items-center gap-3 text-xs text-slate-500">
                            <span className="flex items-center gap-1">
                              <User size={11} />
                              {actorName}
                              {entry.actorRole !== actorName && <span className="text-slate-400">({entry.actorRole})</span>}
                            </span>
                            <span className="flex items-center gap-1">
                              <Clock size={11} />
                              {formatDateTime(entry.timestamp)}
                            </span>
                          </div>
                        </div>

                        <div className="flex items-center gap-2 flex-shrink-0">
                          {entry.verified ? (
                            <CheckCircle size={16} className="text-emerald-500" />
                          ) : (
                            <XCircle size={16} className="text-red-500" />
                          )}
                          {isExpanded ? <ChevronDown size={14} className="text-slate-400" /> : <ChevronRight size={14} className="text-slate-400" />}
                        </div>
                      </div>

                      {/* Expanded hash details */}
                      {isExpanded && (
                        <div className="border-t border-slate-100 px-4 py-3 bg-slate-50/50 rounded-b-xl">
                          <div className="grid grid-cols-2 gap-4">
                            <div>
                              <div className="text-xs text-slate-500 mb-1" style={{ fontWeight: 600 }}>
                                <span className="flex items-center gap-1"><Hash size={10} /> Entry Hash</span>
                              </div>
                              <code className="text-xs text-slate-700 bg-slate-100 px-2 py-1 rounded font-mono block break-all">
                                {entry.entryHash}
                              </code>
                            </div>
                            <div>
                              <div className="text-xs text-slate-500 mb-1" style={{ fontWeight: 600 }}>
                                <span className="flex items-center gap-1"><Link2 size={10} /> Previous Hash</span>
                              </div>
                              <code className="text-xs text-slate-700 bg-slate-100 px-2 py-1 rounded font-mono block break-all">
                                {entry.previousHash}
                              </code>
                            </div>
                          </div>
                          <div className="flex items-center gap-4 mt-3 text-xs text-slate-500">
                            <span>Related: <b className="text-slate-700">{entry.relatedId}</b></span>
                            <span className="flex items-center gap-1">
                              {entry.verified ? (
                                <><CheckCircle size={11} className="text-emerald-500" /> Hash chain valid</>
                              ) : (
                                <><XCircle size={11} className="text-red-500" /> Hash mismatch detected</>
                              )}
                            </span>
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}

            {filteredEntries.length === 0 && (
              <div className="text-center py-16">
                <Search size={32} className="text-slate-300 mx-auto mb-3" />
                <p className="text-slate-500 text-sm">No entries match the current filters.</p>
                <button
                  onClick={() => { setScopeFilter('all'); setEventTypeFilter('all'); }}
                  className="text-indigo-600 text-sm mt-2 hover:text-indigo-700"
                  style={{ fontWeight: 500 }}
                >
                  Clear Filters
                </button>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Verify Audit Integrity SlideOver */}
      <SlideOver
        open={showVerifyModal}
        onOpenChange={setShowVerifyModal}
        title="Verify Audit Integrity"
        description="Validate the cryptographic hash chain for all decision trail entries."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => setShowVerifyModal(false)}
              className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
            >
              Close
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          {/* Summary */}
          <div className={`rounded-xl border p-4 ${allVerified ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'}`}>
            <div className="flex items-center gap-3 mb-3">
              {allVerified ? <ShieldCheck size={20} className="text-emerald-600" /> : <AlertTriangle size={20} className="text-red-600" />}
              <div>
                <div className={`text-sm ${allVerified ? 'text-emerald-800' : 'text-red-800'}`} style={{ fontWeight: 700 }}>
                  {allVerified ? 'All Checks Passed' : 'Integrity Issues Detected'}
                </div>
                <div className={`text-xs ${allVerified ? 'text-emerald-600' : 'text-red-600'}`}>
                  Algorithm: SHA-256 &middot; Verified at {new Date().toLocaleTimeString('en-AU', { hour: '2-digit', minute: '2-digit' })}
                </div>
              </div>
            </div>
            <div className="grid grid-cols-3 gap-3">
              <div className="bg-white/60 rounded-lg p-3 text-center">
                <div className="text-lg text-slate-800" style={{ fontWeight: 700 }}>{filteredEntries.length}</div>
                <div className="text-xs text-slate-500">Total Checked</div>
              </div>
              <div className="bg-white/60 rounded-lg p-3 text-center">
                <div className="text-lg text-emerald-700" style={{ fontWeight: 700 }}>{verifiedCount}</div>
                <div className="text-xs text-emerald-600">Passed</div>
              </div>
              <div className="bg-white/60 rounded-lg p-3 text-center">
                <div className={`text-lg ${filteredEntries.length - verifiedCount > 0 ? 'text-red-700' : 'text-slate-400'}`} style={{ fontWeight: 700 }}>
                  {filteredEntries.length - verifiedCount}
                </div>
                <div className="text-xs text-slate-500">Failed</div>
              </div>
            </div>
          </div>

          {/* Per-entry verification */}
          <div>
            <div className="text-xs text-slate-500 mb-2" style={{ fontWeight: 600 }}>CHAIN VERIFICATION</div>
            <div className="space-y-1.5">
              {filteredEntries.map((entry) => {
                const cfg = eventTypeConfig[entry.eventType] || { label: entry.eventType, color: 'bg-slate-100 text-slate-600' };
                return (
                  <div key={entry.id} className="flex items-center gap-3 px-3 py-2 rounded-lg bg-white border border-slate-100">
                    <span className="text-xs text-slate-500 w-6 text-right" style={{ fontWeight: 600 }}>#{entry.sequence}</span>
                    <span className={`text-xs px-1.5 py-0.5 rounded ${cfg.color} flex-shrink-0`} style={{ fontWeight: 500 }}>{cfg.label}</span>
                    <code className="text-xs text-slate-500 font-mono truncate flex-1">{entry.entryHash}</code>
                    {entry.verified ? (
                      <CheckCircle size={14} className="text-emerald-500 flex-shrink-0" />
                    ) : (
                      <XCircle size={14} className="text-red-500 flex-shrink-0" />
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      </SlideOver>

      {/* Export Decision Trail SlideOver */}
      <SlideOver
        open={showExportModal}
        onOpenChange={setShowExportModal}
        title="Export Decision Trail"
        description="Generate an exportable report of the decision trail."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => setShowExportModal(false)}
              className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm transition-colors"
              style={{ fontWeight: 500 }}
            >
              <FileDown size={14} />
              Generate Export
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          {/* Scope */}
          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 600 }}>Export Scope</label>
            <div className="space-y-2">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="radio"
                  name="exportScope"
                  value="current"
                  checked={exportScope === 'current'}
                  onChange={(e) => setExportScope(e.target.value)}
                  className="text-indigo-600"
                />
                <span className="text-sm text-slate-700">Current filter ({filteredEntries.length} entries)</span>
              </label>
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="radio"
                  name="exportScope"
                  value="full"
                  checked={exportScope === 'full'}
                  onChange={(e) => setExportScope(e.target.value)}
                  className="text-indigo-600"
                />
                <span className="text-sm text-slate-700">Full trail ({decisionTrailEntries.length} entries)</span>
              </label>
            </div>
          </div>

          {/* Format */}
          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 600 }}>Format</label>
            <div className="grid grid-cols-3 gap-2">
              {(['pdf', 'csv', 'json'] as const).map((fmt) => (
                <button
                  key={fmt}
                  onClick={() => setExportFormat(fmt)}
                  className={`px-3 py-2 rounded-lg border text-sm text-center uppercase transition-colors ${
                    exportFormat === fmt
                      ? 'border-indigo-400 bg-indigo-50 text-indigo-700'
                      : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                  }`}
                  style={{ fontWeight: exportFormat === fmt ? 600 : 400 }}
                >
                  {fmt}
                </button>
              ))}
            </div>
          </div>

          {/* Date Range */}
          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 600 }}>
              <span className="flex items-center gap-1"><Calendar size={11} /> Date Range (Optional)</span>
            </label>
            <div className="grid grid-cols-2 gap-2">
              <input
                type="date"
                className="border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:border-indigo-400 outline-none"
                placeholder="From"
              />
              <input
                type="date"
                className="border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:border-indigo-400 outline-none"
                placeholder="To"
              />
            </div>
          </div>

          {/* Preview */}
          <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <div className="flex items-center gap-2 mb-2">
              <Activity size={12} className="text-slate-500" />
              <span className="text-xs text-slate-600" style={{ fontWeight: 600 }}>Export Preview</span>
            </div>
            <div className="space-y-1 text-xs text-slate-500">
              <div className="flex justify-between">
                <span>Entries</span>
                <span className="text-slate-700" style={{ fontWeight: 500 }}>{exportScope === 'current' ? filteredEntries.length : decisionTrailEntries.length}</span>
              </div>
              <div className="flex justify-between">
                <span>Format</span>
                <span className="text-slate-700 uppercase" style={{ fontWeight: 500 }}>{exportFormat}</span>
              </div>
              <div className="flex justify-between">
                <span>Includes hashes</span>
                <span className="text-slate-700" style={{ fontWeight: 500 }}>Yes</span>
              </div>
              <div className="flex justify-between">
                <span>Includes verification</span>
                <span className="text-slate-700" style={{ fontWeight: 500 }}>Yes</span>
              </div>
            </div>
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
