import { useState } from "react";
import { GitCompareArrows, Save, TrendingUp, SlidersHorizontal } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

const scenarios = [
  { id: "BASE", name: "Baseline", delta: "0%", outcome: "Apex Industrial", savings: "$42,000", score: 94, state: "Saved" },
  { id: "FAST", name: "Lead-time Priority", delta: "-4%", outcome: "Summit Flow", savings: "$31,200", score: 89, state: "Saved" },
  { id: "RISK", name: "Risk-Minimized", delta: "-2%", outcome: "Apex Industrial", savings: "$37,900", score: 91, state: "Unsaved" },
];

export function ScenarioSimulator() {
  const [saveOpen, setSaveOpen] = useState(false);
  const [diffOpen, setDiffOpen] = useState(false);

  return (
    <Page
      title="Scenario Simulator"
      subtitle="Evaluate alternative assumptions before final decision by comparing outcome shifts across cost, risk, and delivery objectives."
      actions={
        <>
          <GhostButton label="Compare Scenarios" />
          <PrimaryButton label="Run Simulation" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Active Scenarios" value="3" tone="brand" />
        <Stat label="Best Savings" value="$42k" tone="success" />
        <Stat label="Biggest Risk Drop" value="-12 pts" tone="success" />
        <Stat label="Unsaved Edits" value="1" tone="warning" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-4">
          <Panel title="Scenario List" subtitle="Baseline + alternatives">
            <div className="space-y-2">
              {scenarios.map((scenario, idx) => (
                <button
                  key={scenario.id}
                  className="w-full rounded-lg border p-3 text-left"
                  style={{ background: idx === 0 ? "var(--app-brand-tint-8)" : "var(--app-bg-elevated)", borderColor: idx === 0 ? "var(--app-brand-tint-20)" : "var(--app-border-strong)" }}
                >
                  <div className="flex items-center justify-between mb-1">
                    <span style={{ fontSize: 13, fontWeight: 700, color: "var(--app-text-strong)" }}>{scenario.name}</span>
                    <Badge label={scenario.state} tone={scenario.state === "Saved" ? "success" : "warning"} />
                  </div>
                  <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>
                    Outcome: {scenario.outcome} • Score {scenario.score}
                  </div>
                </button>
              ))}
            </div>
          </Panel>
        </div>

        <div className="col-span-8 space-y-4">
          <Panel title="Assumption Controls" subtitle="Weighted changes used for simulation">
            <div className="grid grid-cols-2 gap-3">
              {[
                ["Price Weight", "40% → 35%", "brand"],
                ["Risk Weight", "10% → 20%", "warning"],
                ["Lead-time Weight", "20% → 25%", "brand"],
                ["Quality Weight", "20% → 15%", "neutral"],
              ].map((item) => (
                <div key={item[0]} className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2 mb-1">
                    <SlidersHorizontal size={13} style={{ color: "var(--app-text-muted)" }} />
                    <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{item[0]}</span>
                  </div>
                  <Badge label={item[1]} tone={item[2] as "brand"} />
                </div>
              ))}
            </div>
          </Panel>

          <Panel
            title="Outcome Comparison Canvas"
            subtitle="Delta visualization across scenarios"
            right={
              <button onClick={() => setDiffOpen(true)} style={{ fontSize: 12, color: "var(--app-brand-500)" }}>
                Open Scenario Diff →
              </button>
            }
          >
            <table style={{ width: "100%", borderCollapse: "collapse" }}>
              <thead>
                <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                  {["Scenario", "Recommended Vendor", "Savings", "Composite Score", "Delta vs Baseline"].map((h) => (
                    <th key={h} style={{ padding: "10px 8px", fontSize: 10, textAlign: "left", color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.06em" }}>
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {scenarios.map((row, idx) => (
                  <tr key={row.id} style={{ borderBottom: idx < scenarios.length - 1 ? "1px solid var(--app-border-strong)" : "none" }}>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-main)", fontWeight: 600 }}>{row.name}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-main)" }}>{row.outcome}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-success)", fontFamily: "'JetBrains Mono', monospace" }}>{row.savings}</td>
                    <td style={{ padding: "10px 8px", fontSize: 12, color: "var(--app-text-main)", fontFamily: "'JetBrains Mono', monospace" }}>{row.score}</td>
                    <td style={{ padding: "10px 8px" }}>
                      <div className="flex items-center gap-1.5">
                        <TrendingUp size={12} style={{ color: idx === 0 ? "var(--app-text-muted)" : "var(--app-brand-500)" }} />
                        <span style={{ fontSize: 12, color: idx === 0 ? "var(--app-text-muted)" : "var(--app-brand-500)" }}>{row.delta}</span>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </Panel>

          <div className="flex justify-end">
            <button onClick={() => setSaveOpen(true)} className="rounded-lg px-3 py-2" style={{ background: "var(--app-success)", color: "white", fontSize: 13, fontWeight: 700 }}>
              <Save size={14} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
              Save Scenario
            </button>
          </div>
        </div>
      </div>

      <SlideOver open={saveOpen} onClose={() => setSaveOpen(false)} title="Save Scenario" subtitle="Persist this assumption set for future comparison.">
        <div className="space-y-3">
          <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
            <div style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-main)", marginBottom: 4 }}>Scenario Name</div>
            <div style={{ fontSize: 12, color: "var(--app-text-muted)" }}>Risk-Minimized with Delivery Bias</div>
          </div>
          <div className="rounded-lg border p-3" style={{ background: "var(--app-brand-tint-4)", borderColor: "var(--app-brand-tint-20)" }}>
            <div style={{ fontSize: 11, color: "var(--app-text-muted)", lineHeight: 1.6 }}>
              Version note: Increased risk weight from 10% to 20% and reduced price weight to improve governance posture.
            </div>
          </div>
          <PrimaryButton label="Confirm Save" />
        </div>
      </SlideOver>

      <SlideOver open={diffOpen} onClose={() => setDiffOpen(false)} title="Scenario Diff" subtitle="Compare assumption and outcome deltas side-by-side.">
        <Panel title="Baseline vs Risk-Minimized" subtitle="Most recent comparison">
          <div className="space-y-2">
            {[
              "Risk score improved: 28 → 16",
              "Savings reduced: $42k → $37.9k",
              "Lead time increased: 4.0w → 4.5w",
            ].map((line) => (
              <div key={line} className="rounded-lg border p-2" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
                <GitCompareArrows size={12} style={{ display: "inline", marginRight: 6, color: "var(--app-brand-500)" }} />
                {line}
              </div>
            ))}
          </div>
        </Panel>
      </SlideOver>
    </Page>
  );
}
