import { useState } from "react";
import { Trophy, Percent, BadgeDollarSign } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

export function AwardDecision() {
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [splitOpen, setSplitOpen] = useState(false);

  return (
    <Page
      title="Award Decision"
      subtitle="Finalize winner or configure split award with sign-off and documented governance checks."
      actions={
        <>
          <GhostButton label="Preview Award Memo" />
          <PrimaryButton label="Proceed to Handoff" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Primary Candidate" value="Apex Industrial" tone="brand" />
        <Stat label="Savings Impact" value="$42,000" tone="success" />
        <Stat label="Split Mode" value="Disabled" tone="neutral" />
        <Stat label="Sign-off Status" value="Awaiting" tone="warning" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-7 space-y-4">
          <Panel title="Winner Recommendation" subtitle="Decision basis">
            <div className="rounded-lg border p-4" style={{ background: "var(--app-brand-tint-8)", borderColor: "var(--app-brand-tint-20)" }}>
              <div className="flex items-center justify-between mb-2">
                <div style={{ fontSize: 16, fontWeight: 700, color: "var(--app-text-strong)" }}>Apex Industrial Solutions</div>
                <Badge label="Recommended" tone="success" />
              </div>
              <p style={{ fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.7 }}>
                Highest composite score with lowest validated lifecycle cost and full compliance clearance.
              </p>
            </div>
          </Panel>

          <Panel title="Split Allocation Controls" subtitle="Optional multi-vendor award">
            <div className="space-y-2">
              {[
                ["Apex Industrial", "70%"],
                ["Summit Flow", "30%"],
              ].map((entry) => (
                <div key={entry[0]} className="rounded-lg border p-3 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{entry[0]}</span>
                  <Badge label={entry[1]} tone="brand" />
                </div>
              ))}
            </div>
            <button onClick={() => setSplitOpen(true)} style={{ marginTop: 10, fontSize: 12, color: "var(--app-brand-500)" }}>
              Configure Split Award →
            </button>
          </Panel>
        </div>

        <div className="col-span-5 space-y-4">
          <Panel title="Savings & Impact" subtitle="Financial outcome">
            <div className="space-y-2">
              <div className="rounded-lg border p-3" style={{ background: "var(--app-success-tint-10)", borderColor: "var(--app-success-tint-20)" }}>
                <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Vs budget</div>
                <div style={{ fontSize: 18, fontWeight: 800, color: "var(--app-success)" }}>
                  <BadgeDollarSign size={15} style={{ display: "inline", marginRight: 6 }} />
                  5.2% under target
                </div>
              </div>
              <div className="rounded-lg border p-3" style={{ background: "var(--app-brand-tint-8)", borderColor: "var(--app-brand-tint-20)" }}>
                <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Potential split variance</div>
                <div style={{ fontSize: 18, fontWeight: 800, color: "var(--app-brand-500)" }}>
                  <Percent size={15} style={{ display: "inline", marginRight: 6 }} />
                  +1.1% cost impact
                </div>
              </div>
            </div>
          </Panel>

          <Panel title="Decision Sign-off" subtitle="Approver commitments">
            <div className="space-y-2">
              {[
                ["Procurement Manager", "Approved", "success"],
                ["Finance Director", "Approved", "success"],
                ["CFO", "Pending", "warning"],
              ].map((item) => (
                <div key={item[0]} className="rounded-lg border p-2 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{item[0]}</span>
                  <Badge label={item[1]} tone={item[2] as "success"} />
                </div>
              ))}
            </div>
          </Panel>

          <button onClick={() => setConfirmOpen(true)} className="w-full rounded-lg py-2.5" style={{ background: "var(--app-brand-600)", color: "white", fontSize: 13, fontWeight: 700 }}>
            <Trophy size={14} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
            Finalize Award
          </button>
        </div>
      </div>

      <SlideOver open={confirmOpen} onClose={() => setConfirmOpen(false)} title="Award Confirmation" subtitle="Finalize winner and lock award decision.">
        <Panel title="Final Decision" subtitle="This action triggers downstream handoff">
          <div style={{ fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.7 }}>
            Winner: <strong>Apex Industrial Solutions</strong><br />
            Award value: <strong>$398,200</strong><br />
            Sign-off requirement: <strong>CFO pending</strong>
          </div>
        </Panel>
        <PrimaryButton label="Confirm Award Decision" />
      </SlideOver>

      <SlideOver open={splitOpen} onClose={() => setSplitOpen(false)} title="Configure Split Award" subtitle="Define allocation percentages across selected vendors.">
        <Panel title="Allocation Plan" subtitle="Must total 100%">
          <div className="space-y-2">
            <div className="rounded-lg border p-2" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>Apex Industrial — 70%</div>
            <div className="rounded-lg border p-2" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>Summit Flow — 30%</div>
          </div>
        </Panel>
      </SlideOver>
    </Page>
  );
}
