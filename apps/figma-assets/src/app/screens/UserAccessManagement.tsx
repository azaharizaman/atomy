import { useState } from "react";
import { UserPlus, Shield, Users } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

export function UserAccessManagement() {
  const [confirmOpen, setConfirmOpen] = useState(false);

  return (
    <Page
      title="User & Access Management"
      subtitle="Manage user lifecycle, role assignments, delegation, and approval authority limits."
      actions={
        <>
          <GhostButton label="Export User Directory" />
          <PrimaryButton label="Invite User" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Active Users" value="64" tone="success" />
        <Stat label="Pending Invites" value="5" tone="warning" />
        <Stat label="Suspended" value="2" tone="danger" />
        <Stat label="Role Changes (7d)" value="8" tone="brand" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-8">
          <Panel title="User Directory" subtitle="Role and authority overview">
            <table style={{ width: "100%", borderCollapse: "collapse" }}>
              <thead>
                <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                  {["User", "Role", "Delegation", "Approval Limit", "State"].map((h) => (
                    <th key={h} style={{ padding: "10px 8px", fontSize: 10, textAlign: "left", textTransform: "uppercase", letterSpacing: "0.06em", color: "var(--app-text-muted)" }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {[
                  ["Sarah Chen", "Buyer", "David M.", "$250,000", "Active"],
                  ["Emily Wong", "Finance Director", "None", "$1,000,000", "Active"],
                  ["Kenji Sato", "Approver", "Sarah C.", "$500,000", "Pending Invite"],
                  ["Robert Kim", "Buyer", "None", "$100,000", "Suspended"],
                ].map((row, idx) => (
                  <tr key={row[0]} style={{ borderBottom: idx < 3 ? "1px solid var(--app-border-strong)" : "none" }}>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-main)", fontWeight: 600 }}>{row[0]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-main)" }}>{row[1]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-muted)" }}>{row[2]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-brand-500)", fontFamily: "'JetBrains Mono', monospace" }}>{row[3]}</td>
                    <td style={{ padding: "10px 8px" }}>
                      <Badge label={row[4]} tone={row[4] === "Active" ? "success" : row[4] === "Suspended" ? "danger" : "warning"} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </Panel>
        </div>

        <div className="col-span-4 space-y-4">
          <Panel title="Role & Permission Detail" subtitle="Selected user: Sarah Chen">
            <div className="space-y-2">
              {[
                ["Can create RFQ", "Enabled", "success"],
                ["Can approve awards", "Enabled", "success"],
                ["Can publish scoring policy", "Disabled", "warning"],
              ].map((row) => (
                <div key={row[0]} className="rounded-lg border p-2 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{row[0]}</span>
                  <Badge label={row[1]} tone={row[2] as "success"} />
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Authority Limits" subtitle="Critical control fields">
            <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
              <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Current approval authority</div>
              <div style={{ fontSize: 18, fontWeight: 800, color: "var(--app-brand-500)", fontFamily: "'JetBrains Mono', monospace" }}>$250,000</div>
            </div>
            <button onClick={() => setConfirmOpen(true)} className="w-full rounded-lg py-2.5" style={{ fontSize: 12, fontWeight: 700, background: "var(--app-brand-600)", color: "white" }}>
              <Shield size={13} style={{ display: "inline", marginRight: 6 }} />
              Save Access Changes
            </button>
          </Panel>
        </div>
      </div>

      <SlideOver open={confirmOpen} onClose={() => setConfirmOpen(false)} title="Confirm Critical Role Change" subtitle="This update affects approval authority and access controls.">
        <Panel title="Pending Access Update" subtitle="User: Sarah Chen">
          <div style={{ fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.7 }}>
            Role: Buyer (unchanged)<br />
            Approval authority: <strong>$250,000 → $300,000</strong><br />
            Delegation: None
          </div>
        </Panel>
        <button className="w-full rounded-lg py-2.5" style={{ background: "var(--app-success)", color: "white", fontSize: 13, fontWeight: 700 }}>
          <UserPlus size={14} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
          Confirm Access Update
        </button>
      </SlideOver>
    </Page>
  );
}
