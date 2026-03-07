import { useState } from "react";
import { ArrowRightLeft, Repeat2, AlertTriangle, CheckCircle2, Wand2 } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

const sourceLines = [
  { id: "LN-001", text: "Centrifugal Pump 6-inch", qty: 4, uom: "EA", price: "$67,200", state: "Mapped" },
  { id: "LN-002", text: "On-site Installation", qty: 8, uom: "Day", price: "$31,200", state: "Conflict" },
  { id: "LN-003", text: "Spare Parts Package", qty: 1, uom: "Set", price: "$21,600", state: "Mapped" },
  { id: "LN-004", text: "Maintenance Plan", qty: 2, uom: "Year", price: "$22,400", state: "Unmapped" },
];

export function QuoteNormalization() {
  const [conflictOpen, setConflictOpen] = useState(false);

  return (
    <Page
      title="Quote Normalization Workspace"
      subtitle="Resolve line-level mapping, conversion, and conflicts before generating the final side-by-side comparison matrix."
      actions={
        <>
          <GhostButton label="Run Auto-Mapping" />
          <PrimaryButton label="Publish Normalized View" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Unmapped Lines" value="1" tone="warning" />
        <Stat label="Conflicts" value="1" tone="danger" />
        <Stat label="Mapped Coverage" value="82%" tone="brand" />
        <Stat label="Currency/UOM Locks" value="3" tone="success" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-4">
          <Panel title="Source Quote Lines" subtitle="Vendor raw structure">
            <div className="space-y-2">
              {sourceLines.map((line) => (
                <button
                  key={line.id}
                  onClick={() => line.state === "Conflict" && setConflictOpen(true)}
                  className="w-full text-left rounded-lg border p-3 transition-colors"
                  style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}
                >
                  <div className="flex items-center justify-between mb-1">
                    <span style={{ fontSize: 11, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)" }}>{line.id}</span>
                    <Badge
                      label={line.state}
                      tone={line.state === "Mapped" ? "success" : line.state === "Conflict" ? "danger" : "warning"}
                    />
                  </div>
                  <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 2 }}>{line.text}</div>
                  <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>
                    {line.qty} {line.uom} • {line.price}
                  </div>
                </button>
              ))}
            </div>
          </Panel>
        </div>

        <div className="col-span-8 space-y-4">
          <Panel title="Target Mapping Grid" subtitle="Canonical RFQ structure aligned for comparison">
            <table style={{ width: "100%", borderCollapse: "collapse" }}>
              <thead>
                <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                  {["RFQ Target Line", "Mapped Source", "Conversion", "Status"].map((h) => (
                    <th key={h} style={{ fontSize: 10, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.06em", textAlign: "left", padding: "10px 8px" }}>
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {[
                  ["Pump Unit", "LN-001", "EA → EA, USD", "Resolved"],
                  ["Installation Services", "LN-002", "Day → Day, USD", "Conflict"],
                  ["Spare Parts", "LN-003", "Set → Set, USD", "Resolved"],
                  ["Maintenance Contract", "—", "Year → Year, USD", "Unmapped"],
                ].map((row, idx) => (
                  <tr key={row[0]} style={{ borderBottom: idx < 3 ? "1px solid var(--app-border-strong)" : "none" }}>
                    <td style={{ padding: "10px 8px", fontSize: 13, color: "var(--app-text-main)" }}>{row[0]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-brand-500)", fontFamily: "'JetBrains Mono', monospace" }}>{row[1]}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-muted)" }}>{row[2]}</td>
                    <td style={{ padding: "10px 8px" }}>
                      <Badge
                        label={row[3]}
                        tone={row[3] === "Resolved" ? "success" : row[3] === "Conflict" ? "danger" : "warning"}
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </Panel>

          <div className="grid grid-cols-2 gap-4">
            <Panel title="Conversion Controls" subtitle="UOM and currency governance">
              <div className="space-y-2">
                <div className="rounded-lg border p-3 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2">
                    <Repeat2 size={14} style={{ color: "var(--app-brand-500)" }} />
                    <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>FX Rate Lock</span>
                  </div>
                  <Badge label="USD @ 1.00" tone="brand" />
                </div>
                <div className="rounded-lg border p-3 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2">
                    <ArrowRightLeft size={14} style={{ color: "var(--app-warning)" }} />
                    <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>UOM Conversion</span>
                  </div>
                  <Badge label="2 pending" tone="warning" />
                </div>
              </div>
            </Panel>

            <Panel title="Conflict Queue" subtitle="Requires human decision">
              <button
                onClick={() => setConflictOpen(true)}
                className="w-full rounded-lg border p-3 text-left"
                style={{ background: "var(--app-danger-tint-10)", borderColor: "var(--app-danger-tint-20)" }}
              >
                <div className="flex items-center gap-2 mb-1">
                  <AlertTriangle size={13} style={{ color: "var(--app-danger)" }} />
                  <span style={{ fontSize: 12, fontWeight: 700, color: "var(--app-danger)" }}>Line Mapping Ambiguity</span>
                </div>
                <div style={{ fontSize: 12, color: "var(--app-text-muted)" }}>
                  LN-002 matches two target lines with close confidence. Choose canonical assignment.
                </div>
              </button>
            </Panel>
          </div>
        </div>
      </div>

      <SlideOver
        open={conflictOpen}
        onClose={() => setConflictOpen(false)}
        title="Resolve Line Mapping Conflict"
        subtitle="Choose the final target line and keep the audit rationale."
      >
        <div className="space-y-3">
          {["Installation Services", "Commissioning Support"].map((option, i) => (
            <button
              key={option}
              className="w-full rounded-lg border p-3 text-left"
              style={{ background: i === 0 ? "var(--app-brand-tint-8)" : "var(--app-bg-elevated)", borderColor: i === 0 ? "var(--app-brand-tint-20)" : "var(--app-border-strong)" }}
            >
              <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)" }}>{option}</div>
              <div style={{ fontSize: 11, color: "var(--app-text-muted)", marginTop: 2 }}>Confidence {i === 0 ? "91%" : "84%"}</div>
            </button>
          ))}
          <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
            <div className="flex items-center gap-2 mb-2">
              <Wand2 size={13} style={{ color: "var(--app-brand-500)" }} />
              <span style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-main)" }}>Normalization Override Note</span>
            </div>
            <p style={{ fontSize: 12, color: "var(--app-text-muted)", lineHeight: 1.6 }}>
              Selected Installation Services due to quantity alignment (8 day units) and consistent commercial terms across vendor submissions.
            </p>
          </div>
          <button className="w-full rounded-lg py-2.5" style={{ background: "var(--app-success)", color: "white", fontSize: 13, fontWeight: 700 }}>
            <CheckCircle2 size={14} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
            Save Resolution
          </button>
        </div>
      </SlideOver>
    </Page>
  );
}
