import { useState } from "react";
import { useNavigate } from "react-router";
import {
  Plus, Search, ChevronDown, ChevronUp, ArrowUpDown, Filter,
  MoreHorizontal, ExternalLink, Copy, Trash2, CheckSquare, Square,
  ChevronLeft, ChevronRight as ChevronRightIcon
} from "lucide-react";
import { rfqs } from "../data/mockData";

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
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("All");
  const [ownerFilter, setOwnerFilter] = useState<string>("All");
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const [sortKey, setSortKey] = useState<SortKey>("deadline");
  const [sortDir, setSortDir] = useState<"asc" | "desc">("asc");
  const [page, setPage] = useState(1);
  const pageSize = 8;

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

  const totalPages = Math.ceil(filtered.length / pageSize);
  const paginated = filtered.slice((page - 1) * pageSize, page * pageSize);

  const toggleSelect = (id: string) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id); else next.add(id);
      return next;
    });
  };

  const allSelected = paginated.length > 0 && paginated.every((r) => selectedIds.has(r.id));
  const toggleAll = () => {
    if (allSelected) setSelectedIds((prev) => { const next = new Set(prev); paginated.forEach((r) => next.delete(r.id)); return next; });
    else setSelectedIds((prev) => { const next = new Set(prev); paginated.forEach((r) => next.add(r.id)); return next; });
  };

  const SortIcon = ({ col }: { col: SortKey }) => {
    if (sortKey !== col) return <ArrowUpDown size={11} style={{ color: "var(--app-text-faint)" }} />;
    return sortDir === "asc" ? <ChevronUp size={11} style={{ color: "var(--app-brand-500)" }} /> : <ChevronDown size={11} style={{ color: "var(--app-brand-500)" }} />;
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
      </div>

      {/* Table */}
      <div className="rounded-xl border overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        <table style={{ width: "100%", borderCollapse: "collapse" }}>
          <thead>
            <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
              <th style={{ width: 40, padding: "10px 12px" }}>
                <button onClick={toggleAll} style={{ color: allSelected ? "var(--app-brand-500)" : "var(--app-text-faint)" }}>
                  {allSelected ? <CheckSquare size={15} /> : <Square size={15} />}
                </button>
              </th>
              {(["id", "title", "status", "owner", "vendors", "budget", "deadline", "priority"] as const).map((col) => (
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
            </tr>
          </thead>
          <tbody>
            {paginated.length === 0 ? (
              <tr>
                <td colSpan={10} style={{ textAlign: "center", padding: "48px 0", color: "var(--app-text-faint)", fontSize: 14 }}>
                  No RFQs match your filters
                </td>
              </tr>
            ) : (
              paginated.map((rfq, i) => {
                const isSelected = selectedIds.has(rfq.id);
                const sc = statusConfig[rfq.status] ?? statusConfig.Closed;
                return (
                  <tr
                    key={rfq.id}
                    style={{ borderBottom: i < paginated.length - 1 ? "1px solid var(--app-bg-elevated)" : "none", background: isSelected ? "var(--app-brand-tint-5)" : "transparent" }}
                    onMouseEnter={(e) => { if (!isSelected) (e.currentTarget as HTMLTableRowElement).style.background = "var(--app-hover-subtle)"; }}
                    onMouseLeave={(e) => { if (!isSelected) (e.currentTarget as HTMLTableRowElement).style.background = "transparent"; }}
                  >
                    <td style={{ padding: "10px 12px" }}>
                      <button onClick={(e) => { e.stopPropagation(); toggleSelect(rfq.id); }} style={{ color: isSelected ? "var(--app-brand-500)" : "var(--app-text-faint)" }}>
                        {isSelected ? <CheckSquare size={15} /> : <Square size={15} />}
                      </button>
                    </td>
                    <td style={{ padding: "10px 12px" }}>
                      <span style={{ fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-400)", fontWeight: 500 }}>{rfq.id}</span>
                    </td>
                    <td style={{ padding: "10px 12px", maxWidth: 240 }}>
                      <button
                        onClick={() => navigate(`/rfqs/${rfq.id}`)}
                        className="text-left hover:text-blue-400 transition-colors"
                        style={{ fontSize: 13, color: "var(--app-text-main)", fontWeight: 500, display: "block", whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis", maxWidth: 220 }}
                      >
                        {rfq.title}
                      </button>
                      <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginTop: 1 }}>{rfq.category}</div>
                    </td>
                    <td style={{ padding: "10px 12px" }}>
                      <span className="flex items-center gap-1.5 rounded-full px-2.5 py-0.5 w-fit" style={{ fontSize: 11, fontWeight: 600, background: sc.bg, color: sc.color }}>
                        <span style={{ width: 5, height: 5, borderRadius: "50%", background: sc.dot, display: "inline-block", flexShrink: 0 }} />
                        {rfq.status}
                      </span>
                    </td>
                    <td style={{ padding: "10px 12px", fontSize: 13, color: "var(--app-text-subtle)", whiteSpace: "nowrap" }}>{rfq.owner}</td>
                    <td style={{ padding: "10px 12px" }}>
                      <span style={{ fontSize: 13, color: "var(--app-text-muted)" }}>{rfq.vendors > 0 ? rfq.vendors : "—"}</span>
                    </td>
                    <td style={{ padding: "10px 12px", fontSize: 13, color: "var(--app-text-subtle)", fontFamily: "'JetBrains Mono', monospace", whiteSpace: "nowrap" }}>{rfq.budget}</td>
                    <td style={{ padding: "10px 12px", fontSize: 12, color: "var(--app-text-muted)", whiteSpace: "nowrap" }}>{rfq.deadline}</td>
                    <td style={{ padding: "10px 12px" }}>
                      <div className="flex items-center gap-1.5">
                        <span style={{ width: 6, height: 6, borderRadius: "50%", background: priorityDot[rfq.priority] ?? "var(--app-text-muted)", flexShrink: 0, display: "inline-block" }} />
                        <span style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{rfq.priority}</span>
                      </div>
                    </td>
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
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      <div className="flex items-center justify-between mt-4">
        <span style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>
          Showing {Math.min((page - 1) * pageSize + 1, filtered.length)}–{Math.min(page * pageSize, filtered.length)} of {filtered.length} RFQs
        </span>
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
      </div>
    </div>
  );
}
