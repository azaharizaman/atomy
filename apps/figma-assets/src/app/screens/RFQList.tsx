import { useState, useRef, useEffect } from "react";
import { useNavigate } from "react-router";
import {
  Plus, Search, ChevronDown, ChevronUp, ArrowUpDown, Filter,
  MoreHorizontal, ExternalLink, CheckSquare, Square,
  ChevronLeft, ChevronRight as ChevronRightIcon,   Menu, ChevronUp as ChevronUpIcon, ChevronDown as ChevronDownIcon
} from "lucide-react";
import { rfqs } from "../data/mockData";

type ColumnId = "id" | "title" | "status" | "owner" | "vendors" | "budget" | "deadline" | "priority";
const DEFAULT_COLUMN_ORDER: ColumnId[] = ["id", "title", "status", "owner", "vendors", "budget", "deadline", "priority"];
const PAGE_SIZE_OPTIONS = [5, 8, 10, 25, 50];
type GroupByOption = "none" | "status" | "owner" | "priority";

const statusConfig: Record<string, { bg: string; color: string; dot: string }> = {
  Open:      { bg: "var(--app-success-tint-10)",  color: "var(--app-success)", dot: "var(--app-success)" },
  Draft:     { bg: "var(--app-purple-tint-10)",  color: "var(--app-accent-purple)", dot: "var(--app-accent-purple)" },
  Closed:    { bg: "var(--app-slate-tint-12)", color: "var(--app-text-subtle)", dot: "var(--app-text-muted)" },
  Awarded:   { bg: "var(--app-brand-tint-10)",  color: "var(--app-brand-400)", dot: "var(--app-brand-500)" },
  Cancelled: { bg: "var(--app-danger-tint-10)",   color: "var(--app-danger-soft)", dot: "var(--app-danger)" },
};

const priorityDot: Record<string, string> = {
  Critical: "var(--app-danger)",
  High: "var(--app-warning)",
  Medium: "var(--app-warning)",
  Low: "var(--app-text-muted)",
};

type SortKey = "id" | "title" | "status" | "owner" | "deadline" | "budget";

export function RFQList() {
  const navigate = useNavigate();
  const configMenuRef = useRef<HTMLDivElement>(null);
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("All");
  const [ownerFilter, setOwnerFilter] = useState<string>("All");
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const [sortKey, setSortKey] = useState<SortKey>("deadline");
  const [sortDir, setSortDir] = useState<"asc" | "desc">("asc");
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(8);
  const [groupBy, setGroupBy] = useState<GroupByOption>("none");
  const [collapsedGroups, setCollapsedGroups] = useState<Set<string>>(new Set());
  const [columnOrder, setColumnOrder] = useState<ColumnId[]>(() => [...DEFAULT_COLUMN_ORDER]);
  const [columnVisible, setColumnVisible] = useState<Record<ColumnId, boolean>>(() =>
    DEFAULT_COLUMN_ORDER.reduce((acc, c) => ({ ...acc, [c]: true }), {} as Record<ColumnId, boolean>)
  );
  const [configMenuOpen, setConfigMenuOpen] = useState(false);

  useEffect(() => {
    const onOutside = (e: MouseEvent) => {
      if (configMenuRef.current && !configMenuRef.current.contains(e.target as Node)) setConfigMenuOpen(false);
    };
    if (configMenuOpen) document.addEventListener("click", onOutside);
    return () => document.removeEventListener("click", onOutside);
  }, [configMenuOpen]);

  const owners = ["All", ...Array.from(new Set(rfqs.map((r) => r.owner)))];
  const statuses = ["All", "Open", "Draft", "Closed", "Awarded", "Cancelled"];

  const handleSort = (key: SortKey) => {
    if (sortKey === key) setSortDir((d) => (d === "asc" ? "desc" : "asc"));
    else { setSortKey(key); setSortDir("asc"); }
  };

  const filtered = rfqs.filter((r) => {
    const matchSearch = r.title.toLowerCase().includes(search.toLowerCase()) || r.id.toLowerCase().includes(search.toLowerCase()) || r.category.toLowerCase().includes(search.toLowerCase());
    const matchStatus = statusFilter === "All" || r.status === statusFilter;
    const matchOwner = ownerFilter === "All" || r.owner === ownerFilter;
    return matchSearch && matchStatus && matchOwner;
  }).sort((a, b) => {
    let av = a[sortKey] as string;
    let bv = b[sortKey] as string;
    return sortDir === "asc" ? av.localeCompare(bv) : bv.localeCompare(av);
  });

  const visibleColumns = columnOrder.filter((c) => columnVisible[c]);
  const totalPages = Math.ceil(filtered.length / pageSize);
  const paginated = filtered.slice((page - 1) * pageSize, page * pageSize);

  type RfqRow = (typeof filtered)[number];
  const groupedData =
    groupBy === "none"
      ? null
      : (() => {
          const map = new Map<string, RfqRow[]>();
          for (const r of filtered) {
            const key = String(r[groupBy]);
            if (!map.has(key)) map.set(key, []);
            map.get(key)!.push(r);
          }
          return Array.from(map.entries()).sort((a, b) => a[0].localeCompare(b[0]));
        })();

  const toggleGroup = (key: string) => {
    setCollapsedGroups((prev) => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });
  };

  const toggleSelect = (id: string) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id); else next.add(id);
      return next;
    });
  };

  const visibleRows = groupBy !== "none" ? filtered : paginated;
  const allSelected = visibleRows.length > 0 && visibleRows.every((r) => selectedIds.has(r.id));
  const toggleAll = () => {
    if (allSelected) setSelectedIds((prev) => { const next = new Set(prev); visibleRows.forEach((r) => next.delete(r.id)); return next; });
    else setSelectedIds((prev) => { const next = new Set(prev); visibleRows.forEach((r) => next.add(r.id)); return next; });
  };

  const SortIcon = ({ col }: { col: SortKey }) => {
    if (sortKey !== col) return <ArrowUpDown size={11} style={{ color: "var(--app-text-faint)" }} />;
    return sortDir === "asc" ? <ChevronUp size={11} style={{ color: "var(--app-brand-500)" }} /> : <ChevronDown size={11} style={{ color: "var(--app-brand-500)" }} />;
  };

  const renderCell = (col: ColumnId, rfq: RfqRow) => {
    const sc = statusConfig[rfq.status] ?? statusConfig.Closed;
    switch (col) {
      case "id":
        return <span style={{ fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-400)", fontWeight: 500 }}>{rfq.id}</span>;
      case "title":
        return (
          <>
            <button
              onClick={() => navigate(`/rfqs/${rfq.id}`)}
              className="text-left hover:text-blue-400 transition-colors"
              style={{ fontSize: 13, color: "var(--app-text-main)", fontWeight: 500, display: "block", whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis", maxWidth: 220 }}
            >
              {rfq.title}
            </button>
            <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginTop: 1 }}>{rfq.category}</div>
          </>
        );
      case "status":
        return (
          <span className="flex items-center gap-1.5 rounded-full px-2.5 py-0.5 w-fit" style={{ fontSize: 11, fontWeight: 600, background: sc.bg, color: sc.color }}>
            <span style={{ width: 5, height: 5, borderRadius: "50%", background: sc.dot, display: "inline-block", flexShrink: 0 }} />
            {rfq.status}
          </span>
        );
      case "owner":
        return <span style={{ fontSize: 13, color: "var(--app-text-subtle)", whiteSpace: "nowrap" }}>{rfq.owner}</span>;
      case "vendors":
        return <span style={{ fontSize: 13, color: "var(--app-text-muted)" }}>{rfq.vendors > 0 ? rfq.vendors : "—"}</span>;
      case "budget":
        return <span style={{ fontSize: 13, color: "var(--app-text-subtle)", fontFamily: "'JetBrains Mono', monospace", whiteSpace: "nowrap" }}>{rfq.budget}</span>;
      case "deadline":
        return <span style={{ fontSize: 12, color: "var(--app-text-muted)", whiteSpace: "nowrap" }}>{rfq.deadline}</span>;
      case "priority":
        return (
          <div className="flex items-center gap-1.5">
            <span style={{ width: 6, height: 6, borderRadius: "50%", background: priorityDot[rfq.priority] ?? "var(--app-text-muted)", flexShrink: 0, display: "inline-block" }} />
            <span style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{rfq.priority}</span>
          </div>
        );
      default:
        return null;
    }
  };

  const renderRow = (rfq: RfqRow, i: number, total: number) => {
    const isSelected = selectedIds.has(rfq.id);
    return (
      <tr
        key={rfq.id}
        style={{ borderBottom: i < total - 1 ? "1px solid var(--app-bg-elevated)" : "none", background: isSelected ? "var(--app-brand-tint-5)" : "transparent" }}
        onMouseEnter={(e) => { if (!isSelected) (e.currentTarget as HTMLTableRowElement).style.background = "var(--app-hover-subtle)"; }}
        onMouseLeave={(e) => { if (!isSelected) (e.currentTarget as HTMLTableRowElement).style.background = "transparent"; }}
      >
        <td style={{ padding: "10px 12px" }}>
          <button onClick={(e) => { e.stopPropagation(); toggleSelect(rfq.id); }} style={{ color: isSelected ? "var(--app-brand-500)" : "var(--app-text-faint)" }}>
            {isSelected ? <CheckSquare size={15} /> : <Square size={15} />}
          </button>
        </td>
        {visibleColumns.map((col) => (
          <td key={col} style={{ padding: "10px 12px", maxWidth: col === "title" ? 240 : undefined }}>
            {renderCell(col, rfq)}
          </td>
        ))}
        <td style={{ padding: "10px 12px" }}>
          <div className="flex items-center gap-1">
            <button
              onClick={() => navigate(`/rfqs/${rfq.id}`)}
              className="rounded p-1.5 transition-colors"
              style={{ color: "var(--app-text-subtle)" }}
              title="Open"
              onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-border-strong)"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-main)"; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "transparent"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; }}
            >
              <ExternalLink size={13} />
            </button>
            <button
              className="rounded p-1.5 transition-colors"
              style={{ color: "var(--app-text-subtle)" }}
              title="More"
              onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-border-strong)"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-main)"; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "transparent"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; }}
            >
              <MoreHorizontal size={13} />
            </button>
          </div>
        </td>
        <td style={{ width: 40, padding: "10px 12px" }} />
      </tr>
    );
  };

  return (
    <div style={{ padding: "24px", minHeight: "100%", fontFamily: "'Inter', system-ui, sans-serif" }}>
      {/* Header */}
      <div className="flex items-center justify-between mb-5">
        <div>
          <h1 style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 3 }}>RFQ Pipeline</h1>
          <p style={{ fontSize: 13, color: "var(--app-text-subtle)" }}>{filtered.length} requests · {rfqs.filter(r => r.status === "Open").length} active</p>
        </div>
        <div className="flex items-center gap-2">
          {selectedIds.size > 0 && (
            <div className="flex items-center gap-2 rounded-lg px-3 py-1.5 mr-2" style={{ background: "var(--app-brand-tint-8)", border: "1px solid var(--app-brand-tint-20)" }}>
              <span style={{ fontSize: 12, color: "var(--app-brand-400)" }}>{selectedIds.size} selected</span>
              <button style={{ fontSize: 12, color: "var(--app-text-subtle)" }} className="hover:text-red-400 transition-colors ml-1">Delete</button>
              <button style={{ fontSize: 12, color: "var(--app-text-subtle)" }} className="hover:text-slate-200 transition-colors">Export</button>
            </div>
          )}
          <button
            onClick={() => navigate("/rfqs/create")}
            className="flex items-center gap-2 rounded-lg transition-opacity hover:opacity-90"
            style={{ height: 36, paddingLeft: 14, paddingRight: 14, background: "var(--app-brand-600)", color: "white", fontSize: 13, fontWeight: 500 }}
          >
            <Plus size={14} /> Create RFQ
          </button>
        </div>
      </div>

      {/* Filter bar */}
      <div className="flex items-center gap-3 mb-4">
        <div className="flex items-center gap-2 rounded-lg px-3 flex-1 max-w-sm" style={{ height: 36, background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)" }}>
          <Search size={13} style={{ color: "var(--app-text-subtle)" }} />
          <input
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            placeholder="Search RFQs, categories…"
            style={{ background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-subtle)", flex: 1 }}
          />
        </div>

        {/* Status filter */}
        <div className="flex items-center gap-1 p-1 rounded-lg" style={{ background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)" }}>
          {statuses.map((s) => (
            <button
              key={s}
              onClick={() => { setStatusFilter(s); setPage(1); }}
              className="rounded transition-all"
              style={{ fontSize: 12, padding: "4px 10px", background: statusFilter === s ? "var(--app-border-strong)" : "transparent", color: statusFilter === s ? "var(--app-text-main)" : "var(--app-text-muted)", fontWeight: statusFilter === s ? 500 : 400 }}
            >
              {s}
            </button>
          ))}
        </div>

        {/* Owner filter */}
        <div className="flex items-center gap-2 rounded-lg px-3" style={{ height: 36, background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)" }}>
          <Filter size={12} style={{ color: "var(--app-text-subtle)" }} />
          <select
            value={ownerFilter}
            onChange={(e) => { setOwnerFilter(e.target.value); setPage(1); }}
            style={{ background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-subtle)", cursor: "pointer" }}
          >
            {owners.map((o) => <option key={o} value={o} style={{ background: "var(--app-bg-elevated)" }}>{o === "All" ? "All Owners" : o}</option>)}
          </select>
        </div>

        {/* Group by */}
        <div className="flex items-center gap-2 rounded-lg px-3" style={{ height: 36, background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)" }}>
          <span style={{ fontSize: 12, color: "var(--app-text-muted)", whiteSpace: "nowrap" }}>Group by</span>
          <select
            value={groupBy}
            onChange={(e) => { setGroupBy(e.target.value as GroupByOption); setPage(1); }}
            style={{ background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-subtle)", cursor: "pointer" }}
          >
            <option value="none" style={{ background: "var(--app-bg-elevated)" }}>None</option>
            <option value="status" style={{ background: "var(--app-bg-elevated)" }}>Status</option>
            <option value="owner" style={{ background: "var(--app-bg-elevated)" }}>Owner</option>
            <option value="priority" style={{ background: "var(--app-bg-elevated)" }}>Priority</option>
          </select>
        </div>
      </div>

      {/* Table */}
      <div className="rounded-xl border overflow-hidden relative" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }} ref={configMenuRef}>
        <table style={{ width: "100%", borderCollapse: "collapse" }}>
          <thead>
            <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
              <th style={{ width: 40, padding: "10px 12px" }}>
                <button onClick={toggleAll} style={{ color: allSelected ? "var(--app-brand-500)" : "var(--app-text-faint)" }}>
                  {allSelected ? <CheckSquare size={15} /> : <Square size={15} />}
                </button>
              </th>
              {visibleColumns.map((col) => (
                <th
                  key={col}
                  className="text-left cursor-pointer group"
                  style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "10px 12px", whiteSpace: "nowrap" }}
                  onClick={() => ["id", "title", "status", "owner", "deadline", "budget"].includes(col) && handleSort(col as SortKey)}
                >
                  <div className="flex items-center gap-1.5">
                    {col === "id" ? "RFQ ID" : col === "vendors" ? "Vendors" : col.charAt(0).toUpperCase() + col.slice(1)}
                    {["id", "title", "status", "owner", "deadline", "budget"].includes(col) && (
                      <SortIcon col={col as SortKey} />
                    )}
                  </div>
                </th>
              ))}
              <th style={{ width: 48, padding: "10px 12px" }} />
              <th style={{ width: 40, padding: "10px 12px" }}>
                <button
                  onClick={(e) => { e.stopPropagation(); setConfigMenuOpen((o) => !o); }}
                  className="rounded p-1.5 transition-colors"
                  style={{ color: configMenuOpen ? "var(--app-brand-500)" : "var(--app-text-subtle)" }}
                  title="Configure table"
                  onMouseEnter={(e) => { if (!configMenuOpen) (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-main)"; }}
                  onMouseLeave={(e) => { if (!configMenuOpen) (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; }}
                >
                  <Menu size={16} />
                </button>
              </th>
            </tr>
          </thead>
          <tbody>
            {groupedData ? (
              groupedData.length === 0 ? (
                <tr>
                  <td colSpan={visibleColumns.length + 3} style={{ textAlign: "center", padding: "48px 0", color: "var(--app-text-faint)", fontSize: 14 }}>
                    No RFQs match your filters
                  </td>
                </tr>
              ) : (
                groupedData.flatMap(([groupKey, rows]) => {
                  const isCollapsed = collapsedGroups.has(groupKey);
                  return [
                    <tr
                      key={`group-${groupKey}`}
                      className="cursor-pointer"
                      style={{ background: "var(--app-bg-elevated)", borderBottom: "1px solid var(--app-border-strong)" }}
                      onClick={() => toggleGroup(groupKey)}
                    >
                      <td colSpan={visibleColumns.length + 3} style={{ padding: "8px 12px", fontSize: 12, fontWeight: 600, color: "var(--app-text-main)" }}>
                        <div className="flex items-center gap-2">
                          {isCollapsed ? <ChevronDownIcon size={14} style={{ color: "var(--app-text-muted)" }} /> : <ChevronUpIcon size={14} style={{ color: "var(--app-text-muted)" }} />}
                          {groupBy === "status" && (() => { const sc = statusConfig[groupKey] ?? statusConfig.Closed; return <span style={{ width: 6, height: 6, borderRadius: "50%", background: sc.dot, flexShrink: 0 }} />; })()}
                          {groupBy === "priority" && <span style={{ width: 6, height: 6, borderRadius: "50%", background: priorityDot[groupKey] ?? "var(--app-text-muted)", flexShrink: 0 }} />}
                          {groupKey} <span style={{ fontWeight: 400, color: "var(--app-text-muted)" }}>({rows.length})</span>
                        </div>
                      </td>
                    </tr>,
                    ...(isCollapsed ? [] : rows.map((rfq, i) => renderRow(rfq, i, rows.length))),
                  ];
                })
              )
            ) : paginated.length === 0 ? (
              <tr>
                <td colSpan={visibleColumns.length + 3} style={{ textAlign: "center", padding: "48px 0", color: "var(--app-text-faint)", fontSize: 14 }}>
                  No RFQs match your filters
                </td>
              </tr>
            ) : (
              paginated.map((rfq, i) => renderRow(rfq, i, paginated.length))
            )}
          </tbody>
        </table>

        {/* Table config popover */}
        {configMenuOpen && (
          <div
            className="rounded-lg border shadow-lg"
            style={{
              position: "absolute",
              top: "100%",
              right: 0,
              marginTop: 4,
              minWidth: 280,
              background: "var(--app-bg-elevated)",
              borderColor: "var(--app-border-strong)",
              zIndex: 50,
              padding: 12,
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.06em", textTransform: "uppercase", marginBottom: 8 }}>Columns</div>
            <div className="space-y-1" style={{ maxHeight: 200, overflowY: "auto" }}>
              {columnOrder.map((col, idx) => (
                <div key={col} className="flex items-center gap-2" style={{ padding: "4px 0" }}>
                  <label className="flex items-center gap-2 cursor-pointer flex-1 min-w-0">
                    <input
                      type="checkbox"
                      checked={columnVisible[col]}
                      onChange={() => setColumnVisible((prev) => ({ ...prev, [col]: !prev[col] }))}
                      style={{ accentColor: "var(--app-brand-500)" }}
                    />
                    <span style={{ fontSize: 13, color: "var(--app-text-main)" }}>{col === "id" ? "RFQ ID" : col === "vendors" ? "Vendors" : col.charAt(0).toUpperCase() + col.slice(1)}</span>
                  </label>
                  <div className="flex items-center gap-0.5 flex-shrink-0">
                    <button
                      type="button"
                      disabled={idx === 0}
                      onClick={() => setColumnOrder((prev) => {
                        const next = [...prev];
                        [next[idx - 1], next[idx]] = [next[idx], next[idx - 1]];
                        return next;
                      })}
                      style={{ padding: 2, color: idx === 0 ? "var(--app-text-faint)" : "var(--app-text-muted)", cursor: idx === 0 ? "not-allowed" : "pointer" }}
                      title="Move left"
                    >
                      <ChevronUpIcon size={14} />
                    </button>
                    <button
                      type="button"
                      disabled={idx === columnOrder.length - 1}
                      onClick={() => setColumnOrder((prev) => {
                        const next = [...prev];
                        [next[idx], next[idx + 1]] = [next[idx + 1], next[idx]];
                        return next;
                      })}
                      style={{ padding: 2, color: idx === columnOrder.length - 1 ? "var(--app-text-faint)" : "var(--app-text-muted)", cursor: idx === columnOrder.length - 1 ? "not-allowed" : "pointer" }}
                      title="Move right"
                    >
                      <ChevronDownIcon size={14} />
                    </button>
                  </div>
                </div>
              ))}
            </div>
            <div style={{ borderTop: "1px solid var(--app-border-strong)", marginTop: 10, paddingTop: 10 }}>
              <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.06em", textTransform: "uppercase", marginBottom: 6 }}>Rows per page</div>
              <div className="flex flex-wrap gap-2">
                {PAGE_SIZE_OPTIONS.map((n) => (
                  <button
                    key={n}
                    type="button"
                    onClick={() => { setPageSize(n); setPage(1); }}
                    className="rounded-lg transition-colors"
                    style={{
                      padding: "6px 12px",
                      fontSize: 12,
                      fontWeight: 500,
                      background: pageSize === n ? "var(--app-brand-600)" : "var(--app-bg-surface)",
                      color: pageSize === n ? "white" : "var(--app-text-subtle)",
                      border: `1px solid ${pageSize === n ? "var(--app-brand-600)" : "var(--app-border-strong)"}`,
                    }}
                  >
                    {n}
                  </button>
                ))}
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Pagination */}
      <div className="flex items-center justify-between mt-4">
        <span style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>
          {groupBy !== "none" && groupedData
            ? `Showing all ${filtered.length} RFQs in ${groupedData.length} groups`
            : `Showing ${Math.min((page - 1) * pageSize + 1, filtered.length)}–${Math.min(page * pageSize, filtered.length)} of ${filtered.length} RFQs`}
        </span>
        {groupBy === "none" && (
          <div className="flex items-center gap-2">
            <button
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={page === 1}
              className="flex items-center justify-center rounded-lg transition-colors"
              style={{ width: 32, height: 32, background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)", color: page === 1 ? "var(--app-text-faint)" : "var(--app-text-subtle)" }}
            >
              <ChevronLeft size={14} />
            </button>
            {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
              <button
                key={p}
                onClick={() => setPage(p)}
                className="flex items-center justify-center rounded-lg transition-colors"
                style={{ width: 32, height: 32, background: p === page ? "var(--app-brand-600)" : "var(--app-bg-surface)", border: `1px solid ${p === page ? "var(--app-brand-600)" : "var(--app-border-strong)"}`, color: p === page ? "white" : "var(--app-text-muted)", fontSize: 13, fontWeight: p === page ? 600 : 400 }}
              >
                {p}
              </button>
            ))}
            <button
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              disabled={page === totalPages}
              className="flex items-center justify-center rounded-lg transition-colors"
              style={{ width: 32, height: 32, background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)", color: page === totalPages ? "var(--app-text-faint)" : "var(--app-text-subtle)" }}
            >
              <ChevronRightIcon size={14} />
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
