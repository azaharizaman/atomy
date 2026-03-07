import { useState } from "react";
import { Send, RefreshCw, Cable } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

export function POContractHandoff() {
  const [handoffOpen, setHandoffOpen] = useState(false);

  return (
    <Page
      title="PO / Contract Handoff"
      subtitle="Prepare and transmit approved outcome to downstream ERP/procurement systems with payload visibility."
      actions={
        <>
          <GhostButton label="Validate Payload" />
          <PrimaryButton label="Queue Handoff" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Destination" value="SAP S/4HANA" tone="brand" />
        <Stat label="State" value="Ready to Send" tone="success" />
        <Stat label="Retries" value="0" tone="neutral" />
        <Stat label="Last Sync" value="2m ago" tone="neutral" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-7 space-y-4">
          <Panel title="Handoff Configuration" subtitle="Destination and mapping">
            <div className="space-y-2">
              {[
                ["Destination system", "SAP S/4HANA"],
                ["Document type", "Purchase Order"],
                ["Currency", "USD"],
                ["Payment terms", "Net 30"],
              ].map((row) => (
                <div key={row[0]} className="rounded-lg border p-3 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <span style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{row[0]}</span>
                  <span style={{ fontSize: 12, color: "var(--app-text-main)", fontWeight: 600 }}>{row[1]}</span>
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Payload Preview" subtitle="Outbound contract/PO payload">
            <pre
              style={{
                margin: 0,
                padding: 12,
                borderRadius: 8,
                border: "1px solid var(--app-border-strong)",
                background: "var(--app-bg-elevated)",
                color: "var(--app-text-main)",
                fontSize: 11,
                lineHeight: 1.6,
                overflowX: "auto",
              }}
            >
{`{
  "awardId": "AWD-2026-001",
  "vendor": "Apex Industrial Solutions",
  "totalAmount": 398200,
  "currency": "USD",
  "terms": "Net 30",
  "lineItems": 4
}`}
            </pre>
          </Panel>
        </div>

        <div className="col-span-5 space-y-4">
          <Panel title="Handoff Status Timeline" subtitle="Transmission lifecycle">
            <div className="space-y-2">
              {[
                ["Validated", "Complete", "success"],
                ["Queued", "Pending", "warning"],
                ["Sent", "Pending", "neutral"],
                ["Acknowledged", "Pending", "neutral"],
              ].map((step) => (
                <div key={step[0]} className="rounded-lg border p-2 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{step[0]}</span>
                  <Badge label={step[1]} tone={step[2] as "success"} />
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Integration Health" subtitle="Connector and retry controls">
            <div className="space-y-2">
              <div className="rounded-lg border p-3" style={{ background: "var(--app-success-tint-10)", borderColor: "var(--app-success-tint-20)" }}>
                <Badge label="Connector Healthy" tone="success" />
              </div>
              <button className="w-full rounded-lg py-2.5" style={{ background: "var(--app-warning-tint-10)", color: "var(--app-warning)", border: "1px solid var(--app-warning-tint-20)", fontSize: 12, fontWeight: 700 }}>
                <RefreshCw size={13} style={{ display: "inline", marginRight: 6 }} />
                Retry Failed Job
              </button>
            </div>
          </Panel>

          <button onClick={() => setHandoffOpen(true)} className="w-full rounded-lg py-2.5" style={{ background: "var(--app-brand-600)", color: "white", fontSize: 13, fontWeight: 700 }}>
            <Send size={14} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
            Create PO/Contract Handoff
          </button>
        </div>
      </div>

      <SlideOver open={handoffOpen} onClose={() => setHandoffOpen(false)} title="Create PO/Contract Handoff" subtitle="Final confirmation before transmission.">
        <Panel title="Ready to Send" subtitle="Destination: SAP S/4HANA">
          <div style={{ fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.7 }}>
            Award: <strong>AWD-2026-001</strong><br />
            Payload hash: <strong>sha256:cc1e...a08f</strong><br />
            Endpoint: <strong>/erp/handoff/po</strong>
          </div>
        </Panel>
        <button className="w-full rounded-lg py-2.5" style={{ background: "var(--app-success)", color: "white", fontSize: 13, fontWeight: 700 }}>
          <Cable size={14} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
          Send Now
        </button>
      </SlideOver>
    </Page>
  );
}
