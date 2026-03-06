import { useState } from "react";
import {
  Archive, Search, Filter, Download, ExternalLink,
  FileText, ShieldCheck, Clock, Eye, Lock,
  ChevronRight, MoreVertical, Trash2, Share2,
  CheckCircle2, AlertTriangle, Info, FileCode,
  FileLock2, HardDrive
} from "lucide-react";

interface EvidenceFile {
  id: string;
  name: string;
  type: "PDF" | "DOCX" | "JSON" | "XLSX";
  category: "Quote" | "Contract" | "AI Log" | "Review";
  rfqId: string;
  vendor: string;
  uploadedAt: string;
  uploadedBy: string;
  hash: string;
  status: "Verified" | "Flagged" | "Archived";
  size: string;
}

const mockEvidence: EvidenceFile[] = [
  {
    id: "EV-9021",
    name: "Apex_Quote_Technical_Spec_v2.pdf",
    type: "PDF",
    category: "Quote",
    rfqId: "RFQ-2024-001",
    vendor: "Apex Industrial",
    uploadedAt: "2024-01-22 14:35",
    uploadedBy: "Quote Intake AI",
    hash: "sha256:8f3e...2a1b",
    status: "Verified",
    size: "2.4 MB"
  },
  {
    id: "EV-9022",
    name: "Extraction_Log_Apex_V001.json",
    type: "JSON",
    category: "AI Log",
    rfqId: "RFQ-2024-001",
    vendor: "Apex Industrial",
    uploadedAt: "2024-01-22 14:36",
    uploadedBy: "Extraction Engine",
    hash: "sha256:4d1a...9c0e",
    status: "Verified",
    size: "128 KB"
  },
  {
    id: "EV-9023",
    name: "GlobalPump_Financial_Audit_Q4.pdf",
    type: "PDF",
    category: "Review",
    rfqId: "RFQ-2024-001",
    vendor: "GlobalPump Corp",
    uploadedAt: "2024-01-23 09:12",
    uploadedBy: "Sarah Chen",
    hash: "sha256:1b9c...ef34",
    status: "Flagged",
    size: "4.1 MB"
  },
  {
    id: "EV-9024",
    name: "Standard_Terms_And_Conditions.docx",
    type: "DOCX",
    category: "Contract",
    rfqId: "Global",
    vendor: "Internal",
    uploadedAt: "2023-12-15 11:00",
    uploadedBy: "System",
    hash: "sha256:ac22...bd88",
    status: "Archived",
    size: "850 KB"
  },
  {
    id: "EV-9025",
    name: "Comparison_Matrix_Final_Export.xlsx",
    type: "XLSX",
    category: "Review",
    rfqId: "RFQ-2024-001",
    vendor: "Multi",
    uploadedAt: "2024-01-24 16:45",
    uploadedBy: "David Martinez",
    hash: "sha256:f5e1...7a22",
    status: "Verified",
    size: "1.2 MB"
  }
];

export function EvidenceVault() {
  const [search, setSearch] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("All");
  const [selectedFile, setSelectedFile] = useState<EvidenceFile | null>(null);

  const categories = ["All", "Quote", "Contract", "AI Log", "Review"];
  
  const filtered = mockEvidence.filter(f => {
    const matchesSearch = f.name.toLowerCase().includes(search.toLowerCase()) || 
                         f.rfqId.toLowerCase().includes(search.toLowerCase()) ||
                         f.vendor.toLowerCase().includes(search.toLowerCase());
    const matchesCategory = selectedCategory === "All" || f.category === selectedCategory;
    return matchesSearch && matchesCategory;
  });

  const getStatusStyle = (status: string) => {
    switch(status) {
      case "Verified": return { color: "var(--app-success)", bg: "var(--app-success-tint-10)", icon: ShieldCheck };
      case "Flagged": return { color: "var(--app-danger)", bg: "var(--app-danger-tint-10)", icon: AlertTriangle };
      default: return { color: "var(--app-text-muted)", bg: "var(--app-bg-elevated)", icon: Archive };
    }
  };

  const getFileIcon = (type: string) => {
    switch(type) {
      case "JSON": return <FileCode size={18} className="text-amber-400" />;
      case "PDF": return <FileText size={18} className="text-red-400" />;
      default: return <FileText size={18} className="text-blue-400" />;
    }
  };

  return (
    <div className="flex flex-col h-full" style={{ background: "var(--app-bg-canvas)" }}>
      {/* Header */}
      <div className="border-b px-6 py-4 flex items-center justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Archive size={14} className="text-indigo-400" />
            <span style={{ fontSize: 11, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Compliance & Audits</span>
          </div>
          <h1 style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)" }}>Evidence Vault</h1>
        </div>
        <div className="flex items-center gap-3">
          <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg border bg-slate-900/50" style={{ borderColor: "var(--app-border-strong)" }}>
             <HardDrive size={14} className="text-slate-500" />
             <span style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-subtle)" }}>Storage: 12.4 GB / 100 GB</span>
          </div>
          <button className="flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-bold text-sm shadow-lg shadow-indigo-500/20 hover:bg-indigo-50 transition-all">
            <FileLock2 size={14} /> Seal Selection
          </button>
        </div>
      </div>

      {/* Toolbar */}
      <div className="px-6 py-3 border-b flex items-center justify-between bg-slate-900/20" style={{ borderColor: "var(--app-border-strong)" }}>
        <div className="flex items-center gap-4 flex-1">
          <div className="relative max-w-sm flex-1">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" />
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search by file name, RFQ, or vendor..."
              className="w-full pl-9 pr-4 py-1.5 rounded-lg border outline-none transition-all focus:border-indigo-500 bg-slate-900/50 text-white text-sm"
              style={{ borderColor: "var(--app-border-strong)" }}
            />
          </div>
          <div className="flex items-center gap-1 p-1 rounded-lg bg-slate-900/50 border" style={{ borderColor: "var(--app-border-strong)" }}>
            {categories.map(cat => (
              <button
                key={cat}
                onClick={() => setSelectedCategory(cat)}
                className={`px-3 py-1 rounded text-xs font-medium transition-all ${selectedCategory === cat ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-400 hover:text-slate-200'}`}
              >
                {cat}
              </button>
            ))}
          </div>
        </div>
        <div className="flex items-center gap-2">
          <button className="p-2 rounded-lg border hover:bg-slate-800 text-slate-400" style={{ borderColor: "var(--app-border-strong)" }}>
            <Filter size={14} />
          </button>
          <button className="p-2 rounded-lg border hover:bg-slate-800 text-slate-400" style={{ borderColor: "var(--app-border-strong)" }}>
            <Download size={14} />
          </button>
        </div>
      </div>

      <div className="flex-1 flex overflow-hidden">
        {/* Main Table */}
        <div className="flex-1 overflow-auto">
          <table className="w-full border-collapse">
            <thead className="sticky top-0 z-10 shadow-sm" style={{ background: "var(--app-bg-surface)" }}>
              <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                <th className="p-4 text-left"><input type="checkbox" className="rounded bg-slate-900 border-slate-700" /></th>
                <th style={{ fontSize: 10, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", padding: "12px 16px", textAlign: "left" }}>File Name</th>
                <th style={{ fontSize: 10, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", padding: "12px 16px", textAlign: "left" }}>Category</th>
                <th style={{ fontSize: 10, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", padding: "12px 16px", textAlign: "left" }}>Related RFQ</th>
                <th style={{ fontSize: 10, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", padding: "12px 16px", textAlign: "left" }}>Uploaded</th>
                <th style={{ fontSize: 10, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", padding: "12px 16px", textAlign: "left" }}>Status</th>
                <th className="p-4"></th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((file) => {
                const style = getStatusStyle(file.status);
                const StatusIcon = style.icon;
                const isSelected = selectedFile?.id === file.id;

                return (
                  <tr 
                    key={file.id} 
                    onClick={() => setSelectedFile(file)}
                    className={`group border-b cursor-pointer transition-colors ${isSelected ? 'bg-indigo-500/5' : 'hover:bg-slate-900/30'}`}
                    style={{ borderColor: "var(--app-border-strong)" }}
                  >
                    <td className="p-4"><input type="checkbox" className="rounded bg-slate-900 border-slate-700" onClick={(e) => e.stopPropagation()} /></td>
                    <td className="p-4">
                      <div className="flex items-center gap-3">
                        {getFileIcon(file.type)}
                        <div>
                          <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)" }}>{file.name}</div>
                          <div style={{ fontSize: 11, color: "var(--app-text-faint)" }}>{file.size} · {file.type}</div>
                        </div>
                      </div>
                    </td>
                    <td className="p-4">
                      <span style={{ fontSize: 11, color: "var(--app-text-muted)" }}>{file.category}</span>
                    </td>
                    <td className="p-4">
                      <div style={{ fontSize: 12, fontWeight: 600, color: "var(--app-brand-400)", fontFamily: "monospace" }}>{file.rfqId}</div>
                      <div style={{ fontSize: 11, color: "var(--app-text-faint)" }}>{file.vendor}</div>
                    </td>
                    <td className="p-4">
                      <div style={{ fontSize: 12, color: "var(--app-text-main)" }}>{file.uploadedAt}</div>
                      <div style={{ fontSize: 11, color: "var(--app-text-faint)" }}>by {file.uploadedBy}</div>
                    </td>
                    <td className="p-4">
                      <div className="flex items-center gap-1.5 px-2 py-1 rounded-full w-fit" style={{ background: style.bg, color: style.color, border: `1px solid ${style.color}22` }}>
                        <StatusIcon size={10} />
                        <span style={{ fontSize: 10, fontWeight: 700 }}>{file.status.toUpperCase()}</span>
                      </div>
                    </td>
                    <td className="p-4 text-right">
                      <button className="p-1 rounded hover:bg-slate-800 opacity-0 group-hover:opacity-100 transition-all text-slate-500">
                        <MoreVertical size={16} />
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {/* Right Detail Panel */}
        {selectedFile && (
          <div className="w-96 border-l flex flex-col shadow-2xl" style={{ borderColor: "var(--app-border-strong)", background: "var(--app-bg-surface)" }}>
            <div className="p-6 border-b flex items-start justify-between" style={{ borderColor: "var(--app-border-strong)" }}>
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-3">
                  <div className="p-2 rounded bg-slate-900 border" style={{ borderColor: "var(--app-border-strong)" }}>
                    {getFileIcon(selectedFile.type)}
                  </div>
                  <div>
                    <div style={{ fontSize: 14, fontWeight: 700, color: "var(--app-text-strong)", wordBreak: "break-all" }}>{selectedFile.name}</div>
                    <div style={{ fontSize: 11, color: "var(--app-text-faint)" }}>{selectedFile.id}</div>
                  </div>
                </div>
                <div className="flex gap-2">
                  <button className="flex-1 py-2 rounded-lg bg-indigo-600 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/20">
                    <Eye size={12} /> Preview
                  </button>
                  <button className="p-2 rounded-lg border hover:bg-slate-800" style={{ borderColor: "var(--app-border-strong)" }}>
                    <Download size={14} className="text-slate-400" />
                  </button>
                </div>
              </div>
            </div>

            <div className="flex-1 overflow-auto p-6 space-y-6">
              <div className="space-y-4">
                <div className="text-[10px] font-bold text-muted uppercase tracking-widest">Metadata & Hash</div>
                <div className="p-4 rounded-xl border bg-slate-900/50 space-y-4" style={{ borderColor: "var(--app-border-strong)" }}>
                  <div>
                    <div style={{ fontSize: 10, color: "var(--app-text-faint)", textTransform: "uppercase", marginBottom: 4 }}>Content Hash (Immutable)</div>
                    <div className="flex items-center justify-between gap-2 p-2 rounded bg-black/30 border border-white/5 font-mono text-[10px] text-indigo-300">
                      {selectedFile.hash}
                      <Lock size={10} className="text-slate-600" />
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <div style={{ fontSize: 10, color: "var(--app-text-faint)", textTransform: "uppercase", marginBottom: 2 }}>Storage Type</div>
                      <div style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-main)" }}>Encrypted S3</div>
                    </div>
                    <div>
                      <div style={{ fontSize: 10, color: "var(--app-text-faint)", textTransform: "uppercase", marginBottom: 2 }}>Retention</div>
                      <div style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-main)" }}>7 Years</div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <div className="text-[10px] font-bold text-muted uppercase tracking-widest">Integrity Check</div>
                <div className="p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 flex items-start gap-3">
                  <ShieldCheck size={16} className="text-emerald-500 mt-0.5" />
                  <div>
                    <div style={{ fontSize: 13, fontWeight: 700, color: "var(--app-success)" }}>Digital Seal Intact</div>
                    <p style={{ fontSize: 11, color: "var(--app-text-muted)", marginTop: 2, lineHeight: 1.5 }}>
                      This document has not been altered since it was uploaded by **{selectedFile.uploadedBy}** on **{selectedFile.uploadedAt}**.
                    </p>
                  </div>
                </div>
              </div>

              <div className="space-y-4 pt-4 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
                <div className="flex gap-2">
                  <button className="flex-1 py-2 flex items-center justify-center gap-2 rounded-lg border text-xs font-bold text-slate-400 hover:bg-slate-800" style={{ borderColor: "var(--app-border-strong)" }}>
                    <Share2 size={12} /> Share Evidence
                  </button>
                  <button className="px-3 py-2 rounded-lg border border-red-500/20 text-red-400 hover:bg-red-500/10">
                    <Trash2 size={14} />
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
