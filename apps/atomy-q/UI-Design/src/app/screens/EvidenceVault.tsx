import { useState } from 'react';
import {
  Search, Filter, FileText, FolderArchive, Plus, Download, Eye,
  ChevronRight, Clock, User, Tag, Archive, CheckCircle, Package,
  File, FileLock, Calendar, ExternalLink, Lock, X
} from 'lucide-react';
import { Link } from 'react-router';
import { SlideOver } from '../components/SlideOver';
import {
  documents, evidenceBundles, rfqs, vendors, formatDate, formatDateTime,
  getUserById, getVendorById, statusColors,
} from '../data/mockData';

const docTypeConfig: Record<string, { label: string; color: string }> = {
  quote: { label: 'Quote', color: 'bg-blue-100 text-blue-700' },
  report: { label: 'Report', color: 'bg-indigo-100 text-indigo-700' },
  due_diligence: { label: 'Due Diligence', color: 'bg-purple-100 text-purple-700' },
  evidence: { label: 'Evidence', color: 'bg-emerald-100 text-emerald-700' },
};

const tagColors: Record<string, string> = {
  quote: 'bg-blue-50 text-blue-600 border-blue-200',
  'IT Hardware': 'bg-slate-50 text-slate-600 border-slate-200',
  comparison: 'bg-indigo-50 text-indigo-600 border-indigo-200',
  report: 'bg-indigo-50 text-indigo-600 border-indigo-200',
  'due-diligence': 'bg-purple-50 text-purple-600 border-purple-200',
  financial: 'bg-amber-50 text-amber-600 border-amber-200',
  insurance: 'bg-teal-50 text-teal-600 border-teal-200',
  evidence: 'bg-emerald-50 text-emerald-600 border-emerald-200',
  contract: 'bg-rose-50 text-rose-600 border-rose-200',
  override: 'bg-orange-50 text-orange-600 border-orange-200',
};

const allDocTypes = ['quote', 'report', 'due_diligence', 'evidence'];

export function EvidenceVault() {
  const [searchQuery, setSearchQuery] = useState('');
  const [typeFilter, setTypeFilter] = useState('all');
  const [rfqFilter, setRfqFilter] = useState('all');
  const [selectedDocId, setSelectedDocId] = useState<string | null>(null);
  const [showCreateBundleModal, setShowCreateBundleModal] = useState(false);
  const [showViewBundleModal, setShowViewBundleModal] = useState(false);
  const [activeBundleId, setActiveBundleId] = useState<string | null>(null);
  const [bundleName, setBundleName] = useState('');
  const [bundleScope, setBundleScope] = useState('');
  const [bundleDescription, setBundleDescription] = useState('');

  const filteredDocs = documents.filter((doc) => {
    if (searchQuery && !doc.name.toLowerCase().includes(searchQuery.toLowerCase()) && !doc.tags.some((t) => t.toLowerCase().includes(searchQuery.toLowerCase()))) return false;
    if (typeFilter !== 'all' && doc.type !== typeFilter) return false;
    if (rfqFilter !== 'all' && doc.rfqId !== rfqFilter) return false;
    return true;
  });

  const selectedDoc = selectedDocId ? documents.find((d) => d.id === selectedDocId) : null;
  const activeBundle = activeBundleId ? evidenceBundles.find((b) => b.id === activeBundleId) : null;

  const handleOpenBundle = (bundleId: string) => {
    setActiveBundleId(bundleId);
    setShowViewBundleModal(true);
  };

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Page Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>Evidence Vault</span>
        </div>

        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-slate-900 text-lg mb-0.5" style={{ fontWeight: 700 }}>Evidence Vault</h1>
            <p className="text-xs text-slate-500">
              Document repository for sourcing evidence and compliance records &middot; {documents.length} documents &middot; {evidenceBundles.length} bundles
            </p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => { setBundleName(''); setBundleScope(''); setBundleDescription(''); setShowCreateBundleModal(true); }}
              className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm transition-colors"
              style={{ fontWeight: 500 }}
            >
              <Plus size={14} />
              Create Bundle
            </button>
          </div>
        </div>

        {/* Search + Filters */}
        <div className="flex items-center gap-3 mt-4">
          <div className="relative flex-1 max-w-md">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search documents by name or tag..."
              className="w-full border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-700 bg-white focus:border-indigo-400 outline-none"
            />
          </div>
          <select
            value={typeFilter}
            onChange={(e) => setTypeFilter(e.target.value)}
            className="border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:border-indigo-400 outline-none"
          >
            <option value="all">All Types</option>
            {allDocTypes.map((t) => (
              <option key={t} value={t}>{docTypeConfig[t]?.label || t}</option>
            ))}
          </select>
          <select
            value={rfqFilter}
            onChange={(e) => setRfqFilter(e.target.value)}
            className="border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:border-indigo-400 outline-none"
          >
            <option value="all">All RFQs</option>
            {rfqs.map((r) => (
              <option key={r.id} value={r.id}>{r.id}</option>
            ))}
          </select>
          {(searchQuery || typeFilter !== 'all' || rfqFilter !== 'all') && (
            <button
              onClick={() => { setSearchQuery(''); setTypeFilter('all'); setRfqFilter('all'); }}
              className="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-700"
              style={{ fontWeight: 500 }}
            >
              <X size={12} />
              Clear
            </button>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-hidden flex">
        {/* Document Table */}
        <div className="flex-1 overflow-y-auto">
          <div className="p-5 space-y-5">
            {/* Documents Table */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
                <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
                  <FileText size={14} className="text-slate-600" />
                </div>
                <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Documents</span>
                <span className="ml-auto text-slate-400 text-xs">{filteredDocs.length} of {documents.length}</span>
              </div>
              <table className="w-full">
                <thead>
                  <tr className="border-b border-slate-100 bg-slate-50">
                    <th className="text-left px-5 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>NAME</th>
                    <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>TYPE</th>
                    <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>RFQ</th>
                    <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>VENDOR</th>
                    <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>UPLOADED</th>
                    <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>SIZE</th>
                    <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>VER</th>
                    <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>TAGS</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredDocs.map((doc) => {
                    const vendor = doc.vendorId ? getVendorById(doc.vendorId) : null;
                    const uploader = getUserById(doc.uploadedBy);
                    const typeCfg = docTypeConfig[doc.type] || { label: doc.type, color: 'bg-slate-100 text-slate-600' };
                    const isSelected = selectedDocId === doc.id;

                    return (
                      <tr
                        key={doc.id}
                        onClick={() => setSelectedDocId(isSelected ? null : doc.id)}
                        className={`border-b border-slate-50 hover:bg-slate-50 cursor-pointer transition-colors ${isSelected ? 'bg-indigo-50/40' : ''}`}
                      >
                        <td className="px-5 py-3">
                          <div className="flex items-center gap-2">
                            <File size={14} className="text-slate-400 flex-shrink-0" />
                            <span className="text-slate-800 text-sm truncate max-w-[200px]" style={{ fontWeight: 500 }}>{doc.name}</span>
                          </div>
                        </td>
                        <td className="px-4 py-3">
                          <span className={`text-xs px-2 py-0.5 rounded ${typeCfg.color}`} style={{ fontWeight: 600 }}>{typeCfg.label}</span>
                        </td>
                        <td className="px-4 py-3">
                          <span className="text-xs text-slate-600">{doc.rfqId || '—'}</span>
                        </td>
                        <td className="px-4 py-3">
                          <span className="text-xs text-slate-600 truncate max-w-[120px] block">{vendor?.name || '—'}</span>
                        </td>
                        <td className="px-4 py-3">
                          <div>
                            <span className="text-xs text-slate-600 block">{formatDate(doc.uploadedAt)}</span>
                            <span className="text-xs text-slate-400">{uploader?.name || doc.uploadedBy}</span>
                          </div>
                        </td>
                        <td className="px-4 py-3">
                          <span className="text-xs text-slate-500">{doc.size}</span>
                        </td>
                        <td className="px-4 py-3">
                          <span className="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded" style={{ fontWeight: 600 }}>v{doc.version}</span>
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-1 flex-wrap">
                            {doc.tags.slice(0, 2).map((tag) => (
                              <span
                                key={tag}
                                className={`text-xs px-1.5 py-0.5 rounded border ${tagColors[tag] || 'bg-slate-50 text-slate-500 border-slate-200'}`}
                              >
                                {tag}
                              </span>
                            ))}
                            {doc.tags.length > 2 && (
                              <span className="text-xs text-slate-400">+{doc.tags.length - 2}</span>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
              {filteredDocs.length === 0 && (
                <div className="text-center py-12">
                  <Search size={28} className="text-slate-300 mx-auto mb-3" />
                  <p className="text-slate-500 text-sm">No documents match the current filters.</p>
                </div>
              )}
            </div>

            {/* Evidence Bundles */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="flex items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
                <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
                  <FolderArchive size={14} className="text-slate-600" />
                </div>
                <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Evidence Bundles</span>
                <span className="ml-auto text-slate-400 text-xs">{evidenceBundles.length} bundle{evidenceBundles.length !== 1 ? 's' : ''}</span>
              </div>
              <div className="grid grid-cols-2 gap-4 p-4">
                {evidenceBundles.map((bundle) => {
                  const bundleStatus = statusColors[bundle.status] || 'bg-slate-100 text-slate-600';
                  const creator = getUserById(bundle.createdBy);
                  return (
                    <div
                      key={bundle.id}
                      onClick={() => handleOpenBundle(bundle.id)}
                      className="rounded-xl border border-slate-200 p-4 hover:shadow-md hover:border-slate-300 transition-all cursor-pointer"
                    >
                      <div className="flex items-start justify-between mb-2">
                        <div className="flex items-center gap-2">
                          <Package size={15} className="text-slate-500" />
                          <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{bundle.name}</span>
                        </div>
                        <span className={`text-xs px-2 py-0.5 rounded capitalize ${bundleStatus}`} style={{ fontWeight: 600 }}>{bundle.status}</span>
                      </div>
                      <p className="text-xs text-slate-500 mb-3 line-clamp-2">{bundle.description}</p>
                      <div className="flex items-center gap-4 text-xs text-slate-500">
                        <span className="flex items-center gap-1">
                          <Tag size={10} />
                          {bundle.scope}
                        </span>
                        <span className="flex items-center gap-1">
                          <FileText size={10} />
                          {bundle.documentIds.length} doc{bundle.documentIds.length !== 1 ? 's' : ''}
                        </span>
                        <span className="flex items-center gap-1">
                          <Clock size={10} />
                          {formatDate(bundle.createdAt)}
                        </span>
                        <span className="flex items-center gap-1">
                          <User size={10} />
                          {creator?.name || bundle.createdBy}
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </div>

        {/* Document Detail Side Panel */}
        {selectedDoc && (
          <div className="w-80 bg-white border-l border-slate-200 flex-shrink-0 overflow-y-auto">
            <div className="p-4 border-b border-slate-100">
              <div className="flex items-center justify-between mb-3">
                <span className="text-xs text-slate-500" style={{ fontWeight: 600 }}>DOCUMENT DETAILS</span>
                <button onClick={() => setSelectedDocId(null)} className="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                  <X size={14} />
                </button>
              </div>
              <div className="flex items-center gap-2 mb-2">
                <File size={16} className="text-slate-400" />
                <span className="text-slate-800 text-sm break-all" style={{ fontWeight: 600 }}>{selectedDoc.name}</span>
              </div>
              <span className={`text-xs px-2 py-0.5 rounded ${docTypeConfig[selectedDoc.type]?.color || 'bg-slate-100 text-slate-600'}`} style={{ fontWeight: 600 }}>
                {docTypeConfig[selectedDoc.type]?.label || selectedDoc.type}
              </span>
            </div>

            <div className="p-4 space-y-4">
              {/* Metadata */}
              <div className="space-y-3">
                {selectedDoc.rfqId && (
                  <div>
                    <div className="text-xs text-slate-500 mb-0.5" style={{ fontWeight: 600 }}>RFQ</div>
                    <span className="text-sm text-slate-700">{selectedDoc.rfqId}</span>
                  </div>
                )}
                {selectedDoc.vendorId && (
                  <div>
                    <div className="text-xs text-slate-500 mb-0.5" style={{ fontWeight: 600 }}>Vendor</div>
                    <span className="text-sm text-slate-700">{getVendorById(selectedDoc.vendorId)?.name || selectedDoc.vendorId}</span>
                  </div>
                )}
                <div>
                  <div className="text-xs text-slate-500 mb-0.5" style={{ fontWeight: 600 }}>Uploaded</div>
                  <span className="text-sm text-slate-700">{formatDateTime(selectedDoc.uploadedAt)}</span>
                </div>
                <div>
                  <div className="text-xs text-slate-500 mb-0.5" style={{ fontWeight: 600 }}>Uploaded By</div>
                  <span className="text-sm text-slate-700">{getUserById(selectedDoc.uploadedBy)?.name || selectedDoc.uploadedBy}</span>
                </div>
                <div>
                  <div className="text-xs text-slate-500 mb-0.5" style={{ fontWeight: 600 }}>Size</div>
                  <span className="text-sm text-slate-700">{selectedDoc.size}</span>
                </div>
                <div>
                  <div className="text-xs text-slate-500 mb-0.5" style={{ fontWeight: 600 }}>Version</div>
                  <span className="text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded" style={{ fontWeight: 600 }}>v{selectedDoc.version}</span>
                </div>
                <div>
                  <div className="text-xs text-slate-500 mb-0.5 flex items-center gap-1" style={{ fontWeight: 600 }}>
                    <Calendar size={10} />
                    Retention Until
                  </div>
                  <span className="text-sm text-slate-700">{formatDate(selectedDoc.retentionUntil)}</span>
                </div>
              </div>

              {/* Tags */}
              <div>
                <div className="text-xs text-slate-500 mb-1.5" style={{ fontWeight: 600 }}>Tags</div>
                <div className="flex flex-wrap gap-1.5">
                  {selectedDoc.tags.map((tag) => (
                    <span
                      key={tag}
                      className={`text-xs px-2 py-0.5 rounded border ${tagColors[tag] || 'bg-slate-50 text-slate-500 border-slate-200'}`}
                    >
                      {tag}
                    </span>
                  ))}
                </div>
              </div>

              {/* Actions */}
              <div className="border-t border-slate-100 pt-4 space-y-2">
                <button className="w-full flex items-center gap-2 justify-center border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                  <Eye size={14} />
                  Preview
                </button>
                <button className="w-full flex items-center gap-2 justify-center border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                  <Download size={14} />
                  Download
                </button>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Create Evidence Bundle SlideOver */}
      <SlideOver
        open={showCreateBundleModal}
        onOpenChange={setShowCreateBundleModal}
        title="Create Evidence Bundle"
        description="Package documents into an auditable evidence bundle."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => setShowCreateBundleModal(false)}
              className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              disabled={!bundleName.trim()}
              className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-white transition-colors ${
                bundleName.trim() ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-indigo-300 cursor-not-allowed'
              }`}
              style={{ fontWeight: 500 }}
            >
              <Plus size={14} />
              Create Bundle
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 600 }}>
              Bundle Name <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={bundleName}
              onChange={(e) => setBundleName(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none"
              placeholder="e.g. RFQ-2401 Comparison Evidence"
            />
          </div>

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 600 }}>Scope (RFQ)</label>
            <select
              value={bundleScope}
              onChange={(e) => setBundleScope(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 bg-white focus:border-indigo-400 outline-none"
            >
              <option value="">Select RFQ...</option>
              {rfqs.map((r) => (
                <option key={r.id} value={r.id}>{r.id} — {r.title}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="text-xs text-slate-600 block mb-1.5" style={{ fontWeight: 600 }}>Description</label>
            <textarea
              rows={4}
              value={bundleDescription}
              onChange={(e) => setBundleDescription(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none"
              placeholder="Describe the purpose of this evidence bundle..."
            />
          </div>

          <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <div className="flex items-center gap-2 text-xs text-slate-500">
              <Archive size={12} />
              <span>Documents can be added after creation. Finalize the bundle to lock its contents.</span>
            </div>
          </div>
        </div>
      </SlideOver>

      {/* View Bundle SlideOver */}
      <SlideOver
        open={showViewBundleModal}
        onOpenChange={setShowViewBundleModal}
        title={activeBundle?.name || 'Bundle Details'}
        description={activeBundle?.description}
        width="md"
        footer={
          activeBundle ? (
            <div className="flex items-center justify-between">
              <span className="text-xs text-slate-500">
                Created {formatDate(activeBundle.createdAt)} by {getUserById(activeBundle.createdBy)?.name || activeBundle.createdBy}
              </span>
              <div className="flex items-center gap-2">
                {activeBundle.status === 'draft' && (
                  <button
                    className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm transition-colors"
                    style={{ fontWeight: 500 }}
                  >
                    <Lock size={14} />
                    Finalize Bundle
                  </button>
                )}
                {activeBundle.status === 'finalized' && (
                  <button
                    className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm transition-colors"
                    style={{ fontWeight: 500 }}
                  >
                    <Download size={14} />
                    Export Bundle
                  </button>
                )}
                <button
                  onClick={() => setShowViewBundleModal(false)}
                  className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
                >
                  Close
                </button>
              </div>
            </div>
          ) : undefined
        }
      >
        {activeBundle && (
          <div className="space-y-5">
            {/* Bundle Info */}
            <div className="flex items-center gap-3 flex-wrap">
              <span className={`text-xs px-2 py-0.5 rounded capitalize ${statusColors[activeBundle.status] || 'bg-slate-100 text-slate-600'}`} style={{ fontWeight: 600 }}>
                {activeBundle.status}
              </span>
              <span className="text-xs text-slate-500 flex items-center gap-1">
                <Tag size={10} />
                Scope: {activeBundle.scope}
              </span>
              <span className="text-xs text-slate-500 flex items-center gap-1">
                <FileText size={10} />
                {activeBundle.documentIds.length} document{activeBundle.documentIds.length !== 1 ? 's' : ''}
              </span>
              {activeBundle.finalizedAt && (
                <span className="text-xs text-slate-500 flex items-center gap-1">
                  <FileLock size={10} />
                  Finalized {formatDate(activeBundle.finalizedAt)}
                </span>
              )}
            </div>

            {/* Manifest */}
            <div>
              <div className="text-xs text-slate-500 mb-2" style={{ fontWeight: 600 }}>BUNDLE MANIFEST</div>
              <div className="space-y-2">
                {activeBundle.documentIds.map((docId) => {
                  const doc = documents.find((d) => d.id === docId);
                  if (!doc) return null;
                  const vendor = doc.vendorId ? getVendorById(doc.vendorId) : null;
                  const typeCfg = docTypeConfig[doc.type] || { label: doc.type, color: 'bg-slate-100 text-slate-600' };

                  return (
                    <div key={docId} className="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white border border-slate-100 hover:border-slate-300 transition-colors">
                      <File size={14} className="text-slate-400 flex-shrink-0" />
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-slate-800 truncate" style={{ fontWeight: 500 }}>{doc.name}</span>
                          <span className={`text-xs px-1.5 py-0.5 rounded flex-shrink-0 ${typeCfg.color}`} style={{ fontWeight: 500 }}>{typeCfg.label}</span>
                        </div>
                        <div className="flex items-center gap-3 text-xs text-slate-500 mt-0.5">
                          {vendor && <span>{vendor.name}</span>}
                          <span>{doc.size}</span>
                          <span>v{doc.version}</span>
                        </div>
                      </div>
                      <button className="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex-shrink-0">
                        <ExternalLink size={12} />
                      </button>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Bundle metadata */}
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
              <div className="text-xs text-slate-500 mb-2" style={{ fontWeight: 600 }}>BUNDLE INFO</div>
              <div className="space-y-1.5 text-xs text-slate-500">
                <div className="flex justify-between">
                  <span>Bundle ID</span>
                  <code className="text-slate-700 font-mono">{activeBundle.id}</code>
                </div>
                <div className="flex justify-between">
                  <span>Status</span>
                  <span className="text-slate-700 capitalize" style={{ fontWeight: 500 }}>{activeBundle.status}</span>
                </div>
                <div className="flex justify-between">
                  <span>Documents</span>
                  <span className="text-slate-700" style={{ fontWeight: 500 }}>{activeBundle.documentIds.length}</span>
                </div>
                <div className="flex justify-between">
                  <span>Created</span>
                  <span className="text-slate-700" style={{ fontWeight: 500 }}>{formatDateTime(activeBundle.createdAt)}</span>
                </div>
                {activeBundle.finalizedAt && (
                  <div className="flex justify-between">
                    <span>Finalized</span>
                    <span className="text-slate-700" style={{ fontWeight: 500 }}>{formatDateTime(activeBundle.finalizedAt)}</span>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </SlideOver>
    </div>
  );
}
