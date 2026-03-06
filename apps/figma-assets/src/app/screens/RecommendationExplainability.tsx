import { useState } from "react";
import { BrainCircuit, ShieldCheck, TrendingDown, CircleHelp, ListChecks } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

export function RecommendationExplainability() {
  const [whyOpen, setWhyOpen] = useState(false);

  return (
    <Page
      title="Recommendation & Explainability"
      subtitle="Review AI recommendation confidence, contributing factors, and trade-off narrative before approvals."
      actions={
        <>
          <GhostButton label="Export Explanation" />
          <PrimaryButton label="Send to Approvals" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Recommended Vendor" value="Apex Industrial" tone="brand" />
        <Stat label="Confidence" value="94%" tone="success" />
        <Stat label="Top Savings Driver" value="$42k" tone="success" />
        <Stat label="Open Review Flags" value="1" tone="warning" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-7 space-y-4">
          <Panel title="Primary Recommendation" subtitle="High confidence outcome">
            <div className="rounded-xl border p-4" style={{ background: "var(--app-brand-tint-8)", borderColor: "var(--app-brand-tint-20)" }}>
              <div className="flex items-start justify-between mb-2">
                <div>
                  <div style={{ fontSize: 16, fontWeight: 700, color: "var(--app-text-strong)" }}>Apex Industrial Solutions</div>
                  <div style={{ fontSize: 12, color: "var(--app-text-muted)" }}>Best balanced outcome across price, delivery reliability, and compliance.</div>
                </div>
                <Badge label="High confidence" tone="success" />
              </div>
              <div className="grid grid-cols-3 gap-3 mt-3">
                {[
                  ["Price", "$398,200", "success"],
                  ["Lead Time", "4 weeks", "brand"],
                  ["Risk", "Low", "success"],
                ].map((m) => (
                  <div key={m[0]} className="rounded-lg border p-3" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                    <div style={{ fontSize: 10, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>{m[0]}</div>
                    <div style={{ fontSize: 14, fontWeight: 700, color: "var(--app-text-main)", marginTop: 2 }}>{m[1]}</div>
                  </div>
                ))}
              </div>
            </div>
          </Panel>

          <Panel title="Top Contributing Factors" subtitle="Weighted rationale">
            <div className="space-y-2">
              {[
                { label: "Lowest total cost of ownership over 3 years", impact: "+28 pts", tone: "success" as const, icon: TrendingDown },
                { label: "Strong policy and sanctions compliance record", impact: "+21 pts", tone: "success" as const, icon: ShieldCheck },
                { label: "Consistent service reliability from prior awards", impact: "+14 pts", tone: "brand" as const, icon: ListChecks },
              ].map((factor) => (
                <div key={factor.label} className="rounded-lg border p-3 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-start gap-2">
                    <factor.icon size={14} style={{ color: "var(--app-brand-500)", marginTop: 1 }} />
                    <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{factor.label}</span>
                  </div>
                  <Badge label={factor.impact} tone={factor.tone} />
                </div>
              ))}
            </div>
          </Panel>
        </div>

        <div className="col-span-5 space-y-4">
          <Panel title="Confidence Meter" subtitle="Model confidence by domain">
            <div className="space-y-3">
              {[
                ["Pricing signal", 96],
                ["Compliance signal", 93],
                ["Delivery signal", 89],
                ["Quality signal", 91],
              ].map(([label, value]) => (
                <div key={label}>
                  <div className="flex items-center justify-between mb-1">
                    <span style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{label}</span>
                    <span style={{ fontSize: 12, color: "var(--app-text-main)", fontFamily: "'JetBrains Mono', monospace" }}>{value}%</span>
                  </div>
                  <div className="rounded-full" style={{ height: 6, background: "var(--app-border-strong)" }}>
                    <div style={{ width: `${value}%`, height: "100%", borderRadius: 6, background: "var(--app-success)" }} />
                  </div>
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Trade-off Narrative" subtitle="Natural language explanation">
            <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
              <p style={{ fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.7 }}>
                Selecting Summit Flow would improve lead time by ~1 week but increases total spend by 6.8% and introduces medium compliance risk.
                Apex Industrial is recommended as the best value while preserving governance confidence.
              </p>
            </div>
            <button onClick={() => setWhyOpen(true)} className="w-full rounded-lg py-2.5" style={{ background: "var(--app-purple-tint-10)", color: "var(--app-accent-purple)", border: "1px solid var(--app-purple-tint-20)", fontSize: 12, fontWeight: 700 }}>
              <CircleHelp size={13} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
              Why This Recommendation
            </button>
          </Panel>
        </div>
      </div>

      <SlideOver open={whyOpen} onClose={() => setWhyOpen(false)} title="Why This Recommendation" subtitle="Expanded model rationale and human-readable explanation.">
        <div className="space-y-3">
          <Panel title="Model Trace" subtitle="Top weighted features">
            <div className="space-y-2">
              {[
                "Total cost benchmark percentile: 92nd",
                "On-time delivery index: 96",
                "Compliance exception count: 0",
              ].map((item) => (
                <div key={item} className="rounded-lg border p-2" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
                  <BrainCircuit size={12} style={{ display: "inline", marginRight: 6, color: "var(--app-brand-500)" }} />
                  {item}
                </div>
              ))}
            </div>
          </Panel>
          <Panel title="Human Review Guidance" subtitle="Before final approval">
            <ul style={{ margin: 0, paddingLeft: 18, color: "var(--app-text-main)", fontSize: 12, lineHeight: 1.7 }}>
              <li>Validate commercial terms against legal template.</li>
              <li>Confirm backup supplier in case schedule changes.</li>
              <li>Attach decision rationale to audit trail.</li>
            </ul>
          </Panel>
        </div>
      </SlideOver>
    </Page>
  );
}
