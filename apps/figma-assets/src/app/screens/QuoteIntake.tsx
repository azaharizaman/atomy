import { useState } from "react";
import {
  Upload, CheckCircle2, XCircle, AlertTriangle, Clock, RefreshCw,
  FileText, ChevronRight, X, Plus, Zap, BarChart3, Eye, Download,
  AlertCircle, CheckCheck, Layers, Shield, PanelLeftClose, PanelLeftOpen,
  FileSearch, Table2
} from "lucide-react";
import { quoteSubmissions } from "../data/mockData";
import {
  Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetFooter
} from "../components/ui/sheet";

const statusConfig: Record<string, { bg: string; color: string; icon: any; border: string }> = {
  Accepted:              { bg: "var(--app-success-tint-8)",  color: "var(--app-success)", icon: CheckCircle2, border: "var(--app-success-tint-15)" },
  "Parsed with Warnings":{ bg: "var(--app-warning-tint-8)",  color: "var(--app-warning)", icon: AlertTriangle, border: "var(--app-warning-tint-15)" },
  Processing:            { bg: "var(--app-brand-tint-8)",  color: "var(--app-brand-400)", icon: RefreshCw,    border: "var(--app-brand-tint-15)" },
  Rejected:              { bg: "var(--app-danger-tint-8)",   color: "var(--app-danger-soft)", icon: XCircle,      border: "var(--app-danger-tint-15)" },
};

const confidenceColor = (score: number | null) => {
  if (score === null) return "var(--app-text-subtle)";
  if (score >= 85) return "var(--app-success)";
  if (score >= 65) return "var(--app-warning)";
  return "var(--app-danger)";
};

const confidenceBg = (score: number | null) => {
  if (score === null) return "var(--app-slate-tint-10)";
  if (score >= 85) return "var(--app-success-tint-10)";
  if (score >= 65) return "var(--app-warning-tint-10)";
  return "var(--app-danger-tint-10)";
};

const mockLineItems = [
  { id: 1, description: "Industrial Centrifugal Pump — 6\" Outlet", qty: 4, uom: "Units", unitPrice: 18200, total: 72800, confidence: 98, mapped: true, unSpscCode: "40151503", pricingAnomaly: false },
  { id: 2, description: "Installation & Commissioning Service", qty: 8, uom: "Days", unitPrice: 4500, total: 36000, confidence: 72, mapped: true, unSpscCode: "72151500", pricingAnomaly: false },
  { id: 3, description: "Spare Parts Kit (2-Year Supply)", qty: 1, uom: "Set", unitPrice: 24800, total: 24800, confidence: 91, mapped: true, unSpscCode: "40151505", pricingAnomaly: true },
  { id: 4, description: "Annual Maintenance Contract", qty: 2, uom: "Years", unitPrice: 12600, total: 25200, confidence: 45, mapped: false, unSpscCode: null, pricingAnomaly: false },
  { id: 5, description: "Training Program — 2 Engineers", qty: 1, uom: "Program", unitPrice: 6800, total: 6800, confidence: 88, mapped: true, unSpscCode: "86101700", pricingAnomaly: false },
];

const mockWarnings = [
  { id: 1, field: "Warranty Terms", message: "Warranty duration not specified. Policy requires minimum 12 months.", severity: "High" },
  { id: 2, field: "Currency", message: "Quote uses EUR but RFQ requires USD. Auto-conversion applied at 1.08 rate.", severity: "Medium" },
  { id: 3, field: "Incoterms", message: "Incoterm 'EXW' detected. Project policy prefers 'DDP' or 'CIF'.", severity: "Medium" },
  { id: 4, field: "Lead Time", message: "Extracted lead time (45 days) exceeds RFQ requirement of 30 days.", severity: "High" },
  { id: 5, field: "Payment Days", message: "Payment terms 'Net 60' extracted. Standard policy is 'Net 30'.", severity: "Low" },
];

export function QuoteIntake() {
  const [selectedSubmission, setSelectedSubmission] = useState(quoteSubmissions[1]);
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [showInsights, setShowInsights] = useState(false);
  const [dragOver, setDragOver] = useState(false);
  const [uploadProgress, setUploadProgress] = useState<number | null>(null);
  const [acceptedIds, setAcceptedIds] = useState<Set<string>>(new Set(["QS-001", "QS-005"]));
  const [queueCollapsed, setQueueCollapsed] = useState(false);
  const [previewMode, setPreviewMode] = useState<"document" | "table">("document");

  const handleAccept = (id: string) => {
    setAcceptedIds((prev) => new Set([...prev, id]));
  };

  const getStatus = (sub: typeof quoteSubmissions[0]) => {
    if (acceptedIds.has(sub.id) && sub.status !== "Rejected") return "Accepted";
    return sub.status;
  };

  const sc = statusConfig[getStatus(selectedSubmission)] ?? statusConfig.Processing;
  const StatusIcon = sc.icon;

  const handleUpload = () => {
    setShowUploadModal(false);
    setUploadProgress(0);
    const interval = setInterval(() => {
      setUploadProgress((p) => {
        if (p === null || p >= 100) { clearInterval(interval); return null; }
        return p + 12;
      });
    }, 200);
  };

  return (
    <div style={{ display: "flex", height: "100%", minHeight: "calc(100vh - 88px)", fontFamily: "'Inter', system-ui, sans-serif" }}>
      {/* Left: Queue */}
      <div 
        className="flex-shrink-0 flex flex-col border-r transition-all duration-200" 
        style={{ 
          width: queueCollapsed ? 48 : 320, 
          background: "var(--app-bg-surface)", 
          borderColor: "var(--app-border-strong)",
          overflow: "hidden"
        }}
      >
        {/* Queue Header */}
        <div className={`px-4 py-3 border-b flex items-center ${queueCollapsed ? 'justify-center' : 'justify-between'}`} style={{ borderColor: "var(--app-border-strong)", minHeight: 53 }}>
          {!queueCollapsed && (
            <div>
              <h2 style={{ fontSize: 14, fontWeight: 600, color: "var(--app-text-strong)" }}>Intake Queue</h2>
              <p style={{ fontSize: 11, color: "var(--app-text-subtle)", marginTop: 1 }}>{quoteSubmissions.length} submissions</p>
            </div>
          )}
          <div className="flex items-center gap-2">
            {!queueCollapsed && (
              <button
                onClick={() => setShowUploadModal(true)}
                className="flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition-opacity hover:opacity-90"
                style={{ fontSize: 12, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}
              >
                <Upload size={12} /> Upload
              </button>
            )}
            <button 
              onClick={() => setQueueCollapsed(!queueCollapsed)}
              className="p-1.5 rounded hover:bg-slate-800 text-slate-400 transition-colors"
              title={queueCollapsed ? "Expand Queue" : "Collapse Queue"}
            >
              {queueCollapsed ? <PanelLeftOpen size={16} /> : <PanelLeftClose size={16} />}
            </button>
          </div>
        </div>

        {!queueCollapsed && (
          <>
            {/* Upload progress */}
            {uploadProgress !== null && (
              <div className="px-4 py-3 border-b" style={{ borderColor: "var(--app-border-strong)", background: "var(--app-brand-tint-4)" }}>
                <div className="flex items-center justify-between mb-1.5">
                  <span style={{ fontSize: 12, color: "var(--app-brand-400)" }}>Uploading & parsing…</span>
                  <span style={{ fontSize: 12, color: "var(--app-brand-400)" }}>{uploadProgress}%</span>
                </div>
                <div className="rounded-full overflow-hidden" style={{ height: 4, background: "var(--app-border-strong)" }}>
                  <div style={{ height: "100%", width: `${uploadProgress}%`, background: "var(--app-brand-500)", transition: "width 0.2s", borderRadius: 2 }} />
                </div>
              </div>
            )}

            {/* Filter tabs */}
            <div className="flex items-center gap-0 px-2 py-2 border-b" style={{ borderColor: "var(--app-border-strong)" }}>
              {["All", "Pending", "Accepted", "Issues"].map((f) => (
                <button key={f} className="flex-1 rounded py-1 transition-colors" style={{ fontSize: 11, color: "var(--app-text-muted)" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; (e.currentTarget as HTMLButtonElement).style.background = "var(--app-hover-soft)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-muted)"; (e.currentTarget as HTMLButtonElement).style.background = "transparent"; }}>
                  {f}
                </button>
              ))}
            </div>

            {/* Queue items */}
            <div className="flex-1 overflow-y-auto py-2" style={{ scrollbarWidth: "none" }}>
              {quoteSubmissions.map((sub) => {
                const status = getStatus(sub);
                const sc2 = statusConfig[status] ?? statusConfig.Processing;
                const Icon2 = sc2.icon;
                const isSelected = selectedSubmission.id === sub.id;

                return (
                  <div
                    key={sub.id}
                    className="mx-2 mb-1 rounded-lg p-3 cursor-pointer border transition-all"
                    style={{
                      background: isSelected ? "var(--app-brand-tint-8)" : "transparent",
                      borderColor: isSelected ? "var(--app-brand-tint-25)" : "transparent",
                    }}
                    onClick={() => setSelectedSubmission(sub)}
                    onMouseEnter={(e) => { if (!isSelected) { (e.currentTarget as HTMLDivElement).style.background = "var(--app-hover-soft)"; } }}
                    onMouseLeave={(e) => { if (!isSelected) { (e.currentTarget as HTMLDivElement).style.background = "transparent"; } }}
                  >
                    <div className="flex items-start justify-between mb-1.5">
                      <span style={{ fontSize: 11, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 500 }}>{sub.id}</span>
                      <span className="flex items-center gap-1 rounded-full px-2 py-0.5" style={{ fontSize: 10, fontWeight: 600, background: sc2.bg, color: sc2.color }}>
                        <Icon2 size={9} />
                        {status}
                      </span>
                    </div>
                    <div style={{ fontSize: 13, fontWeight: 500, color: "var(--app-text-main)", marginBottom: 3, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>
                      {sub.vendor}
                    </div>
                    <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginBottom: 4 }}>{sub.rfqId}</div>
                    <div className="flex items-center justify-between">
                      <span style={{ fontSize: 10, color: "var(--app-text-faint)" }}>{sub.submittedAt}</span>
                      {sub.confidence !== null && (
                        <span className="rounded px-1.5 py-0.5" style={{ fontSize: 10, fontWeight: 700, background: confidenceBg(sub.confidence), color: confidenceColor(sub.confidence) }}>
                          {sub.confidence}%
                        </span>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </>
        )}
        {queueCollapsed && (
          <div className="flex-1 flex flex-col items-center py-4 gap-4 relative">
            {quoteSubmissions.map((sub) => {
              const isSelected = selectedSubmission.id === sub.id;
              return (
                <button
                  key={sub.id}
                  onClick={() => setSelectedSubmission(sub)}
                  className={`w-8 h-8 rounded-lg flex items-center justify-center transition-all z-10 ${isSelected ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' : 'bg-slate-800 text-slate-400 hover:text-white'}`}
                  title={sub.vendor}
                >
                  <span style={{ fontSize: 10, fontWeight: 700 }}>{sub.id.split('-')[1]}</span>
                </button>
              );
            })}
            <button 
              onClick={() => setShowUploadModal(true)}
              className="w-8 h-8 rounded-lg bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-all mt-auto mb-4 z-10"
              title="Upload Quote"
            >
              <Plus size={16} />
            </button>
          </div>
        )}
      </div>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col overflow-hidden" style={{ background: "var(--app-bg-canvas)" }}>
        {/* Submission header */}
        <div className="px-6 py-4 border-b flex items-start justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
          <div>
            <div className="flex items-center gap-3 mb-1">
              <span style={{ fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 500 }}>{selectedSubmission.id}</span>
              <span className="flex items-center gap-1.5 rounded-full px-2.5 py-0.5" style={{ fontSize: 11, fontWeight: 600, background: sc.bg, color: sc.color, border: `1px solid ${sc.border}` }}>
                <StatusIcon size={11} />
                {getStatus(selectedSubmission)}
              </span>
              <span style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>{selectedSubmission.rfqId}</span>
            </div>
            <h2 style={{ fontSize: 18, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 4 }}>{selectedSubmission.vendor}</h2>
            <div className="flex items-center gap-4" style={{ fontSize: 12, color: "var(--app-text-muted)" }}>
              <span>{selectedSubmission.fileName}</span>
              <span>{selectedSubmission.fileSize}</span>
              <span>Submitted {selectedSubmission.submittedAt}</span>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <button
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
              style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
              onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
            >
              <Download size={13} /> Download
            </button>
            {getStatus(selectedSubmission) !== "Accepted" && getStatus(selectedSubmission) !== "Rejected" && (
              <button
                onClick={() => handleAccept(selectedSubmission.id)}
                className="flex items-center gap-2 rounded-lg px-3 py-2 transition-opacity hover:opacity-90"
                style={{ fontSize: 13, fontWeight: 500, background: "var(--app-success)", color: "white" }}
              >
                <CheckCheck size={13} /> Accept Quote
              </button>
            )}
          </div>
        </div>

        {/* Split Content Area */}
        <div className="flex-1 flex overflow-hidden">
          {/* Left Sidebar: Scorecard & Issues */}
          <div className="w-[360px] flex-shrink-0 border-r overflow-y-auto p-6 space-y-6" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            {/* Confidence Score */}
            <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
              <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>Average AI Confidence</div>
              {selectedSubmission.confidence !== null ? (
                <>
                  <div style={{ fontSize: 38, fontWeight: 800, color: confidenceColor(selectedSubmission.confidence), letterSpacing: "-0.03em", lineHeight: 1, marginBottom: 6 }}>
                    {selectedSubmission.confidence}%
                  </div>
                  <div className="rounded-full overflow-hidden mb-3" style={{ height: 6, background: "var(--app-border-strong)" }}>
                    <div style={{ height: "100%", width: `${selectedSubmission.confidence}%`, background: confidenceColor(selectedSubmission.confidence), borderRadius: 3 }} />
                  </div>
                  <div style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>
                    {selectedSubmission.confidence >= 85 ? "High confidence — ready to accept" : selectedSubmission.confidence >= 65 ? "Medium confidence — review warnings" : "Low confidence — manual review required"}
                  </div>
                </>
              ) : (
                <div className="flex items-center gap-2" style={{ fontSize: 13, color: "var(--app-brand-400)" }}>
                  <RefreshCw size={14} className="animate-spin" />
                  Processing…
                </div>
              )}
            </div>

            {/* Parse Stats */}
            <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
              <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>Extraction Results</div>
              <div className="space-y-2">
                {[
                  { label: "Line Items", value: selectedSubmission.lineItems ?? "—", icon: Layers, color: "var(--app-brand-400)" },
                  { label: "Commercial Terms", value: "4 extracted", icon: FileText, color: "var(--app-success)" },
                  { label: "Risk Assessment", value: selectedSubmission.warnings ? `${selectedSubmission.warnings} risks detected` : "—", icon: AlertTriangle, color: selectedSubmission.warnings ? "var(--app-warning)" : "var(--app-text-subtle)" },
                ].map((item) => (
                  <div key={item.label} className="flex items-center justify-between">
                    <div className="flex items-center gap-2" style={{ fontSize: 12, color: "var(--app-text-muted)" }}>
                      <item.icon size={12} style={{ color: item.color }} />
                      {item.label}
                    </div>
                    <span style={{ fontSize: 14, fontWeight: 700, color: item.color }}>{item.value}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* AI Insights */}
            <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
              <div className="flex items-center gap-2 mb-3">
                <Zap size={12} style={{ color: "var(--app-accent-purple)" }} />
                <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.08em", textTransform: "uppercase" }}>AI Insights</div>
              </div>
              <div style={{ fontSize: 12, color: "var(--app-text-muted)", lineHeight: 1.6 }}>
                {selectedSubmission.confidence !== null ? (
                  selectedSubmission.confidence >= 85
                    ? "All line items successfully mapped. No critical issues detected. Ready for comparison."
                    : selectedSubmission.confidence >= 65
                    ? "3 line items require attention. Currency conversion applied. Commercial terms extracted with warnings."
                    : "Multiple extraction failures detected. Manual data entry may be required for accurate comparison."
                ) : "Parsing in progress. AI extraction engine processing document…"}
              </div>
              {selectedSubmission.confidence !== null && (
                <button
                  onClick={() => setShowInsights(true)}
                  style={{ fontSize: 12, color: "var(--app-accent-purple)", marginTop: 8 }}
                  className="flex items-center gap-1 hover:opacity-80 transition-opacity"
                >
                  Review AI extraction <ChevronRight size={11} />
                </button>
              )}
            </div>

            {/* Validation Issues */}
            {(getStatus(selectedSubmission) === "Parsed with Warnings" || getStatus(selectedSubmission) === "Rejected") && (
              <div className="space-y-3">
                <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.08em", textTransform: "uppercase" }}>Validation Issues</div>
                <div className="space-y-2">
                  {mockWarnings.map((w) => (
                    <div key={w.id} className="flex flex-col gap-2 rounded-lg p-3" style={{
                      background: w.severity === "High" ? "var(--app-danger-tint-5)" : w.severity === "Medium" ? "var(--app-warning-tint-5)" : "var(--app-brand-tint-5)",
                      border: `1px solid ${w.severity === "High" ? "var(--app-danger-tint-15)" : w.severity === "Medium" ? "var(--app-warning-tint-12)" : "var(--app-brand-tint-10)"}`,
                    }}>
                      <div className="flex items-start gap-2">
                        <AlertCircle size={13} style={{ color: w.severity === "High" ? "var(--app-danger-soft)" : w.severity === "Medium" ? "var(--app-warning-soft)" : "var(--app-brand-400)", flexShrink: 0, marginTop: 1 }} />
                        <div className="flex-1">
                          <div className="flex items-center justify-between">
                            <span style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-main)" }}>{w.field}</span>
                            <span className="rounded px-1.5 py-0.5" style={{ fontSize: 9, fontWeight: 700, background: w.severity === "High" ? "var(--app-danger-tint-15)" : w.severity === "Medium" ? "var(--app-warning-tint-12)" : "var(--app-brand-tint-10)", color: w.severity === "High" ? "var(--app-danger-soft)" : "var(--app-warning-soft)" }}>
                              {w.severity}
                            </span>
                          </div>
                          <div style={{ fontSize: 11, color: "var(--app-text-muted)", marginTop: 2, lineHeight: 1.4 }}>{w.message}</div>
                        </div>
                      </div>
                      <button 
                        onClick={() => setShowInsights(true)}
                        style={{ fontSize: 11, color: "var(--app-brand-500)", alignSelf: "flex-end" }} 
                        className="hover:opacity-80 transition-opacity font-medium"
                      >
                        Resolve
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Right Section: Document Preview / Table Toggle */}
          <div className="flex-1 flex flex-col overflow-hidden relative">
            {/* Toggle Bar */}
            <div className="absolute top-4 right-6 z-20 flex bg-slate-900/80 backdrop-blur border border-white/10 rounded-lg p-1 shadow-2xl">
              <button
                onClick={() => setPreviewMode("document")}
                className={`flex items-center gap-2 px-3 py-1.5 rounded-md transition-all text-xs font-semibold ${previewMode === "document" ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:text-slate-200'}`}
              >
                <FileSearch size={14} /> Document
              </button>
              <button
                onClick={() => setPreviewMode("table")}
                className={`flex items-center gap-2 px-3 py-1.5 rounded-md transition-all text-xs font-semibold ${previewMode === "table" ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:text-slate-200'}`}
              >
                <Table2 size={14} /> Line Items
              </button>
            </div>

            {/* Content Area */}
            <div className="flex-1 overflow-auto p-6">
              {previewMode === "document" ? (
                <div className="h-full w-full rounded-xl border border-dashed flex flex-col items-center justify-center gap-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <div className="w-full max-w-2xl h-[800px] bg-slate-800/50 rounded-lg shadow-2xl border border-white/5 flex flex-col">
                    <div className="h-12 border-b border-white/5 flex items-center px-4 justify-between bg-slate-900/50">
                      <div className="flex items-center gap-2">
                        <FileText size={16} className="text-blue-400" />
                        <span className="text-xs font-medium text-slate-300">{selectedSubmission.fileName}</span>
                      </div>
                      <div className="flex items-center gap-3">
                        <div className="h-1.5 w-24 bg-slate-700 rounded-full overflow-hidden">
                          <div className="h-full w-1/3 bg-blue-500" />
                        </div>
                        <span className="text-[10px] text-slate-500">Page 1 of 3</span>
                      </div>
                    </div>
                    <div className="flex-1 p-12 space-y-8 overflow-y-auto">
                      {/* Mock PDF Content */}
                      <div className="flex justify-between items-start">
                        <div className="space-y-2">
                          <div className="h-6 w-48 bg-slate-700 rounded" />
                          <div className="h-3 w-32 bg-slate-800 rounded" />
                        </div>
                        <div className="h-16 w-16 bg-slate-700 rounded-lg" />
                      </div>
                      <div className="space-y-4 pt-8">
                        <div className="h-4 w-full bg-slate-800 rounded" />
                        <div className="h-4 w-full bg-slate-800 rounded" />
                        <div className="h-4 w-2/3 bg-slate-800 rounded" />
                      </div>
                      <div className="pt-8 space-y-3">
                        <div className="h-10 w-full bg-slate-700/30 rounded border border-white/5" />
                        <div className="h-10 w-full bg-slate-700/30 rounded border border-white/5" />
                        <div className="h-10 w-full bg-slate-700/30 rounded border border-white/5" />
                        <div className="h-10 w-full bg-slate-700/30 rounded border border-white/5" />
                      </div>
                      <div className="pt-12 flex justify-end">
                        <div className="space-y-3 w-48">
                          <div className="flex justify-between"><div className="h-3 w-16 bg-slate-800 rounded" /><div className="h-3 w-16 bg-slate-800 rounded" /></div>
                          <div className="flex justify-between"><div className="h-3 w-16 bg-slate-800 rounded" /><div className="h-3 w-16 bg-slate-800 rounded" /></div>
                          <div className="h-px bg-slate-700" />
                          <div className="flex justify-between"><div className="h-4 w-16 bg-slate-700 rounded" /><div className="h-4 w-20 bg-slate-700 rounded" /></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <p className="text-xs text-slate-500 italic">Interactive PDF viewer placeholder — AI extraction active</p>
                </div>
              ) : (
                <div className="rounded-xl border overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center justify-between px-4 py-3 border-b" style={{ borderColor: "var(--app-border-strong)" }}>
                    <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase" }}>
                      Extracted Line Items ({mockLineItems.length})
                    </h3>
                    <div className="flex items-center gap-2" style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>
                      <CheckCircle2 size={11} style={{ color: "var(--app-success)" }} />
                      {mockLineItems.filter(l => l.mapped).length} mapped
                      {mockLineItems.some(l => !l.mapped) && (
                        <span style={{ color: "var(--app-warning)" }}>· {mockLineItems.filter(l => !l.mapped).length} unmapped</span>
                      )}
                    </div>
                  </div>
                  <table style={{ width: "100%", borderCollapse: "collapse" }}>
                    <thead>
                      <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                        {["#", "Description", "UNSPSC Code", "Qty", "UOM", "Unit Price", "Total", "Confidence", "Mapping"].map((h) => (
                          <th key={h} style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "8px 14px", textAlign: "left", background: "var(--app-bg-elevated)" }}>{h}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {mockLineItems.map((item, i) => (
                        <tr key={item.id} style={{ borderBottom: i < mockLineItems.length - 1 ? "1px solid var(--app-bg-elevated)" : "none" }}>
                          <td style={{ padding: "10px 14px", fontSize: 12, color: "var(--app-text-faint)" }}>{item.id}</td>
                          <td style={{ padding: "10px 14px", fontSize: 13, color: "var(--app-text-main)", maxWidth: 280 }}>
                            <div className="flex items-center gap-2">
                              <div style={{ whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>{item.description}</div>
                              {item.pricingAnomaly && (
                                <span className="flex-shrink-0" title="Pricing Anomaly Detected">
                                  <AlertTriangle size={12} style={{ color: "var(--app-danger)" }} />
                                </span>
                              )}
                            </div>
                          </td>
                          <td style={{ padding: "10px 14px", fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-text-muted)" }}>
                            {item.unSpscCode ?? <span style={{ color: "var(--app-text-faint)" }}>—</span>}
                          </td>
                          <td style={{ padding: "10px 14px", fontSize: 13, color: "var(--app-text-subtle)", textAlign: "right" }}>{item.qty}</td>
                          <td style={{ padding: "10px 14px", fontSize: 12, color: "var(--app-text-muted)" }}>{item.uom}</td>
                          <td style={{ padding: "10px 14px", fontSize: 13, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-text-subtle)", textAlign: "right" }}>
                            ${item.unitPrice.toLocaleString()}
                          </td>
                          <td style={{ padding: "10px 14px", fontSize: 13, fontFamily: "'JetBrains Mono', monospace", fontWeight: 600, color: "var(--app-brand-400)", textAlign: "right" }}>
                            ${item.total.toLocaleString()}
                          </td>
                          <td style={{ padding: "10px 14px" }}>
                            <div className="flex items-center gap-2">
                              <div className="rounded-full overflow-hidden" style={{ width: 48, height: 4, background: "var(--app-border-strong)" }}>
                                <div style={{ height: "100%", width: `${item.confidence}%`, background: confidenceColor(item.confidence), borderRadius: 2 }} />
                              </div>
                              <span style={{ fontSize: 11, color: confidenceColor(item.confidence), fontWeight: 600 }}>{item.confidence}%</span>
                            </div>
                          </td>
                          <td style={{ padding: "10px 14px" }}>
                            {item.mapped ? (
                              <div className="flex items-center gap-1" style={{ fontSize: 11, color: "var(--app-success)" }}>
                                <CheckCircle2 size={11} /> Mapped
                              </div>
                            ) : (
                              <button style={{ fontSize: 11, color: "var(--app-warning)", background: "var(--app-warning-tint-8)", border: "1px solid var(--app-warning-tint-15)", borderRadius: 4, padding: "2px 8px" }}>
                                Map Now
                              </button>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                    <tfoot>
                      <tr style={{ borderTop: "2px solid var(--app-border-strong)" }}>
                        <td colSpan={4} style={{ padding: "10px 14px", fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", textAlign: "right" }}>TOTAL</td>
                        <td />
                        <td style={{ padding: "10px 14px", fontSize: 14, fontFamily: "'JetBrains Mono', monospace", fontWeight: 800, color: "var(--app-text-strong)", textAlign: "right" }}>
                          ${mockLineItems.reduce((s, i) => s + i.total, 0).toLocaleString()}
                        </td>
                        <td colSpan={2} />
                      </tr>
                    </tfoot>
                  </table>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Upload Modal */}
      {showUploadModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "var(--app-overlay-strong)" }}>
          <div className="rounded-xl border shadow-2xl p-6" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", width: 520 }}>
            <div className="flex items-center justify-between mb-4">
              <h3 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)" }}>Upload & Parse Quote</h3>
              <button onClick={() => setShowUploadModal(false)} style={{ color: "var(--app-text-subtle)" }} className="hover:text-slate-300 transition-colors">
                <X size={16} />
              </button>
            </div>

            <div
              className="rounded-xl border-2 border-dashed p-10 text-center mb-5 transition-all cursor-pointer"
              style={{ borderColor: dragOver ? "var(--app-brand-500)" : "var(--app-border-strong)", background: dragOver ? "var(--app-brand-tint-6)" : "var(--app-brand-tint-2)" }}
              onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
              onDragLeave={() => setDragOver(false)}
              onDrop={(e) => { e.preventDefault(); setDragOver(false); }}
            >
              <Upload size={28} style={{ color: "var(--app-brand-500)", margin: "0 auto 10px" }} />
              <div style={{ fontSize: 15, fontWeight: 600, color: "var(--app-text-subtle)", marginBottom: 4 }}>Drag & drop vendor quote here</div>
              <div style={{ fontSize: 13, color: "var(--app-text-subtle)", marginBottom: 12 }}>PDF, XLSX, CSV, DOCX · Up to 25MB</div>
              <button className="rounded-lg px-4 py-2" style={{ fontSize: 13, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}>
                Browse Files
              </button>
            </div>

            <div className="mb-4">
              <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 6 }}>LINK TO RFQ</label>
              <select style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-subtle)", outline: "none", cursor: "pointer" }}>
                <option style={{ background: "var(--app-bg-elevated)" }}>RFQ-2024-001 — Industrial Pumping Equipment</option>
                <option style={{ background: "var(--app-bg-elevated)" }}>RFQ-2024-005 — IT Hardware Refresh 2024</option>
                <option style={{ background: "var(--app-bg-elevated)" }}>RFQ-2024-007 — Preventive Maintenance Contracts</option>
              </select>
            </div>

            <div className="rounded-lg p-3 mb-4 flex items-start gap-2" style={{ background: "var(--app-purple-tint-6)", border: "1px solid var(--app-purple-tint-15)" }}>
              <Zap size={13} style={{ color: "var(--app-accent-purple)", flexShrink: 0, marginTop: 1 }} />
              <div style={{ fontSize: 12, color: "var(--app-accent-purple)", lineHeight: 1.5 }}>
                AI parser will extract line items, prices, and terms automatically. Low-confidence extractions will be flagged for review.
              </div>
            </div>

            <div className="flex justify-end gap-2">
              <button onClick={() => setShowUploadModal(false)} className="rounded-lg px-4 py-2" style={{ fontSize: 13, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}>Cancel</button>
              <button onClick={handleUpload} className="flex items-center gap-2 rounded-lg px-4 py-2" style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}>
                <Upload size={13} /> Upload & Parse
              </button>
            </div>
          </div>
        </div>
      )}

      {/* AI Extraction Insights Drawer */}
      <Sheet open={showInsights} onOpenChange={setShowInsights}>
        <SheetContent className="sm:max-w-xl overflow-y-auto p-6">
          <SheetHeader className="p-0 mb-6">
            <div className="flex items-center gap-2 mb-1">
              <Zap size={16} className="text-purple-500" />
              <SheetTitle>AI Extraction Insights</SheetTitle>
            </div>
            <SheetDescription>
              Detailed breakdown of the intelligence pipeline results for {selectedSubmission.id}.
            </SheetDescription>
          </SheetHeader>

          <div className="space-y-8">
            {/* Confidence Breakdown */}
            <section>
              <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-4">Confidence Breakdown</h4>
              <div className="space-y-4">
                {[
                  { label: "Entity Extraction", score: 94, desc: "Vendors, Dates, Addresses" },
                  { label: "Line Item Parsing", score: 82, desc: "Descriptions, Quantities, Prices" },
                  { label: "Commercial Terms", score: 71, desc: "Incoterms, Payment, Warranty" },
                  { label: "Semantic Mapping", score: 65, desc: "UNSPSC Taxonomy Alignment" },
                ].map((item) => (
                  <div key={item.label}>
                    <div className="flex justify-between items-end mb-1.5">
                      <div>
                        <div className="text-sm font-medium">{item.label}</div>
                        <div className="text-[11px] text-muted-foreground">{item.desc}</div>
                      </div>
                      <div className="text-sm font-bold" style={{ color: confidenceColor(item.score) }}>{item.score}%</div>
                    </div>
                    <div className="h-1.5 w-full bg-border rounded-full overflow-hidden">
                      <div
                        className="h-full rounded-full transition-all"
                        style={{ width: `${item.score}%`, background: confidenceColor(item.score) }}
                      />
                    </div>
                  </div>
                ))}
              </div>
            </section>

            {/* Extracted Commercial Terms */}
            <section className="rounded-lg border bg-muted/30 p-5">
              <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-4 flex items-center gap-2">
                <FileText size={12} /> Extracted Commercial Terms
              </h4>
              <div className="grid grid-cols-2 gap-y-5 gap-x-8">
                {[
                  { label: "Incoterm", value: "EXW (Ex Works)", confidence: 88 },
                  { label: "Payment Days", value: "60 Days", confidence: 92 },
                  { label: "Lead Time", value: "45 Days", confidence: 64 },
                  { label: "Warranty", value: "Not Found", confidence: 0 },
                ].map((term) => (
                  <div key={term.label}>
                    <div className="text-[10px] text-muted-foreground uppercase font-bold tracking-tight mb-0.5">{term.label}</div>
                    <div className={`text-sm font-medium ${term.value === "Not Found" ? 'text-destructive' : ''}`}>{term.value}</div>
                    {term.confidence > 0 && (
                      <div className="text-[10px] mt-1" style={{ color: confidenceColor(term.confidence) }}>
                        {term.confidence}% confidence
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </section>

            {/* Semantic Mapping Context */}
            <section>
              <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Semantic Mapping Context</h4>
              <div className="space-y-3">
                <div className="text-xs text-muted-foreground leading-relaxed">
                  The AI engine maps extracted descriptions to standard UNSPSC taxonomy codes to enable normalized cross-vendor comparison.
                </div>
                <div className="p-4 rounded-md border border-dashed bg-elevated text-xs space-y-2.5">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Taxonomy Version:</span>
                    <span className="font-mono font-medium text-main">v25.0901</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Mapping Strategy:</span>
                    <span className="font-medium text-main">Neural Semantic Search</span>
                  </div>
                </div>
              </div>
            </section>

            {/* Normalization Logs */}
            <section>
              <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Normalization Actions</h4>
              <div className="space-y-2.5">
                {[
                  "Currency conversion: EUR → USD applied (Rate: 1.0852)",
                  "UoM normalization: 'Boxes' → 'Units' (Factor: 12)",
                  "Date normalization: 'DD/MM/YYYY' detected",
                ].map((log, i) => (
                  <div key={i} className="flex gap-2.5 text-xs text-muted-foreground">
                    <div className="w-1 h-1 rounded-full bg-blue-500 mt-1.5 shrink-0" />
                    {log}
                  </div>
                ))}
              </div>
            </section>
          </div>

          <SheetFooter className="border-t pt-6 mt-8 p-0">
            <button
              onClick={() => setShowInsights(false)}
              className="w-full h-10 rounded-md bg-primary text-primary-foreground text-sm font-medium hover:bg-primary/90 transition-colors"
            >
              Done Reviewing
            </button>
          </SheetFooter>
        </SheetContent>
      </Sheet>
    </div>
  );
}
