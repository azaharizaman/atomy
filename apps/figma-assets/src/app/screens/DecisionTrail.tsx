import { useState } from "react";
import { useNavigate } from "react-router";
import {
  FileText, User, Calendar, Clock, Filter, Search, Download,
  CheckCircle2, XCircle, AlertTriangle, Edit, Send, Eye,
  MessageSquare, DollarSign, Shield, Award, GitCompareArrows,
  ChevronRight, ExternalLink, Info, GitBranch
} from "lucide-react";

interface DecisionEvent {
  id: string;
  timestamp: string;
  actor: string;
  actorRole: string;
  action: string;
  category: "RFQ" | "Approval" | "Comparison" | "Award" | "Risk" | "System";
  rfqId: string;
  rfqTitle: string;
  details: string;
  impact?: string;
  metadata?: Record<string, any>;
}

const mockTrail: DecisionEvent[] = [
  {
    id: "DT-2024-0156",
    timestamp: "2024-01-23 14:35:22",
    actor: "Sarah Chen",
    actorRole: "Senior Procurement Manager",
    action: "Sent RFQ for Approval",
    category: "Approval",
    rfqId: "RFQ-2024-001",
    rfqTitle: "Industrial Pumping Equipment — Q1 2024",
    details: "Award recommendation for Apex Industrial Solutions ($165,600) sent to approval chain: David Martinez → Emily Wong → James Thompson",
    impact: "Financial commitment pending approval",
    metadata: { amount: 165600, vendor: "Apex Industrial Solutions", approvers: 3 },
  },
  {
    id: "DT-2024-0155",
    timestamp: "2024-01-23 11:18:45",
    actor: "AI Comparison Engine",
    actorRole: "System",
    action: "Completed Quote Comparison",
    category: "Comparison",
    rfqId: "RFQ-2024-001",
    rfqTitle: "Industrial Pumping Equipment — Q1 2024",
    details: "Automated comparison of 3 vendor quotes completed. Apex Industrial Solutions scored 94/100, ranked #1 based on weighted criteria.",
    metadata: { vendorsCompared: 3, winnerScore: 94, savingsVsTarget: 12.4 },
  },
  {
    id: "DT-2024-0154",
    timestamp: "2024-01-22 16:42:11",
    actor: "Risk AI Engine",
    actorRole: "System",
    action: "Risk Assessment Completed",
    category: "Risk",
    rfqId: "RFQ-2024-005",
    rfqTitle: "IT Hardware Refresh 2024",
    details: "TechSource Partners assessed as HIGH RISK (score: 72/100). Critical flags: Financial instability, delivery concerns.",
    impact: "Recommended mitigation: Performance bond, staged payments",
    metadata: { riskScore: 72, riskLevel: "High", criticalFlags: 2 },
  },
  {
    id: "DT-2024-0153",
    timestamp: "2024-01-22 14:55:08",
    actor: "Emily Wong",
    actorRole: "Finance Director",
    action: "Approved Award Request",
    category: "Approval",
    rfqId: "RFQ-2024-007",
    rfqTitle: "Preventive Maintenance Contracts",
    details: "Approval granted for Reliable Maintenance Co. award ($128,400). Comment: 'Approved. Good pricing compared to previous year.'",
    metadata: { amount: 128400, vendor: "Reliable Maintenance Co.", decision: "Approved" },
  },
  {
    id: "DT-2024-0152",
    timestamp: "2024-01-22 09:32:56",
    actor: "Michael Zhang",
    actorRole: "IT Manager",
    action: "Created RFQ",
    category: "RFQ",
    rfqId: "RFQ-2024-013",
    rfqTitle: "Network Infrastructure Upgrade",
    details: "New RFQ created for network switch and firewall replacement. Budget: $325,000. Category: IT Hardware. Priority: High.",
    metadata: { budget: 325000, category: "IT Hardware", priority: "High" },
  },
  {
    id: "DT-2024-0151",
    timestamp: "2024-01-21 17:08:33",
    actor: "Sarah Chen",
    actorRole: "Senior Procurement Manager",
    action: "Awarded RFQ",
    category: "Award",
    rfqId: "RFQ-2024-007",
    rfqTitle: "Preventive Maintenance Contracts",
    details: "RFQ awarded to Reliable Maintenance Co. following full approval chain. Contract value: $128,400. Expected savings: 5% vs previous year.",
    impact: "Annual commitment executed",
    metadata: { amount: 128400, vendor: "Reliable Maintenance Co.", savings: 5 },
  },
  {
    id: "DT-2024-0150",
    timestamp: "2024-01-21 15:44:19",
    actor: "David Martinez",
    actorRole: "Procurement Manager",
    action: "Rejected Vendor Quote",
    category: "Comparison",
    rfqId: "RFQ-2024-009",
    rfqTitle: "Security Systems Upgrade",
    details: "Quote from SecureGuard Systems rejected due to non-compliance with technical specifications. Vendor notified.",
    impact: "Vendor removed from consideration",
    metadata: { vendor: "SecureGuard Systems", reason: "Non-compliance" },
  },
  {
    id: "DT-2024-0149",
    timestamp: "2024-01-21 11:22:07",
    actor: "Quote Intake AI",
    actorRole: "System",
    action: "Quote Parsed and Accepted",
    category: "System",
    rfqId: "RFQ-2024-001",
    rfqTitle: "Industrial Pumping Equipment — Q1 2024",
    details: "GlobalPump Technologies quote automatically parsed. Confidence: 92%. 5 line items extracted. Total: $152,400.",
    metadata: { vendor: "GlobalPump Technologies", confidence: 92, lineItems: 5, total: 152400 },
  },
];

const categoryConfig: Record<string, { color: string; bg: string; icon: any }> = {
  RFQ: { color: "var(--app-brand-500)", bg: "var(--app-brand-tint-10)", icon: FileText },
  Approval: { color: "var(--app-success)", bg: "var(--app-success-tint-10)", icon: CheckCircle2 },
  Comparison: { color: "var(--app-accent-purple)", bg: "var(--app-purple-tint-10)", icon: GitCompareArrows },
  Award: { color: "var(--app-warning)", bg: "var(--app-warning-tint-10)", icon: Award },
  Risk: { color: "var(--app-danger)", bg: "var(--app-danger-tint-10)", icon: Shield },
  System: { color: "var(--app-text-muted)", bg: "var(--app-slate-tint-10)", icon: Info },
};

export function DecisionTrail() {
  const navigate = useNavigate();
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [search, setSearch] = useState("");
  const [selectedEvent, setSelectedEvent] = useState<DecisionEvent | null>(null);

  const categories = ["All", "RFQ", "Approval", "Comparison", "Award", "Risk", "System"];
  
  const filtered = mockTrail.filter((event) => {
    const matchCategory = categoryFilter === "All" || event.category === categoryFilter;
    const matchSearch =
      search === "" ||
      event.action.toLowerCase().includes(search.toLowerCase()) ||
      event.actor.toLowerCase().includes(search.toLowerCase()) ||
      event.rfqId.toLowerCase().includes(search.toLowerCase()) ||
      event.details.toLowerCase().includes(search.toLowerCase());
    return matchCategory && matchSearch;
  });

  return (
    <div style={{ display: "flex", height: "100%", minHeight: "calc(100vh - 88px)", fontFamily: "'Inter', system-ui, sans-serif", background: "var(--app-bg-canvas)" }}>
      {/* Left: Event List */}
      <div className="flex-shrink-0 flex flex-col border-r" style={{ width: 420, background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        {/* Header */}
        <div className="px-4 py-4 border-b" style={{ borderColor: "var(--app-border-strong)" }}>
          <div className="flex items-center gap-2 mb-4">
            <GitBranch size={18} style={{ color: "var(--app-brand-500)" }} />
            <h2 style={{ fontSize: 16, fontWeight: 700, color: "var(--app-text-strong)" }}>Decision Trail</h2>
          </div>
          <p style={{ fontSize: 12, color: "var(--app-text-muted)", marginBottom: 12 }}>
            Complete audit log of procurement decisions and system events
          </p>

          {/* Search */}
          <div className="flex items-center gap-2 rounded-lg px-3 mb-3" style={{ height: 36, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
            <Search size={13} style={{ color: "var(--app-text-subtle)" }} />
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search events, actors, RFQs…"
              style={{ background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-main)", flex: 1 }}
            />
          </div>

          {/* Category Filter */}
          <div className="flex flex-wrap gap-1.5">
            {categories.map((cat) => {
              const config = categoryConfig[cat] || categoryConfig.System;
              const Icon = config.icon;
              const isActive = categoryFilter === cat;
              return (
                <button
                  key={cat}
                  onClick={() => setCategoryFilter(cat)}
                  className="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 transition-all"
                  style={{
                    fontSize: 11,
                    background: isActive ? config.bg : "transparent",
                    color: isActive ? config.color : "var(--app-text-muted)",
                    border: `1px solid ${isActive ? config.color + "44" : "var(--app-border-strong)"}`,
                    fontWeight: isActive ? 600 : 400,
                  }}
                >
                  {cat !== "All" && <Icon size={10} />}
                  {cat}
                </button>
              );
            })}
          </div>
        </div>

        {/* Event List */}
        <div className="flex-1 overflow-y-auto" style={{ scrollbarWidth: "none" }}>
          {filtered.length === 0 ? (
            <div className="flex items-center justify-center h-32" style={{ color: "var(--app-text-subtle)", fontSize: 13 }}>
              No events match your filters
            </div>
          ) : (
            <div className="relative px-4 py-3">
              <div className="absolute left-8 top-3 bottom-3 w-px" style={{ background: "var(--app-border-strong)" }} />
              {filtered.map((event, i) => {
                const config = categoryConfig[event.category];
                const Icon = config.icon;
                const isSelected = selectedEvent?.id === event.id;

                return (
                  <div
                    key={event.id}
                    className="relative mb-4 cursor-pointer"
                    onClick={() => setSelectedEvent(event)}
                  >
                    <div
                      className="absolute -left-[12px] flex items-center justify-center rounded-full"
                      style={{
                        width: 20,
                        height: 20,
                        background: "var(--app-bg-surface)",
                        border: `2px solid ${config.color}`,
                        zIndex: 1,
                      }}
                    >
                      <Icon size={10} style={{ color: config.color }} />
                    </div>

                    <div
                      className="rounded-lg border p-3 ml-6 transition-all"
                      style={{
                        background: isSelected ? "var(--app-brand-tint-8)" : "var(--app-bg-elevated)",
                        borderColor: isSelected ? "var(--app-brand-tint-20)" : "var(--app-border-strong)",
                      }}
                      onMouseEnter={(e) => {
                        if (!isSelected) (e.currentTarget as HTMLDivElement).style.background = "var(--app-hover-soft)";
                      }}
                      onMouseLeave={(e) => {
                        if (!isSelected) (e.currentTarget as HTMLDivElement).style.background = "var(--app-bg-elevated)";
                      }}
                    >
                      <div className="flex items-start justify-between mb-1.5">
                        <div className="flex items-center gap-1.5">
                          <span
                            className="rounded px-1.5 py-0.5"
                            style={{
                              fontSize: 9,
                              fontWeight: 700,
                              background: config.bg,
                              color: config.color,
                              textTransform: "uppercase",
                              letterSpacing: "0.05em",
                            }}
                          >
                            {event.category}
                          </span>
                          <span style={{ fontSize: 10, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-text-subtle)" }}>
                            {event.id}
                          </span>
                        </div>
                        <span style={{ fontSize: 10, color: "var(--app-text-faint)" }}>{event.timestamp.split(" ")[1]}</span>
                      </div>

                      <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 2 }}>
                        {event.action}
                      </div>

                      <div className="flex items-center gap-2 mb-2" style={{ fontSize: 11, color: "var(--app-text-muted)" }}>
                        <User size={10} />
                        <span>{event.actor}</span>
                        <span>·</span>
                        <span style={{ fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-400)", fontSize: 10 }}>
                          {event.rfqId}
                        </span>
                      </div>

                      <p
                        style={{
                          fontSize: 12,
                          color: "var(--app-text-subtle)",
                          lineHeight: 1.5,
                          display: "-webkit-box",
                          WebkitLineClamp: 2,
                          WebkitBoxOrient: "vertical",
                          overflow: "hidden",
                        }}
                      >
                        {event.details}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Export Button */}
        <div className="p-4 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
          <button
            className="w-full flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 transition-colors"
            style={{ fontSize: 13, fontWeight: 500, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)";
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)";
            }}
          >
            <Download size={14} /> Export Audit Log
          </button>
        </div>
      </div>

      {/* Right: Event Detail */}
      <div className="flex-1 overflow-auto" style={{ background: "var(--app-bg-canvas)" }}>
        {selectedEvent ? (
          <>
            {/* Header */}
            <div className="px-6 py-4 border-b" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div className="flex items-center gap-3 mb-2">
                <span style={{ fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 500 }}>
                  {selectedEvent.id}
                </span>
                <span
                  className="flex items-center gap-1.5 rounded-full px-2.5 py-1"
                  style={{
                    fontSize: 10,
                    fontWeight: 700,
                    background: categoryConfig[selectedEvent.category].bg,
                    color: categoryConfig[selectedEvent.category].color,
                    border: `1px solid ${categoryConfig[selectedEvent.category].color}44`,
                    textTransform: "uppercase",
                    letterSpacing: "0.05em",
                  }}
                >
                  {(() => {
                    const CategoryIcon = categoryConfig[selectedEvent.category].icon;
                    return <CategoryIcon size={10} />;
                  })()}
                  {selectedEvent.category}
                </span>
              </div>
              <h2 style={{ fontSize: 18, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 3 }}>
                {selectedEvent.action}
              </h2>
              <div className="flex items-center gap-4" style={{ fontSize: 12, color: "var(--app-text-muted)" }}>
                <span className="flex items-center gap-1.5">
                  <Calendar size={12} />
                  {selectedEvent.timestamp}
                </span>
                <span className="flex items-center gap-1.5">
                  <User size={12} />
                  {selectedEvent.actor} ({selectedEvent.actorRole})
                </span>
              </div>
            </div>

            <div className="p-6 space-y-5">
              {/* Context */}
              <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <div className="flex items-center justify-between mb-3">
                  <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase" }}>
                    Related RFQ
                  </h3>
                  <button
                    onClick={() => navigate(`/rfqs/${selectedEvent.rfqId}`)}
                    className="flex items-center gap-1.5 transition-opacity hover:opacity-80"
                    style={{ fontSize: 12, color: "var(--app-brand-500)" }}
                  >
                    View RFQ <ExternalLink size={11} />
                  </button>
                </div>
                <div className="flex items-start gap-3">
                  <FileText size={16} style={{ color: "var(--app-brand-500)", flexShrink: 0, marginTop: 2 }} />
                  <div>
                    <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 2 }}>
                      {selectedEvent.rfqTitle}
                    </div>
                    <div style={{ fontSize: 11, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-text-muted)" }}>
                      {selectedEvent.rfqId}
                    </div>
                  </div>
                </div>
              </div>

              {/* Event Details */}
              <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>
                  Event Details
                </h3>
                <p style={{ fontSize: 13, color: "var(--app-text-subtle)", lineHeight: 1.7, marginBottom: 16 }}>
                  {selectedEvent.details}
                </p>

                {selectedEvent.impact && (
                  <div className="flex items-start gap-2 rounded-lg p-3" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
                    <AlertTriangle size={14} style={{ color: "var(--app-warning)", flexShrink: 0, marginTop: 1 }} />
                    <div>
                      <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-warning)", textTransform: "uppercase", letterSpacing: "0.05em", marginBottom: 4 }}>
                        Impact
                      </div>
                      <div style={{ fontSize: 12, color: "var(--app-text-subtle)", lineHeight: 1.5 }}>{selectedEvent.impact}</div>
                    </div>
                  </div>
                )}
              </div>

              {/* Metadata */}
              {selectedEvent.metadata && Object.keys(selectedEvent.metadata).length > 0 && (
                <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>
                    Additional Metadata
                  </h3>
                  <div className="grid grid-cols-2 gap-3">
                    {Object.entries(selectedEvent.metadata).map(([key, value]) => (
                      <div key={key} className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                        <div style={{ fontSize: 10, fontWeight: 500, color: "var(--app-text-faint)", textTransform: "uppercase", letterSpacing: "0.05em", marginBottom: 4 }}>
                          {key.replace(/([A-Z])/g, " $1").trim()}
                        </div>
                        <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", fontFamily: typeof value === "number" ? "'JetBrains Mono', monospace" : "inherit" }}>
                          {typeof value === "number" && key.toLowerCase().includes("amount")
                            ? `$${value.toLocaleString()}`
                            : typeof value === "number" && key.toLowerCase().includes("score")
                            ? `${value}/100`
                            : String(value)}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Actor Info */}
              <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>
                  Actor Information
                </h3>
                <div className="flex items-center gap-3">
                  <div className="flex items-center justify-center rounded-full" style={{ width: 40, height: 40, background: "var(--app-bg-elevated)" }}>
                    <User size={18} style={{ color: "var(--app-text-subtle)" }} />
                  </div>
                  <div>
                    <div style={{ fontSize: 14, fontWeight: 600, color: "var(--app-text-strong)" }}>{selectedEvent.actor}</div>
                    <div style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{selectedEvent.actorRole}</div>
                  </div>
                </div>
              </div>
            </div>
          </>
        ) : (
          <div className="flex items-center justify-center h-full" style={{ color: "var(--app-text-subtle)" }}>
            <div className="text-center">
              <FileText size={48} style={{ color: "var(--app-border-strong)", margin: "0 auto 16px" }} />
              <p style={{ fontSize: 14 }}>Select an event to view details</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
