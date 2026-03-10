import { useState } from 'react';
import {
  Plus, Edit3, Archive, Send, Shield, Layers, Tag, ChevronRight,
  ToggleLeft, ToggleRight, Trash2, AlertTriangle, CheckCircle2,
  FileText, Clock, Hash, X, GripVertical,
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import {
  scoringPolicies, scoringModels, rfqs, statusColors, formatDate,
} from '../data/mockData';

type PolicyStatus = 'draft' | 'published' | 'archived';

const allCategories = [
  'IT Hardware', 'Network Equipment', 'General Supplies', 'Furniture',
  'Facilities', 'IT Infrastructure', 'Professional Services', 'Software',
];

export function ScoringPolicies() {
  const [selectedPolicyId, setSelectedPolicyId] = useState<string | null>(scoringPolicies[0]?.id ?? null);
  const [editMode, setEditMode] = useState(false);
  const [showPublishModal, setShowPublishModal] = useState(false);
  const [showAssignModal, setShowAssignModal] = useState(false);

  const [editDimensions, setEditDimensions] = useState<typeof scoringPolicies[0]['dimensions']>([]);
  const [editRules, setEditRules] = useState<string[]>([]);
  const [newRuleText, setNewRuleText] = useState('');
  const [assignCategories, setAssignCategories] = useState<string[]>([]);

  const selected = scoringPolicies.find(p => p.id === selectedPolicyId) ?? null;

  const enterEdit = () => {
    if (!selected) return;
    setEditDimensions([...selected.dimensions.map(d => ({ ...d }))]);
    setEditRules([...selected.rules]);
    setEditMode(true);
  };

  const cancelEdit = () => {
    setEditMode(false);
    setEditDimensions([]);
    setEditRules([]);
  };

  const openAssign = () => {
    if (!selected) return;
    setAssignCategories([...selected.assignedCategories]);
    setShowAssignModal(true);
  };

  const toggleAssignCategory = (cat: string) => {
    setAssignCategories(prev =>
      prev.includes(cat) ? prev.filter(c => c !== cat) : [...prev, cat],
    );
  };

  const addRule = () => {
    const text = newRuleText.trim();
    if (!text) return;
    setEditRules(prev => [...prev, text]);
    setNewRuleText('');
  };

  const removeRule = (idx: number) => {
    setEditRules(prev => prev.filter((_, i) => i !== idx));
  };

  const affectedModels = selected
    ? scoringModels.filter(m =>
        m.assignedCategories.some(c => selected.assignedCategories.includes(c)),
      )
    : [];
  const affectedRfqs = selected
    ? rfqs.filter(r => selected.assignedCategories.includes(r.category))
    : [];

  const RangeBar = ({ min, max }: { min: number; max: number }) => (
    <div className="relative h-2 w-full bg-slate-100 rounded-full overflow-hidden">
      <div
        className="absolute h-full bg-indigo-400 rounded-full"
        style={{ left: `${min}%`, width: `${max - min}%` }}
      />
      <div
        className="absolute h-full w-0.5 bg-indigo-700"
        style={{ left: `${min}%` }}
      />
      <div
        className="absolute h-full w-0.5 bg-indigo-700"
        style={{ left: `${max}%` }}
      />
    </div>
  );

  return (
    <div className="min-h-screen">
      {/* Header */}
      <div className="flex items-center justify-between mb-5">
        <div>
          <h1 className="text-xl text-slate-900" style={{ fontWeight: 700 }}>Scoring Policies</h1>
          <p className="text-sm text-slate-500 mt-0.5">Define, version, and assign scoring constraints</p>
        </div>
        <button
          className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 transition-colors"
          style={{ fontWeight: 500 }}
        >
          <Plus size={16} />
          Create Policy
        </button>
      </div>

      <div className="flex gap-5">
        {/* ── Left: Policy List ── */}
        <div className="w-[35%] flex-shrink-0 space-y-3">
          {scoringPolicies.map(policy => {
            const isActive = selectedPolicyId === policy.id;
            return (
              <button
                key={policy.id}
                onClick={() => { setSelectedPolicyId(policy.id); cancelEdit(); }}
                className={`w-full text-left bg-white border rounded-xl p-4 transition-all ${
                  isActive
                    ? 'border-indigo-300 ring-2 ring-indigo-100 shadow-sm'
                    : 'border-slate-200 hover:border-slate-300 hover:shadow-sm'
                }`}
              >
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2 mb-1.5">
                      <span className="text-sm text-slate-900 truncate" style={{ fontWeight: 600 }}>
                        {policy.name}
                      </span>
                    </div>
                    <div className="flex items-center gap-2 mb-2">
                      <span className="inline-flex items-center gap-1 text-[11px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>
                        <Hash size={10} />
                        v{policy.version}
                      </span>
                      <span className={`text-[11px] px-2 py-0.5 rounded-full ${statusColors[policy.status]}`} style={{ fontWeight: 500 }}>
                        {policy.status}
                      </span>
                    </div>
                    <p className="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                      {policy.description}
                    </p>
                  </div>
                  <ChevronRight size={16} className={`flex-shrink-0 mt-1 transition-colors ${isActive ? 'text-indigo-500' : 'text-slate-300'}`} />
                </div>
                <div className="flex items-center gap-3 mt-3 pt-3 border-t border-slate-100">
                  <span className="inline-flex items-center gap-1 text-[11px] text-slate-500">
                    <Layers size={11} />
                    {policy.dimensions.length} dimensions
                  </span>
                  <span className="inline-flex items-center gap-1 text-[11px] text-slate-500">
                    <Tag size={11} />
                    {policy.assignedCategories.length} categories
                  </span>
                  <span className="ml-auto text-[11px] text-slate-400">
                    {formatDate(policy.updatedAt)}
                  </span>
                </div>
              </button>
            );
          })}
        </div>

        {/* ── Right: Policy Detail / Editor ── */}
        <div className="flex-1 min-w-0">
          {selected ? (
            <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
              {/* Detail Header */}
              <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                  <div className="flex items-center gap-3 mb-1">
                    <h2 className="text-lg text-slate-900" style={{ fontWeight: 600 }}>{selected.name}</h2>
                    <span className="inline-flex items-center gap-1 text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>
                      <Hash size={11} />
                      v{selected.version}
                    </span>
                    <span className={`text-xs px-2 py-0.5 rounded-full ${statusColors[selected.status]}`} style={{ fontWeight: 500 }}>
                      {selected.status}
                    </span>
                  </div>
                  <p className="text-sm text-slate-500">{selected.description}</p>
                </div>
                <div className="flex items-center gap-2">
                  {!editMode && (
                    <>
                      <button
                        onClick={enterEdit}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors"
                        style={{ fontWeight: 500 }}
                      >
                        <Edit3 size={13} /> Edit
                      </button>
                      {selected.status === 'draft' && (
                        <button
                          onClick={() => setShowPublishModal(true)}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
                          style={{ fontWeight: 500 }}
                        >
                          <Send size={13} /> Publish
                        </button>
                      )}
                      {selected.status === 'published' && (
                        <button
                          onClick={() => setShowPublishModal(true)}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
                          style={{ fontWeight: 500 }}
                        >
                          <Send size={13} /> Publish New Version
                        </button>
                      )}
                      {selected.status !== 'archived' && (
                        <button
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-500 hover:bg-slate-50 transition-colors"
                          style={{ fontWeight: 500 }}
                        >
                          <Archive size={13} /> Archive
                        </button>
                      )}
                    </>
                  )}
                  {editMode && (
                    <>
                      <button
                        onClick={cancelEdit}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors"
                        style={{ fontWeight: 500 }}
                      >
                        Cancel
                      </button>
                      <button
                        onClick={cancelEdit}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                        style={{ fontWeight: 500 }}
                      >
                        <CheckCircle2 size={13} /> Save Changes
                      </button>
                    </>
                  )}
                </div>
              </div>

              <div className="px-6 py-5 space-y-6">
                {/* ── Dimensions ── */}
                <div>
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-sm text-slate-900" style={{ fontWeight: 600 }}>Scoring Dimensions</h3>
                    {editMode && (
                      <button className="text-xs text-indigo-600 hover:text-indigo-700" style={{ fontWeight: 500 }}>
                        + Add Dimension
                      </button>
                    )}
                  </div>
                  <div className="border border-slate-200 rounded-lg overflow-hidden">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="bg-slate-50/70 border-b border-slate-200">
                          {editMode && <th className="w-8 px-2 py-2.5" />}
                          <th className="px-4 py-2.5 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Dimension</th>
                          <th className="px-4 py-2.5 text-center text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Min %</th>
                          <th className="px-4 py-2.5 text-center text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Max %</th>
                          <th className="px-4 py-2.5 text-center text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Range</th>
                          <th className="px-4 py-2.5 text-center text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Mandatory</th>
                          {editMode && <th className="w-10 px-2 py-2.5" />}
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {(editMode ? editDimensions : selected.dimensions).map((dim, idx) => (
                          <tr key={idx} className="group">
                            {editMode && (
                              <td className="px-2 py-3 text-center">
                                <GripVertical size={14} className="text-slate-300 cursor-grab" />
                              </td>
                            )}
                            <td className="px-4 py-3">
                              <div className="flex items-center gap-2">
                                <Shield size={14} className="text-indigo-400" />
                                <span className="text-slate-900" style={{ fontWeight: 500 }}>{dim.name}</span>
                              </div>
                            </td>
                            <td className="px-4 py-3 text-center">
                              {editMode ? (
                                <input
                                  type="number"
                                  value={dim.weightMin}
                                  onChange={e => {
                                    const val = parseInt(e.target.value) || 0;
                                    setEditDimensions(prev => prev.map((d, i) => i === idx ? { ...d, weightMin: val } : d));
                                  }}
                                  className="w-16 text-center text-xs border border-slate-200 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                  min={0}
                                  max={100}
                                />
                              ) : (
                                <span className="text-xs text-slate-600" style={{ fontWeight: 500 }}>{dim.weightMin}%</span>
                              )}
                            </td>
                            <td className="px-4 py-3 text-center">
                              {editMode ? (
                                <input
                                  type="number"
                                  value={dim.weightMax}
                                  onChange={e => {
                                    const val = parseInt(e.target.value) || 0;
                                    setEditDimensions(prev => prev.map((d, i) => i === idx ? { ...d, weightMax: val } : d));
                                  }}
                                  className="w-16 text-center text-xs border border-slate-200 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                  min={0}
                                  max={100}
                                />
                              ) : (
                                <span className="text-xs text-slate-600" style={{ fontWeight: 500 }}>{dim.weightMax}%</span>
                              )}
                            </td>
                            <td className="px-4 py-3">
                              <div className="w-32 mx-auto">
                                <RangeBar min={dim.weightMin} max={dim.weightMax} />
                              </div>
                            </td>
                            <td className="px-4 py-3 text-center">
                              {editMode ? (
                                <button
                                  onClick={() =>
                                    setEditDimensions(prev =>
                                      prev.map((d, i) => i === idx ? { ...d, mandatory: !d.mandatory } : d),
                                    )
                                  }
                                  className="inline-flex"
                                >
                                  {dim.mandatory
                                    ? <ToggleRight size={22} className="text-indigo-600" />
                                    : <ToggleLeft size={22} className="text-slate-300" />
                                  }
                                </button>
                              ) : dim.mandatory ? (
                                <span className="inline-flex items-center gap-1 text-[11px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full" style={{ fontWeight: 500 }}>
                                  <CheckCircle2 size={10} /> Required
                                </span>
                              ) : (
                                <span className="text-[11px] text-slate-400">Optional</span>
                              )}
                            </td>
                            {editMode && (
                              <td className="px-2 py-3 text-center">
                                <button className="p-1 rounded hover:bg-red-50 text-slate-300 hover:text-red-500 transition-colors">
                                  <Trash2 size={13} />
                                </button>
                              </td>
                            )}
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>

                {/* ── Constraint Rules ── */}
                <div>
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-sm text-slate-900" style={{ fontWeight: 600 }}>Constraint Rules</h3>
                  </div>
                  <div className="space-y-2">
                    {(editMode ? editRules : selected.rules).map((rule, idx) => (
                      <div key={idx} className="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5">
                        <FileText size={14} className="text-slate-400 flex-shrink-0" />
                        <span className="text-sm text-slate-700 flex-1">{rule}</span>
                        {editMode && (
                          <button
                            onClick={() => removeRule(idx)}
                            className="p-1 rounded hover:bg-red-50 text-slate-300 hover:text-red-500 transition-colors flex-shrink-0"
                          >
                            <X size={14} />
                          </button>
                        )}
                      </div>
                    ))}
                    {editMode && (
                      <div className="flex items-center gap-2 mt-2">
                        <input
                          type="text"
                          value={newRuleText}
                          onChange={e => setNewRuleText(e.target.value)}
                          onKeyDown={e => e.key === 'Enter' && addRule()}
                          placeholder="Type a new constraint rule…"
                          className="flex-1 text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400"
                        />
                        <button
                          onClick={addRule}
                          className="px-3 py-2 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                          style={{ fontWeight: 500 }}
                        >
                          Add
                        </button>
                      </div>
                    )}
                  </div>
                </div>

                {/* ── Assigned Categories ── */}
                <div>
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-sm text-slate-900" style={{ fontWeight: 600 }}>Assigned Categories</h3>
                    <button
                      onClick={openAssign}
                      className="text-xs text-indigo-600 hover:text-indigo-700"
                      style={{ fontWeight: 500 }}
                    >
                      Manage Assignments
                    </button>
                  </div>
                  {selected.assignedCategories.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                      {selected.assignedCategories.map(cat => (
                        <span
                          key={cat}
                          className="inline-flex items-center gap-1.5 text-xs bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg border border-indigo-100"
                          style={{ fontWeight: 500 }}
                        >
                          <Tag size={11} />
                          {cat}
                        </span>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-slate-400 italic">No categories assigned yet.</p>
                  )}
                </div>

                {/* ── Version Info ── */}
                <div className="bg-slate-50 border border-slate-200 rounded-lg p-4">
                  <div className="flex items-center gap-6">
                    <div>
                      <span className="text-[11px] text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Version</span>
                      <p className="text-sm text-slate-900 mt-0.5" style={{ fontWeight: 600 }}>v{selected.version}</p>
                    </div>
                    <div>
                      <span className="text-[11px] text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Last Updated</span>
                      <p className="text-sm text-slate-900 mt-0.5 flex items-center gap-1.5">
                        <Clock size={13} className="text-slate-400" />
                        {formatDate(selected.updatedAt)}
                      </p>
                    </div>
                    <div>
                      <span className="text-[11px] text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Status</span>
                      <p className="mt-0.5">
                        <span className={`text-xs px-2 py-0.5 rounded-full ${statusColors[selected.status]}`} style={{ fontWeight: 500 }}>
                          {selected.status}
                        </span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <div className="bg-white border border-slate-200 rounded-xl flex items-center justify-center py-24">
              <div className="text-center">
                <Shield size={40} className="mx-auto text-slate-200 mb-3" />
                <p className="text-sm text-slate-400">Select a policy to view details</p>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* ── Confirm Publish SlideOver ── */}
      <SlideOver
        open={showPublishModal}
        onOpenChange={setShowPublishModal}
        title="Confirm Publish"
        description={`Publish ${selected?.name ?? ''} as v${(selected?.version ?? 0) + 1}`}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => setShowPublishModal(false)}
              className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50"
              style={{ fontWeight: 500 }}
            >
              Cancel
            </button>
            <button
              onClick={() => setShowPublishModal(false)}
              className="px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700"
              style={{ fontWeight: 500 }}
            >
              Confirm &amp; Publish
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div className="flex gap-3">
              <AlertTriangle size={18} className="text-amber-600 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm text-amber-800" style={{ fontWeight: 500 }}>
                  Publishing will apply this policy to all assigned categories.
                </p>
                <p className="text-xs text-amber-700 mt-1">
                  Existing scoring models within those categories will use the updated constraints. In-progress comparisons may need to be re-run.
                </p>
              </div>
            </div>
          </div>

          {affectedModels.length > 0 && (
            <div>
              <p className="text-xs text-slate-500 mb-2" style={{ fontWeight: 600 }}>Affected Scoring Models ({affectedModels.length})</p>
              <div className="space-y-2">
                {affectedModels.map(m => (
                  <div key={m.id} className="flex items-center justify-between border border-slate-200 rounded-lg px-4 py-2.5">
                    <div className="flex items-center gap-2">
                      <Shield size={14} className="text-indigo-400" />
                      <span className="text-sm text-slate-800" style={{ fontWeight: 500 }}>{m.name}</span>
                    </div>
                    <span className={`text-[11px] px-2 py-0.5 rounded-full ${statusColors[m.status]}`} style={{ fontWeight: 500 }}>
                      {m.status}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {affectedRfqs.length > 0 && (
            <div>
              <p className="text-xs text-slate-500 mb-2" style={{ fontWeight: 600 }}>Affected RFQs ({affectedRfqs.length})</p>
              <div className="space-y-2">
                {affectedRfqs.map(r => (
                  <div key={r.id} className="flex items-center justify-between border border-slate-200 rounded-lg px-4 py-2.5">
                    <div className="flex items-center gap-2">
                      <span className="text-sm text-indigo-600" style={{ fontWeight: 600 }}>{r.id}</span>
                      <span className="text-sm text-slate-600">{r.title}</span>
                    </div>
                    <span className={`text-[11px] px-2 py-0.5 rounded-full ${statusColors[r.status]}`} style={{ fontWeight: 500 }}>
                      {r.status}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </SlideOver>

      {/* ── Policy Assignment SlideOver ── */}
      <SlideOver
        open={showAssignModal}
        onOpenChange={setShowAssignModal}
        title="Policy Assignment"
        description={`Assign "${selected?.name ?? ''}" to procurement categories`}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => setShowAssignModal(false)}
              className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50"
              style={{ fontWeight: 500 }}
            >
              Cancel
            </button>
            <button
              onClick={() => setShowAssignModal(false)}
              className="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
              style={{ fontWeight: 500 }}
            >
              Save Assignments
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <p className="text-xs text-slate-500">
            Select the procurement categories this policy applies to. Categories can only belong to one active policy at a time.
          </p>
          <div className="space-y-2">
            {allCategories.map(cat => {
              const isAssigned = assignCategories.includes(cat);
              const ownedByOther = scoringPolicies.find(
                p => p.id !== selected?.id && p.status === 'published' && p.assignedCategories.includes(cat),
              );
              return (
                <button
                  key={cat}
                  onClick={() => toggleAssignCategory(cat)}
                  className={`w-full flex items-center justify-between px-4 py-3 border rounded-lg text-left transition-colors ${
                    isAssigned
                      ? 'border-indigo-300 bg-indigo-50'
                      : 'border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <div className={`w-4 h-4 rounded border-2 flex items-center justify-center transition-colors ${
                      isAssigned ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300'
                    }`}>
                      {isAssigned && (
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                          <path d="M2 5L4 7L8 3" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      )}
                    </div>
                    <span className="text-sm text-slate-700" style={{ fontWeight: isAssigned ? 500 : 400 }}>{cat}</span>
                  </div>
                  {ownedByOther && !isAssigned && (
                    <span className="text-[10px] text-slate-400 italic">
                      {ownedByOther.name}
                    </span>
                  )}
                </button>
              );
            })}
          </div>
          <div className="flex flex-wrap gap-1.5 pt-2 border-t border-slate-100">
            {assignCategories.map(cat => (
              <span key={cat} className="inline-flex items-center gap-1 text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-lg" style={{ fontWeight: 500 }}>
                {cat}
                <button onClick={() => toggleAssignCategory(cat)} className="hover:text-indigo-900">
                  <X size={12} />
                </button>
              </span>
            ))}
            {assignCategories.length === 0 && (
              <span className="text-xs text-slate-400 italic">No categories selected</span>
            )}
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
