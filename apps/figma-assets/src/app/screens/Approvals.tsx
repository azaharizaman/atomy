import { useState } from "react";
import { useNavigate } from "react-router";
import {
  CheckCircle2, XCircle, Clock, AlertCircle, User, Calendar,
  DollarSign, FileText, MessageSquare, ArrowRight, Filter,
  Send, ThumbsUp, ThumbsDown, AlertTriangle, ChevronDown,
  Eye, Download, MoreHorizontal
} from "lucide-react";

interface ApprovalRequest {
  id: string;
  rfqId: string;
  rfqTitle: string;
  requestedBy: string;
  requestedAt: string;
  status: "Pending" | "Approved" | "Rejected" | "Escalated";
  amount: number;
  vendor: string;
  urgency: "Low" | "Medium" | "High" | "Critical";
  approvers: {
    name: string;
    role: string;
    status: "Pending" | "Approved" | "Rejected";
    timestamp?: string;
    comment?: string;
  }[];
  description: string;
  daysOpen: number;
}

const mockApprovals: ApprovalRequest[] = [
  {
    id: "APR-2024-001",
    rfqId: "RFQ-2024-001",
    rfqTitle: "Industrial Pumping Equipment — Q1 2024",
    requestedBy: "Sarah Chen",
    requestedAt: "2024-01-22 14:35",
    status: "Pending",
    amount: 165600,
    vendor: "Apex Industrial Solutions",
    urgency: "High",
    daysOpen: 2,
    description: "Award recommendation for industrial pump procurement. Vendor scored 94/100 on evaluation matrix.",
    approvers: [
      { name: "David Martinez", role: "Procurement Manager", status: "Approved", timestamp: "2024-01-22 15:12", comment: "Price is competitive. Approve." },
      { name: "Emily Wong", role: "Finance Director", status: "Pending" },
      { name: "James Thompson", role: "CFO", status: "Pending" },
    ],
  },
  {
    id: "APR-2024-002",
    rfqId: "RFQ-2024-005",
    rfqTitle: "IT Hardware Refresh 2024",
    requestedBy: "Michael Zhang",
    requestedAt: "2024-01-21 09:18",
    status: "Escalated",
    amount: 482000,
    vendor: "TechSource Partners",
    urgency: "Critical",
    daysOpen: 3,
    description: "Urgent IT hardware replacement required due to end-of-life equipment. Budget exceeds threshold requiring CFO approval.",
    approvers: [
      { name: "David Martinez", role: "Procurement Manager", status: "Approved", timestamp: "2024-01-21 10:45", comment: "Escalating to CFO for budget approval." },
      { name: "Emily Wong", role: "Finance Director", status: "Approved", timestamp: "2024-01-21 16:22", comment: "Budget impact reviewed. Recommend approval." },
      { name: "James Thompson", role: "CFO", status: "Pending" },
    ],
  },
  {
    id: "APR-2024-003",
    rfqId: "RFQ-2024-007",
    rfqTitle: "Preventive Maintenance Contracts",
    requestedBy: "Lisa Anderson",
    requestedAt: "2024-01-20 11:42",
    status: "Approved",
    amount: 128400,
    vendor: "Reliable Maintenance Co.",
    urgency: "Medium",
    daysOpen: 4,
    description: "Annual maintenance contract renewal with 5% discount from previous year.",
    approvers: [
      { name: "David Martinez", role: "Procurement Manager", status: "Approved", timestamp: "2024-01-20 14:15", comment: "Good pricing, approved." },
      { name: "Emily Wong", role: "Finance Director", status: "Approved", timestamp: "2024-01-21 09:30", comment: "Approved." },
      { name: "James Thompson", role: "CFO", status: "Approved", timestamp: "2024-01-21 11:05", comment: "Final approval granted." },
    ],
  },
  {
    id: "APR-2024-004",
    rfqId: "RFQ-2024-009",
    rfqTitle: "Security Systems Upgrade",
    requestedBy: "Robert Kim",
    requestedAt: "2024-01-19 16:28",
    status: "Rejected",
    amount: 215000,
    vendor: "SecureGuard Systems",
    urgency: "Low",
    daysOpen: 5,
    description: "Security camera and access control system modernization.",
    approvers: [
      { name: "David Martinez", role: "Procurement Manager", status: "Approved", timestamp: "2024-01-20 08:22", comment: "Specs look good." },
      { name: "Emily Wong", role: "Finance Director", status: "Rejected", timestamp: "2024-01-20 14:18", comment: "Budget not allocated for this fiscal year. Defer to Q3." },
      { name: "James Thompson", role: "CFO", status: "Pending" },
    ],
  },
];

const statusConfig: Record<string, { bg: string; color: string; icon: any }> = {
  Pending: { bg: "var(--app-warning-tint-10)", color: "var(--app-warning)", icon: Clock },
  Approved: { bg: "var(--app-success-tint-10)", color: "var(--app-success)", icon: CheckCircle2 },
  Rejected: { bg: "var(--app-danger-tint-10)", color: "var(--app-danger)", icon: XCircle },
  Escalated: { bg: "var(--app-purple-tint-10)", color: "var(--app-accent-purple)", icon: AlertCircle },
};

const urgencyConfig: Record<string, { color: string; bg: string }> = {
  Critical: { color: "var(--app-danger)", bg: "var(--app-danger-tint-10)" },
  High: { color: "var(--app-warning-soft)", bg: "var(--app-orange-tint-10)" },
  Medium: { color: "var(--app-warning)", bg: "var(--app-warning-tint-10)" },
  Low: { color: "var(--app-text-muted)", bg: "var(--app-slate-tint-10)" },
};

export function Approvals() {
  const navigate = useNavigate();
  const [statusFilter, setStatusFilter] = useState("Pending");
  const [selectedApproval, setSelectedApproval] = useState<ApprovalRequest | null>(null);
  const [comment, setComment] = useState("");

  const statuses = ["All", "Pending", "Approved", "Rejected", "Escalated"];
  const filtered = mockApprovals.filter((a) => statusFilter === "All" || a.status === statusFilter);
  const pendingOrEscalated = mockApprovals.filter((a) => a.status === "Pending" || a.status === "Escalated");
  const highRiskPending = pendingOrEscalated.filter((a) => a.urgency === "High" || a.urgency === "Critical");
  const stalePending = pendingOrEscalated.filter((a) => a.daysOpen >= 3);

  const averagePendingAge = pendingOrEscalated.length > 0
    ? Math.round((pendingOrEscalated.reduce((sum, approval) => sum + approval.daysOpen, 0) / pendingOrEscalated.length) * 10) / 10
    : 0;

  const decisionFeed = mockApprovals
    .filter((a) => a.status === "Approved" || a.status === "Rejected")
    .map((approval) => {
      const latestDecision = [...approval.approvers]
        .reverse()
        .find((approver) => approver.status !== "Pending" && approver.timestamp);

      return {
        id: approval.id,
        rfqId: approval.rfqId,
        title: approval.rfqTitle,
        status: approval.status,
        by: latestDecision?.name ?? "System",
        at: latestDecision?.timestamp ?? approval.requestedAt,
      };
    })
    .slice(0, 5);

  const reasonBreakdown = [
    {
      label: "Risk Escalation",
      count: pendingOrEscalated.filter((a) => a.urgency === "Critical" || a.status === "Escalated").length,
      color: "var(--app-danger)",
      bg: "var(--app-danger-tint-10)",
    },
    {
      label: "Budget Threshold",
      count: pendingOrEscalated.filter((a) => a.amount >= 200000).length,
      color: "var(--app-warning)",
      bg: "var(--app-warning-tint-10)",
    },
    {
      label: "Standard Review",
      count: pendingOrEscalated.filter((a) => a.amount < 200000 && a.urgency !== "Critical").length,
      color: "var(--app-brand-500)",
      bg: "var(--app-brand-tint-8)",
    },
  ].filter((reason) => reason.count > 0);

  const stats = {
    pending: mockApprovals.filter(a => a.status === "Pending").length,
    escalated: mockApprovals.filter(a => a.status === "Escalated").length,
  };

  const handleAction = (action: "approve" | "reject") => {
    setSelectedApproval(null);
    setComment("");
  };

  return (
    <div style={{ display: "flex", height: "100%", minHeight: "calc(100vh - 88px)", fontFamily: "'Inter', system-ui, sans-serif", background: "var(--app-bg-canvas)" }}>
      {/* Left: Approval Queue */}
      <div className="flex-shrink-0 flex flex-col border-r" style={{ width: 380, background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        {/* Header */}
        <div className="px-4 py-4 border-b" style={{ borderColor: "var(--app-border-strong)" }}>
          <h2 style={{ fontSize: 16, fontWeight: 700, color: "var(--app-text-strong)", marginBottom: 8 }}>Approval Workflow</h2>
          
          {/* Stats */}
          <div className="grid grid-cols-2 gap-2 mb-4">
            {[
              { label: "Pending", count: stats.pending, color: "var(--app-warning)", bg: "var(--app-warning-tint-8)" },
              { label: "Escalated", count: stats.escalated, color: "var(--app-accent-purple)", bg: "var(--app-purple-tint-8)" },
            ].map((stat) => (
              <div key={stat.label} className="rounded-lg border p-2" style={{ background: stat.bg, borderColor: "transparent" }}>
                <div style={{ fontSize: 20, fontWeight: 800, color: stat.color, letterSpacing: "-0.02em", lineHeight: 1 }}>{stat.count}</div>
                <div style={{ fontSize: 11, color: "var(--app-text-muted)", marginTop: 2 }}>{stat.label}</div>
              </div>
            ))}
          </div>

          {/* Filter */}
          <div className="flex items-center gap-1 p-1 rounded-lg" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
            {statuses.map((s) => (
              <button
                key={s}
                onClick={() => setStatusFilter(s)}
                className="flex-1 rounded transition-all"
                style={{ fontSize: 11, padding: "4px 6px", background: statusFilter === s ? "var(--app-bg-surface)" : "transparent", color: statusFilter === s ? "var(--app-text-main)" : "var(--app-text-muted)", fontWeight: statusFilter === s ? 500 : 400 }}
              >
                {s}
              </button>
            ))}
          </div>
        </div>

        {/* Queue List */}
        <div className="flex-1 overflow-y-auto py-2" style={{ scrollbarWidth: "none" }}>
          {filtered.map((approval) => {
            const sc = statusConfig[approval.status];
            const uc = urgencyConfig[approval.urgency];
            const StatusIcon = sc.icon;
            const isSelected = selectedApproval?.id === approval.id;

            return (
              <div
                key={approval.id}
                className="mx-2 mb-1 rounded-lg p-3 cursor-pointer border transition-all"
                style={{
                  background: isSelected ? "var(--app-brand-tint-8)" : "transparent",
                  borderColor: isSelected ? "var(--app-brand-tint-20)" : "transparent",
                }}
                onClick={() => setSelectedApproval(approval)}
                onMouseEnter={(e) => { if (!isSelected) (e.currentTarget as HTMLDivElement).style.background = "var(--app-hover-soft)"; }}
                onMouseLeave={(e) => { if (!isSelected) (e.currentTarget as HTMLDivElement).style.background = "transparent"; }}
              >
                <div className="flex items-start justify-between mb-2">
                  <div className="flex items-center gap-1.5">
                    <span style={{ fontSize: 11, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 500 }}>{approval.id}</span>
                    <span className="rounded px-1.5 py-0.5" style={{ fontSize: 9, fontWeight: 700, background: uc.bg, color: uc.color }}>
                      {approval.urgency}
                    </span>
                  </div>
                  <span className="flex items-center gap-1 rounded-full px-2 py-0.5" style={{ fontSize: 10, fontWeight: 600, background: sc.bg, color: sc.color }}>
                    <StatusIcon size={9} />
                    {approval.status}
                  </span>
                </div>
                <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 2, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>
                  {approval.rfqTitle}
                </div>
                <div className="flex items-center gap-3 mb-2" style={{ fontSize: 11, color: "var(--app-text-muted)" }}>
                  <span>{approval.vendor}</span>
                  <span>·</span>
                  <span style={{ fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-400)" }}>${(approval.amount / 1000).toFixed(0)}K</span>
                </div>
                <div className="flex items-center justify-between">
                  <span style={{ fontSize: 10, color: "var(--app-text-subtle)" }}>By {approval.requestedBy}</span>
                  <span style={{ fontSize: 10, color: "var(--app-text-faint)" }}>{approval.daysOpen}d ago</span>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Right: Approval Detail */}
      <div className="flex-1 overflow-auto" style={{ background: "var(--app-bg-canvas)" }}>
        {selectedApproval ? (
          <>
            {/* Header */}
            <div className="px-6 py-4 border-b flex items-start justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div className="flex-1">
                <div className="flex items-center gap-3 mb-2">
                  <span style={{ fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 500 }}>
                    {selectedApproval.id}
                  </span>
                  <span
                    className="flex items-center gap-1.5 rounded-full px-2.5 py-1"
                    style={{
                      background: statusConfig[selectedApproval.status].bg,
                      color: statusConfig[selectedApproval.status].color,
                      border: `1px solid ${statusConfig[selectedApproval.status].color}33`,
                      fontSize: 11,
                      fontWeight: 700,
                    }}
                  >
                    {(() => {
                      const StatusIcon = statusConfig[selectedApproval.status].icon;
                      return <StatusIcon size={11} />;
                    })()}
                    {selectedApproval.status}
                  </span>
                  <span className="rounded px-2 py-0.5" style={{ fontSize: 10, fontWeight: 700, background: urgencyConfig[selectedApproval.urgency].bg, color: urgencyConfig[selectedApproval.urgency].color }}>
                    {selectedApproval.urgency} URGENCY
                  </span>
                </div>
                <h2 style={{ fontSize: 18, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 3 }}>{selectedApproval.rfqTitle}</h2>
                <div className="flex items-center gap-4" style={{ fontSize: 12, color: "var(--app-text-muted)" }}>
                  <span className="flex items-center gap-1.5">
                    <User size={12} />
                    Requested by {selectedApproval.requestedBy}
                  </span>
                  <span className="flex items-center gap-1.5">
                    <Calendar size={12} />
                    {selectedApproval.requestedAt}
                  </span>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <button
                  className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
                  style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
                >
                  <Download size={13} /> Export
                </button>
                <button
                  onClick={() => navigate(`/rfqs/${selectedApproval.rfqId}`)}
                  className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
                  style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
                >
                  <Eye size={13} /> View RFQ
                </button>
              </div>
            </div>

            <div className="p-6 space-y-5">
              {/* Key Details */}
              <div className="grid grid-cols-3 gap-4">
                <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2 mb-2">
                    <DollarSign size={13} style={{ color: "var(--app-text-subtle)" }} />
                    <span style={{ fontSize: 11, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", textTransform: "uppercase" }}>Award Amount</span>
                  </div>
                  <div style={{ fontSize: 24, fontWeight: 800, color: "var(--app-text-strong)", fontFamily: "'JetBrains Mono', monospace", letterSpacing: "-0.02em" }}>
                    ${selectedApproval.amount.toLocaleString()}
                  </div>
                </div>

                <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2 mb-2">
                    <User size={13} style={{ color: "var(--app-text-subtle)" }} />
                    <span style={{ fontSize: 11, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", textTransform: "uppercase" }}>Recommended Vendor</span>
                  </div>
                  <div style={{ fontSize: 15, fontWeight: 700, color: "var(--app-text-main)" }}>{selectedApproval.vendor}</div>
                  <button style={{ fontSize: 12, color: "var(--app-brand-500)", marginTop: 4 }} className="hover:opacity-80 transition-opacity">
                    View vendor profile →
                  </button>
                </div>

                <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2 mb-2">
                    <FileText size={13} style={{ color: "var(--app-text-subtle)" }} />
                    <span style={{ fontSize: 11, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", textTransform: "uppercase" }}>RFQ Reference</span>
                  </div>
                  <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-brand-400)", fontFamily: "'JetBrains Mono', monospace", marginBottom: 2 }}>
                    {selectedApproval.rfqId}
                  </div>
                  <button
                    onClick={() => navigate(`/rfqs/${selectedApproval.rfqId}`)}
                    style={{ fontSize: 12, color: "var(--app-brand-500)" }}
                    className="hover:opacity-80 transition-opacity"
                  >
                    View full RFQ →
                  </button>
                </div>
              </div>

              {/* Description */}
              <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>
                  Request Details
                </h3>
                <p style={{ fontSize: 13, color: "var(--app-text-main)", lineHeight: 1.6 }}>{selectedApproval.description}</p>
              </div>

              {/* Approval Chain */}
              <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 16 }}>
                  Approval Chain
                </h3>
                <div className="relative pl-8">
                  <div className="absolute left-3 top-2 bottom-2 w-px" style={{ background: "var(--app-border-strong)" }} />
                  {selectedApproval.approvers.map((approver, i) => {
                    const isDone = approver.status !== "Pending";
                    const isApproved = approver.status === "Approved";
                    const isRejected = approver.status === "Rejected";

                    return (
                      <div key={i} className="relative mb-6 last:mb-0">
                        <div
                          className="absolute -left-[21px] flex items-center justify-center rounded-full"
                          style={{
                            width: 24,
                            height: 24,
                            background: "var(--app-bg-surface)",
                            border: `2px solid ${isDone ? (isApproved ? "var(--app-success)" : "var(--app-danger)") : "var(--app-text-subtle)"}`,
                          }}
                        >
                          {isApproved && <CheckCircle2 size={12} style={{ color: "var(--app-success)" }} />}
                          {isRejected && <XCircle size={12} style={{ color: "var(--app-danger)" }} />}
                          {!isDone && <Clock size={12} style={{ color: "var(--app-text-subtle)" }} />}
                        </div>

                        <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                          <div className="flex items-center justify-between mb-2">
                            <div>
                              <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)" }}>{approver.name}</div>
                              <div style={{ fontSize: 11, color: "var(--app-text-muted)", marginTop: 1 }}>{approver.role}</div>
                            </div>
                            <div className="flex items-center gap-1.5 rounded-full px-2.5 py-0.5" style={{ fontSize: 10, fontWeight: 600, background: isDone ? (isApproved ? "var(--app-success-tint-10)" : "var(--app-danger-tint-10)") : "var(--app-slate-tint-10)", color: isDone ? (isApproved ? "var(--app-success)" : "var(--app-danger)") : "var(--app-text-muted)" }}>
                              {approver.status}
                            </div>
                          </div>
                          {approver.timestamp && (
                            <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginBottom: approver.comment ? 6 : 0 }}>
                              {approver.timestamp}
                            </div>
                          )}
                          {approver.comment && (
                            <div className="flex items-start gap-2 rounded p-2 mt-2" style={{ background: "var(--app-bg-surface)" }}>
                              <MessageSquare size={12} style={{ color: "var(--app-text-subtle)", flexShrink: 0, marginTop: 2 }} />
                              <div style={{ fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.5 }}>{approver.comment}</div>
                            </div>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Action Panel */}
              {selectedApproval.status === "Pending" && (
                <div className="rounded-xl border p-5" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-brand-tint-20)" }}>
                  <h3 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 12 }}>Your Action Required</h3>
                  <div className="mb-4">
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 6 }}>
                      COMMENT (OPTIONAL)
                    </label>
                    <textarea
                      value={comment}
                      onChange={(e) => setComment(e.target.value)}
                      rows={3}
                      placeholder="Add a comment or reason for your decision…"
                      style={{ width: "100%", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "10px 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box", resize: "none", lineHeight: 1.6 }}
                      onFocus={(e) => { e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                      onBlur={(e) => { e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
                    />
                  </div>
                  <div className="flex items-center gap-3">
                    <button
                      onClick={() => handleAction("reject")}
                      className="flex items-center gap-2 rounded-lg px-4 py-2.5 transition-opacity hover:opacity-90"
                      style={{ fontSize: 13, fontWeight: 600, background: "var(--app-danger-tint-10)", color: "var(--app-danger)", border: "1px solid var(--app-danger-tint-20)" }}
                    >
                      <ThumbsDown size={14} /> Reject
                    </button>
                    <button
                      onClick={() => handleAction("approve")}
                      className="flex-1 flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 transition-opacity hover:opacity-90"
                      style={{ fontSize: 13, fontWeight: 600, background: "var(--app-success)", color: "white" }}
                    >
                      <ThumbsUp size={14} /> Approve Request
                    </button>
                  </div>
                </div>
              )}
            </div>
          </>
        ) : (
          <div className="p-6" style={{ color: "var(--app-text-subtle)" }}>
            <div className="rounded-xl border p-5 mb-5" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div className="flex items-start justify-between gap-4">
                <div>
                  <h2 style={{ fontSize: 19, fontWeight: 700, color: "var(--app-text-strong)", marginBottom: 4 }}>Approval Control Tower</h2>
                  <p style={{ fontSize: 13, color: "var(--app-text-muted)", lineHeight: 1.6 }}>
                    Use this space to triage urgent items while no request is selected.
                  </p>
                </div>
                <button
                  onClick={() => {
                    setStatusFilter("Pending");
                    if (pendingOrEscalated.length > 0) setSelectedApproval(pendingOrEscalated[0]);
                  }}
                  className="flex items-center gap-2 rounded-lg px-3 py-2 transition-opacity hover:opacity-90"
                  style={{ fontSize: 12, fontWeight: 600, background: "var(--app-brand-600)", color: "white", whiteSpace: "nowrap" }}
                >
                  Open Next Approval
                  <ArrowRight size={12} />
                </button>
              </div>
            </div>

            <div className="grid grid-cols-4 gap-3 mb-5">
              {[
                { label: "Pending Queue", value: pendingOrEscalated.length, hint: "Need review", tone: "var(--app-warning)", bg: "var(--app-warning-tint-10)" },
                { label: "High Risk", value: highRiskPending.length, hint: "Immediate attention", tone: "var(--app-danger)", bg: "var(--app-danger-tint-10)" },
                { label: "Stale (>3d)", value: stalePending.length, hint: "Escalation candidate", tone: "var(--app-accent-purple)", bg: "var(--app-purple-tint-10)" },
                { label: "Avg Queue Age", value: `${averagePendingAge}d`, hint: "Pending/Escalated", tone: "var(--app-brand-500)", bg: "var(--app-brand-tint-8)" },
              ].map((card) => (
                <div key={card.label} className="rounded-xl border p-4" style={{ background: card.bg, borderColor: "transparent" }}>
                  <div style={{ fontSize: 24, fontWeight: 800, color: card.tone, letterSpacing: "-0.02em", lineHeight: 1 }}>{card.value}</div>
                  <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-main)", marginTop: 6 }}>{card.label}</div>
                  <div style={{ fontSize: 10, color: "var(--app-text-muted)", marginTop: 2 }}>{card.hint}</div>
                </div>
              ))}
            </div>

            <div className="grid grid-cols-2 gap-5 mb-5">
              <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <div className="flex items-center justify-between mb-3">
                  <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase" }}>
                    Action Required Now
                  </h3>
                  <span className="rounded-full px-2 py-0.5" style={{ fontSize: 10, fontWeight: 700, color: "var(--app-danger)", background: "var(--app-danger-tint-10)" }}>
                    {highRiskPending.length} urgent
                  </span>
                </div>
                <div className="space-y-2">
                  {(highRiskPending.length > 0 ? highRiskPending : pendingOrEscalated).slice(0, 5).map((approval) => (
                    <button
                      key={approval.id}
                      onClick={() => setSelectedApproval(approval)}
                      className="w-full text-left rounded-lg border p-3 transition-colors"
                      style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}
                      onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-hover-soft)"; }}
                      onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-bg-elevated)"; }}
                    >
                      <div className="flex items-center justify-between mb-1">
                        <span style={{ fontSize: 11, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 600 }}>{approval.id}</span>
                        <span style={{ fontSize: 10, color: "var(--app-text-faint)" }}>{approval.daysOpen}d open</span>
                      </div>
                      <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 2, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>
                        {approval.rfqTitle}
                      </div>
                      <div className="flex items-center gap-2" style={{ fontSize: 11, color: "var(--app-text-muted)" }}>
                        <span>{approval.vendor}</span>
                        <span>·</span>
                        <span style={{ color: "var(--app-danger)", fontWeight: 600 }}>{approval.urgency}</span>
                      </div>
                    </button>
                  ))}
                </div>
              </div>

              <div className="space-y-5">
                <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>
                    Why Items Are Pending
                  </h3>
                  <div className="space-y-2">
                    {reasonBreakdown.map((reason) => (
                      <div key={reason.label} className="flex items-center justify-between rounded-lg border px-3 py-2" style={{ background: reason.bg, borderColor: "transparent" }}>
                        <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{reason.label}</span>
                        <span style={{ fontSize: 12, fontWeight: 700, color: reason.color }}>{reason.count}</span>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>
                    Recent Decisions
                  </h3>
                  <div className="space-y-2">
                    {decisionFeed.map((item) => (
                      <div key={item.id} className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                        <div className="flex items-center justify-between mb-1">
                          <span style={{ fontSize: 11, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)" }}>{item.id}</span>
                          <span className="rounded px-1.5 py-0.5" style={{ fontSize: 10, fontWeight: 700, color: item.status === "Approved" ? "var(--app-success)" : "var(--app-danger)", background: item.status === "Approved" ? "var(--app-success-tint-10)" : "var(--app-danger-tint-10)" }}>
                            {item.status}
                          </span>
                        </div>
                        <div style={{ fontSize: 12, color: "var(--app-text-main)", marginBottom: 3 }}>{item.rfqId}</div>
                        <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>{item.by} • {item.at}</div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 10 }}>
                Quick Filters
              </h3>
              <div className="flex items-center gap-2 flex-wrap">
                <button
                  onClick={() => {
                    setStatusFilter("Escalated");
                    if (stats.escalated > 0) {
                      const firstEscalated = mockApprovals.find((a) => a.status === "Escalated");
                      if (firstEscalated) setSelectedApproval(firstEscalated);
                    }
                  }}
                  className="rounded-lg px-3 py-1.5 transition-colors"
                  style={{ fontSize: 12, border: "1px solid var(--app-danger-tint-20)", color: "var(--app-danger)", background: "var(--app-danger-tint-10)" }}
                >
                  Escalated Now
                </button>
                <button
                  onClick={() => {
                    setStatusFilter("Pending");
                    if (stalePending.length > 0) setSelectedApproval(stalePending[0]);
                  }}
                  className="rounded-lg px-3 py-1.5 transition-colors"
                  style={{ fontSize: 12, border: "1px solid var(--app-warning-tint-20)", color: "var(--app-warning)", background: "var(--app-warning-tint-10)" }}
                >
                  Aging Queue
                </button>
                <button
                  onClick={() => setStatusFilter("Approved")}
                  className="rounded-lg px-3 py-1.5 transition-colors"
                  style={{ fontSize: 12, border: "1px solid var(--app-success-tint-20)", color: "var(--app-success)", background: "var(--app-success-tint-10)" }}
                >
                  Recent Approvals
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
