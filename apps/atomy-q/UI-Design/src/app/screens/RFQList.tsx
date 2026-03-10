import { useState, useMemo, Fragment } from 'react';
import { Link, useNavigate } from 'react-router';
import {
  Search, Plus, ChevronDown, ChevronLeft, ChevronRight,
  MoreHorizontal, Archive, Copy, FileDown, X, CheckSquare,
  Users, FileText, DollarSign, TrendingDown, Tag, Calendar,
  ArrowUpDown, ArrowUp, ArrowDown,
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import {
  rfqs, statusColors, formatCurrency, formatDate,
  getUserById,
} from '../data/mockData';

type StatusFilter = 'all' | 'draft' | 'open' | 'closed' | 'archived';
type SortField = 'id' | 'title' | 'status' | 'deadline' | 'estimatedValue';
type SortDir = 'asc' | 'desc';

const PAGE_SIZE = 10;

const statusFilters: { key: StatusFilter; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'draft', label: 'Draft' },
  { key: 'open', label: 'Open' },
  { key: 'closed', label: 'Closed' },
  { key: 'archived', label: 'Archived' },
];

const categories = [...new Set(rfqs.map(r => r.category))];
const owners = [...new Set(rfqs.map(r => r.owner))];

export function RFQList() {
  const navigate = useNavigate();

  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [ownerFilter, setOwnerFilter] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [contextMenuId, setContextMenuId] = useState<string | null>(null);
  const [showArchiveModal, setShowArchiveModal] = useState(false);
  const [showBulkModal, setShowBulkModal] = useState<string | null>(null);
  const [sortField, setSortField] = useState<SortField>('id');
  const [sortDir, setSortDir] = useState<SortDir>('desc');
  const [page, setPage] = useState(1);
  const [archiveTargetId, setArchiveTargetId] = useState<string | null>(null);
  const [expandedRowId, setExpandedRowId] = useState<string | null>(null);

  const filtered = useMemo(() => {
    let list = [...rfqs];
    if (statusFilter !== 'all') list = list.filter(r => r.status === statusFilter);
    if (categoryFilter) list = list.filter(r => r.category === categoryFilter);
    if (ownerFilter) list = list.filter(r => r.owner === ownerFilter);
    if (searchQuery) {
      const q = searchQuery.toLowerCase();
      list = list.filter(r =>
        r.id.toLowerCase().includes(q) ||
        r.title.toLowerCase().includes(q) ||
        r.category.toLowerCase().includes(q),
      );
    }
    list.sort((a, b) => {
      const valA = a[sortField] ?? '';
      const valB = b[sortField] ?? '';
      if (typeof valA === 'number' && typeof valB === 'number') return sortDir === 'asc' ? valA - valB : valB - valA;
      return sortDir === 'asc' ? String(valA).localeCompare(String(valB)) : String(valB).localeCompare(String(valA));
    });
    return list;
  }, [statusFilter, categoryFilter, ownerFilter, searchQuery, sortField, sortDir]);

  const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  const paginated = filtered.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  const toggleSort = (field: SortField) => {
    if (sortField === field) setSortDir(d => (d === 'asc' ? 'desc' : 'asc'));
    else { setSortField(field); setSortDir('asc'); }
  };

  const SortIcon = ({ field }: { field: SortField }) => {
    if (sortField !== field) return <ArrowUpDown size={13} className="text-slate-300" />;
    return sortDir === 'asc' ? <ArrowUp size={13} className="text-indigo-500" /> : <ArrowDown size={13} className="text-indigo-500" />;
  };

  const toggleSelect = (id: string) =>
    setSelectedIds(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);

  const toggleAll = () => {
    const ids = paginated.map(r => r.id);
    const allSelected = ids.every(id => selectedIds.includes(id));
    setSelectedIds(allSelected ? selectedIds.filter(id => !ids.includes(id)) : [...new Set([...selectedIds, ...ids])]);
  };

  const allOnPageSelected = paginated.length > 0 && paginated.every(r => selectedIds.includes(r.id));

  const clearFilters = () => {
    setStatusFilter('all');
    setCategoryFilter('');
    setOwnerFilter('');
    setSearchQuery('');
    setPage(1);
  };

  const hasActiveFilters = statusFilter !== 'all' || categoryFilter !== '' || ownerFilter !== '' || searchQuery !== '';

  const handleArchive = (id: string) => {
    setArchiveTargetId(id);
    setShowArchiveModal(true);
    setContextMenuId(null);
  };

  const toggleExpand = (id: string, e: React.MouseEvent) => {
    e.stopPropagation();
    setExpandedRowId(prev => prev === id ? null : id);
  };

  return (
    <div className="min-h-screen">
      {/* Header */}
      <div className="flex items-center justify-between mb-5">
        <div>
          <h1 className="text-xl text-slate-900" style={{ fontWeight: 700 }}>RFQ Pipeline</h1>
          <p className="text-sm text-slate-500 mt-0.5">{filtered.length} requests for quotation</p>
        </div>
        <Link
          to="/rfq/create"
          className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 transition-colors"
          style={{ fontWeight: 500 }}
        >
          <Plus size={16} />
          Create RFQ
        </Link>
      </div>

      {/* Search & Filters */}
      <div className="bg-white border border-slate-200 rounded-xl p-4 mb-4">
        <div className="flex items-center gap-3 flex-wrap">
          <div className="relative flex-1 min-w-[260px]">
            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={e => { setSearchQuery(e.target.value); setPage(1); }}
              placeholder="Search by ID, title, or category..."
              className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400"
            />
          </div>

          <div className="flex items-center gap-1 border border-slate-200 rounded-lg p-0.5">
            {statusFilters.map(sf => (
              <button
                key={sf.key}
                onClick={() => { setStatusFilter(sf.key); setPage(1); }}
                className={`px-3 py-1.5 text-xs rounded-md transition-colors ${
                  statusFilter === sf.key
                    ? 'bg-indigo-600 text-white'
                    : 'text-slate-600 hover:bg-slate-100'
                }`}
                style={{ fontWeight: statusFilter === sf.key ? 600 : 400 }}
              >
                {sf.label}
              </button>
            ))}
          </div>

          <div className="relative">
            <select
              value={categoryFilter}
              onChange={e => { setCategoryFilter(e.target.value); setPage(1); }}
              className="appearance-none text-xs border border-slate-200 rounded-lg pl-3 pr-7 py-2 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            >
              <option value="">All Categories</option>
              {categories.map(c => <option key={c} value={c}>{c}</option>)}
            </select>
            <ChevronDown size={14} className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
          </div>

          <div className="relative">
            <select
              value={ownerFilter}
              onChange={e => { setOwnerFilter(e.target.value); setPage(1); }}
              className="appearance-none text-xs border border-slate-200 rounded-lg pl-3 pr-7 py-2 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            >
              <option value="">All Owners</option>
              {owners.map(o => {
                const user = getUserById(o);
                return <option key={o} value={o}>{user?.name ?? o}</option>;
              })}
            </select>
            <ChevronDown size={14} className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
          </div>

          {hasActiveFilters && (
            <button onClick={clearFilters} className="flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700">
              <X size={14} /> Clear
            </button>
          )}
        </div>
      </div>

      {/* Bulk Action Toolbar */}
      {selectedIds.length > 0 && (
        <div className="bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3 mb-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <CheckSquare size={16} className="text-indigo-600" />
            <span className="text-sm text-indigo-700" style={{ fontWeight: 600 }}>{selectedIds.length} selected</span>
          </div>
          <div className="flex items-center gap-2">
            <button onClick={() => setShowBulkModal('close')} className="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50" style={{ fontWeight: 500 }}>Close Selected</button>
            <button onClick={() => setShowBulkModal('archive')} className="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50" style={{ fontWeight: 500 }}>Archive Selected</button>
            <button onClick={() => setShowBulkModal('assign')} className="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50" style={{ fontWeight: 500 }}>Assign Owner</button>
            <button className="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50" style={{ fontWeight: 500 }}>Export</button>
            <button onClick={() => setSelectedIds([])} className="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-700">
              <X size={14} />
            </button>
          </div>
        </div>
      )}

      {/* Table */}
      <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-200 bg-slate-50/70">
                <th className="w-10 px-3 py-3">
                  <input
                    type="checkbox"
                    checked={allOnPageSelected}
                    onChange={toggleAll}
                    className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                  />
                </th>
                <th className="w-8 px-1 py-3" />
                <th className="px-3 py-3 text-left">
                  <button onClick={() => toggleSort('id')} className="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>
                    ID <SortIcon field="id" />
                  </button>
                </th>
                <th className="px-3 py-3 text-left">
                  <button onClick={() => toggleSort('title')} className="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>
                    RFQ <SortIcon field="title" />
                  </button>
                </th>
                <th className="px-3 py-3 text-left">
                  <button onClick={() => toggleSort('status')} className="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>
                    Status <SortIcon field="status" />
                  </button>
                </th>
                <th className="px-3 py-3 text-left">
                  <button onClick={() => toggleSort('deadline')} className="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>
                    Deadline <SortIcon field="deadline" />
                  </button>
                </th>
                <th className="px-3 py-3 text-right">
                  <button onClick={() => toggleSort('estimatedValue')} className="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wider ml-auto" style={{ fontWeight: 600 }}>
                    Est. Value <SortIcon field="estimatedValue" />
                  </button>
                </th>
                <th className="w-10 px-3 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {paginated.map(rfq => {
                const owner = getUserById(rfq.owner);
                const isSelected = selectedIds.includes(rfq.id);
                const isExpanded = expandedRowId === rfq.id;
                const deadlineDate = new Date(rfq.deadline);
                const isOverdue = rfq.status === 'open' && deadlineDate < new Date();
                const isDueSoon = rfq.status === 'open' && !isOverdue && (deadlineDate.getTime() - Date.now()) < 7 * 86400000;

                return (
                  <Fragment key={rfq.id}>
                    <tr
                      className={`group hover:bg-slate-50/80 transition-colors cursor-pointer ${isSelected ? 'bg-indigo-50/40' : ''}`}
                      onClick={() => navigate(`/rfq/${rfq.id}`)}
                    >
                      <td className="px-3 py-3" onClick={e => e.stopPropagation()}>
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => toggleSelect(rfq.id)}
                          className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                      </td>
                      <td className="px-1 py-3">
                        <button
                          onClick={e => toggleExpand(rfq.id, e)}
                          className={`p-0.5 rounded hover:bg-slate-200 text-slate-400 transition-transform ${isExpanded ? 'rotate-90' : ''}`}
                        >
                          <ChevronRight size={14} />
                        </button>
                      </td>
                      <td className="px-3 py-3">
                        <span className="text-indigo-600" style={{ fontWeight: 600 }}>{rfq.id}</span>
                      </td>
                      <td className="px-3 py-3">
                        <div>
                          <span className="text-slate-900 block" style={{ fontWeight: 500 }}>{rfq.title}</span>
                          {owner && (
                            <div className="flex items-center gap-1.5 mt-0.5">
                              <span className="flex-shrink-0 w-4 h-4 rounded-full bg-indigo-100 text-indigo-700 text-[9px] flex items-center justify-center" style={{ fontWeight: 600 }}>
                                {owner.initials}
                              </span>
                              <span className="text-xs text-slate-500">{owner.name}</span>
                            </div>
                          )}
                        </div>
                      </td>
                      <td className="px-3 py-3">
                        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs ${statusColors[rfq.status]}`} style={{ fontWeight: 500 }}>
                          {rfq.status}
                        </span>
                      </td>
                      <td className="px-3 py-3">
                        <span className={`text-xs ${isOverdue ? 'text-red-600' : isDueSoon ? 'text-amber-600' : 'text-slate-600'}`} style={{ fontWeight: isOverdue || isDueSoon ? 500 : 400 }}>
                          {formatDate(rfq.deadline)}
                        </span>
                      </td>
                      <td className="px-3 py-3 text-right text-xs text-slate-900" style={{ fontWeight: 500 }}>{formatCurrency(rfq.estimatedValue)}</td>
                      <td className="px-3 py-3 text-center relative" onClick={e => e.stopPropagation()}>
                        <button
                          onClick={() => setContextMenuId(contextMenuId === rfq.id ? null : rfq.id)}
                          className="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors"
                        >
                          <MoreHorizontal size={16} />
                        </button>
                        {contextMenuId === rfq.id && (
                          <div className="absolute right-3 top-full mt-1 z-30 w-44 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                            <Link to={`/rfq/${rfq.id}`} className="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">
                              <FileText size={13} /> Open
                            </Link>
                            <button className="w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">
                              <Copy size={13} /> Duplicate
                            </button>
                            <button
                              onClick={() => handleArchive(rfq.id)}
                              className="w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50"
                            >
                              <Archive size={13} /> Archive
                            </button>
                            <button className="w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">
                              <FileDown size={13} /> Export PDF
                            </button>
                          </div>
                        )}
                      </td>
                    </tr>

                    {/* Expanded Row */}
                    {isExpanded && (
                      <tr className="bg-slate-50/60">
                        <td colSpan={8} className="px-6 py-4">
                          <div className="grid grid-cols-6 gap-4">
                            <div className="flex items-start gap-2">
                              <Tag size={13} className="text-slate-400 mt-0.5 flex-shrink-0" />
                              <div>
                                <span className="text-[10px] text-slate-400 uppercase tracking-wider block" style={{ fontWeight: 600 }}>Category</span>
                                <span className="text-xs text-slate-700" style={{ fontWeight: 500 }}>{rfq.category}</span>
                              </div>
                            </div>
                            <div className="flex items-start gap-2">
                              <Calendar size={13} className="text-slate-400 mt-0.5 flex-shrink-0" />
                              <div>
                                <span className="text-[10px] text-slate-400 uppercase tracking-wider block" style={{ fontWeight: 600 }}>Deadline</span>
                                <span className={`text-xs ${isOverdue ? 'text-red-600' : isDueSoon ? 'text-amber-600' : 'text-slate-700'}`} style={{ fontWeight: 500 }}>
                                  {formatDate(rfq.deadline)}
                                </span>
                              </div>
                            </div>
                            <div className="flex items-start gap-2">
                              <Users size={13} className="text-slate-400 mt-0.5 flex-shrink-0" />
                              <div>
                                <span className="text-[10px] text-slate-400 uppercase tracking-wider block" style={{ fontWeight: 600 }}>Vendors</span>
                                <span className="text-xs text-slate-700" style={{ fontWeight: 500 }}>{rfq.vendorCount}</span>
                              </div>
                            </div>
                            <div className="flex items-start gap-2">
                              <FileText size={13} className="text-slate-400 mt-0.5 flex-shrink-0" />
                              <div>
                                <span className="text-[10px] text-slate-400 uppercase tracking-wider block" style={{ fontWeight: 600 }}>Quotes</span>
                                <span className="text-xs text-slate-700" style={{ fontWeight: 500 }}>{rfq.quoteCount}</span>
                              </div>
                            </div>
                            <div className="flex items-start gap-2">
                              <DollarSign size={13} className="text-slate-400 mt-0.5 flex-shrink-0" />
                              <div>
                                <span className="text-[10px] text-slate-400 uppercase tracking-wider block" style={{ fontWeight: 600 }}>Est. Value</span>
                                <span className="text-xs text-slate-700" style={{ fontWeight: 500 }}>{formatCurrency(rfq.estimatedValue)}</span>
                              </div>
                            </div>
                            <div className="flex items-start gap-2">
                              <TrendingDown size={13} className="text-slate-400 mt-0.5 flex-shrink-0" />
                              <div>
                                <span className="text-[10px] text-slate-400 uppercase tracking-wider block" style={{ fontWeight: 600 }}>Savings</span>
                                {rfq.savings > 0 ? (
                                  <span className="text-xs text-emerald-600" style={{ fontWeight: 500 }}>{formatCurrency(rfq.savings)}</span>
                                ) : (
                                  <span className="text-xs text-slate-400">—</span>
                                )}
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    )}
                  </Fragment>
                );
              })}
              {paginated.length === 0 && (
                <tr>
                  <td colSpan={8} className="px-6 py-12 text-center text-sm text-slate-400">
                    No RFQs match your filters.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="flex items-center justify-between border-t border-slate-200 px-4 py-3 bg-slate-50/50">
          <span className="text-xs text-slate-500">
            Showing {filtered.length === 0 ? 0 : (page - 1) * PAGE_SIZE + 1}–{Math.min(page * PAGE_SIZE, filtered.length)} of {filtered.length}
          </span>
          <div className="flex items-center gap-1">
            <button
              onClick={() => setPage(p => Math.max(1, p - 1))}
              disabled={page === 1}
              className="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <ChevronLeft size={14} />
            </button>
            {Array.from({ length: totalPages }, (_, i) => i + 1).map(p => (
              <button
                key={p}
                onClick={() => setPage(p)}
                className={`w-8 h-8 rounded-lg text-xs transition-colors ${
                  page === p ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'
                }`}
                style={{ fontWeight: page === p ? 600 : 400 }}
              >
                {p}
              </button>
            ))}
            <button
              onClick={() => setPage(p => Math.min(totalPages, p + 1))}
              disabled={page === totalPages}
              className="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <ChevronRight size={14} />
            </button>
          </div>
        </div>
      </div>

      {/* Archive Confirmation SlideOver */}
      <SlideOver
        open={showArchiveModal}
        onOpenChange={setShowArchiveModal}
        title="Confirm Archive"
        description={`Are you sure you want to archive ${archiveTargetId ?? 'this RFQ'}?`}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => setShowArchiveModal(false)}
              className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50"
              style={{ fontWeight: 500 }}
            >
              Cancel
            </button>
            <button
              onClick={() => setShowArchiveModal(false)}
              className="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700"
              style={{ fontWeight: 500 }}
            >
              Archive
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div className="flex gap-3">
              <Archive size={18} className="text-amber-600 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm text-amber-800" style={{ fontWeight: 500 }}>This action will move the RFQ to the archive.</p>
                <p className="text-xs text-amber-700 mt-1">Archived RFQs are read-only and cannot accept new quotes. This action can be undone by a Procurement Manager.</p>
              </div>
            </div>
          </div>
          {archiveTargetId && (() => {
            const rfq = rfqs.find(r => r.id === archiveTargetId);
            if (!rfq) return null;
            return (
              <div className="border border-slate-200 rounded-lg p-4 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-slate-900" style={{ fontWeight: 600 }}>{rfq.id}</span>
                  <span className={`text-xs px-2 py-0.5 rounded-full ${statusColors[rfq.status]}`}>{rfq.status}</span>
                </div>
                <p className="text-sm text-slate-600">{rfq.title}</p>
                <div className="grid grid-cols-2 gap-2 text-xs text-slate-500 pt-2 border-t border-slate-100">
                  <span>Vendors: {rfq.vendorCount}</span>
                  <span>Quotes: {rfq.quoteCount}</span>
                  <span>Value: {formatCurrency(rfq.estimatedValue)}</span>
                  <span>Created: {formatDate(rfq.createdAt)}</span>
                </div>
              </div>
            );
          })()}
        </div>
      </SlideOver>

      {/* Bulk Action Confirmation SlideOver */}
      <SlideOver
        open={showBulkModal !== null}
        onOpenChange={() => setShowBulkModal(null)}
        title={`Confirm Bulk ${showBulkModal === 'close' ? 'Close' : showBulkModal === 'archive' ? 'Archive' : 'Assign'}`}
        description={`This action will affect ${selectedIds.length} selected RFQ(s).`}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => setShowBulkModal(null)}
              className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50"
              style={{ fontWeight: 500 }}
            >
              Cancel
            </button>
            <button
              onClick={() => { setShowBulkModal(null); setSelectedIds([]); }}
              className="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
              style={{ fontWeight: 500 }}
            >
              Confirm
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p className="text-sm text-blue-800" style={{ fontWeight: 500 }}>
              {showBulkModal === 'close' && 'Close selected RFQs for submissions. No new quotes will be accepted.'}
              {showBulkModal === 'archive' && 'Move selected RFQs to the archive. They will become read-only.'}
              {showBulkModal === 'assign' && 'Reassign the owner of the selected RFQs.'}
            </p>
          </div>
          {showBulkModal === 'assign' && (
            <div>
              <label className="block text-xs text-slate-500 mb-1" style={{ fontWeight: 500 }}>New Owner</label>
              <select className="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                {owners.map(o => {
                  const u = getUserById(o);
                  return <option key={o} value={o}>{u?.name ?? o}</option>;
                })}
              </select>
            </div>
          )}
          <div className="space-y-2">
            <p className="text-xs text-slate-500" style={{ fontWeight: 500 }}>Affected RFQs:</p>
            {selectedIds.map(id => {
              const rfq = rfqs.find(r => r.id === id);
              if (!rfq) return null;
              return (
                <div key={id} className="flex items-center justify-between text-xs border border-slate-100 rounded-lg px-3 py-2">
                  <span className="text-indigo-600" style={{ fontWeight: 600 }}>{rfq.id}</span>
                  <span className="text-slate-600 truncate ml-3">{rfq.title}</span>
                </div>
              );
            })}
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
