import { useState } from "react";
import { Plug, RefreshCw, AlertTriangle } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

export function IntegrationMonitor() {
  const [retryOpen, setRetryOpen] = useState(false);

  return (
    <Page
      title="Integration & API Monitor"
      subtitle="Track connector health, failed job logs, and retry queues for platform integrations."
      actions={
        <>
          <GhostButton label="View API Logs" />
          <PrimaryButton label="Run Health Check" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Healthy Connectors" value="7" tone="success" />
        <Stat label="Degraded" value="1" tone="warning" />
        <Stat label="Outages" value="0" tone="success" />
        <Stat label="Queued Retries" value="3" tone="warning" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-5">
          <Panel title="Connector Health" subtitle="ERP, identity, and storage connectors">
            <div className="space-y-2">
              {[
                ["SAP S/4HANA", "Healthy", "success"],
                ["Azure AD SSO", "Healthy", "success"],
                ["Document Vault", "Healthy", "success"],
                ["Mail Relay", "Degraded", "warning"],
              ].map((row) => (
                <div key={row[0]} className="rounded-lg border p-3 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2">
                    <Plug size={13} style={{ color: "var(--app-brand-500)" }} />
                    <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{row[0]}</span>
                  </div>
                  <Badge label={row[1]} tone={row[2] as "success"} />
                </div>
              ))}
            </div>
          </Panel>
        </div>

        <div className="col-span-7 space-y-4">
          <Panel title="Failed Job Log" subtitle="Recent failed integration jobs">
            <table style={{ width: "100%", borderCollapse: "collapse" }}>
              <thead>
                <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                  {["Job ID", "Connector", "Failure", "Time", "Action"].map((h) => (
                    <th key={h} style={{ padding: "10px 8px", fontSize: 10, textAlign: "left", textTransform: "uppercase", letterSpacing: "0.06em", color: "var(--app-text-muted)" }}>
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {[
                  ["JOB-992", "Mail Relay", "SMTP timeout", "2m ago", "Retry"],
                  ["JOB-988", "SAP S/4HANA", "Payload schema mismatch", "18m ago", "Inspect"],
                  ["JOB-974", "Document Vault", "Permission denied", "42m ago", "Retry"],
                ].map((row, idx) => (
                  <tr key={row[0]} style={{ borderBottom: idx < 2 ? "1px solid var(--app-border-strong)" : "none" }}>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-brand-500)", fontFamily: "'JetBrains Mono', monospace" }}>{row[0]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-main)" }}>{row[1]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-warning)" }}>{row[2]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-muted)" }}>{row[3]}</td>
                    <td style={{ padding: "10px 8px" }}>
                      <button onClick={() => setRetryOpen(true)} style={{ fontSize: 12, color: "var(--app-brand-500)" }}>
                        {row[4]}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </Panel>

          <Panel title="Retry Queue" subtitle="Pending automatic and manual retries">
            <div className="space-y-2">
              {["JOB-992 scheduled in 60s", "JOB-974 manual retry requested", "JOB-961 waiting dependency"].map((row) => (
                <div key={row} className="rounded-lg border p-2" style={{ background: "var(--app-warning-tint-10)", borderColor: "var(--app-warning-tint-20)", fontSize: 12, color: "var(--app-warning)" }}>
                  <AlertTriangle size={12} style={{ display: "inline", marginRight: 6 }} />
                  {row}
                </div>
              ))}
            </div>
          </Panel>
        </div>
      </div>

      <SlideOver open={retryOpen} onClose={() => setRetryOpen(false)} title="Retry Integration Job" subtitle="Confirm retry action and preserve traceability.">
        <Panel title="Retry Plan" subtitle="Job: JOB-992">
          <div style={{ fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.7 }}>
            Connector: Mail Relay<br />
            Last error: SMTP timeout<br />
            Strategy: exponential backoff (3 attempts)
          </div>
        </Panel>
        <button className="w-full rounded-lg py-2.5" style={{ background: "var(--app-brand-600)", color: "white", fontSize: 13, fontWeight: 700 }}>
          <RefreshCw size={14} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
          Retry Job
        </button>
      </SlideOver>
    </Page>
  );
}
