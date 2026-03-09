import { useState, useMemo } from 'react';
import {
  FileText, Search, ChevronRight, Plus, Copy, Archive, Send,
  Edit3, Eye, Clock, BarChart3, Tag, Lock, CalendarDays,
  MoreHorizontal, Filter, Layers, CheckCircle, AlertTriangle, Hash
} from 'lucide-react';
import { Link } from 'react-router';
import { rfqTemplates, statusColors, formatDate } from '../data/mockData';
import type { TemplateStatus } from '../data/mockData';
import { SlideOver } from '../components/SlideOver';

const statusFilterOptions: { id: string; label: string }[] = [
  { id: 'all', label: 'All' },
  { id: 'published', label: 'Published' },
  { id: 'draft', label: 'Draft' },
  { id: 'archived', label: 'Archived' },
];

const categoryOptions = [...new Set(rfqTemplates.map((t) => t.category))];

export function RFQTemplates() {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedTemplateId, setSelectedTemplateId] = useState<string | null>(rfqTemplates[0]?.id ?? null);
  const [statusFilter, setStatusFilter] = useState('all');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [showArchiveModal, setShowArchiveModal] = useState(false);
  const [editMode, setEditMode] = useState(false);

  const [editName, setEditName] = useState('');
  const [editCategory, setEditCategory] = useState('');
  const [editDescription, setEditDescription] = useState('');
  const [editDeadlineDays, setEditDeadlineDays] = useState(14);
  const [editLockedFields, setEditLockedFields] = useState<string[]>([]);

  const filteredTemplates = useMemo(() => {
    let items = rfqTemplates;
    if (statusFilter !== 'all') items = items.filter((t) => t.status === statusFilter);
    if (categoryFilter !== 'all') items = items.filter((t) => t.category === categoryFilter);
    if (searchQuery) {
      const q = searchQuery.toLowerCase();
      items = items.filter((t) => t.name.toLowerCase().includes(q) || t.category.toLowerCase().includes(q));
    }
    return items;
  }, [searchQuery, statusFilter, categoryFilter]);

  const selectedTemplate = rfqTemplates.find((t) => t.id === selectedTemplateId);

  const startEdit = () => {
    if (!selectedTemplate) return;
    setEditName(selectedTemplate.name);
    setEditCategory(selectedTemplate.category);
    setEditDescription(selectedTemplate.description);
    setEditDeadlineDays(selectedTemplate.defaultDeadlineDays);
    setEditLockedFields([...selectedTemplate.lockedFields]);
    setEditMode(true);
  };

  const cancelEdit = () => {
    setEditMode(false);
  };

  const toggleLockedField = (field: string) => {
    setEditLockedFields((prev) =>
      prev.includes(field) ? prev.filter((f) => f !== field) : [...prev, field]
    );
  };

  const lockableFields = ['category', 'terms.warranty', 'terms.payment', 'terms.delivery', 'scoring_model', 'deadline'];

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>RFQ Templates</span>
        </div>
        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-3 mb-1">
              <Layers size={20} className="text-indigo-600" />
              <h1 className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>RFQ Templates</h1>
              <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full" style={{ fontWeight: 600 }}>
                {rfqTemplates.length} templates
              </span>
            </div>
            <p className="text-xs text-slate-500 mt-0.5">Create, manage, and apply reusable RFQ templates to accelerate procurement workflows.</p>
          </div>
          <button
            onClick={() => {
              setSelectedTemplateId(null);
              setEditName('');
              setEditCategory(categoryOptions[0] ?? '');
              setEditDescription('');
              setEditDeadlineDays(14);
              setEditLockedFields([]);
              setEditMode(true);
            }}
            className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2.5 text-sm"
            style={{ fontWeight: 500 }}
          >
            <Plus size={15} />
            Create Template
          </button>
        </div>
      </div>

      {/* Split Layout */}
      <div className="flex flex-1 overflow-hidden">
        {/* ─── Left Panel: Template List ─── */}
        <div className="w-[30%] min-w-[300px] bg-white border-r border-slate-200 flex flex-col flex-shrink-0">
          {/* Search + Filters */}
          <div className="p-3 border-b border-slate-100 space-y-2">
            <div className="relative">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search templates..."
                className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-700 focus:border-indigo-400 outline-none"
              />
            </div>
            <div className="flex items-center gap-2">
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="flex-1 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-600 bg-white focus:border-indigo-400 outline-none"
              >
                {statusFilterOptions.map((opt) => (
                  <option key={opt.id} value={opt.id}>{opt.label}</option>
                ))}
              </select>
              <select
                value={categoryFilter}
                onChange={(e) => setCategoryFilter(e.target.value)}
                className="flex-1 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-600 bg-white focus:border-indigo-400 outline-none"
              >
                <option value="all">All Categories</option>
                {categoryOptions.map((cat) => (
                  <option key={cat} value={cat}>{cat}</option>
                ))}
              </select>
            </div>
          </div>

          {/* Template Cards */}
          <div className="flex-1 overflow-y-auto p-2 space-y-1">
            {filteredTemplates.length === 0 && (
              <div className="text-center py-10">
                <FileText size={24} className="mx-auto text-slate-300 mb-2" />
                <div className="text-sm text-slate-400">No templates match your filters.</div>
              </div>
            )}
            {filteredTemplates.map((tpl) => {
              const isSelected = selectedTemplateId === tpl.id && !editMode;
              return (
                <button
                  key={tpl.id}
                  onClick={() => { setSelectedTemplateId(tpl.id); setEditMode(false); }}
                  className={`w-full text-left p-3 rounded-lg border transition-colors ${
                    isSelected
                      ? 'border-indigo-400 bg-indigo-50/60'
                      : 'border-slate-100 hover:border-slate-200 hover:bg-slate-50'
                  }`}
                >
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1">
                      <div className="text-sm text-slate-800 truncate" style={{ fontWeight: 600 }}>{tpl.name}</div>
                      <div className="flex items-center gap-2 mt-1.5">
                        <span className="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>
                          {tpl.category}
                        </span>
                        <span className={`text-[10px] px-1.5 py-0.5 rounded ${statusColors[tpl.status] ?? 'bg-slate-100 text-slate-600'}`} style={{ fontWeight: 600 }}>
                          {tpl.status}
                        </span>
                      </div>
                    </div>
                    {tpl.usageCount > 0 && (
                      <div className="flex items-center gap-1 text-[10px] text-slate-400 flex-shrink-0">
                        <BarChart3 size={10} />
                        {tpl.usageCount}
                      </div>
                    )}
                  </div>
                  {tpl.lastUsedAt && (
                    <div className="flex items-center gap-1 mt-2 text-[10px] text-slate-400">
                      <Clock size={9} />
                      Last used {formatDate(tpl.lastUsedAt)}
                    </div>
                  )}
                </button>
              );
            })}
          </div>
        </div>

        {/* ─── Right Panel: Detail / Editor ─── */}
        <div className="flex-1 overflow-y-auto p-6">
          {!selectedTemplate && !editMode && (
            <div className="flex flex-col items-center justify-center h-full text-center">
              <Layers size={40} className="text-slate-200 mb-3" />
              <div className="text-sm text-slate-400" style={{ fontWeight: 500 }}>Select a template from the list</div>
              <div className="text-xs text-slate-300 mt-1">or create a new one to get started</div>
            </div>
          )}

          {/* View Mode */}
          {selectedTemplate && !editMode && (
            <div className="max-w-2xl space-y-6">
              {/* Template Header */}
              <div className="flex items-start justify-between">
                <div>
                  <div className="flex items-center gap-3">
                    <h2 className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>{selectedTemplate.name}</h2>
                    <span className={`text-xs px-2 py-0.5 rounded ${statusColors[selectedTemplate.status] ?? 'bg-slate-100 text-slate-600'}`} style={{ fontWeight: 600 }}>
                      {selectedTemplate.status}
                    </span>
                  </div>
                  <p className="text-xs text-slate-500 mt-1">{selectedTemplate.description}</p>
                </div>
              </div>

              {/* Actions Bar */}
              <div className="flex items-center gap-2">
                <button
                  onClick={startEdit}
                  className="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3.5 py-2 text-xs"
                  style={{ fontWeight: 500 }}
                >
                  <Edit3 size={13} />
                  Edit
                </button>
                <button className="flex items-center gap-1.5 border border-slate-200 text-slate-600 rounded-lg px-3.5 py-2 text-xs hover:bg-slate-50" style={{ fontWeight: 500 }}>
                  <Copy size={13} />
                  Duplicate
                </button>
                {selectedTemplate.status === 'draft' && (
                  <button className="flex items-center gap-1.5 border border-emerald-200 text-emerald-700 bg-emerald-50 rounded-lg px-3.5 py-2 text-xs hover:bg-emerald-100" style={{ fontWeight: 500 }}>
                    <Send size={13} />
                    Publish
                  </button>
                )}
                {selectedTemplate.status !== 'archived' && (
                  <button
                    onClick={() => setShowArchiveModal(true)}
                    className="flex items-center gap-1.5 border border-red-200 text-red-600 rounded-lg px-3.5 py-2 text-xs hover:bg-red-50"
                    style={{ fontWeight: 500 }}
                  >
                    <Archive size={13} />
                    Archive
                  </button>
                )}
              </div>

              {/* Detail Cards */}
              <div className="grid grid-cols-3 gap-3">
                <div className="bg-white rounded-xl border border-slate-200 p-4">
                  <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
                    <Tag size={12} />
                    Category
                  </div>
                  <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{selectedTemplate.category}</div>
                </div>
                <div className="bg-white rounded-xl border border-slate-200 p-4">
                  <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
                    <CalendarDays size={12} />
                    Default Deadline
                  </div>
                  <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{selectedTemplate.defaultDeadlineDays} days</div>
                </div>
                <div className="bg-white rounded-xl border border-slate-200 p-4">
                  <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
                    <BarChart3 size={12} />
                    Times Used
                  </div>
                  <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{selectedTemplate.usageCount}</div>
                </div>
              </div>

              {/* Usage Statistics */}
              <div className="bg-white rounded-xl border border-slate-200 p-5">
                <h3 className="text-sm text-slate-800 mb-3" style={{ fontWeight: 700 }}>Usage Statistics</h3>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <div className="text-xs text-slate-500 mb-1">Total RFQs Created</div>
                    <div className="text-xl text-slate-800" style={{ fontWeight: 700 }}>{selectedTemplate.usageCount}</div>
                  </div>
                  <div>
                    <div className="text-xs text-slate-500 mb-1">Last Used</div>
                    <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>
                      {selectedTemplate.lastUsedAt ? formatDate(selectedTemplate.lastUsedAt) : 'Never'}
                    </div>
                  </div>
                  <div>
                    <div className="text-xs text-slate-500 mb-1">Created By</div>
                    <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{selectedTemplate.createdBy}</div>
                  </div>
                  <div>
                    <div className="text-xs text-slate-500 mb-1">Template ID</div>
                    <div className="text-sm text-slate-400 font-mono">{selectedTemplate.id}</div>
                  </div>
                </div>

                {selectedTemplate.usageCount > 0 && (
                  <div className="mt-4 pt-4 border-t border-slate-100">
                    <div className="text-xs text-slate-500 mb-2" style={{ fontWeight: 600 }}>Usage Trend (last 6 months)</div>
                    <div className="flex items-end gap-1.5 h-16">
                      {[3, 1, 2, 0, 4, 2].map((v, i) => (
                        <div
                          key={i}
                          className="flex-1 bg-indigo-200 rounded-t"
                          style={{ height: `${v > 0 ? Math.max((v / 4) * 100, 15) : 4}%` }}
                        />
                      ))}
                    </div>
                    <div className="flex justify-between text-[9px] text-slate-400 mt-1">
                      <span>Oct</span><span>Nov</span><span>Dec</span><span>Jan</span><span>Feb</span><span>Mar</span>
                    </div>
                  </div>
                )}
              </div>

              {/* Locked Fields */}
              {selectedTemplate.lockedFields.length > 0 && (
                <div className="bg-white rounded-xl border border-slate-200 p-5">
                  <h3 className="text-sm text-slate-800 mb-3" style={{ fontWeight: 700 }}>Locked Fields</h3>
                  <p className="text-xs text-slate-400 mb-3">These fields are locked when this template is applied to a new RFQ.</p>
                  <div className="flex flex-wrap gap-2">
                    {selectedTemplate.lockedFields.map((field) => (
                      <span key={field} className="inline-flex items-center gap-1.5 text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-1 rounded-lg" style={{ fontWeight: 500 }}>
                        <Lock size={10} />
                        {field}
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Edit / Create Mode */}
          {editMode && (
            <div className="max-w-2xl space-y-6">
              <div>
                <h2 className="text-slate-900 text-base" style={{ fontWeight: 700 }}>
                  {selectedTemplate ? 'Edit Template' : 'Create New Template'}
                </h2>
                <p className="text-xs text-slate-500 mt-1">
                  {selectedTemplate ? `Editing "${selectedTemplate.name}"` : 'Define a reusable RFQ template with default settings and constraints.'}
                </p>
              </div>

              <div className="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">
                {/* Name */}
                <div className="p-4">
                  <label className="flex items-center gap-2 text-sm text-slate-700 mb-2" style={{ fontWeight: 600 }}>
                    <FileText size={14} className="text-slate-400" />
                    Template Name <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={editName}
                    onChange={(e) => setEditName(e.target.value)}
                    placeholder="e.g. IT Hardware Standard"
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 outline-none"
                  />
                </div>

                {/* Category */}
                <div className="p-4">
                  <label className="flex items-center gap-2 text-sm text-slate-700 mb-2" style={{ fontWeight: 600 }}>
                    <Tag size={14} className="text-slate-400" />
                    Category <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={editCategory}
                    onChange={(e) => setEditCategory(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:border-indigo-400 outline-none"
                  >
                    <option value="">Select category</option>
                    {categoryOptions.map((cat) => (
                      <option key={cat} value={cat}>{cat}</option>
                    ))}
                    <option value="IT Infrastructure">IT Infrastructure</option>
                    <option value="Facilities">Facilities</option>
                    <option value="Furniture">Furniture</option>
                    <option value="Software">Software</option>
                  </select>
                </div>

                {/* Description */}
                <div className="p-4">
                  <label className="flex items-center gap-2 text-sm text-slate-700 mb-2" style={{ fontWeight: 600 }}>
                    <FileText size={14} className="text-slate-400" />
                    Description
                  </label>
                  <textarea
                    rows={3}
                    value={editDescription}
                    onChange={(e) => setEditDescription(e.target.value)}
                    placeholder="Describe when this template should be used..."
                    className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none"
                  />
                </div>

                {/* Default Deadline */}
                <div className="p-4">
                  <label className="flex items-center gap-2 text-sm text-slate-700 mb-1" style={{ fontWeight: 600 }}>
                    <CalendarDays size={14} className="text-slate-400" />
                    Default Deadline (days)
                  </label>
                  <p className="text-xs text-slate-400 mb-2">Number of days from creation to RFQ close date when this template is applied.</p>
                  <input
                    type="number"
                    min={1}
                    max={90}
                    value={editDeadlineDays}
                    onChange={(e) => setEditDeadlineDays(parseInt(e.target.value) || 14)}
                    className="w-32 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 outline-none"
                  />
                </div>

                {/* Line Item Presets */}
                <div className="p-4">
                  <label className="flex items-center gap-2 text-sm text-slate-700 mb-1" style={{ fontWeight: 600 }}>
                    <Hash size={14} className="text-slate-400" />
                    Line Item Presets
                  </label>
                  <p className="text-xs text-slate-400 mb-3">Pre-defined line item categories that will be included in RFQs using this template.</p>
                  <div className="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <div className="flex flex-wrap gap-2">
                      {['Servers', 'Memory', 'Storage', 'Networking', 'Accessories', 'Services'].map((preset) => (
                        <span key={preset} className="text-xs bg-white border border-slate-200 text-slate-600 px-2.5 py-1 rounded-lg" style={{ fontWeight: 500 }}>
                          {preset}
                        </span>
                      ))}
                    </div>
                    <button className="flex items-center gap-1 text-xs text-indigo-600 mt-2.5 hover:text-indigo-700" style={{ fontWeight: 500 }}>
                      <Plus size={12} />
                      Add Preset Item
                    </button>
                  </div>
                </div>

                {/* Locked Fields Toggles */}
                <div className="p-4">
                  <label className="flex items-center gap-2 text-sm text-slate-700 mb-1" style={{ fontWeight: 600 }}>
                    <Lock size={14} className="text-slate-400" />
                    Locked Fields
                  </label>
                  <p className="text-xs text-slate-400 mb-3">Fields that cannot be modified when this template is used to create a new RFQ.</p>
                  <div className="space-y-1.5">
                    {lockableFields.map((field) => {
                      const isLocked = editLockedFields.includes(field);
                      return (
                        <label
                          key={field}
                          className={`flex items-center justify-between p-2.5 rounded-lg border cursor-pointer transition-colors ${
                            isLocked ? 'border-indigo-200 bg-indigo-50/50' : 'border-slate-100 hover:bg-slate-50'
                          }`}
                        >
                          <div className="flex items-center gap-2">
                            <Lock size={12} className={isLocked ? 'text-indigo-500' : 'text-slate-300'} />
                            <span className="text-xs text-slate-700" style={{ fontWeight: 500 }}>{field}</span>
                          </div>
                          <input
                            type="checkbox"
                            checked={isLocked}
                            onChange={() => toggleLockedField(field)}
                            className="accent-indigo-600"
                          />
                        </label>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Form Actions */}
              <div className="flex items-center justify-end gap-3 pt-2">
                <button
                  onClick={cancelEdit}
                  className="border border-slate-200 rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  className="flex items-center gap-2 border border-slate-200 text-slate-700 rounded-lg px-4 py-2.5 text-sm hover:bg-slate-50"
                  style={{ fontWeight: 500 }}
                >
                  <Eye size={14} />
                  Save as Draft
                </button>
                <button
                  disabled={!editName.trim() || !editCategory}
                  className={`flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm text-white ${
                    editName.trim() && editCategory ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-indigo-300 cursor-not-allowed'
                  }`}
                  style={{ fontWeight: 500 }}
                >
                  <CheckCircle size={14} />
                  {selectedTemplate ? 'Save Changes' : 'Create Template'}
                </button>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* ─── Confirm Archive Modal ─── */}
      <SlideOver
        open={showArchiveModal}
        onOpenChange={setShowArchiveModal}
        title="Confirm Archive"
        description={selectedTemplate ? `Archive "${selectedTemplate.name}"` : 'Archive template'}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowArchiveModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
              Cancel
            </button>
            <button
              onClick={() => setShowArchiveModal(false)}
              className="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm"
              style={{ fontWeight: 500 }}
            >
              <Archive size={14} />
              Archive Template
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-2">
              <AlertTriangle size={14} className="text-amber-600" />
              <span className="text-sm text-amber-700" style={{ fontWeight: 700 }}>Archive Template</span>
            </div>
            <p className="text-xs text-amber-700">
              Archiving this template will prevent it from being used for new RFQs. Existing RFQs created from this template will not be affected.
            </p>
          </div>

          {selectedTemplate && (
            <div className="bg-white rounded-xl border border-slate-200 p-4">
              <div className="text-sm text-slate-800 mb-2" style={{ fontWeight: 600 }}>{selectedTemplate.name}</div>
              <div className="grid grid-cols-2 gap-3 text-xs">
                <div>
                  <span className="text-slate-400">Category</span>
                  <div className="text-slate-700 mt-0.5" style={{ fontWeight: 500 }}>{selectedTemplate.category}</div>
                </div>
                <div>
                  <span className="text-slate-400">Times Used</span>
                  <div className="text-slate-700 mt-0.5" style={{ fontWeight: 500 }}>{selectedTemplate.usageCount}</div>
                </div>
                <div>
                  <span className="text-slate-400">Status</span>
                  <div className="mt-0.5">
                    <span className={`text-[10px] px-1.5 py-0.5 rounded ${statusColors[selectedTemplate.status]}`} style={{ fontWeight: 600 }}>
                      {selectedTemplate.status}
                    </span>
                  </div>
                </div>
                <div>
                  <span className="text-slate-400">Last Used</span>
                  <div className="text-slate-700 mt-0.5" style={{ fontWeight: 500 }}>
                    {selectedTemplate.lastUsedAt ? formatDate(selectedTemplate.lastUsedAt) : 'Never'}
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </SlideOver>
    </div>
  );
}
