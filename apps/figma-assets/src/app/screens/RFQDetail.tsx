import { useState } from "react";
import { useNavigate, useParams } from "react-router";
import {
  ArrowLeft, Users, FileText, Activity, GitCompareArrows, Send,
  Clock, CalendarDays, DollarSign, Tag, AlertTriangle, Check,
  Mail, Bell, Download, ExternalLink, MoreHorizontal, Plus,
  CheckCircle2, XCircle, Circle, ArrowRight, Zap
} from "lucide-react";
import { rfqs, vendors, activityTimeline } from "../data/mockData";

const statusConfig: Record<string, { bg: string; color: string; dot: string }> = {
  Open:    { bg: "var(--app-success-tint-10)",  color: "var(--app-success)", dot: "var(--app-success)" },
  Draft:   { bg: "var(--app-purple-tint-10)",  color: "var(--app-accent-purple)", dot: "var(--app-accent-purple)" },
  Closed:  { bg: "var(--app-slate-tint-12)", color: "var(--app-text-subtle)", dot: "var(--app-text-muted)" },
  Awarded: { bg: "var(--app-brand-tint-10)",  color: "var(--app-brand-400)", dot: "var(--app-brand-500)" },
  Cancelled: { bg: "var(--app-danger-tint-10)", color: "var(--app-danger-soft)", dot: "var(--app-danger)" },
};

const vendorStatusConfig: Record<string, { bg: string; color: string }> = {
  Responded:    { bg: "var(--app-success-tint-10)",   color: "var(--app-success)" },
  Invited:      { bg: "var(--app-brand-tint-10)",   color: "var(--app-brand-400)" },
  Declined:     { bg: "var(--app-danger-tint-10)",    color: "var(--app-danger-soft)" },
  "Not Invited": { bg: "var(--app-slate-tint-10)", color: "var(--app-text-muted)" },
};

const timelineTypeConfig: Record<string, { color: string; icon: any }> = {
  system:  { color: "var(--app-text-subtle)", icon: Zap },
  intake:  { color: "var(--app-brand-500)", icon: FileText },
  action:  { color: "var(--app-success)", icon: Check },
  vendor:  { color: "var(--app-warning)", icon: Users },
};

const tabs = ["Overview", "Vendors", "Documents", "Activity"];

export function RFQDetail() {
  const navigate = useNavigate();
  const { id } = useParams();
  const [activeTab, setActiveTab] = useState("Overview");
  const [showInviteModal, setShowInviteModal] = useState(false);
  const [showReminderModal, setShowReminderModal] = useState(false);

  const rfq = rfqs.find((r) => r.id === id) ?? rfqs[0];
  const sc = statusConfig[rfq.status] ?? statusConfig.Closed;

  const docs = [
    { name: "Scope_of_Work_v2.pdf", type: "PDF", size: "1.2 MB", uploaded: "Jan 12", by: "Sarah Chen" },
    { name: "Technical_Specifications.xlsx", type: "XLSX", size: "860 KB", uploaded: "Jan 12", by: "Sarah Chen" },
    { name: "Insurance_Requirements.pdf", type: "PDF", size: "420 KB", uploaded: "Jan 13", by: "Sarah Chen" },
    { name: "Apex_Quote_v2.pdf", type: "PDF", size: "2.4 MB", uploaded: "Jan 18", by: "AI Parser" },
    { name: "GlobalPump_Quote_Jan2024.xlsx", type: "XLSX", size: "1.8 MB", uploaded: "Jan 19", by: "AI Parser" },
  ];

  return (
    <div style={{ minHeight: "100%", fontFamily: "'Inter', system-ui, sans-serif" }}>
      {/* RFQ Header */}
      <div className="border-b px-6 py-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        <button
          onClick={() => navigate("/rfqs")}
          className="flex items-center gap-1.5 mb-4 transition-colors hover:opacity-80"
          style={{ fontSize: 12, color: "var(--app-text-subtle)" }}
        >
          <ArrowLeft size={13} /> Back to RFQs
        </button>

        <div className="flex items-start justify-between">
          <div className="flex items-start gap-4">
            <div>
              <div className="flex items-center gap-3 mb-1">
                <span style={{ fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 500 }}>{rfq.id}</span>
                <span className="flex items-center gap-1.5 rounded-full px-2.5 py-0.5" style={{ fontSize: 11, fontWeight: 600, background: sc.bg, color: sc.color }}>
                  <span style={{ width: 5, height: 5, borderRadius: "50%", background: sc.dot, display: "inline-block" }} />
                  {rfq.status}
                </span>
                <span className="rounded px-2 py-0.5" style={{ fontSize: 10, fontWeight: 600, background: "var(--app-orange-tint-10)", color: "var(--app-warning-soft)" }}>
                  {rfq.priority}
                </span>
              </div>
              <h1 style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 8 }}>{rfq.title}</h1>
              <div className="flex items-center gap-4">
                {[
                  { icon: Tag, label: rfq.category },
                  { icon: Users, label: rfq.owner },
                  { icon: CalendarDays, label: `Deadline: ${rfq.deadline}` },
                  { icon: DollarSign, label: rfq.budget },
                ].map((m) => (
                  <div key={m.label} className="flex items-center gap-1.5" style={{ fontSize: 12, color: "var(--app-text-muted)" }}>
                    <m.icon size={12} style={{ color: "var(--app-text-subtle)" }} />
                    {m.label}
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowReminderModal(true)}
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
              style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
              onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
            >
              <Bell size={13} /> Send Reminder
            </button>
            <button
              onClick={() => navigate(`/rfqs/${rfq.id}/vendors`)}
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
              style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
              onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
            >
              <Users size={13} /> Manage Vendors
            </button>
            <button
              onClick={() => setShowInviteModal(true)}
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-opacity hover:opacity-90"
              style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}
            >
              <Mail size={13} /> Invite Vendors
            </button>
            <button
              onClick={() => navigate(`/quote-comparison/${rfq.id}`)}
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-opacity hover:opacity-90"
              style={{ fontSize: 13, fontWeight: 500, background: "var(--app-accent-purple)", color: "white" }}
            >
              <GitCompareArrows size={13} /> Run Comparison
            </button>
          </div>
        </div>

        {/* Tabs */}
        <div className="flex items-center gap-1 mt-4 -mb-4">
          {tabs.map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className="px-4 py-2 transition-all"
              style={{
                fontSize: 13,
                fontWeight: activeTab === tab ? 500 : 400,
                color: activeTab === tab ? "var(--app-text-main)" : "var(--app-text-muted)",
                borderBottom: activeTab === tab ? "2px solid var(--app-brand-500)" : "2px solid transparent",
                marginBottom: -1,
              }}
            >{tab}</button>
          ))}
        </div>
      </div>

      {/* Content */}
      <div className="flex gap-0" style={{ minHeight: "calc(100% - 160px)" }}>
        <div className="flex-1 overflow-auto p-6">

          {/* Overview Tab */}
          {activeTab === "Overview" && (
            <div className="grid grid-cols-2 gap-4">
              {/* RFQ Progress */}
              <div className="col-span-2 rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 16 }}>RFQ Lifecycle Progress</h3>
                <div className="flex items-center gap-0">
                  {[
                    { label: "Created", done: true },
                    { label: "Open", done: rfq.status !== "Draft" },
                    { label: "Quotes In", done: ["Closed", "Awarded"].includes(rfq.status) },
                    { label: "Compared", done: rfq.status === "Awarded" },
                    { label: "Approved", done: rfq.status === "Awarded" },
                    { label: "Awarded", done: rfq.status === "Awarded" },
                  ].map((stage, i, arr) => (
                    <div key={stage.label} className="flex items-center" style={{ flex: i < arr.length - 1 ? "1 1 0" : "0 0 auto" }}>
                      <div className="flex flex-col items-center gap-1.5">
                        <div className="flex items-center justify-center rounded-full" style={{ width: 28, height: 28, background: stage.done ? "var(--app-brand-600)" : "var(--app-border-strong)", border: `2px solid ${stage.done ? "var(--app-brand-500)" : "var(--app-text-faint)"}` }}>
                          {stage.done ? <Check size={13} style={{ color: "white" }} /> : <Circle size={6} style={{ color: "var(--app-text-faint)" }} />}
                        </div>
                        <span style={{ fontSize: 11, color: stage.done ? "var(--app-text-subtle)" : "var(--app-text-subtle)", whiteSpace: "nowrap" }}>{stage.label}</span>
                      </div>
                      {i < arr.length - 1 && (
                        <div className="flex-1 mx-1 h-px mt-[-14px]" style={{ background: stage.done ? "var(--app-brand-600)" : "var(--app-border-strong)" }} />
                      )}
                    </div>
                  ))}
                </div>
              </div>

              {/* Key Details */}
              <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>Key Details</h3>
                <div className="space-y-3">
                  {[
                    { label: "RFQ ID", value: rfq.id, mono: true },
                    { label: "Category", value: rfq.category },
                    { label: "Owner", value: rfq.owner },
                    { label: "Budget", value: rfq.budget, mono: true },
                    { label: "Submission Deadline", value: rfq.deadline },
                    { label: "Created", value: rfq.created },
                    { label: "Vendors Invited", value: String(rfq.vendors) },
                    { label: "Priority", value: rfq.priority },
                  ].map((item) => (
                    <div key={item.label} className="flex items-center justify-between py-1.5" style={{ borderBottom: "1px solid var(--app-bg-elevated)" }}>
                      <span style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>{item.label}</span>
                      <span style={{ fontSize: 12, color: "var(--app-text-main)", fontFamily: item.mono ? "'JetBrains Mono', monospace" : "inherit", fontWeight: 500 }}>{item.value}</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Vendor Participation Summary */}
              <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <div className="flex items-center justify-between mb-3">
                  <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase" }}>Vendor Participation</h3>
                  <button onClick={() => navigate(`/rfqs/${rfq.id}/vendors`)} style={{ fontSize: 12, color: "var(--app-brand-500)" }} className="hover:opacity-80 transition-opacity flex items-center gap-1">
                    Manage <ArrowRight size={11} />
                  </button>
                </div>
                <div className="grid grid-cols-2 gap-3 mb-4">
                  {[
                    { label: "Responded", count: 2, color: "var(--app-success)" },
                    { label: "Invited", count: 1, color: "var(--app-brand-500)" },
                    { label: "Declined", count: 1, color: "var(--app-danger)" },
                    { label: "Not Invited", count: 2, color: "var(--app-text-subtle)" },
                  ].map((item) => (
                    <div key={item.label} className="rounded-lg p-3" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
                      <div style={{ fontSize: 22, fontWeight: 700, color: item.color, letterSpacing: "-0.02em" }}>{item.count}</div>
                      <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginTop: 2 }}>{item.label}</div>
                    </div>
                  ))}
                </div>
                <div className="rounded-lg overflow-hidden" style={{ border: "1px solid var(--app-border-strong)" }}>
                  {vendors.slice(0, 4).map((v, i) => {
                    const vs = vendorStatusConfig[v.status] ?? vendorStatusConfig["Not Invited"];
                    return (
                      <div key={v.id} className="flex items-center justify-between px-3 py-2" style={{ borderBottom: i < 3 ? "1px solid var(--app-bg-elevated)" : "none" }}>
                        <span style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>{v.name}</span>
                        <span className="rounded-full px-2 py-0.5" style={{ fontSize: 10, fontWeight: 600, background: vs.bg, color: vs.color }}>{v.status}</span>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          )}

          {/* Vendors Tab */}
          {activeTab === "Vendors" && (
            <div>
              <div className="flex items-center justify-between mb-4">
                <h3 style={{ fontSize: 14, fontWeight: 600, color: "var(--app-text-main)" }}>Vendor Roster ({vendors.length})</h3>
                <button onClick={() => navigate(`/rfqs/${rfq.id}/vendors`)} className="flex items-center gap-2 rounded-lg px-3 py-2 transition-opacity hover:opacity-90" style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}>
                  <ExternalLink size={13} /> Full Vendor Management
                </button>
              </div>
              <div className="rounded-xl border overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <table style={{ width: "100%", borderCollapse: "collapse" }}>
                  <thead>
                    <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                      {["Vendor", "Status", "Contact", "Channel", "Invited", "Response", "Quote Value"].map((h) => (
                        <th key={h} style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "10px 14px", textAlign: "left", background: "var(--app-bg-elevated)" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {vendors.map((v, i) => {
                      const vs = vendorStatusConfig[v.status] ?? vendorStatusConfig["Not Invited"];
                      return (
                        <tr key={v.id} style={{ borderBottom: i < vendors.length - 1 ? "1px solid var(--app-bg-elevated)" : "none" }}>
                          <td style={{ padding: "10px 14px" }}>
                            <div style={{ fontSize: 13, fontWeight: 500, color: "var(--app-text-main)" }}>{v.name}</div>
                          </td>
                          <td style={{ padding: "10px 14px" }}>
                            <span className="rounded-full px-2.5 py-0.5" style={{ fontSize: 11, fontWeight: 600, background: vs.bg, color: vs.color }}>{v.status}</span>
                          </td>
                          <td style={{ padding: "10px 14px", fontSize: 12, color: "var(--app-text-muted)" }}>{v.contact}</td>
                          <td style={{ padding: "10px 14px", fontSize: 12, color: "var(--app-text-subtle)" }}>{v.channel ?? "—"}</td>
                          <td style={{ padding: "10px 14px", fontSize: 12, color: "var(--app-text-muted)" }}>{v.invitedDate ?? "—"}</td>
                          <td style={{ padding: "10px 14px", fontSize: 12, color: "var(--app-text-muted)" }}>{v.responseDate ?? "—"}</td>
                          <td style={{ padding: "10px 14px", fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: v.quoteValue ? "var(--app-success)" : "var(--app-text-faint)" }}>{v.quoteValue ?? "—"}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Documents Tab */}
          {activeTab === "Documents" && (
            <div>
              <div className="flex items-center justify-between mb-4">
                <h3 style={{ fontSize: 14, fontWeight: 600, color: "var(--app-text-main)" }}>Documents ({docs.length})</h3>
                <button className="flex items-center gap-2 rounded-lg px-3 py-2" style={{ fontSize: 13, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}>
                  <Plus size={13} /> Upload Document
                </button>
              </div>
              <div className="rounded-xl border overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                {docs.map((doc, i) => (
                  <div key={doc.name} className="flex items-center gap-3 px-4 py-3 transition-colors" style={{ borderBottom: i < docs.length - 1 ? "1px solid var(--app-bg-elevated)" : "none" }}
                    onMouseEnter={(e) => { (e.currentTarget as HTMLDivElement).style.background = "var(--app-hover-subtle)"; }}
                    onMouseLeave={(e) => { (e.currentTarget as HTMLDivElement).style.background = "transparent"; }}>
                    <div className="flex items-center justify-center rounded" style={{ width: 32, height: 32, background: doc.type === "PDF" ? "var(--app-danger-tint-10)" : "var(--app-success-tint-10)", flexShrink: 0 }}>
                      <FileText size={14} style={{ color: doc.type === "PDF" ? "var(--app-danger-soft)" : "var(--app-success)" }} />
                    </div>
                    <div className="flex-1">
                      <div style={{ fontSize: 13, color: "var(--app-text-main)", fontWeight: 500 }}>{doc.name}</div>
                      <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginTop: 1 }}>{doc.size} · Uploaded {doc.uploaded} by {doc.by}</div>
                    </div>
                    <div className="flex items-center gap-1">
                      <button className="rounded p-1.5 transition-colors" style={{ color: "var(--app-text-subtle)" }} title="Download"
                        onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; (e.currentTarget as HTMLButtonElement).style.background = "var(--app-border-strong)"; }}
                        onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; (e.currentTarget as HTMLButtonElement).style.background = "transparent"; }}>
                        <Download size={13} />
                      </button>
                      <button className="rounded p-1.5 transition-colors" style={{ color: "var(--app-text-subtle)" }}
                        onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; (e.currentTarget as HTMLButtonElement).style.background = "var(--app-border-strong)"; }}
                        onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; (e.currentTarget as HTMLButtonElement).style.background = "transparent"; }}>
                        <MoreHorizontal size={13} />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Activity Tab */}
          {activeTab === "Activity" && (
            <div className="max-w-lg">
              <h3 style={{ fontSize: 14, fontWeight: 600, color: "var(--app-text-main)", marginBottom: 16 }}>Activity Timeline</h3>
              <div className="relative pl-6">
                <div className="absolute left-2 top-2 bottom-2 w-px" style={{ background: "var(--app-border-strong)" }} />
                {activityTimeline.map((event) => {
                  const tc = timelineTypeConfig[event.type] ?? timelineTypeConfig.system;
                  const Icon = tc.icon;
                  return (
                    <div key={event.id} className="relative mb-5">
                      <div className="absolute -left-[18px] flex items-center justify-center rounded-full" style={{ width: 20, height: 20, background: "var(--app-bg-elevated)", border: `1.5px solid ${tc.color}` }}>
                        <Icon size={10} style={{ color: tc.color }} />
                      </div>
                      <div className="rounded-lg p-3" style={{ background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)" }}>
                        <div style={{ fontSize: 13, color: "var(--app-text-main)", fontWeight: 500, marginBottom: 2 }}>{event.action}</div>
                        <div className="flex items-center gap-2" style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>
                          <span>{event.actor}</span>
                          <span>·</span>
                          <span>{event.time}</span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}
        </div>

        {/* Right rail: Activity stream (always visible except on Activity tab) */}
        {activeTab !== "Activity" && (
          <div className="flex-shrink-0 border-l p-4" style={{ width: 280, borderColor: "var(--app-border-strong)", background: "var(--app-bg-canvas)" }}>
            <h3 style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>Recent Activity</h3>
            <div className="relative pl-5">
              <div className="absolute left-1.5 top-1 bottom-1 w-px" style={{ background: "var(--app-border-strong)" }} />
              {activityTimeline.slice(0, 5).map((event) => {
                const tc = timelineTypeConfig[event.type] ?? timelineTypeConfig.system;
                const Icon = tc.icon;
                return (
                  <div key={event.id} className="relative mb-4">
                    <div className="absolute -left-[14px] flex items-center justify-center rounded-full" style={{ width: 16, height: 16, background: "var(--app-bg-surface)", border: `1.5px solid ${tc.color}` }}>
                      <Icon size={8} style={{ color: tc.color }} />
                    </div>
                    <div style={{ fontSize: 12, color: "var(--app-text-subtle)", lineHeight: 1.4 }}>{event.action}</div>
                    <div style={{ fontSize: 10, color: "var(--app-text-faint)", marginTop: 2 }}>{event.time}</div>
                  </div>
                );
              })}
            </div>
          </div>
        )}
      </div>

      {/* Invite Vendors Modal */}
      {showInviteModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "var(--app-overlay)" }}>
          <div className="rounded-xl border shadow-2xl p-6" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", width: 480 }}>
            <div className="flex items-center justify-between mb-4">
              <h3 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)" }}>Send Invitations</h3>
              <button onClick={() => setShowInviteModal(false)} style={{ color: "var(--app-text-subtle)" }} className="hover:text-slate-300 transition-colors">✕</button>
            </div>
            <p style={{ fontSize: 13, color: "var(--app-text-muted)", marginBottom: 16 }}>Select vendors and invitation channel for <strong style={{ color: "var(--app-text-main)" }}>{rfq.id}</strong></p>
            <div className="space-y-2 mb-4">
              {vendors.filter(v => v.status === "Not Invited").map(v => (
                <label key={v.id} className="flex items-center gap-3 rounded-lg p-3 cursor-pointer" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
                  <input type="checkbox" defaultChecked style={{ accentColor: "var(--app-brand-500)" }} />
                  <div>
                    <div style={{ fontSize: 13, color: "var(--app-text-main)", fontWeight: 500 }}>{v.name}</div>
                    <div style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>{v.contact}</div>
                  </div>
                </label>
              ))}
            </div>
            <div className="flex justify-end gap-2">
              <button onClick={() => setShowInviteModal(false)} className="rounded-lg px-4 py-2" style={{ fontSize: 13, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}>Cancel</button>
              <button onClick={() => setShowInviteModal(false)} className="rounded-lg px-4 py-2" style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}>Send Invitations</button>
            </div>
          </div>
        </div>
      )}

      {/* Reminder Modal */}
      {showReminderModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "var(--app-overlay)" }}>
          <div className="rounded-xl border shadow-2xl p-6" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", width: 400 }}>
            <div className="flex items-center justify-between mb-4">
              <h3 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)" }}>Send Reminder</h3>
              <button onClick={() => setShowReminderModal(false)} style={{ color: "var(--app-text-subtle)" }} className="hover:text-slate-300 transition-colors">✕</button>
            </div>
            <p style={{ fontSize: 13, color: "var(--app-text-muted)", marginBottom: 16 }}>Send a reminder to all pending vendors for <strong style={{ color: "var(--app-text-main)" }}>{rfq.id}</strong>. This will notify TechFlow Dynamics who has not yet responded.</p>
            <div className="flex justify-end gap-2">
              <button onClick={() => setShowReminderModal(false)} className="rounded-lg px-4 py-2" style={{ fontSize: 13, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}>Cancel</button>
              <button onClick={() => setShowReminderModal(false)} className="rounded-lg px-4 py-2 flex items-center gap-2" style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}>
                <Send size={13} /> Send Reminder
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
