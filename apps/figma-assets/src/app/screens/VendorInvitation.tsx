import { useState } from "react";
import { useNavigate, useParams } from "react-router";
import {
  ArrowLeft, Mail, MessageSquare, Globe, Plus, Send, Bell,
  CheckCircle2, Clock, XCircle, Circle, MoreHorizontal, Filter,
  RefreshCw, UserPlus, ExternalLink, AlertTriangle, ChevronDown, X
} from "lucide-react";
import { vendors, rfqs } from "../data/mockData";

const statusConfig: Record<string, { bg: string; color: string; icon: any; label: string }> = {
  Responded:    { bg: "var(--app-success-tint-10)",   color: "var(--app-success)", icon: CheckCircle2, label: "Responded" },
  Invited:      { bg: "var(--app-brand-tint-10)",   color: "var(--app-brand-400)", icon: Clock,        label: "Awaiting Response" },
  Declined:     { bg: "var(--app-danger-tint-10)",    color: "var(--app-danger-soft)", icon: XCircle,      label: "Declined" },
  "Not Invited":{ bg: "var(--app-slate-tint-10)",  color: "var(--app-text-muted)", icon: Circle,       label: "Not Invited" },
};

const channelIcons: Record<string, any> = {
  Email: Mail,
  Portal: Globe,
  SMS: MessageSquare,
};

const channels = ["Email", "Portal", "SMS"];

export function VendorInvitation() {
  const navigate = useNavigate();
  const { id } = useParams();
  const rfq = rfqs.find((r) => r.id === id) ?? rfqs[0];

  const [vendorList, setVendorList] = useState(vendors);
  const [statusFilter, setStatusFilter] = useState("All");
  const [showAddVendor, setShowAddVendor] = useState(false);
  const [showReminderModal, setShowReminderModal] = useState<string | null>(null);
  const [selectedChannel, setSelectedChannel] = useState<Record<string, string>>({});
  const [scheduledReminder, setScheduledReminder] = useState<Record<string, string>>({});
  const [reminderNote, setReminderNote] = useState("");

  const statuses = ["All", "Not Invited", "Invited", "Responded", "Declined"];

  const filtered = vendorList.filter((v) => statusFilter === "All" || v.status === statusFilter);

  const handleInvite = (vendorId: string) => {
    setVendorList((prev) =>
      prev.map((v) =>
        v.id === vendorId
          ? { ...v, status: "Invited", invitedDate: "2024-01-24", channel: selectedChannel[vendorId] ?? "Email" }
          : v
      )
    );
  };

  const getChannel = (vendorId: string) => selectedChannel[vendorId] ?? "Email";

  const summaryStats = {
    total: vendorList.length,
    invited: vendorList.filter((v) => v.status === "Invited").length,
    responded: vendorList.filter((v) => v.status === "Responded").length,
    declined: vendorList.filter((v) => v.status === "Declined").length,
    notInvited: vendorList.filter((v) => v.status === "Not Invited").length,
  };

  const reminderTarget = vendorList.find((v) => v.id === showReminderModal);

  return (
    <div style={{ minHeight: "100%", fontFamily: "'Inter', system-ui, sans-serif" }}>
      {/* Header */}
      <div className="border-b px-6 py-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        <button
          onClick={() => navigate(`/rfqs/${rfq.id}`)}
          className="flex items-center gap-1.5 mb-3 transition-colors hover:opacity-80"
          style={{ fontSize: 12, color: "var(--app-text-subtle)" }}
        >
          <ArrowLeft size={13} /> Back to {rfq.id}
        </button>
        <div className="flex items-start justify-between">
          <div>
            <h1 style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 4 }}>Vendor Invitation Management</h1>
            <p style={{ fontSize: 13, color: "var(--app-text-subtle)" }}>{rfq.id} — {rfq.title}</p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowAddVendor(true)}
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
              style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
              onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
            >
              <UserPlus size={13} /> Add Vendor
            </button>
            <button
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-opacity hover:opacity-90"
              style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}
              onClick={() => {
                vendorList.filter((v) => v.status === "Not Invited").forEach((v) => handleInvite(v.id));
              }}
            >
              <Send size={13} /> Invite All Pending
            </button>
          </div>
        </div>
      </div>

      <div className="p-6">
        {/* Summary Stats */}
        <div className="grid grid-cols-5 gap-3 mb-6">
          {[
            { label: "Total Vendors", count: summaryStats.total, color: "var(--app-text-subtle)", bg: "var(--app-slate-tint-8)" },
            { label: "Responded", count: summaryStats.responded, color: "var(--app-success)", bg: "var(--app-success-tint-8)" },
            { label: "Awaiting Response", count: summaryStats.invited, color: "var(--app-brand-400)", bg: "var(--app-brand-tint-8)" },
            { label: "Declined", count: summaryStats.declined, color: "var(--app-danger-soft)", bg: "var(--app-danger-tint-8)" },
            { label: "Not Yet Invited", count: summaryStats.notInvited, color: "var(--app-accent-purple)", bg: "var(--app-purple-tint-8)" },
          ].map((stat) => (
            <div key={stat.label} className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div style={{ fontSize: 26, fontWeight: 700, color: stat.color, letterSpacing: "-0.02em", lineHeight: 1, marginBottom: 4 }}>{stat.count}</div>
              <div style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>{stat.label}</div>
            </div>
          ))}
        </div>

        {/* RFQ Deadline Notice */}
        <div className="flex items-center gap-3 rounded-lg px-4 py-3 mb-5" style={{ background: "var(--app-warning-tint-6)", border: "1px solid var(--app-warning-tint-15)" }}>
          <AlertTriangle size={14} style={{ color: "var(--app-warning)", flexShrink: 0 }} />
          <div style={{ fontSize: 13, color: "var(--app-warning-soft)" }}>
            Quote submission deadline: <strong>{rfq.deadline}</strong>. {summaryStats.invited} vendor{summaryStats.invited !== 1 ? "s have" : " has"} not yet responded.
            {summaryStats.invited > 0 && (
              <button className="ml-2 underline hover:no-underline" style={{ color: "var(--app-warning)" }}>Send bulk reminder</button>
            )}
          </div>
        </div>

        {/* Filter bar */}
        <div className="flex items-center gap-3 mb-4">
          <div className="flex items-center gap-1 p-1 rounded-lg" style={{ background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)" }}>
            {statuses.map((s) => (
              <button
                key={s}
                onClick={() => setStatusFilter(s)}
                className="rounded transition-all"
                style={{ fontSize: 12, padding: "4px 10px", background: statusFilter === s ? "var(--app-border-strong)" : "transparent", color: statusFilter === s ? "var(--app-text-main)" : "var(--app-text-muted)", fontWeight: statusFilter === s ? 500 : 400 }}
              >
                {s} {s !== "All" && <span style={{ opacity: 0.6 }}>({vendorList.filter(v => v.status === s).length})</span>}
              </button>
            ))}
          </div>
          <div style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>
            Showing {filtered.length} of {vendorList.length} vendors
          </div>
        </div>

        {/* Vendor Table */}
        <div className="rounded-xl border overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
          <table style={{ width: "100%", borderCollapse: "collapse" }}>
            <thead>
              <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                {["Vendor", "Status", "Contact", "Channel", "Date Invited", "Response Date", "Quote Value", "Notes", "Actions"].map((h) => (
                  <th key={h} style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "10px 14px", textAlign: "left", background: "var(--app-bg-elevated)", whiteSpace: "nowrap" }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filtered.map((vendor, i) => {
                const sc = statusConfig[vendor.status] ?? statusConfig["Not Invited"];
                const StatusIcon = sc.icon;
                const channelKey = getChannel(vendor.id);
                const ChannelIcon = channelIcons[channelKey] ?? Mail;

                return (
                  <tr
                    key={vendor.id}
                    style={{ borderBottom: i < filtered.length - 1 ? "1px solid var(--app-bg-elevated)" : "none" }}
                    onMouseEnter={(e) => { (e.currentTarget as HTMLTableRowElement).style.background = "var(--app-hover-subtle)"; }}
                    onMouseLeave={(e) => { (e.currentTarget as HTMLTableRowElement).style.background = "transparent"; }}
                  >
                    <td style={{ padding: "12px 14px" }}>
                      <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-main)" }}>{vendor.name}</div>
                    </td>

                    <td style={{ padding: "12px 14px" }}>
                      <div className="flex items-center gap-1.5 rounded-full px-2.5 py-1 w-fit" style={{ background: sc.bg }}>
                        <StatusIcon size={11} style={{ color: sc.color, flexShrink: 0 }} />
                        <span style={{ fontSize: 11, fontWeight: 600, color: sc.color, whiteSpace: "nowrap" }}>{vendor.status}</span>
                      </div>
                    </td>

                    <td style={{ padding: "12px 14px" }}>
                      <div style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>{vendor.contact}</div>
                    </td>

                    <td style={{ padding: "12px 14px" }}>
                      {vendor.status !== "Not Invited" ? (
                        <div className="flex items-center gap-1.5" style={{ fontSize: 12, color: "var(--app-text-muted)" }}>
                          <ChannelIcon size={12} style={{ color: "var(--app-text-subtle)" }} />
                          {vendor.channel}
                        </div>
                      ) : (
                        <div className="flex items-center gap-1 rounded-lg px-2 py-1" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", width: "fit-content" }}>
                          <select
                            value={getChannel(vendor.id)}
                            onChange={(e) => setSelectedChannel((prev) => ({ ...prev, [vendor.id]: e.target.value }))}
                            style={{ background: "transparent", border: "none", outline: "none", fontSize: 12, color: "var(--app-text-subtle)", cursor: "pointer" }}
                          >
                            {channels.map((c) => <option key={c} value={c} style={{ background: "var(--app-bg-elevated)" }}>{c}</option>)}
                          </select>
                        </div>
                      )}
                    </td>

                    <td style={{ padding: "12px 14px", fontSize: 12, color: "var(--app-text-muted)" }}>
                      {vendor.invitedDate ?? <span style={{ color: "var(--app-text-faint)" }}>—</span>}
                    </td>

                    <td style={{ padding: "12px 14px", fontSize: 12, color: "var(--app-text-muted)" }}>
                      {vendor.responseDate ?? <span style={{ color: "var(--app-text-faint)" }}>—</span>}
                    </td>

                    <td style={{ padding: "12px 14px" }}>
                      {vendor.quoteValue ? (
                        <span style={{ fontSize: 13, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-success)", fontWeight: 600 }}>{vendor.quoteValue}</span>
                      ) : (
                        <span style={{ color: "var(--app-text-faint)", fontSize: 12 }}>—</span>
                      )}
                    </td>

                    <td style={{ padding: "12px 14px", maxWidth: 200 }}>
                      <span style={{ fontSize: 11, color: "var(--app-text-subtle)", lineHeight: 1.4 }}>{vendor.notes ?? "—"}</span>
                    </td>

                    <td style={{ padding: "12px 14px" }}>
                      <div className="flex items-center gap-1.5">
                        {vendor.status === "Not Invited" && (
                          <button
                            onClick={() => handleInvite(vendor.id)}
                            className="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 transition-opacity hover:opacity-90"
                            style={{ fontSize: 12, fontWeight: 500, background: "var(--app-brand-600)", color: "white", whiteSpace: "nowrap" }}
                          >
                            <Send size={11} /> Invite
                          </button>
                        )}
                        {vendor.status === "Invited" && (
                          <button
                            onClick={() => setShowReminderModal(vendor.id)}
                            className="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 transition-colors"
                            style={{ fontSize: 12, background: "var(--app-warning-tint-10)", color: "var(--app-warning-soft)", border: "1px solid var(--app-warning-tint-20)", whiteSpace: "nowrap" }}
                            onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-warning-tint-18)"; }}
                            onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-warning-tint-10)"; }}
                          >
                            <Bell size={11} /> Remind
                          </button>
                        )}
                        {vendor.status === "Responded" && (
                          <button
                            className="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 transition-colors"
                            style={{ fontSize: 12, background: "var(--app-success-tint-8)", color: "var(--app-success)", border: "1px solid var(--app-success-tint-15)", whiteSpace: "nowrap" }}
                          >
                            <ExternalLink size={11} /> View Quote
                          </button>
                        )}
                        <button
                          className="rounded-lg p-1.5 transition-colors"
                          style={{ color: "var(--app-text-subtle)" }}
                          onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; (e.currentTarget as HTMLButtonElement).style.background = "var(--app-border-strong)"; }}
                          onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; (e.currentTarget as HTMLButtonElement).style.background = "transparent"; }}
                        >
                          <MoreHorizontal size={13} />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {/* Reminder Scheduler */}
        {summaryStats.invited > 0 && (
          <div className="mt-4 rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase", marginBottom: 12 }}>Reminder Scheduler</h3>
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2" style={{ fontSize: 13, color: "var(--app-text-subtle)" }}>
                <Clock size={14} style={{ color: "var(--app-text-subtle)" }} />
                Auto-reminder configured:
              </div>
              <div className="flex items-center gap-2 rounded-lg px-3 py-1.5" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", fontSize: 12, color: "var(--app-brand-400)" }}>
                <Bell size={12} />
                3 days before deadline
              </div>
              <div className="flex items-center gap-2 rounded-lg px-3 py-1.5" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", fontSize: 12, color: "var(--app-brand-400)" }}>
                <Bell size={12} />
                1 day before deadline
              </div>
              <button
                className="flex items-center gap-2 rounded-lg px-3 py-1.5 ml-auto transition-colors"
                style={{ fontSize: 12, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}
                onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
                onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
              >
                <RefreshCw size={12} /> Configure Schedule
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Reminder Confirmation Modal */}
      {showReminderModal && reminderTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "var(--app-overlay)" }}>
          <div className="rounded-xl border shadow-2xl p-6" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", width: 460 }}>
            <div className="flex items-center justify-between mb-1">
              <h3 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)" }}>Send Reminder</h3>
              <button onClick={() => setShowReminderModal(null)} style={{ color: "var(--app-text-subtle)" }} className="hover:text-slate-300 transition-colors">
                <X size={16} />
              </button>
            </div>
            <p style={{ fontSize: 13, color: "var(--app-text-muted)", marginBottom: 16 }}>
              A reminder will be sent to <strong style={{ color: "var(--app-text-main)" }}>{reminderTarget.name}</strong> via <strong style={{ color: "var(--app-text-main)" }}>{reminderTarget.channel}</strong>.
            </p>

            <div className="rounded-lg p-3 mb-4" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
              <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginBottom: 6 }}>CONTACT</div>
              <div style={{ fontSize: 13, color: "var(--app-text-main)" }}>{reminderTarget.contact}</div>
            </div>

            <div className="mb-4">
              <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 6 }}>OPTIONAL MESSAGE</label>
              <textarea
                value={reminderNote}
                onChange={(e) => setReminderNote(e.target.value)}
                rows={3}
                placeholder="Add a custom note to the reminder email…"
                style={{ width: "100%", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "10px 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box", resize: "none", lineHeight: 1.6 }}
                onFocus={(e) => { e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                onBlur={(e) => { e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
              />
            </div>

            <div className="flex justify-end gap-2">
              <button
                onClick={() => setShowReminderModal(null)}
                className="rounded-lg px-4 py-2"
                style={{ fontSize: 13, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}
              >
                Cancel
              </button>
              <button
                onClick={() => {
                  setShowReminderModal(null);
                  setReminderNote("");
                }}
                className="flex items-center gap-2 rounded-lg px-4 py-2"
                style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}
              >
                <Send size={13} /> Send Reminder
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Add Vendor Modal */}
      {showAddVendor && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "var(--app-overlay)" }}>
          <div className="rounded-xl border shadow-2xl p-6" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", width: 440 }}>
            <div className="flex items-center justify-between mb-4">
              <h3 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)" }}>Add Vendor to Shortlist</h3>
              <button onClick={() => setShowAddVendor(false)} style={{ color: "var(--app-text-subtle)" }} className="hover:text-slate-300 transition-colors">
                <X size={16} />
              </button>
            </div>
            <div className="space-y-3 mb-4">
              {[
                { label: "Company Name", placeholder: "e.g., Meridian Equipment Ltd" },
                { label: "Contact Email", placeholder: "contact@vendor.com" },
                { label: "Contact Name", placeholder: "First Last" },
              ].map((f) => (
                <div key={f.label}>
                  <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>{f.label.toUpperCase()}</label>
                  <input
                    placeholder={f.placeholder}
                    style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box" }}
                    onFocus={(e) => { e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                    onBlur={(e) => { e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
                  />
                </div>
              ))}
            </div>
            <div className="flex justify-end gap-2">
              <button onClick={() => setShowAddVendor(false)} className="rounded-lg px-4 py-2" style={{ fontSize: 13, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}>Cancel</button>
              <button onClick={() => setShowAddVendor(false)} className="rounded-lg px-4 py-2" style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}>Add to Shortlist</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
