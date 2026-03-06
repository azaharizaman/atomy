import { TrendingUp, ShieldCheck, Clock3, Star } from "lucide-react";
import { GhostButton, Page, Panel, PrimaryButton, Stat, Badge } from "./BlueprintPrimitives";

export function VendorProfilePerformance() {
  return (
    <Page
      title="Vendor Profile & Performance"
      subtitle="Review historical performance, compliance posture, and sourcing outcomes for informed selection."
      actions={
        <>
          <GhostButton label="View Linked RFQs" />
          <PrimaryButton label="Use in Comparison" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Vendor" value="Apex Industrial" tone="brand" />
        <Stat label="Performance Score" value="94/100" tone="success" />
        <Stat label="Compliance Incidents" value="0" tone="success" />
        <Stat label="Awards Won (12m)" value="8" tone="brand" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-8 space-y-4">
          <Panel title="Performance Scorecard" subtitle="Delivery, quality, and commercial consistency">
            <div className="grid grid-cols-2 gap-3">
              {[
                ["On-time Delivery", "96%", "success"],
                ["Quality Acceptance", "93%", "success"],
                ["Commercial Accuracy", "91%", "brand"],
                ["Issue Resolution SLA", "98%", "success"],
              ].map((metric) => (
                <div key={metric[0]} className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>{metric[0]}</div>
                  <div style={{ fontSize: 18, fontWeight: 800, color: "var(--app-text-main)" }}>{metric[1]}</div>
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Prior Quote Outcomes" subtitle="Recent RFQ history and result links">
            <table style={{ width: "100%", borderCollapse: "collapse" }}>
              <thead>
                <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                  {["RFQ", "Category", "Outcome", "Score", "Result Date"].map((h) => (
                    <th key={h} style={{ padding: "10px 8px", fontSize: 10, textAlign: "left", textTransform: "uppercase", letterSpacing: "0.06em", color: "var(--app-text-muted)" }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {[
                  ["RFQ-2026-018", "Industrial Equipment", "Won", "94", "2026-02-21"],
                  ["RFQ-2026-011", "Maintenance Services", "Won", "91", "2026-01-29"],
                  ["RFQ-2025-098", "Facilities Upgrade", "Runner-up", "87", "2025-12-13"],
                ].map((row, idx) => (
                  <tr key={row[0]} style={{ borderBottom: idx < 2 ? "1px solid var(--app-border-strong)" : "none" }}>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-brand-500)" }}>{row[0]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-main)" }}>{row[1]}</td>
                    <td style={{ padding: "10px 8px" }}><Badge label={row[2]} tone={row[2] === "Won" ? "success" : "warning"} /></td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-main)", fontFamily: "'JetBrains Mono', monospace" }}>{row[3]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-muted)" }}>{row[4]}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </Panel>
        </div>

        <div className="col-span-4 space-y-4">
          <Panel title="Risk Trend" subtitle="12-month posture">
            <div className="space-y-2">
              {[
                { label: "Financial", value: "Low", tone: "success" as const, icon: TrendingUp },
                { label: "Compliance", value: "Low", tone: "success" as const, icon: ShieldCheck },
                { label: "Delivery", value: "Low", tone: "success" as const, icon: Clock3 },
                { label: "Reputation", value: "Stable", tone: "brand" as const, icon: Star },
              ].map((row) => (
                <div key={row.label} className="rounded-lg border p-2 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2">
                    <row.icon size={12} style={{ color: "var(--app-brand-500)" }} />
                    <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{row.label}</span>
                  </div>
                  <Badge label={row.value} tone={row.tone} />
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Profile Tags" subtitle="Operational context">
            <div className="flex flex-wrap gap-2">
              {["Preferred Supplier", "ISO 9001", "Local Support", "Multi-site Ready"].map((tag) => (
                <Badge key={tag} label={tag} tone="brand" />
              ))}
            </div>
          </Panel>
        </div>
      </div>
    </Page>
  );
}
